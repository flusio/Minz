<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Migration;

use Minz\Database;
use Minz\Request;
use Minz\Response;

/**
 * The Migration\Controller allows to manage the migrations with CLI commands.
 *
 * The migrations themselves are managed by the \Minz\Migration\Migrator class.
 *
 * The controller provides 7 actions:
 *
 * - setup: to initialize or migrate the application
 * - initialize: to initialize the application
 * - migrate: to migrate the application
 * - rollback: to rollback the application
 * - seed: to seed data
 * - index: to list the migrations
 * - create: to create a new migration
 *
 * The setup() method calls initialize() or migrate() depending on the current
 * state of the application. It is also able to seed the data. The seeds are
 * usually defined in the src/seeds.php file.
 *
 * To connect this controller to your application, start by creating a
 * controller that inherit from it:
 *
 *     namespace App;
 *
 *     class Migrations extends \Minz\Migration\Controller
 *     {
 *     }
 *
 * Then, add the routes to your Router, for instance:
 *
 *     $router = new \Minz\Router();
 *     $router->addRoute('CLI', '/migrations', 'Migrations#index');
 *     $router->addRoute('CLI', '/migrations/setup', 'Migrations#setup');
 *     $router->addRoute('CLI', '/migrations/rollback', 'Migrations#rollback');
 *     // ...
 *
 * You can easily create new actions in your own Controller if you need to.
 *
 * Note that the controller defines 4 paths (for the schema, the seeds, the
 * migrations and the migrations version files) and 1 namespace for the
 * migrations. You can redefine these by overidding the methods at the end of
 * this class.
 *
 * Note that your ./cli script must be able to handle Responses Generators as
 * the setup() method yields its responses. This is the case of the
 * \Minz\Response::sendToCli() method:
 *
 *     $request = \Minz\Request::initFromCli($argv);
 *
 *     $application = new \App\Application();
 *     $response = $application->run($request);
 *
 *     \Minz\Response::sendToCli($response);
 *
 * Once everything is configured, you can write and apply migrations (usually
 * under src/migrations).
 *
 * @phpstan-import-type ResponseGenerator from Response
 */
class Controller
{
    /**
     * Initialize or migrate the application.
     *
     * If the action detects a migrations version file, it will migrate the
     * application. Otherwise, it will initialize it.
     *
     * If the "seed" parameter is given, the seeds will be loaded as well.
     * The seeds are not applied if the init/migration fails before.
     *
     * @request_param bool seed
     *     Whether the seeds should be loaded or not (false by default).
     *
     * @response 500
     *     If an error occurs during the setup of the application.
     *
     * @response 200
     *     If everything goes fine.
     *
     * @return ResponseGenerator
     */
    public function setup(Request $request): \Generator
    {
        $migrations_version_path = static::migrationsVersionPath();

        if (file_exists($migrations_version_path)) {
            $response = $this->migrate($request);
        } else {
            $response = $this->initialize($request);
        }

        yield $response;

        $apply_seeds = $request->paramBoolean('seed', false);
        $code = $response->code();

        if ($apply_seeds && $code >= 200 && $code < 300) {
            yield $this->seed($request);
        }
    }

    /**
     * Create the database and set the migration version.
     *
     * The database is only created if a schema exists (src/schema.sql by
     * default).
     *
     * @response 500
     *     If an error occurs during the initialization.
     *
     * @response 200
     *     On success.
     */
    public function initialize(Request $request): Response
    {
        $schema_path = static::schemaPath();
        $migrations_path = static::migrationsPath();
        $migrations_version_path = static::migrationsVersionPath();

        if (file_exists($schema_path)) {
            \Minz\Database::create();

            $schema = @file_get_contents($schema_path);

            if ($schema === false) {
                return Response::text(500, "Cannot read the database schema ({$schema_path}).");
            }

            $database = \Minz\Database::get();

            try {
                $database->exec($schema);
            } catch (\PDOException $e) {
                $error = (string)$e;
                return Response::text(500, "Cannot load the database schema:\n{$error}");
            }
        }

        $migrator = new Migrator($migrations_path, static::migrationsNamespace());
        $version = $migrator->lastVersion();

        $saved = @file_put_contents($migrations_version_path, $version ?? '');

        if ($saved === false) {
            return Response::text(500, "Cannot save the migrations version file ({$migrations_version_path}).");
        }

        return Response::text(200, 'The system has been initialized.');
    }

    /**
     * Execute the migrations that have not been applied yet.
     *
     * @response 500
     *     If the migration fails.
     *
     * @response 200
     *     If the migration works successfully, or if the system is already
     *     up-to-date.
     */
    public function migrate(Request $request): Response
    {
        $migrations_path = static::migrationsPath();
        $migrations_version_path = static::migrationsVersionPath();

        $migration_version = @file_get_contents($migrations_version_path);

        if ($migration_version === false) {
            return Response::text(500, "Cannot read the migrations version file ({$migrations_version_path}).");
        }

        $migrator = new Migrator($migrations_path, static::migrationsNamespace());
        $migration_version = trim($migration_version);

        if ($migration_version) {
            $migrator->setVersion($migration_version);
        }

        if ($migrator->upToDate()) {
            return Response::text(200, 'Your system is already up to date.');
        }

        $results = $migrator->migrate();
        $new_version = $migrator->version();

        $saved = @file_put_contents($migrations_version_path, $new_version);

        if ($saved === false) {
            return Response::text(500, "Cannot save the migrations version number ({$new_version}).");
        }

        $has_error = false;
        $results_as_text = [];

        foreach ($results as $migration_version => $result) {
            if ($result === false) {
                $result = 'KO';
            } elseif ($result === true) {
                $result = 'OK';
            }

            if ($result !== 'OK') {
                $has_error = true;
            }

            $results_as_text[] = "{$migration_version}: {$result}";
        }

        return Response::text($has_error ? 500 : 200, implode("\n", $results_as_text));
    }

    /**
     * Execute the rollback of the latest migrations.
     *
     * @request_param int steps
     *     The number of rollbacks to execute (default is 1).
     *
     * @response 500
     *     If the rollback fails.
     *
     * @response 200
     *     If the rollback works successfully.
     */
    public function rollback(Request $request): Response
    {
        $migrations_path = static::migrationsPath();
        $migrations_version_path = static::migrationsVersionPath();

        $migration_version = @file_get_contents($migrations_version_path);

        if ($migration_version === false) {
            return Response::text(500, "Cannot read the migrations version file ({$migrations_version_path}).");
        }

        $migrator = new Migrator($migrations_path, static::migrationsNamespace());
        $migration_version = trim($migration_version);

        if ($migration_version) {
            $migrator->setVersion($migration_version);
        }

        /** @var int */
        $steps = $request->paramInteger('steps', 1);
        $results = $migrator->rollback($steps);
        $new_version = $migrator->version();

        $saved = @file_put_contents($migrations_version_path, $new_version);

        if ($saved === false) {
            return Response::text(500, "Cannot save the migrations version number ({$new_version}).");
        }

        if (empty($results)) {
            return Response::text(200, 'There was no rollback to apply.');
        }

        $has_error = false;
        $results_as_text = [];

        foreach ($results as $migration_version => $result) {
            if ($result === false) {
                $result = 'KO';
            } elseif ($result === true) {
                $result = 'OK';
            }

            if ($result !== 'OK') {
                $has_error = true;
            }

            $results_as_text[] = "{$migration_version}: {$result}";
        }

        return Response::text($has_error ? 500 : 200, implode("\n", $results_as_text));
    }

    /**
     * Seeds the application.
     *
     * The seeds must be placed in a PHP file which is loaded and interpreted
     * (src/seeds.php by default).
     *
     * You must make sure that your seeds are idempotent, i.e. data are not
     * duplicated if the seeds are loaded several times.
     *
     * @response 500
     *     If an error occurs during seeding.
     *
     * @response 200
     *     If the seeds are loaded.
     */
    public function seed(Request $request): Response
    {
        $seeds_path = static::seedsPath();

        if (!file_exists($seeds_path)) {
            return Response::text(500, "Cannot load the seeds file ({$seeds_path}).");
        }

        try {
            include_once($seeds_path);
            return Response::text(200, 'Seeds loaded.');
        } catch (\Exception $e) {
            $error = (string)$e;
            return Response::text(500, "Cannot load the seeds:\n{$error}");
        }
    }

    /**
     * Display the list of the migrations.
     *
     * @response 200
     */
    public function index(Request $request): Response
    {
        $migrations_path = static::migrationsPath();
        $migrations_version_path = static::migrationsVersionPath();

        $migration_version = @file_get_contents($migrations_version_path);

        $migrator = new Migrator($migrations_path, static::migrationsNamespace());

        if ($migration_version !== false) {
            $migration_version = trim($migration_version);
        }

        if ($migration_version) {
            $migrator->setVersion($migration_version);
        }

        $versions = array_keys($migrator->migrations());
        $current_version = $migrator->version();

        $found_current_version = $current_version === null;
        $formatted_versions = [];

        foreach ($versions as $version) {
            $formatted_version = $version;

            if ($found_current_version) {
                $formatted_version .= ' (not applied)';
            }

            $formatted_versions[] = $formatted_version;

            if ($version === $current_version) {
                $found_current_version = true;
            }
        }

        return Response::text(200, implode("\n", $formatted_versions));
    }

    /**
     * Create a new migration file.
     *
     * @request_param string name
     *     The name of the migration. Only characters from A to Z and numbers
     *     are accepted.
     *
     * @response 400
     *     If the name is empty.
     *
     * @response 500
     *     If the generation of the version number failed.
     *
     * @response 200
     *     If the migration file is created.
     */
    public function create(Request $request): Response
    {
        $migrations_path = static::migrationsPath();

        /** @var string */
        $name = $request->param('name', '');
        $name = preg_replace('/[^a-zA-Z0-9]/', '', $name);

        if (!$name) {
            return Response::text(400, 'The migration name cannot be empty.');
        }

        $now = \Minz\Time::now()->format('Ymd');
        $version = '';
        for ($i = 1; $i <= 9999; $i++) {
            $number = $now . str_pad(strval($i), 4, '0', STR_PAD_LEFT);
            $base_version = "Migration{$number}";
            $existing_migration = glob("{$migrations_path}/{$base_version}*");

            if (!$existing_migration) {
                $version = "{$base_version}{$name}";
                break;
            }
        }

        if (!$version) {
            return Response::text(500, 'Cannot generate the version number (too many migrations).');
        }

        $migrations_namespace = static::migrationsNamespace();
        $migration_path = "{$migrations_path}/{$version}.php";
        $migration_as_text = <<<PHP
            <?php

            namespace {$migrations_namespace};

            class {$version}
            {
                public function migrate(): bool
                {
                    \$database = \Minz\Database::get();

                    \$database->exec(<<<'SQL'
                    SQL);

                    return true;
                }

                public function rollback(): bool
                {
                    \$database = \Minz\Database::get();

                    \$database->exec(<<<'SQL'
                    SQL);

                    return true;
                }
            }
            PHP;

        $result = @file_put_contents($migration_path, $migration_as_text);

        if ($result === false) {
            return Response::text(500, "Cannot save the migration file ({$migration_path}).");
        }

        return Response::text(200, "The migration {$version} has been created.");
    }

    public static function schemaPath(): string
    {
        return \Minz\Configuration::$app_path . '/src/schema.sql';
    }

    public static function seedsPath(): string
    {
        return \Minz\Configuration::$app_path . '/src/seeds.php';
    }

    public static function migrationsPath(): string
    {
        return \Minz\Configuration::$app_path . '/src/migrations';
    }

    public static function migrationsVersionPath(): string
    {
        return \Minz\Configuration::$data_path . '/migrations_version.txt';
    }

    public static function migrationsNamespace(): string
    {
        $app_name = \Minz\Configuration::$app_name;
        return "{$app_name}\\migrations";
    }
}
