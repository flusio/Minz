<?php

namespace Minz\Tests;

/**
 * Provide an appRun() method to help to run requests over Application.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait ApplicationHelper
{
    protected static $application;

    /**
     * Try to load an application based on a convention: the class must be
     * named \<app_name>\Application, where <app_name> is the value from the
     * Configuration.
     *
     * If the application cannot be loaded, then you have to set the
     * self::$application by yourself.
     *
     * The application class MUST declare a `run` method accepting a
     * \Minz\Request and returning a \Minz\Response.
     *
     * @beforeClass
     */
    public static function loadApplication()
    {
        $app_name = \Minz\Configuration::$app_name;
        $application_class_name = "\\{$app_name}\\Application";
        try {
            self::$application = new $application_class_name();
        } catch (\Error $e) {
            // fail silently, the application must be set manually
        }
    }

    /**
     * Create a request based on the parameters, and run it over the
     * $application.
     *
     * @see \Minz\Request
     *
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @param array $headers
     *
     * @return \Minz\Response
     */
    public function appRun($method, $uri, $parameters = [], $headers = [])
    {
        $request = new \Minz\Request($method, $uri, $parameters, $headers);
        return self::$application->run($request);
    }
}
