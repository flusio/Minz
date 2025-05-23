<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    use Tests\FilesHelper;

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
        $this->assertSame('bar', $request->param('foo'));
        $this->assertSame('egg', $request->param('spam'));
        $this->assertSame('file', $request->param('some'));
        $this->assertSame('cookie', $request->cookie('a'));
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
        $this->assertSame('./cli', $request->param('bin'));
        $this->assertSame('bar', $request->param('foo'));
        $this->assertSame('qux', $request->param('foo-baz'));
        $this->assertTrue($request->paramBoolean('spam'));
    }

    public function testInitFromCliWhenNoArguments(): void
    {
        $argv = [
            './cli',
        ];

        $request = Request::initFromCli($argv);

        $this->assertSame('CLI', $request->method());
        $this->assertSame('/help', $request->path());
        $this->assertSame('./cli', $request->param('bin'));
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

    public function testHasParamWithExistingParam(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar'
        ]);

        $result = $request->hasParam('foo');

        $this->assertTrue($result);
    }

    public function testHasParamWithMissingParam(): void
    {
        $request = new Request('GET', '/', [
        ]);

        $result = $request->hasParam('foo');

        $this->assertFalse($result);
    }

    public function testParam(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar',
            'baz' => 42,
            'spam' => true,
        ]);

        $foo = $request->param('foo');
        $baz = $request->param('baz');
        $spam = $request->param('spam');

        $this->assertSame('bar', $foo);
        $this->assertSame('42', $baz);
        $this->assertSame('1', $spam);
    }

    public function testParamWithDefaultValue(): void
    {
        $request = new Request('GET', '/', []);

        $foo = $request->param('foo', 'bar');

        $this->assertSame('bar', $foo);
    }

    public function testParamBoolean(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'true'
        ]);

        $foo = $request->paramBoolean('foo');

        $this->assertTrue($foo);
    }

    public function testParamBooleanWithDefaultValue(): void
    {
        $request = new Request('GET', '/', []);

        $foo = $request->paramBoolean('foo', true);

        $this->assertTrue($foo);
    }

    public function testParamInteger(): void
    {
        $request = new Request('GET', '/', [
            'foo' => '42'
        ]);

        $foo = $request->paramInteger('foo');

        $this->assertSame(42, $foo);
    }

    public function testParamIntegerWithDefaultValue(): void
    {
        $request = new Request('GET', '/', []);

        $foo = $request->paramInteger('foo', 42);

        $this->assertSame(42, $foo);
    }

    public function testParamDatetime(): void
    {
        $request = new Request('GET', '/', [
            'foo' => '2024-03-07T16:00'
        ]);

        $foo = $request->paramDatetime('foo');

        $this->assertInstanceOf(\DateTimeImmutable::class, $foo);
        $this->assertSame('1709827200', $foo->format('U'));
    }

    public function testParamDatetimeWithCustomFormat(): void
    {
        $request = new Request('GET', '/', [
            'foo' => '2024-03-07'
        ]);

        $foo = $request->paramDatetime('foo', format: 'Y-m-d');

        $this->assertInstanceOf(\DateTimeImmutable::class, $foo);
        $this->assertSame('2024-03-07', $foo->format('Y-m-d'));
    }

    public function testParamDatetimeWithDefaultValue(): void
    {
        $request = new Request('GET', '/', [
        ]);
        $default_value = new \DateTimeImmutable('2024-03-07');

        $foo = $request->paramDatetime('foo', default: $default_value);

        $this->assertInstanceOf(\DateTimeImmutable::class, $foo);
        $this->assertSame('2024-03-07', $foo->format('Y-m-d'));
    }

    public function testParamArray(): void
    {
        $request = new Request('GET', '/', [
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $request->paramArray('foo');

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamArrayWithDefaultValue(): void
    {
        $request = new Request('GET', '/', []);

        $foo = $request->paramArray('foo', ['spam' => 'egg']);

        $this->assertSame(['spam' => 'egg'], $foo);
    }

    public function testParamArrayWithDefaultValueMergesValues(): void
    {
        $request = new Request('GET', '/', [
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $request->paramArray('foo', ['spam' => 'egg']);

        $this->assertSame([
            'spam' => 'egg',
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamArrayWithNonArrayValue(): void
    {
        // here, we set foo as a simple string (i.e. bar)
        $request = new Request('GET', '/', [
            'foo' => 'bar',
        ]);

        $foo = $request->paramArray('foo');

        // but paramArray is always returning an array
        $this->assertSame(['bar'], $foo);
    }

    public function testParamJson(): void
    {
        $request = new Request('GET', '/', [
            'foo' => '{"bar": "baz"}',
        ]);

        $foo = $request->paramJson('foo');

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamJsonWithDefaultValue(): void
    {
        $request = new Request('GET', '/', []);

        $foo = $request->paramJson('foo', ['bar' => 'baz']);

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamJsonWithNonArrayJson(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'null',
        ]);

        $foo = $request->paramJson('foo');

        $this->assertSame([null], $foo);
    }

    public function testParamJsonNonStringValue(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 42,
        ]);

        $foo = $request->paramJson('foo');

        $this->assertNull($foo);
    }

    public function testParamJsonWithInvalidJson(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar',
        ]);

        $foo = $request->paramJson('foo');

        $this->assertNull($foo);
    }

    public function testParamFile(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $request = new Request('GET', '/', [
            'foo' => [
                'tmp_name' => $tmp_filepath,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);

        /** @var File $foo */
        $foo = $request->paramFile('foo');

        $this->assertSame($tmp_filepath, $foo->filepath);
        $this->assertNull($foo->error);
    }

    public function testParamFileReturnsNullIfFileInvalid(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar',
        ]);

        $foo = $request->paramFile('foo');

        $this->assertNull($foo);
    }

    public function testParamFileReturnsNullIfParamIsMissing(): void
    {
        $request = new Request('GET', '/', []);

        $foo = $request->paramFile('foo');

        $this->assertNull($foo);
    }

    public function testSetParam(): void
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar'
        ]);

        $request->setParam('foo', 'baz');

        $foo = $request->param('foo');
        $this->assertSame('baz', $foo);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isAcceptingProvider')]
    public function testIsAccepting(string $header, string $media, bool $expected): void
    {
        $request = new Request('GET', '/', [], [
            'HTTP_ACCEPT' => $header,
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

    public function testHeader(): void
    {
        $request = new Request('GET', '/', [], [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ]);

        $protocol = $request->header('SERVER_PROTOCOL');

        $this->assertSame('HTTP/1.1', $protocol);
    }

    public function testHeaderWithDefaultValue(): void
    {
        $request = new Request('GET', '/', [], []);

        $protocol = $request->header('SERVER_PROTOCOL', 'foo');

        $this->assertSame('foo', $protocol);
    }

    public function testCookie(): void
    {
        $request = new Request('GET', '/', [], [
            'COOKIE' => [
                'foo' => 'bar',
            ],
        ]);

        $foo = $request->cookie('foo');

        $this->assertSame('bar', $foo);
    }

    public function testCookieWithDefaultValue(): void
    {
        $request = new Request('GET', '/', [], []);

        $foo = $request->cookie('foo', 'baz');

        $this->assertSame('baz', $foo);
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
