<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testListRoutes()
    {
        $router = new Router();

        $routes = $router->routes();

        $this->assertSame(0, count($routes));
    }

    public function testAddRoute()
    {
        $router = new Router();

        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $routes = $router->routes();
        $this->assertSame([
            'get' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    public function testAddRouteAcceptsCliVia()
    {
        $router = new Router();

        $router->addRoute('cli', '/rabbits', 'rabbits#list');

        $routes = $router->routes();
        $this->assertSame([
            'cli' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAddRouteFailsIfPathIsEmpty($emptyPath)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "pattern" cannot be empty.');

        $router = new Router();

        $router->addRoute('get', $emptyPath, 'rabbits#list');
    }

    public function testAddRouteFailsIfPathDoesntStartWithSlash()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "pattern" must start by a slash (/).');

        $router = new Router();

        $router->addRoute('get', 'rabbits', 'rabbits#list');
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAddRouteFailsIfToIsEmpty($emptyTo)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "action_pointer" cannot be empty.');

        $router = new Router();

        $router->addRoute('get', '/rabbits', $emptyTo);
    }

    public function testAddRouteFailsIfToDoesntContainHash()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "action_pointer" must contain a hash (#).');

        $router = new Router();

        $router->addRoute('get', '/rabbits', 'rabbits_list');
    }

    public function testAddRouteFailsIfToContainsMoreThanOneHash()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            'Route "action_pointer" must contain at most one hash (#).'
        );

        $router = new Router();

        $router->addRoute('get', '/rabbits', 'rabbits#list#more');
    }

    /**
     * @dataProvider invalidViaProvider
     */
    public function testAddRouteFailsIfViaIsInvalid($invalidVia)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidVia} via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();

        $router->addRoute($invalidVia, '/rabbits', 'rabbits#list');
    }

    public function testMatch()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        list(
            $action_pointer,
            $parameters
        ) = $router->match('get', '/rabbits');

        $this->assertSame('rabbits#list', $action_pointer);
        $this->assertSame([
            '_action_pointer' => 'rabbits#list',
        ], $parameters);
    }

    public function testMatchWithTrailingSlashes()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        list(
            $action_pointer,
            $parameters,
        ) = $router->match('get', '/rabbits//');

        $this->assertSame('rabbits#list', $action_pointer);
        $this->assertSame([
            '_action_pointer' => 'rabbits#list',
        ], $parameters);
    }

    public function testMatchWithParam()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#get');

        list(
            $action_pointer,
            $parameters
        ) = $router->match('get', '/rabbits/42');

        $this->assertSame('rabbits#get', $action_pointer);
        $this->assertSame([
            'id' => '42',
            '_action_pointer' => 'rabbits#get',
        ], $parameters);
    }

    public function testMatchWithWildcard()
    {
        $router = new Router();
        $router->addRoute('get', '/assets/*', 'assets#serve');

        list(
            $action_pointer,
            $parameters
        ) = $router->match('get', '/assets/path/to/an/asset.css');

        $this->assertSame('assets#serve', $action_pointer);
        $this->assertSame([
            '*' => 'path/to/an/asset.css',
            '_action_pointer' => 'assets#serve',
        ], $parameters);
    }

    public function testMatchFailsIfPatternIsLongerThanPath()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "get /rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#show');

        $router->match('get', '/rabbits');
    }

    public function testMatchFailsIfNotMatchingVia()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "post /rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $router->match('post', '/rabbits');
    }

    public function testMatchFailsIfIncorrectPath()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "get /no-rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $router->match('get', '/no-rabbits');
    }

    public function testMatchWithParamFailsIfIncorrectPath()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "get /rabbits/42/details" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#get');

        $router->match('get', '/rabbits/42/details');
    }

    /**
     * @dataProvider invalidViaProvider
     */
    public function testMatchFailsIfViaIsInvalid($invalidVia)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidVia} via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $router->match($invalidVia, '/rabbits');
    }

    public function testUriByPointer()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $uri = $router->uriByPointer('get', 'rabbits#list');

        $this->assertSame('/rabbits', $uri);
    }

    public function testUriByPointerWithParams()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#details');

        $uri = $router->uriByPointer('get', 'rabbits#details', ['id' => 42]);

        $this->assertSame('/rabbits/42', $uri);
    }

    public function testUriWithAdditionalParameters()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#details');

        $uri = $router->uriByPointer('get', 'rabbits#details', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $uri);
    }

    public function testUriByPointerWithUrlOptionPath()
    {
        Configuration::$url_options['path'] = '/path';
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $uri = $router->uriByPointer('get', 'rabbits#list');

        $this->assertSame('/path/rabbits', $uri);

        Configuration::$url_options['path'] = '';
    }

    public function testUriByPointerFailsIfParameterIsMissing()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#details');

        $uri = $router->uriByPointer('get', 'rabbits#details');
    }

    public function testUriByPointerFailsIfActionPointerNotRegistered()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage(
            'Action pointer "get rabbits#list" doesn’t match any route.'
        );

        $router = new Router();

        $router->uriByPointer('get', 'rabbits#list');
    }

    /**
     * @dataProvider invalidViaProvider
     */
    public function testUriByPointerFailsIfViaIsInvalid($invalid_via)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalid_via} via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');

        $router->uriByPointer($invalid_via, 'rabbits#list');
    }

    public function testUriByName()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list', 'rabbits');

        $uri = $router->uriByName('rabbits');

        $this->assertSame('/rabbits', $uri);
    }

    public function testUriByNameWithParams()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#details', 'rabbit');

        $uri = $router->uriByName('rabbit', ['id' => 42]);

        $this->assertSame('/rabbits/42', $uri);
    }

    public function testUriByNameWithAdditionalParameters()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#details', 'rabbit');

        $uri = $router->uriByName('rabbit', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $uri);
    }

    public function testUriByNameWithUrlOptionPath()
    {
        Configuration::$url_options['path'] = '/path';
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list', 'rabbits');

        $uri = $router->uriByName('rabbits');

        $this->assertSame('/path/rabbits', $uri);

        Configuration::$url_options['path'] = '';
    }

    public function testUriByNameFailsIfParameterIsMissing()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#details', 'rabbit');

        $uri = $router->uriByName('rabbit');
    }

    public function testUriByNameFailsIfNameNotRegistered()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage(
            'Route named "rabbits" doesn’t match any route.'
        );

        $router = new Router();

        $router->uriByName('rabbits');
    }

    public function emptyValuesProvider()
    {
        return [
            [''],
            [null],
            [false],
            [[]],
        ];
    }

    public function invalidViaProvider()
    {
        return [
            ['invalid'],
            ['postpost'],
            [' get'],
        ];
    }
}
