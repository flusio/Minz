<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type ConfigurationUrl from Configuration
 *
 * @phpstan-import-type RequestMethod from Request
 */
class UrlTest extends TestCase
{
    /**
     * @var ConfigurationUrl
     */
    private array $default_url_options;

    public function setUp(): void
    {
        $this->default_url_options = Configuration::$url_options;
    }

    public function tearDown(): void
    {
        Configuration::$url_options = $this->default_url_options;
        Engine::reset();
    }

    public function testFor(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::for('rabbits#list');

        $this->assertSame('/rabbits', $url);
    }

    public function testForWithName(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list', 'rabbits');
        Engine::init($router);

        $url = Url::for('rabbits');

        $this->assertSame('/rabbits', $url);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('methodProvider')]
    public function testForWithAnyVia(string $method): void
    {
        $router = new Router();
        // @phpstan-ignore-next-line
        $router->addRoute($method, '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::for('rabbits#list');

        $this->assertSame('/rabbits', $url);
    }

    public function testForWithParams(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#list');
        Engine::init($router);

        $url = Url::for('rabbits#list', ['id' => 42]);

        $this->assertSame('/rabbits/42', $url);
    }

    public function testForWithAdditionalParams(): void
    {
        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::for('rabbits#list', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $url);
    }

    public function testForWithUrlOptionsPath(): void
    {
        Configuration::$url_options['path'] = '/path';

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::for('rabbits#list');

        $this->assertSame('/path/rabbits', $url);

        Configuration::$url_options['path'] = '';
    }

    public function testForFailsIfRouterIsNotRegistered(): void
    {
        $this->expectException(Errors\UrlError::class);
        $this->expectExceptionMessage(
            'You must init the Engine with a Router before calling this method.'
        );

        Url::for('rabbits#list');
    }

    public function testForFailsIfActionPointerDoesNotExist(): void
    {
        $this->expectException(Errors\UrlError::class);
        $this->expectExceptionMessage(
            'rabbits#list action pointer or route name does not exist in the router.'
        );

        $router = new Router();
        Engine::init($router);

        Url::for('rabbits#list');
    }

    public function testForWithParamsFailsIfParameterIsMissing(): void
    {
        $this->expectException(Errors\UrlError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('GET', '/rabbits/:id', 'rabbits#list');
        Engine::init($router);

        Url::for('rabbits#list');
    }

    public function testAbsoluteFor(): void
    {
        Configuration::$url_options['host'] = 'my-domain.com';

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame('http://my-domain.com/rabbits', $url);
    }

    /**
     * @param 'http'|'https' $protocol
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('defaultPortProvider')]
    public function testAbsoluteForWithDefaultPort(string $protocol, int $port): void
    {
        Configuration::$url_options['host'] = 'my-domain.com';
        Configuration::$url_options['port'] = $port;
        Configuration::$url_options['protocol'] = $protocol;

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame($protocol . '://my-domain.com/rabbits', $url);
    }

    public function testAbsoluteForWithCustomPort(): void
    {
        Configuration::$url_options['host'] = 'my-domain.com';
        Configuration::$url_options['port'] = 8080;

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame('http://my-domain.com:8080/rabbits', $url);
    }

    public function testAbsoluteForWithPath(): void
    {
        Configuration::$url_options['host'] = 'my-domain.com';
        Configuration::$url_options['path'] = '/path';

        $router = new Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        Engine::init($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame('http://my-domain.com/path/rabbits', $url);
    }

    /**
     * @return array<array{RequestMethod}>
     */
    public static function methodProvider(): array
    {
        return [
            ['GET'],
            ['POST'],
            ['PATCH'],
            ['PUT'],
            ['DELETE'],
            ['CLI'],
        ];
    }

    /**
     * @return array<array{'http'|'https', int}>
     */
    public static function defaultPortProvider(): array
    {
        return [
            ['http', 80],
            ['https', 443],
        ];
    }
}
