<?php

namespace Minz;

/**
 * The Environment class initialize the application environment, by setting
 * correct global runtime configuration to correct values according to the
 * defined Configuration::$environment.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Environment
{
    /**
     * Initialize the application environment.
     */
    public static function initialize()
    {
        // Configure system logger
        $app_name = Configuration::$app_name;
        openlog($app_name, LOG_PERROR | LOG_PID, LOG_USER);

        // Configure error reporting
        $environment = Configuration::$environment;
        switch ($environment) {
            case 'development':
            case 'test':
                error_reporting(E_ALL);
                ini_set('display_errors', 'On');
                ini_set('log_errors', 'On');
                break;
            case 'production':
            default:
                error_reporting(E_ALL);
                ini_set('display_errors', 'Off');
                ini_set('log_errors', 'On');
                break;
        }
    }

    /**
     * Set the session name to the app name, and start the session.
     *
     * @param string $samesite Either Lax, Strict or None (default is "Lax")
     * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/Set-Cookie/SameSite
     */
    public static function startSession($samesite = 'Lax')
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

        // Some browsers don't accept cookies if domain is localhost
        // @see https://stackoverflow.com/a/1188145
        if ($url_options['host'] !== 'localhost') {
            $cookie_params['domain'] = $url_options['host'];
        }

        session_set_cookie_params($cookie_params);
        session_start();
    }
}
