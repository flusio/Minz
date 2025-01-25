<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Configuration class represents the configuration of the application.
 *
 * It must be loaded at the very beginning of any initialization of Minz-based
 * applications and scripts (e.g. `public/index.php`, `cli`, `tests/bootstrap.php`).
 * It’s done with the `load()` method. For instance, in `public/index.php`:
 *
 * ```php
 * $app_path = realpath(__DIR__ . '/..');
 * include $app_path . '/autoload.php';
 * \Minz\Configuration::load('development', $app_path);
 * ```
 *
 * The first argument is the environment name. Valid values are `development`,
 * `test` and `production`. It also can be set to `dotenv`, in which case Minz
 * will look for an `APP_ENVIRONMENT` environment variable (either with PHP
 * `getenv()` function or in a `.env` file). The second argument, `$app_path`,
 * must point to the root directory of your application.
 *
 * Configuration is loaded from a `$app_path/configuration/environment_$environment.php`
 * where `$app_path` and `$environment` are replaced by their corresponding
 * variables (e.g. `/path/to/myapp/configuration/environment_development.php`).
 * These files must return a PHP array and have access to the `$app_path`
 * variable. If a `.env` file exists at the root path, the files also have
 * access to a `$dotenv` variable. For instance:
 *
 * ```php
 * return [
 *     'secret_key' => $dotenv->pop('APP_SECRET_KEY'),
 *     'url_options' => [
 *         'host' => $dotenv->pop('APP_HOST', 'localhost'),
 *     ],
 *     'data_path' => $dotenv->pop('APP_DATA_PATH', $app_path . '/data'),
 * ];
 * ```
 *
 * Configuration can then be accessed from anywhere in your application:
 *
 * ```php
 * echo 'Application name is: ' . \Minz\Configuration::$app_name;
 * echo 'Data path is: ' . \Minz\Configuration::$data_path;
 * echo 'Environment is: ' . \Minz\Configuration::$environment;
 * ```
 *
 * Available configuration variables are described below. Please note that some
 * are automatically generated, some must be declared, and the last are
 * optional.
 *
 * You can declare configuration options specific to your application in the
 * `$application` attribute. This attribute is declared as a array<string, mixed> type.
 * You can precise the types by defining a custom Configuration in your app that
 * inherits from the Minz Configuration:
 *
 * ```php
 * namespace App;
 *
 * /**
 *  * @phpstan-type ConfigurationApplication array{
 *  *     'your_option': string,
 *  *     // ...
 *  * }
 *  *
 * class Configuration extends \Minz\Configuration
 * {
 *     /** @var ConfigurationApplication *
 *     public static array $application;
 * }
 * ```
 *
 * @see \Minz\Dotenv
 *
 * @phpstan-type ConfigurationEnvironment value-of<self::VALID_ENVIRONMENTS>
 *
 * @phpstan-type ConfigurationArray array<string, mixed>
 *
 * @phpstan-type ConfigurationDatabase array{
 *     'dsn': string,
 *     'username': ?string,
 *     'password': ?string,
 *     'options': mixed[],
 *     'type': 'sqlite'|'pgsql',
 *     'host': ?string,
 *     'port': ?int,
 *     'dbname': ?string,
 *     'path': ?string,
 * }
 *
 * @phpstan-type ConfigurationMailer array{
 *     'type': 'mail'|'smtp'|'test',
 *     'from': string,
 *     'debug': int,
 *     'smtp'?: array{
 *         'domain': string,
 *         'host': string,
 *         'port': int,
 *         'auth': bool,
 *         'auth_type': 'CRAM-MD5'|'LOGIN'|'PLAIN'|'XOAUTH2'|'',
 *         'username': string,
 *         'password': string,
 *         'secure': 'ssl'|'tls'|'',
 *     },
 * }
 *
 * @phpstan-type ConfigurationUrl array{
 *     'host': string,
 *     'protocol': 'http'|'https',
 *     'port': int,
 *     'path': string,
 * }
 *
 * @phpstan-type ConfigurationJobsAdapter value-of<self::VALID_JOBS_ADAPTERS>
 */
class Configuration
{
    private const VALID_ENVIRONMENTS = ['development', 'test', 'production'];

    private const VALID_DATABASE_TYPES = ['sqlite', 'pgsql'];

    private const VALID_MAILER_TYPES = ['mail', 'smtp', 'test'];

    private const VALID_JOBS_ADAPTERS = ['database', 'test'];

    /**
     * AUTOMATIC VARIABLES
     *
     * These variables are accessible in the configuration files, but you
     * really don’t want to overwrite their values. No, you don’t.
     */

    /**
     * The environment in which the application run.
     *
     * @var ConfigurationEnvironment
     */
    public static string $environment;

    /**
     * The path to the root directory of the application.
     */
    public static string $app_path;

    /**
     * The path to the configuration directory.
     */
    public static string $configuration_path;

    /**
     * The path to the current configuration file.
     */
    public static string $configuration_filepath;

    /**
     * REQUIRED VARIABLES
     *
     * These variables *must* be declared in your configuration file. The
     * initialization will fail if they are not.
     */

    /**
     * A cryptographically secret key generated by the administrator, it must
     * be at least 64 chars long in production.
     */
    public static string $secret_key;

    /**
     * The web server information to build URLs. It’s an array where keys are:
     * - host: the domain name serving your application (required)
     * - port: the port of your server (default is set to `443` if protocol is
     *   https, else `80`)
     * - path: the path to your application (default is `/`)
     * - protocol: the protocol used by your server (default is `http`)
     *
     * @var ConfigurationUrl
     */
    public static array $url_options;

    /**
     * OPTIONAL VARIABLES
     *
     * Loading configuration will not fail if these variables aren’t declared
     * in your file. It doesn’t mean your application will work as expected
     * though.
     */

    /**
     * The name of the application. It must be identical to the application
     * namespace (e.g. if your base namespace is `myapp\`, its value must be
     * set to `myapp`). Default is `App`.
     */
    public static string $app_name;

    /**
     * Declare options specific to your application (default is an empty array).
     *
     * @var array<string, mixed>
     */
    public static array $application;

    /**
     * The path to the data directory (default is `$app_path/data`).
     */
    public static string $data_path;

    /**
     * The path to the SQL schema of your application (default is
     * `$app_path/src/schema.sql`)
     */
    public static string $schema_path;

    /**
     * The path to a temporary directory (default is a random directory created
     * under `sys_get_temp_dir()/$app_name`)
     */
    public static string $tmp_path;

    /**
     * The information to access your database. It’s an array where keys are:
     * - dsn: the Data Source Name containing the info to connect to the
     *   database. Only `pgsql` and `sqlite` drivers are supported. A
     *   connection to a Postgres database requires that `host`, `port` and
     *   `dbname` are declared in the DSN. It is recommended to pass
     *   username and password separately. Username and password from the
     *   DSN have priority over the ones from the configuration.
     * - username: the username to connect to the database (optional)
     * - password: the password to connect to the database (optional)
     * - options: options to pass to the \PDO constructor (optional)
     *
     * @see https://www.php.net/manual/pdo.construct.php
     *
     * @var ?ConfigurationDatabase
     */
    public static ?array $database;

    /**
     * The information to send emails. It’s an array where keys are:
     * - type: either `mail` (default), `smtp` or `test`
     * - from: a valid email address (default is `root@localhost`)
     * - debug: the level of verbosity of PHPMailer (default is `2` in
     *   development environment, `0` otherwise)
     * - smtp: information to connect to a SMTP server (only used if type is `stmp`).
     *   It’s an array where keys are:
     *     - domain: the domain used in the Message-ID header (default is ``)
     *     - host: the SMTP server address (default is `localhost`)
     *     - port: the SMTP port (default is `25`)
     *     - auth: if SMTP authentication should be used (default is `false`)
     *     - auth_type: possible values are `CRAM-MD5`, `LOGIN`, `PLAIN`,
     *       `XOAUTH2` or `` (default)
     *     - username: the username to connect to the SMTP server (default is ``)
     *     - password: the password to connect to the SMTP server (default is ``)
     *     - secure: possible values are `ssl`, `tls` or `` (default)
     *
     * @see \PHPMailer\PHPMailer\PHPMailer
     *
     * @var ConfigurationMailer
     */
    public static array $mailer;

    /**
     * Specify the adapter of the jobs mechanism.
     *
     * If set to `database`, the jobs are stored in database.
     * If set to `test`, the jobs are immediately executed.
     *
     * @var ConfigurationJobsAdapter
     */
    public static string $jobs_adapter;

    /**
     * Specify if syslog must output \Minz\Log calls to the console (default is `false`)
     */
    public static bool $no_syslog_output;

    /**
     * Load the application configuration for a given environment.
     *
     * @param ConfigurationEnvironment|'dotenv' $environment
     *     Can be set to development, production, test or dotenv. In the last
     *     case, a `APP_ENVIRONMENT` is searched either in the environment
     *     variables or in a `.env` file.
     * @param string $app_path
     *     The path to the root directory of the application.
     *
     * @throws \Minz\Errors\ConfigurationError
     *     Raised if the environment is not part of the valid environments, if
     *     the corresponding environment configuration file doesn't exist, if a
     *     required value is missing, or if a value doesn't match the required
     *     format.
     */
    public static function load(string $environment, string $app_path): void
    {
        // If the app declares a .env file, we initialize a $dotenv variable
        // (it will be accessible in the configuration file then).
        $dotenv_path = $app_path . '/.env';
        if (file_exists($dotenv_path)) {
            $dotenv = new Dotenv($dotenv_path);
        }

        // If environment is set to dotenv, we look for an APP_ENVIRONMENT
        // environment variable to find the "real" value of the environment.
        if ($environment === 'dotenv') {
            if (isset($dotenv)) {
                $environment = $dotenv->pop('APP_ENVIRONMENT');
            } else {
                $environment = getenv('APP_ENVIRONMENT');
            }

            if (!$environment) {
                throw new Errors\ConfigurationError(
                    'You must declare an APP_ENVIRONMENT environment variable when using dotenv environment.'
                );
            }
        }

        if (!in_array($environment, self::VALID_ENVIRONMENTS, true)) {
            throw new Errors\ConfigurationError(
                "{$environment} is not a valid environment."
            );
        }

        /** @var ConfigurationEnvironment $environment */
        $environment = $environment;

        // Load the configuration file and make sure that it exists. A missing
        // file would be problematic to load required variables :)
        $configuration_path = $app_path . '/configuration';
        $configuration_filename = "environment_{$environment}.php";
        $configuration_filepath = $configuration_path . '/' . $configuration_filename;
        if (!file_exists($configuration_filepath)) {
            throw new Errors\ConfigurationError(
                "configuration/{$configuration_filename} file cannot be found."
            );
        }

        // The consequence of including the configuration file this way is that
        // it has access to the variable declare above: that's what we want!
        $raw_configuration = include($configuration_filepath);

        // Initialize the automatic variables
        static::$environment = $environment;
        static::$app_path = $app_path;
        static::$configuration_path = $configuration_path;
        static::$configuration_filepath = $configuration_filepath;

        // Then, get the required variables from the configuration file
        static::$secret_key = self::getSecretKey($raw_configuration, $environment);
        static::$url_options = self::getUrlOptions($raw_configuration);

        // And, finally, get the optional variables
        static::$app_name = $raw_configuration['app_name'] ?? 'App';
        static::$application = $raw_configuration['application'] ?? [];
        static::$data_path = $raw_configuration['data_path'] ?? $app_path . '/data';
        static::$schema_path = $raw_configuration['schema_path'] ?? $app_path . '/src/schema.sql';
        $default_tmp_path = sys_get_temp_dir() . '/' . static::$app_name . '/' . bin2hex(random_bytes(10));
        static::$tmp_path = $raw_configuration['tmp_path'] ?? $default_tmp_path;
        static::$database = self::getDatabase($raw_configuration);
        static::$mailer = self::getMailer($raw_configuration, $environment);
        $jobs_adapter = $raw_configuration['jobs_adapter'] ?? 'database';
        if (!in_array($jobs_adapter, self::VALID_JOBS_ADAPTERS, true)) {
            $jobs_adapter = 'database';
        }
        static::$jobs_adapter = $jobs_adapter;
        static::$no_syslog_output = $raw_configuration['no_syslog_output'] ?? false;
    }

    /**
     * Return the value associated to the key of an array, or throw an error if
     * it doesn't exist.
     *
     * @param ConfigurationArray $array
     *
     * @throws \Minz\Errors\ConfigurationError
     *     Raised if the given key is not in the array
     */
    private static function failIfMissing(array $array, string $key): void
    {
        if (!isset($array[$key])) {
            throw new Errors\ConfigurationError("{$key} configuration key is required");
        }
    }

    /**
     * Return the secret_key option.
     *
     * @param ConfigurationArray $array
     * @param ConfigurationEnvironment $environment
     *
     * @throws \Minz\Errors\ConfigurationError
     *     Raised if the secret_key is missing, or if its length is less than
     *     64 chars in production.
     */
    private static function getSecretKey(array $array, string $environment): string
    {
        self::failIfMissing($array, 'secret_key');

        /** @var string $secret_key */
        $secret_key = $array['secret_key'];

        if ($environment === 'production' && strlen($secret_key) < 64) {
            throw new Errors\ConfigurationError(
                'The secret_key must be at least 64 chars long and be cryptographically secure.'
            );
        }

        return $secret_key;
    }

    /**
     * Return the final url_options configuration.
     *
     * @param ConfigurationArray $array
     *
     * @throws \Minz\Errors\ConfigurationError
     *     Raised if the url_options is missing, if it’s not an array, or if it
     *     doesn’t contain a host option.
     *
     * @return ConfigurationUrl
     */
    private static function getUrlOptions(array $array): array
    {
        self::failIfMissing($array, 'url_options');

        $url_options = $array['url_options'] ?? [];

        if (!is_array($url_options)) {
            throw new Errors\ConfigurationError(
                'URL options configuration must be an array, containing at least a host key.'
            );
        }

        $host = $url_options['host'] ?? '';
        $protocol = $url_options['protocol'] ?? 'http';
        $path = $url_options['path'] ?? '/';
        $port = $url_options['port'] ?? null;

        if (!is_string($host) || !$host) {
            throw new Errors\ConfigurationError(
                'URL options configuration must contain at least a host key.'
            );
        }

        if ($protocol !== 'http' && $protocol !== 'https') {
            throw new Errors\ConfigurationError(
                'URL protocol must be either http or https.'
            );
        }

        if (!is_int($port)) {
            $port = $protocol === 'https' ? 443 : 80;
        }

        return [
            'host' => $host,
            'path' => $path,
            'protocol' => $protocol,
            'port' => $port,
        ];
    }

    /**
     * Return the final database configuration.
     *
     * @param ConfigurationArray $array
     *
     * @throws \Minz\Errors\ConfigurationError
     *     Raised if the database option is not an array, if the dsn key is
     *     missing, or if the database type is invalid. Also if host, port or
     *     dbname are missing from the DSN if type is pgsql.
     *
     * @return ?ConfigurationDatabase
     */
    private static function getDatabase(array $array): ?array
    {
        $database = $array['database'] ?? null;

        if ($database === null) {
            return null;
        }

        if (!is_array($database)) {
            throw new Errors\ConfigurationError(
                'Database configuration must be an array, containing at least a dsn key.'
            );
        }

        $dsn = $database['dsn'] ?? '';

        if (!is_string($dsn) || !$dsn) {
            throw new Errors\ConfigurationError(
                'Database configuration must contain at least a dsn key.'
            );
        }

        $info_from_dsn = self::extractDsnInfo($dsn);
        $database = array_merge($database, $info_from_dsn);

        $type = $database['type'] ?? '';

        if (!in_array($type, self::VALID_DATABASE_TYPES, true)) {
            throw new Errors\ConfigurationError(
                "{$database['type']} database is not supported."
            );
        }

        if ($type === 'pgsql') {
            if (!isset($database['host'])) {
                throw new Errors\ConfigurationError(
                    'pgsql connection requires a `host` key, check your dsn string.'
                );
            }

            if (!isset($database['port'])) {
                throw new Errors\ConfigurationError(
                    'pgsql connection requires a `port` key, check your dsn string.'
                );
            }

            if (!isset($database['dbname'])) {
                throw new Errors\ConfigurationError(
                    'pgsql connection requires a `dbname` key, check your dsn string.'
                );
            }
        }

        return [
            'dsn' => $dsn,
            'type' => $type,
            'username' => $database['username'] ?? null,
            'password' => $database['password'] ?? null,
            'options' => $database['options'] ?? [],
            'host' => $database['host'] ?? null,
            'port' => $database['port'] ?? null,
            'dbname' => $database['dbname'] ?? null,
            'path' => $database['path'] ?? null,
        ];
    }

    /**
     * Split a DSN string and return the information as an array.
     *
     * @return array<string, string>
     */
    private static function extractDsnInfo(string $dsn): array
    {
        $info = [];

        list($database_type, $dsn_rest) = explode(':', $dsn, 2);

        if ($database_type === 'sqlite') {
            $info['path'] = $dsn_rest;
        } elseif ($database_type === 'pgsql') {
            $dsn_parts = explode(';', $dsn_rest);
            foreach ($dsn_parts as $dsn_part) {
                list($part_key, $part_value) = explode('=', $dsn_part, 2);
                if ($part_key === 'user') {
                    $part_key = 'username';
                }
                $info[$part_key] = $part_value;
            }
        }

        $info['type'] = $database_type;

        return $info;
    }

    /**
     * Return the final mailer configuration.
     *
     * @param ConfigurationArray $array
     * @param ConfigurationEnvironment $environment
     *
     * @throws \Minz\Errors\ConfigurationError
     *     Raised if the mailer option is not an array, or if the type key is
     *     invalid.
     *
     * @return ConfigurationMailer
     */
    private static function getMailer(array $array, string $environment): array
    {
        $mailer = $array['mailer'] ?? [];

        if (!is_array($mailer)) {
            throw new Errors\ConfigurationError(
                'Mailer configuration must be an array.'
            );
        }

        $type = $mailer['type'] ?? '';

        if (!is_string($type) || !$type) {
            $type = 'mail';
        }

        if (!in_array($type, self::VALID_MAILER_TYPES, true)) {
            throw new Errors\ConfigurationError("{$type} is not a valid mailer type.");
        }

        $clean_mailer = [
            'type' => $type,
            'from' => $mailer['from'] ?? 'root@localhost',
            'debug' => $environment === 'development' ? 2 : 0,
        ];

        if ($type === 'smtp') {
            $clean_mailer['smtp'] = [
                'domain' => $mailer['smtp']['domain'] ?? '',
                'host' => $mailer['smtp']['host'] ?? 'localhost',
                'port' => $mailer['smtp']['port'] ?? 25,
                'auth' => $mailer['smtp']['auth'] ?? false,
                'auth_type' => $mailer['smtp']['auth_type'] ?? '',
                'username' => $mailer['smtp']['username'] ?? '',
                'password' => $mailer['smtp']['password'] ?? '',
                'secure' => $mailer['smtp']['secure'] ?? '',
            ];
        }

        return $clean_mailer;
    }
}
