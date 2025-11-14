<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

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

        $router->addRoute('GET', '/rabbits', 'rabbits#list', 'rabbits');

        $routes = $router->routes();
        $this->assertSame([
            'rabbits' => [
                'name' => 'rabbits',
                'method' => 'GET',
                'pattern' => '/rabbits',
                'action' => 'rabbits#list',
            ],
        ], $routes);
    }

    public function testAddRouteAcceptsCliMethod(): void
    {
        $router = new Router();

        $router->addRoute('CLI', '/rabbits', 'rabbits#list', 'rabbits');

        $routes = $router->routes();
        $this->assertSame([
            'rabbits' => [
                'name' => 'rabbits',
                'method' => 'CLI',
                'pattern' => '/rabbits',
                'action' => 'rabbits#list',
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
        $this->expectExceptionMessage('Route "action" must contain a hash (#).');

        $router = new Router();

        $router->addRoute('GET', '/rabbits', 'rabbits_list');
    }

    public function testAddRouteFailsIfToContainsMoreThanOneHash(): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            'Route "action" must contain at most one hash (#).'
        );

        $router = new Router();

        $router->addRoute('GET', '/rabbits', 'rabbits#list#more');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidMethodProvider')]
    public function testAddRouteFailsIfMethodIsInvalid(string $invalidMethod): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidMethod} method is invalid (GET, POST, PATCH, PUT, DELETE, OPTIONS, CLI)."
        );

        $router = new Router();

        // @phpstan-ignore-next-line
        $router->addRoute($invalidMethod, '/rabbits', 'rabbits#list');
    }

    public function testMatch(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        list($route, $parameters) = $router->match('GET', '/rabbits');

        $this->assertSame('rabbits#list', $route['name']);
        $this->assertSame([], $parameters);
    }

    public function testMatchWithTrailingSlashes(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        list($route, $parameters) = $router->match('GET', '/rabbits//');

        $this->assertSame('rabbits#list', $route['name']);
        $this->assertSame([], $parameters);
    }

    public function testMatchWithParam(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#get');

        list($route, $parameters) = $router->match('GET', '/rabbits/42');

        $this->assertSame('rabbits#get', $route['name']);
        $this->assertSame(['id' => '42'], $parameters);
    }

    public function testMatchWithWildcard(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/assets/*', 'assets#serve');

        list(
            $route,
            $parameters
        ) = $router->match('GET', '/assets/path/to/an/asset.css');

        $this->assertSame('assets#serve', $route['name']);
        $this->assertSame(['*' => 'path/to/an/asset.css'], $parameters);
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

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidMethodProvider')]
    public function testMatchFailsIfMethodIsInvalid(string $invalidMethod): void
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidMethod} method is invalid (GET, POST, PATCH, PUT, DELETE, OPTIONS, CLI)."
        );

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        // @phpstan-ignore-next-line
        $router->match($invalidMethod, '/rabbits');
    }

    public function testAllowedMethodsForPathWithKnownPath(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/new', 'rabbits#new');
        $router->addRoute('POST', '/rabbits/new', 'rabbits#create');

        $allowed_methods = $router->allowedMethodsForPath('/rabbits/new');

        $this->assertEquals(['GET', 'POST'], $allowed_methods);
    }

    public function testAllowedMethodsForPathWithUnknownPath(): void
    {
        $router = new Router();

        $allowed_methods = $router->allowedMethodsForPath('/rabbits/new');

        $this->assertEquals([], $allowed_methods);
    }

    public function testIsRedirectableWithSupportedPath(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/new', 'rabbits#new');

        $is_redirectable = $router->isRedirectable('/rabbits/new');

        $this->assertTrue($is_redirectable);
    }

    public function testIsRedirectableWithNotSupportedPath(): void
    {
        $router = new Router();

        $is_redirectable = $router->isRedirectable('/rabbits/new');

        $this->assertFalse($is_redirectable);
    }

    public function testIsRedirectableWithSupportedUrl(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/new', 'rabbits#new');

        $is_redirectable = $router->isRedirectable('http://localhost/rabbits/new');

        $this->assertTrue($is_redirectable);
    }

    public function testIsRedirectableWithNotSupportedUrl(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/new', 'rabbits#new');

        $is_redirectable = $router->isRedirectable('http://bad.example.com/rabbits/new');

        $this->assertFalse($is_redirectable);
    }

    public function testIsRedirectableWithQueryPart(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/new', 'rabbits#new');

        $is_redirectable = $router->isRedirectable('/rabbits/new?foo=bar');

        $this->assertTrue($is_redirectable);
    }

    public function testIsRedirectableWithFragmentPart(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/new', 'rabbits#new');

        $is_redirectable = $router->isRedirectable('/rabbits/new#foo');

        $this->assertTrue($is_redirectable);
    }

    public function testUriByName(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list', 'rabbits');

        $uri = $router->uriByName('rabbits');

        $this->assertSame('/rabbits', $uri);
    }

    public function testUriByNameWhenRouteHasNoName(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');

        $uri = $router->uriByName('rabbits#list');

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
        $this->expectExceptionMessage('Route named "rabbits" doesn’t match any route.');

        $router = new Router();

        $router->uriByName('rabbits');
    }

    /**
     * @return array<array{string}>
     */
    public static function invalidMethodProvider(): array
    {
        return [
            ['invalid'],
            ['POSTPOST'],
            [' GET'],
        ];
    }
}
