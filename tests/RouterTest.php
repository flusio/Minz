<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testListRoutes(): void
    {
        $router = new Router();

        $routes = $router->routes();

        $this->assertSame(0, count($routes));
    }

    public function testAddRoute(): void
    {
        $router = new Router();

        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        $routes = $router->routes();
        $this->assertSame([
            'GET' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    public function testAddRouteAcceptsCliMethod(): void
    {
        $router = new Router();

        $router->addRoute('CLI', '/rabbits', 'rabbits#list');

        $routes = $router->routes();
        $this->assertSame([
            'CLI' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    public function testAddRouteFailsIfPathDoesntStartWithSlash(): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "pattern" must start by a slash (/).');

        $router = new Router();

        $router->addRoute('GET', 'rabbits', 'rabbits#list');
    }

    public function testAddRouteFailsIfToDoesntContainHash(): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "action_pointer" must contain a hash (#).');

        $router = new Router();

        $router->addRoute('GET', '/rabbits', 'rabbits_list');
    }

    public function testAddRouteFailsIfToContainsMoreThanOneHash(): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            'Route "action_pointer" must contain at most one hash (#).'
        );

        $router = new Router();

        $router->addRoute('GET', '/rabbits', 'rabbits#list#more');
    }

    /**
     * @dataProvider invalidMethodProvider
     */
    public function testAddRouteFailsIfMethodIsInvalid(string $invalidMethod): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidMethod} method is invalid (GET, POST, PATCH, PUT, DELETE, CLI)."
        );

        $router = new Router();

        // @phpstan-ignore-next-line
        $router->addRoute($invalidMethod, '/rabbits', 'rabbits#list');
    }

    public function testMatch(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        list(
            $action_pointer,
            $parameters
        ) = $router->match('GET', '/rabbits');

        $this->assertSame('rabbits#list', $action_pointer);
        $this->assertSame([
            '_action_pointer' => 'rabbits#list',
        ], $parameters);
    }

    public function testMatchWithTrailingSlashes(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        list(
            $action_pointer,
            $parameters,
        ) = $router->match('GET', '/rabbits//');

        $this->assertSame('rabbits#list', $action_pointer);
        $this->assertSame([
            '_action_pointer' => 'rabbits#list',
        ], $parameters);
    }

    public function testMatchWithParam(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#get');

        list(
            $action_pointer,
            $parameters
        ) = $router->match('GET', '/rabbits/42');

        $this->assertSame('rabbits#get', $action_pointer);
        $this->assertSame([
            'id' => '42',
            '_action_pointer' => 'rabbits#get',
        ], $parameters);
    }

    public function testMatchWithWildcard(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/assets/*', 'assets#serve');

        list(
            $action_pointer,
            $parameters
        ) = $router->match('GET', '/assets/path/to/an/asset.css');

        $this->assertSame('assets#serve', $action_pointer);
        $this->assertSame([
            '*' => 'path/to/an/asset.css',
            '_action_pointer' => 'assets#serve',
        ], $parameters);
    }

    public function testMatchFailsIfPatternIsLongerThanPath(): void
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "GET /rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#show');

        $router->match('GET', '/rabbits');
    }

    public function testMatchFailsIfNotMatchingMethod(): void
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "POST /rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        $router->match('POST', '/rabbits');
    }

    public function testMatchFailsIfIncorrectPath(): void
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "GET /no-rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        $router->match('GET', '/no-rabbits');
    }

    public function testMatchWithParamFailsIfIncorrectPath(): void
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "GET /rabbits/42/details" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#get');

        $router->match('GET', '/rabbits/42/details');
    }

    /**
     * @dataProvider invalidMethodProvider
     */
    public function testMatchFailsIfMethodIsInvalid(string $invalidMethod): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidMethod} method is invalid (GET, POST, PATCH, PUT, DELETE, CLI)."
        );

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        // @phpstan-ignore-next-line
        $router->match($invalidMethod, '/rabbits');
    }

    public function testUriByPointer(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        $uri = $router->uriByPointer('GET', 'rabbits#list');

        $this->assertSame('/rabbits', $uri);
    }

    public function testUriByPointerWithParams(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#details');

        $uri = $router->uriByPointer('GET', 'rabbits#details', ['id' => 42]);

        $this->assertSame('/rabbits/42', $uri);
    }

    public function testUriWithAdditionalParameters(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#details');

        $uri = $router->uriByPointer('GET', 'rabbits#details', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $uri);
    }

    public function testUriByPointerFailsIfParameterIsMissing(): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#details');

        $uri = $router->uriByPointer('GET', 'rabbits#details');
    }

    public function testUriByPointerFailsIfActionPointerNotRegistered(): void
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage(
            'Action pointer "GET rabbits#list" doesn’t match any route.'
        );

        $router = new Router();

        $router->uriByPointer('GET', 'rabbits#list');
    }

    /**
     * @dataProvider invalidMethodProvider
     */
    public function testUriByPointerFailsIfMethodIsInvalid(string $invalid_method): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalid_method} method is invalid (GET, POST, PATCH, PUT, DELETE, CLI)."
        );

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        // @phpstan-ignore-next-line
        $router->uriByPointer($invalid_method, 'rabbits#list');
    }

    public function testUriByName(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list', 'rabbits');

        $uri = $router->uriByName('rabbits');

        $this->assertSame('/rabbits', $uri);
    }

    public function testUriByNameWithParams(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#details', 'rabbit');

        $uri = $router->uriByName('rabbit', ['id' => 42]);

        $this->assertSame('/rabbits/42', $uri);
    }

    public function testUriByNameWithAdditionalParameters(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#details', 'rabbit');

        $uri = $router->uriByName('rabbit', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $uri);
    }

    public function testUriByNameFailsIfParameterIsMissing(): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#details', 'rabbit');

        $uri = $router->uriByName('rabbit');
    }

    public function testUriByNameFailsIfNameNotRegistered(): void
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage(
            'Route named "rabbits" doesn’t match any route.'
        );

        $router = new Router();

        $router->uriByName('rabbits');
    }

    /**
     * @return array<array{string}>
     */
    public function invalidMethodProvider(): array
    {
        return [
            ['invalid'],
            ['POSTPOST'],
            [' GET'],
        ];
    }
}
