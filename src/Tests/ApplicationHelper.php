<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use Minz\Errors;
use Minz\Request;
use Minz\Response;

/**
 * Provide an appRun() method to help to run requests over Application.
 *
 * ```php
 * class ControllerTest extends \PHPUnit\Framework\TestCase
 * {
 *     use \Minz\Tests\ApplicationHelper;
 *
 *     public function testGetHome(): void
 *     {
 *         $response = $this->appRun('GET', '/');
 *
 *         // Assert things on $response
 *     }
 * }
 * ```
 *
 * @phpstan-import-type RequestMethod from Request
 * @phpstan-import-type ResponseReturnable from Response
 * @phpstan-import-type Parameters from ParameterBag
 *
 * @phpstan-ignore-next-line trait.unused
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
     * @param Parameters $parameters
     * @param Parameters $headers
     * @param Parameters $cookies
     * @param Parameters $server
     *
     * @return ResponseReturnable
     *
     * @throws Errors\RuntimeException
     *     Raised if the Application class doesn't exist.
     */
    public function appRun(
        string $method,
        string $uri,
        array $parameters = [],
        array $headers = [],
        array $cookies = [],
        array $server = [],
    ): mixed {
        if (!self::$application) {
            $app_name = \Minz\Configuration::$app_name;
            $application_class_name = "\\{$app_name}\\Application";
            throw new Errors\RuntimeException("{$application_class_name} doesn't exist, or run() is not callable.");
        }

        $request = new Request($method, $uri, $parameters, $headers, $cookies, $server);
        return self::$application->run($request);
    }
}
