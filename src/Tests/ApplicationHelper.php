<?php

namespace Minz\Tests;

use Minz\Request;
use Minz\Response;

/**
 * Provide an appRun() method to help to run requests over Application.
 *
 * @phpstan-import-type RequestMethod from Request
 * @phpstan-import-type RequestParameters from Request
 * @phpstan-import-type RequestHeaders from Request
 * @phpstan-import-type ResponseReturnable from Response
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait ApplicationHelper
{
    protected static ?object $application;

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
     */
    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        $app_name = \Minz\Configuration::$app_name;
        $application_class_name = "\\{$app_name}\\Application";
        try {
            $application = new $application_class_name();
        } catch (\Error $e) {
            // fail silently, the application must be set manually
            return;
        }

        if (is_callable([$application, 'run'])) {
            self::$application = $application;
        }
    }

    /**
     * Create a request based on the parameters, and run it over the
     * $application.
     *
     * @see \Minz\Request
     *
     * @param RequestMethod $method
     * @param RequestParameters $parameters
     * @param RequestHeaders $headers
     *
     * @return ResponseReturnable
     */
    public function appRun(string $method, string $uri, array $parameters = [], array $headers = []): mixed
    {
        if (!self::$application) {
            $app_name = \Minz\Configuration::$app_name;
            $application_class_name = "\\{$app_name}\\Application";
            throw new \RuntimeException("{$application_class_name} doesn't exist, or run() is not callable.");
        }

        $request = new Request($method, $uri, $parameters, $headers);
        return self::$application->run($request);
    }
}
