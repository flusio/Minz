<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testInitFromGlobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['REQUEST_URI'] = '/path';
        $_GET['foo'] = 'bar';
        $_POST['spam'] = 'egg';
        $_FILES['some'] = 'file';
        $_COOKIE['a'] = 'cookie';

        $request = Request::initFromGlobals();

        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['REQUEST_URI']);
        unset($_GET['foo']);
        unset($_POST['spam']);
        unset($_FILES['some']);
        unset($_COOKIE['a']);

        $this->assertSame('GET', $request->method());
        $this->assertSame('/path', $request->path());
        $this->assertSame('/path', $request->selfUri());
        $this->assertSame('bar', $request->parameters->get('foo'));
        $this->assertSame('egg', $request->parameters->get('spam'));
        $this->assertSame('file', $request->parameters->get('some'));
        $this->assertSame('cookie', $request->cookies->get('a'));
        $this->assertSame('get', $request->server->get('REQUEST_METHOD'));
        $this->assertSame('/path', $request->server->get('REQUEST_URI'));
    }

    public function testInitFromGlobalsFailsIfRequestMethodIsInvalid(): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage("The HTTP method 'UNSUPPORTED' is not supported.");

        $_SERVER['REQUEST_METHOD'] = 'unsupported';
        $_SERVER['REQUEST_URI'] = '/';

        $request = Request::initFromGlobals();
    }

    public function testInitFromGlobalsFailsIfRequestMethodIsCli(): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage("The HTTP method 'CLI' is not supported.");

        $_SERVER['REQUEST_METHOD'] = 'CLI';
        $_SERVER['REQUEST_URI'] = '/';

        $request = Request::initFromGlobals();
    }

    public function testInitFromCli(): void
    {
        $argv = [
            './cli',
            'users',
            'create',
            '--foo=bar',
            '--foo-baz=qux',
            '--spam',
        ];

        $request = Request::initFromCli($argv);

        $this->assertSame('CLI', $request->method());
        $this->assertSame('/users/create', $request->path());
        $this->assertSame('/users/create', $request->selfUri());
        $this->assertSame('./cli', $request->parameters->get('bin'));
        $this->assertSame('bar', $request->parameters->get('foo'));
        $this->assertSame('qux', $request->parameters->get('foo-baz'));
        $this->assertTrue($request->parameters->getBoolean('spam'));
    }

    public function testInitFromCliWhenNoArguments(): void
    {
        $argv = [
            './cli',
        ];

        $request = Request::initFromCli($argv);

        $this->assertSame('CLI', $request->method());
        $this->assertSame('/help', $request->path());
        $this->assertSame('./cli', $request->parameters->get('bin'));
    }

    public function testConstructorFailsIfUriIsEmpty(): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('URI cannot be empty.');

        new Request('GET', '');
    }

    public function testConstructorFailsIfUriIsInvalid(): void
    {
        $invalid_uri = 'http:///';
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage("{$invalid_uri} URI path cannot be parsed.");

        new Request('GET', $invalid_uri);
    }

    public function testConstructorFailsIfUriPathDoesntStartWithSlash(): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('no_slash URI path must start with a slash.');

        new Request('GET', 'no_slash');
    }

    public function testMethod(): void
    {
        $request = new Request('GET', '/');

        $method = $request->method();

        $this->assertSame('GET', $method);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('requestToPathProvider')]
    public function testPath(string $requestUri, string $expectedPath): void
    {
        $request = new Request('GET', $requestUri);

        $path = $request->path();

        $this->assertSame($expectedPath, $path);
    }

    public function testPathWithUrlOptionsPath(): void
    {
        $old_url_path = \Minz\Configuration::$url_options['path'];
        \Minz\Configuration::$url_options['path'] = '/minz';
        $request = new Request('GET', '/minz/rabbits');

        $path = $request->path();

        \Minz\Configuration::$url_options['path'] = $old_url_path;

        $this->assertSame('/rabbits', $path);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('requestToUriProvider')]
    public function testSelfUri(string $request_uri, string $expected_uri): void
    {
        $request = new Request('GET', $request_uri);

        $self_uri = $request->selfUri();

        $this->assertSame($expected_uri, $self_uri);
    }

    public function testIp(): void
    {
        $expected_ip = '127.0.0.1';
        $request = new Request('GET', '/', server: [
            'REMOTE_ADDR' => $expected_ip,
        ]);

        $ip = $request->ip();

        $this->assertSame($expected_ip, $ip);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isAcceptingProvider')]
    public function testIsAccepting(string $header, string $media, bool $expected): void
    {
        $request = new Request('GET', '/', [], [
            'Accept' => $header,
        ]);

        $is_accepting = $request->isAccepting($media);

        $this->assertSame($expected, $is_accepting);
    }

    public function testIsAcceptingWithNoAcceptHeader(): void
    {
        // Equivalent to */*
        $request = new Request('GET', '/', [], []);

        $is_accepting = $request->isAccepting('text/html');

        $this->assertTrue($is_accepting);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function requestToPathProvider(): array
    {
        return [
            ['/', '/'],
            ['/rabbits', '/rabbits'],
            ['//rabbits', '//rabbits'],
            ['///rabbits', '///rabbits'],
            ['/rabbits/details.html', '/rabbits/details.html'],
            ['/rabbits//details.html', '/rabbits//details.html'],
            ['/rabbits?id=42', '/rabbits'],
            ['/rabbits#hash', '/rabbits'],
            ['http://domain.com', '/'],
            ['http://domain.com/', '/'],
            ['http://domain.com/rabbits', '/rabbits'],
            ['http://domain.com//rabbits', '//rabbits'],
            ['http://domain.com/rabbits?id=42', '/rabbits'],
            ['http://domain.com/rabbits#hash', '/rabbits'],
        ];
    }

    /**
     * @return array<array{string, string}>
     */
    public static function requestToUriProvider(): array
    {
        return [
            ['/', '/'],
            ['/rabbits', '/rabbits'],
            ['//rabbits', '//rabbits'],
            ['///rabbits', '///rabbits'],
            ['/rabbits/details.html', '/rabbits/details.html'],
            ['/rabbits//details.html', '/rabbits//details.html'],
            ['/rabbits?id=42', '/rabbits?id=42'],
            ['/rabbits#hash', '/rabbits'],
            ['http://domain.com', '/'],
            ['http://domain.com/', '/'],
            ['http://domain.com/rabbits', '/rabbits'],
            ['http://domain.com//rabbits', '//rabbits'],
            ['http://domain.com/rabbits?id=42', '/rabbits?id=42'],
            ['http://domain.com/rabbits#hash', '/rabbits'],
        ];
    }

    /**
     * @return array<array{string, string, bool}>
     */
    public static function isAcceptingProvider(): array
    {
        return [
            ['text/html', 'text/html', true],
            ['text/html; q=0.2', 'text/html', true],
            ['text/plain; q=0.5, text/html', 'text/html', true],
            ['*/*', 'text/html', true],
            ['*/*', 'application/json', true],
            ['text/*', 'text/html', true],
            ['text/*', 'text/plain', true],
            ['text/*; q=0.5, text/plain', 'text/html', true],
            ['text/html', 'text/plain', false],
            ['text/*', 'application/json', false],
            ['text/*; q=0.5, text/plain', 'application/json', false],
            ['*/plain', 'text/plain', false], // this format is invalid and rejected
        ];
    }
}
