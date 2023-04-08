<?php

namespace Minz;

/**
 * The Environment initializes the logs system, PHP errors reporting and
 * session cookie.
 *
 * You’ll almost never interact with the Environment class, but it’s very
 * important to initialize it to get errors reporting right. It’s generally
 * done just after loading the Configuration:
 *
 * ```php
 * $app_path = realpath(__DIR__ . '/..');
 * include $app_path . '/autoload.php';
 *
 * \Minz\Configuration::load('dotenv', $app_path);
 * \Minz\Environment::initialize();
 * \Minz\Environment::startSession();
 * ```
 *
 * If your application needs PHP sessions, please don’t call the PHP function
 * `session_start` directly. It’s the job of `startSession()` to configure the
 * session correctly based on the information from the configuration.
 *
 * If you don’t need sessions, don’t call `startSession()`.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Environment
{
    /**
     * Initialize the logs system and configure errors reporting.
     *
     * The log system is configured to pass the PID with each message. Logs are
     * printed to the standard error unless Configuration::$no_syslog_output is
     * true.
     *
     * Errors reporting is configured accordingly to the official
     * recommendations:
     *
     * - all errors are displayed/logged in development and test
     * - all except deprecated and strict errors are logged in production
     *
     * @see \Minz\Configuration::$no_syslog_output
     * @see https://www.php.net/manual/function.openlog
     * @see https://www.php.net/manual/errorfunc.configuration.php#ini.error-reporting
     * @see https://github.com/php/php-src/blob/master/php.ini-production
     */
    public static function initialize(): void
    {
        // Configure the system logger.
        $app_name = Configuration::$app_name;
        if (Configuration::$no_syslog_output) {
            openlog($app_name, LOG_PID, LOG_USER);
        } else {
            openlog($app_name, LOG_PERROR | LOG_PID, LOG_USER);
        }

        // Configure error reporting
        $environment = Configuration::$environment;
        switch ($environment) {
            case 'development':
            case 'test':
                error_reporting(E_ALL);
                ini_set('display_errors', 'On');
                ini_set('display_startup_errors', 'On');
                ini_set('log_errors', 'On');
                break;

            case 'production':
            default:
                error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
                ini_set('display_errors', 'Off');
                ini_set('display_startup_errors', 'Off');
                ini_set('log_errors', 'On');
                break;
        }
    }

    /**
     * Set the session name to the app name, and start the session with a
     * correct configuration for the cookie.
     *
     * @param 'Lax'|'Strict'|'None' $samesite
     *
     * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/Set-Cookie/SameSite
     */
    public static function startSession(string $samesite = 'Lax'): void
    {
        $url_options = Configuration::$url_options;
        session_name(Configuration::$app_name);

        $cookie_params = [
            'lifetime' => 0,
            'path' => $url_options['path'],
            'secure' => $url_options['protocol'] === 'https',
            'httponly' => true,
            'samesite' => $samesite,
        ];

        // Some browsers don't accept cookies if domain is set to localhost
        // @see https://stackoverflow.com/a/1188145
        if ($url_options['host'] !== 'localhost') {
            $cookie_params['domain'] = $url_options['host'];
        }

        session_set_cookie_params($cookie_params);
        session_start();
    }
}
