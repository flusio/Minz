<?php

namespace Minz;

/**
 * Represent the configuration of the application.
 *
 * `\Minz\Configuration::load($env, $app_path)` must be called at the very
 * beginning of the app initialization.
 *
 * Configurations must be declared under a `configuration/` directory. They are
 * loaded for a given environment (either "development", "test" or
 * "production") and from a `configuration/environment_<environment>.php`
 * file, where `<environment>` is replaced by the value of the current env.
 * These files must return a PHP array.
 *
 * An `environment` and an `app_path` values are automatically set from the
 * parameters of the `load` method.
 *
 * Required parameters are:
 * - app_name: it must be the same as the base namespace of the application
 * - url_options, with:
 *   - host: the domain name pointing to your server
 *   - port: the listening port of your server (default is set to 443 if
 *           protocol is https, else 80)
 *   - path: URI path to your application (default is /)
 *   - protocol: the protocol used by your server (default is http)
 *
 * Other automated values are:
 * - configuration_path: the path to the configuration directory
 * - configuration_filepath: the path to the current configuration file
 *
 * Other optional keys are:
 * - data_path: the path to the data directory, default to $app_path/data
 * - schema_path: the path to the SQL schema file, default to $app_path/src/schema.sql
 * - database: an array specifying:
 *   - dsn
 *   - username (optional if database is sqlite)
 *   - password (optional if database is sqlite)
 *   - options (optional)
 *   See https://www.php.net/manual/fr/pdo.construct.php
 * - mailer: an array specifying mailing options, with:
 *   - type: either `mail` or `smtp`, default 'mail'
 *   - from: a valid email address, default 'root@localhost'
 *   - debug: optional, default is 2 if environment is set to `development`, 0
 *     otherwise
 *   - smtp: only if type is set to `stmp` (optional), with:
 *     - domain: the domain used in the Message-ID header, default ''
 *     - host: the SMTP server address, default 'localhost'
 *     - port: default 25
 *     - auth: whether to use SMTP authentication, default false
       - auth_type: 'CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2' or '', default ''
 *     - username: default ''
 *     - password: default ''
 *     - secure: '', 'ssl' or 'tls', default ''
 * - application: you can set options specific to your application here,
 *   default to empty array
 * - no_syslog: `true` to silent calls to \Minz\Log (wrapper aroung syslog function),
 *   default to `false`
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Configuration
{
    /** @var string[] */
    private const VALID_ENVIRONMENTS = ['development', 'test', 'production'];

    /** @var string The environment in which the application is run */
    public static $environment;

    /** @var string The base path of the application */
    public static $app_path;

    /** @var string The path to the configuration directory */
    public static $configuration_path;

    /** @var string The path to the current configuration file */
    public static $configuration_filepath;

    /** @var string The path to the data directory */
    public static $data_path;

    /** @var string The path to the schema.sql file */
    public static $schema_path;

    /**
     * @var string The name of the application. It must be identical to the
     *             application's namespace.
     */
    public static $app_name;

    /** @var array The web server information to build URLs */
    public static $url_options;

    /** @var array An array containing mailer configuration */
    public static $mailer;

    /** @var string[] An array containing database configuration */
    public static $database;

    /** @var array An array for options spectific to the application */
    public static $application;

    /** @var boolean Indicate if syslog must be called via \Minz\Log calls */
    public static $no_syslog;

    /**
     * Load the application's configuration, for a given environment.
     *
     * @param string $environment
     * @param string $app_path
     *
     * @throws \Minz\Errors\ConfigurationError if the environment is not part
     *                                         of the valid environments
     * @throws \Minz\Errors\ConfigurationError if the corresponding environment
     *                                         configuration file doesn't exist
     * @throws \Minz\Errors\ConfigurationError if a required value is missing
     * @throws \Minz\Errors\ConfigurationError if a value doesn't match with
     *                                         the required format
     *
     * @return void
     */
    public static function load($environment, $app_path)
    {
        if (!in_array($environment, self::VALID_ENVIRONMENTS)) {
            throw new Errors\ConfigurationError(
                "{$environment} is not a valid environment."
            );
        }

        $configuration_path = $app_path . '/configuration';
        $configuration_filename = "environment_{$environment}.php";
        $configuration_filepath = $configuration_path . '/' . $configuration_filename;
        if (!file_exists($configuration_filepath)) {
            throw new Errors\ConfigurationError(
                "configuration/{$configuration_filename} file cannot be found."
            );
        }

        $raw_configuration = include($configuration_filepath);

        self::$environment = $environment;
        self::$app_path = $app_path;
        self::$configuration_path = $configuration_path;
        self::$configuration_filepath = $configuration_filepath;

        self::$app_name = self::getRequired($raw_configuration, 'app_name');

        $url_options = self::getRequired($raw_configuration, 'url_options');
        if (!is_array($url_options)) {
            throw new Errors\ConfigurationError(
                'URL options configuration must be an array, containing at least a host key.'
            );
        }

        if (!isset($url_options['host'])) {
            throw new Errors\ConfigurationError(
                'URL options configuration must contain at least a host key.'
            );
        }

        $default_url_options = [
            'path' => '/',
            'protocol' => 'http',
        ];
        self::$url_options = array_merge($default_url_options, $url_options);
        if (!isset(self::$url_options['port'])) {
            self::$url_options['port'] = self::$url_options['protocol'] === 'https' ? 443 : 80;
        }

        self::$data_path = self::getDefault(
            $raw_configuration,
            'data_path',
            $app_path . '/data'
        );

        self::$schema_path = self::getDefault(
            $raw_configuration,
            'schema_path',
            $app_path . '/src/schema.sql'
        );

        $database = self::getDefault($raw_configuration, 'database', null);
        if ($database !== null) {
            if (!is_array($database)) {
                throw new Errors\ConfigurationError(
                    'Database configuration must be an array, containing at least a dsn key.'
                );
            }

            if (!isset($database['dsn'])) {
                throw new Errors\ConfigurationError(
                    'Database configuration must contain at least a dsn key.'
                );
            }

            $info_from_dsn = self::extractDsnInfo($database['dsn']);
            $additional_default_values = [
                'username' => null,
                'password' => null,
                'options' => [],
            ];
            $database = array_merge($additional_default_values, $database, $info_from_dsn);

            if ($database['type'] === 'sqlite') {
                // all should be good, do nothing
            } elseif ($database['type'] === 'pgsql') {
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
            } else {
                throw new Errors\ConfigurationError(
                    "{$database['type']} database is not supported."
                );
            }
        }
        self::$database = $database;

        $default_mailer_options = [
            'type' => 'mail',
            'from' => 'root@localhost',
            'debug' => $environment === 'development' ? 2 : 0,
        ];
        $mailer = array_merge(
            $default_mailer_options,
            self::getDefault($raw_configuration, 'mailer', [])
        );

        if ($mailer['type'] === 'smtp') {
            $default_smtp_options = [
                'domain' => '', // the domain used in the Message-ID header
                'host' => 'localhost', // the SMTP server address
                'port' => 25,
                'auth' => false,
                'auth_type' => '', // 'CRAM-MD5', 'LOGIN', 'PLAIN', 'XOAUTH2' or ''
                'username' => '',
                'password' => '',
                'secure' => '', // '', 'ssl' or 'tls'
            ];
            if (!isset($mailer['smtp']) || !is_array($mailer['smtp'])) {
                $mailer['smtp'] = [];
            }
            $smtp_options = array_merge($default_smtp_options, $mailer['smtp']);
            $mailer['smtp'] = $smtp_options;
        }

        self::$mailer = $mailer;

        self::$application = self::getDefault($raw_configuration, 'application', []);

        self::$no_syslog = self::getDefault($raw_configuration, 'no_syslog', false);
    }

    /**
     * Return the value associated to the key of an array, or throw an error if
     * it doesn't exist.
     *
     * @param mixed[] $array
     * @param string $key
     *
     * @throws \Minz\Errors\ConfigurationError if the given key is not in the array
     *
     * @return mixed
     */
    private static function getRequired($array, $key)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            throw new Errors\ConfigurationError("{$key} configuration key is required");
        }
    }

    /**
     * Return the value associated to the key of an array, or a default one if
     * it doesn't exist.
     *
     * @param mixed[] $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private static function getDefault($array, $key, $default)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }

    /**
     * Split a DSN string and return the information as an array
     *
     * @param string $dsn
     *
     * @return array
     */
    private static function extractDsnInfo($dsn)
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
}
