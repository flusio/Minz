<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Migration;

use Minz\Database;
use Minz\Errors;

/**
 * The Migrator helps to migrate data (in a database or not) or the
 * architecture of a Minz application.
 *
 * It is usually used with \Minz\Migrator\Controller to manage easily the
 * migrations.
 *
 * A migration is just a class defining a "migrate" method, and optionnally a
 * "rollback" method. It is usually used to transform the database structure
 * for instance. A migration is applied only once by the administrator of the
 * server.
 *
 * It is good practice to declare a "rollback" method in the migrations classes
 * to allow administrators to reverse the changes and to return to a previous
 * version of the application.
 *
 * The job of the Migrator is to manage the versions numbers of the migrations
 * and to apply/reverse the migrations in order.
 *
 * @phpstan-type Migration array{
 *     'migration': MigrationCallable,
 *     'rollback': ?MigrationCallable,
 * }
 *
 * @phpstan-type MigrationCallable callable(): (string|bool)
 *
 * @phpstan-type MigrationResult string|bool
 *
 * @phpstan-type MigrationVersion string
 */
class Migrator
{
    /** @var ?MigrationVersion */
    private ?string $version = null;

    /** @var array<MigrationVersion, Migration> */
    private array $migrations = [];

    /**
     * Create a Migrator instance. If directory is given, it'll load the
     * migrations from it.
     *
     * By default, the Migrator will load migrations with the following
     * namespace: <app_name>\migrations (where <app_name> is the name declared
     * in the configuration, "App" by default). You can pass a namespace as a
     * second parameter to the constructor to override this value.
     *
     * These classes must declare a `migrate` method and can declare a
     * `rollback` method.
     *
     * @throws \Minz\Errors\MigrationError if a file doesn't contain a valid class
     * @throws \Minz\Errors\MigrationError if a migrate method is not callable
     *                                     on a migration
     */
    public function __construct(?string $directory = null, ?string $namespace = null)
    {
        if (!$directory) {
            return;
        }

        if (!is_dir($directory)) {
            throw new Errors\MigrationError("The directory {$directory} cannot be read.");
        }

        $filenames = scandir($directory);
        if (!$filenames) {
            throw new Errors\MigrationError("The directory {$directory} cannot be read.");
        }

        if ($namespace === null) {
            $app_name = \Minz\Configuration::$app_name;
            $namespace = "{$app_name}\\migrations";
        }

        foreach ($filenames as $filename) {
            if ($filename[0] === '.') {
                continue;
            }

            $migration_version = basename($filename, '.php');
            $migration_classname = "\\{$namespace}\\{$migration_version}";

            try {
                $migration = new $migration_classname();
            } catch (\Error $e) {
                throw new Errors\MigrationError("{$migration_version} migration cannot be instantiated.");
            }

            $migration_callback = [$migration, 'migrate'];
            if (!is_callable($migration_callback)) {
                throw new Errors\MigrationError("Migration {$migration_classname}::migrate cannot be called.");
            }

            $rollback_callback = [$migration, 'rollback'];
            if (is_callable($rollback_callback)) {
                $this->addMigration($migration_version, $migration_callback, $rollback_callback);
            } else {
                $this->addMigration($migration_version, $migration_callback);
            }
        }
    }

    /**
     * Register a migration into the migration system.
     *
     * @param MigrationVersion $version
     *     The name version of the migration (be careful, migrations are sorted
     *     with the `strnatcmp` function)
     * @param MigrationCallable $migration
     *     The migration function to execute, it should return true on success
     *     and must return false on error.
     * @param ?MigrationCallable $rollback
     *     An optional rollback function to execute, it should behave as
     *     migration (but by reversing its effect).
     */
    public function addMigration(string $version, callable $migration, ?callable $rollback = null): void
    {
        $this->migrations[$version] = [
            'migration' => $migration,
            'rollback' => $rollback,
        ];
    }

    /**
     * Return the list of migrations, sorted with `strnatcmp`
     *
     * @see https://www.php.net/manual/en/function.strnatcmp.php
     *
     * @return array<MigrationVersion, Migration>
     */
    public function migrations(bool $reverse = false): array
    {
        $migrations = $this->migrations;
        if ($reverse) {
            uksort($migrations, function ($migration1, $migration2): int {
                return strnatcmp($migration2, $migration1);
            });
        } else {
            uksort($migrations, function ($migration1, $migration2): int {
                return strnatcmp($migration1, $migration2);
            });
        }
        return $migrations;
    }

    /**
     * Set the actual version of the application.
     *
     * @param MigrationVersion $version
     *
     * @throws \Minz\Errors\MigrationError
     *     If there is no migrations corresponding to the given version.
     */
    public function setVersion(string $version): void
    {
        $version = trim($version);
        if (!isset($this->migrations[$version])) {
            throw new Errors\MigrationError("{$version} migration does not exist.");
        }

        $this->version = $version;
    }

    /**
     * @return ?MigrationVersion
     */
    public function version(): ?string
    {
        return $this->version;
    }

    /**
     * @return ?MigrationVersion
     */
    public function lastVersion(): ?string
    {
        $migrations = array_keys($this->migrations());
        if (!$migrations) {
            return null;
        }

        return end($migrations);
    }

    /**
     * Return true if the application is up-to-date, false otherwise. If no
     * migrations are registered, it always returns true.
     */
    public function upToDate(): bool
    {
        return $this->version === $this->lastVersion();
    }

    /**
     * Migrate the system to the latest version.
     *
     * It only executes migrations AFTER the current version. If a migration
     * returns false or fails, it immediatly stops the process. A migration can
     * also return a string to explain an error. The migration must return true
     * if it's successful.
     *
     * Return the results of each executed migration. If an exception was
     * raised in a migration, its result is set to the exception message.
     *
     * @return array<MigrationVersion, MigrationResult>
     */
    public function migrate(): array
    {
        try {
            $database = Database::get();
        } catch (Errors\DatabaseError $e) {
            $database = null;
        }

        $result = [];
        $apply_migration = $this->version === null;

        foreach ($this->migrations() as $version => $callbacks) {
            if (!$apply_migration) {
                $apply_migration = $this->version === $version;
                continue;
            }

            if ($database) {
                $database->beginTransaction();
            }

            try {
                /** @var MigrationResult $migration_result */
                $migration_result = call_user_func($callbacks['migration']);
                $result[$version] = $migration_result;
            } catch (\Exception $e) {
                $migration_result = false;
                $result[$version] = $e->getMessage();
            }

            if ($database && $migration_result === true) {
                $database->commit();
            } elseif ($database) {
                $database->rollBack();
            }

            if ($migration_result !== true) {
                break;
            }

            $this->version = $version;
        }

        return $result;
    }

    /**
     * Rollback the system by X steps.
     *
     * It executes migrations from the current version until $max_steps or
     * until the last first version. If a rollback returns false or fails, it
     * immediatly stops the process. A rollback can also return a strign to
     * explain an error. The rollback must return true if it's successful.
     *
     * If a migration was added without rollback function, it’s considered as a
     * failing rollback.
     *
     * Return the results of each executed rollback. If an exception was raised
     * in a rollback, its result is set to the exception message.
     *
     * @return array<MigrationVersion, MigrationResult>
     */
    public function rollback(int $max_steps): array
    {
        $result = [];
        $count_steps = 0;
        $set_version_to_null = true;
        $apply_rollback = false;

        // Stop early if the current version is null
        if ($this->version === null) {
            return $result;
        }

        try {
            $database = Database::get();
        } catch (Errors\DatabaseError $e) {
            $database = null;
        }

        foreach ($this->migrations(reverse: true) as $version => $callbacks) {
            // Skip the rollbacks until we found the current versions (useful
            // if we’re not at the latest version)
            if (!$apply_rollback && $this->version === $version) {
                $apply_rollback = true;
            } elseif (!$apply_rollback) {
                continue;
            }

            // Change the current version
            $this->version = $version;

            // Stop when we did enough steps
            $count_steps += 1;
            if ($count_steps > $max_steps) {
                $set_version_to_null = false;
                break;
            }

            if ($database) {
                $database->beginTransaction();
            }

            // Execute the rollback and get the result
            if (isset($callbacks['rollback'])) {
                try {
                    /** @var MigrationResult $migration_result */
                    $migration_result = call_user_func($callbacks['rollback']);
                    $result[$version] = $migration_result;
                } catch (\Exception $e) {
                    $migration_result = false;
                    $result[$version] = $e->getMessage();
                }
            } else {
                $migration_result = false;
                $result[$version] = 'No rollback!';
            }

            if ($database && $migration_result === true) {
                $database->commit();
            } elseif ($database) {
                $database->rollBack();
            }

            // The current rollback failed, stop here
            if ($migration_result !== true) {
                $set_version_to_null = false;
                break;
            }
        }

        // We executed the rollbacks until the end, so we must set the current
        // version to null.
        if ($set_version_to_null) {
            $this->version = null;
        }

        return $result;
    }
}
