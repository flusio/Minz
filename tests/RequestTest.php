<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    use Tests\FilesHelper;

    /**
     * @dataProvider invalidMethodProvider
     */
    public function testConstructorFailsIfInvalidMethod(?string $invalidMethod): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage(
            "`{$invalidMethod}` method is invalid (accepted methods: get, post, patch, put, delete, cli)."
        );

        // @phpstan-ignore-next-line
        new Request($invalidMethod, '/');
    }

    public function testConstructorFailsIfUriIsEmpty(): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('URI cannot be empty.');

        new Request('get', '');
    }

    public function testConstructorFailsIfUriIsInvalid(): void
    {
        $invalid_uri = 'http:///';
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage("{$invalid_uri} URI path cannot be parsed.");

        new Request('get', $invalid_uri);
    }

    public function testConstructorFailsIfUriPathDoesntStartWithSlash(): void
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('no_slash URI path must start with a slash.');

        new Request('get', 'no_slash');
    }

    public function testMethod(): void
    {
        $request = new Request('get', '/');

        $method = $request->method();

        $this->assertSame('get', $method);
    }

    /**
     * @dataProvider requestToPathProvider
     */
    public function testPath(string $requestUri, string $expectedPath): void
    {
        $request = new Request('get', $requestUri);

        $path = $request->path();

        $this->assertSame($expectedPath, $path);
    }

    public function testPathWithUrlOptionsPath(): void
    {
        $old_url_path = \Minz\Configuration::$url_options['path'];
        \Minz\Configuration::$url_options['path'] = '/minz';
        $request = new Request('get', '/minz/rabbits');

        $path = $request->path();

        \Minz\Configuration::$url_options['path'] = $old_url_path;

        $this->assertSame('/rabbits', $path);
    }

    public function testParam(): void
    {
        $request = new Request('get', '/', [
            'foo' => 'bar'
        ]);

        $foo = $request->param('foo');

        $this->assertSame('bar', $foo);
    }

    public function testParamWithDefaultValue(): void
    {
        $request = new Request('get', '/', []);

        $foo = $request->param('foo', 'bar');

        $this->assertSame('bar', $foo);
    }

    public function testParamBoolean(): void
    {
        $request = new Request('get', '/', [
            'foo' => 'true'
        ]);

        $foo = $request->paramBoolean('foo');

        $this->assertTrue($foo);
    }

    public function testParamBooleanWithDefaultValue(): void
    {
        $request = new Request('get', '/', []);

        $foo = $request->paramBoolean('foo', true);

        $this->assertTrue($foo);
    }

    public function testParamInteger(): void
    {
        $request = new Request('get', '/', [
            'foo' => '42'
        ]);

        $foo = $request->paramInteger('foo');

        $this->assertSame(42, $foo);
    }

    public function testParamIntegerWithDefaultValue(): void
    {
        $request = new Request('get', '/', []);

        $foo = $request->paramInteger('foo', 42);

        $this->assertSame(42, $foo);
    }

    public function testParamArray(): void
    {
        $request = new Request('get', '/', [
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $request->paramArray('foo');

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamArrayWithDefaultValue(): void
    {
        $request = new Request('get', '/', []);

        $foo = $request->paramArray('foo', ['spam' => 'egg']);

        $this->assertSame(['spam' => 'egg'], $foo);
    }

    public function testParamArrayWithDefaultValueMergesValues(): void
    {
        $request = new Request('get', '/', [
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $request->paramArray('foo', ['spam' => 'egg']);

        $this->assertSame([
            'spam' => 'egg',
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamWithNonArrayValue(): void
    {
        // here, we set foo as a simple string (i.e. bar)
        $request = new Request('get', '/', [
            'foo' => 'bar',
        ]);

        $foo = $request->paramArray('foo');

        // but paramArray is always returning an array
        $this->assertSame(['bar'], $foo);
    }

    public function testParamFile(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $request = new Request('get', '/', [
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
        $request = new Request('get', '/', [
            'foo' => 'bar',
        ]);

        $foo = $request->paramFile('foo');

        $this->assertNull($foo);
    }

    public function testParamFileReturnsNullIfParamIsMissing(): void
    {
        $request = new Request('get', '/', []);

        $foo = $request->paramFile('foo');

        $this->assertNull($foo);
    }

    public function testSetParam(): void
    {
        $request = new Request('get', '/', [
            'foo' => 'bar'
        ]);

        $request->setParam('foo', 'baz');

        $foo = $request->param('foo');
        $this->assertSame('baz', $foo);
    }

    /**
     * @dataProvider isAcceptingProvider
     */
    public function testIsAccepting(string $header, string $media, bool $expected): void
    {
        $request = new Request('get', '/', [], [
            'HTTP_ACCEPT' => $header,
        ]);

        $is_accepting = $request->isAccepting($media);

        $this->assertSame($expected, $is_accepting);
    }

    public function testIsAcceptingWithNoAcceptHeader(): void
    {
        // Equivalent to */*
        $request = new Request('get', '/', [], []);

        $is_accepting = $request->isAccepting('text/html');

        $this->assertTrue($is_accepting);
    }

    public function testHeader(): void
    {
        $request = new Request('get', '/', [], [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ]);

        $protocol = $request->header('SERVER_PROTOCOL');

        $this->assertSame('HTTP/1.1', $protocol);
    }

    public function testHeaderWithDefaultValue(): void
    {
        $request = new Request('get', '/', [], []);

        $protocol = $request->header('SERVER_PROTOCOL', 'foo');

        $this->assertSame('foo', $protocol);
    }

    public function testCookie(): void
    {
        $request = new Request('get', '/', [], [
            'COOKIE' => [
                'foo' => 'bar',
            ],
        ]);

        $foo = $request->cookie('foo');

        $this->assertSame('bar', $foo);
    }

    public function testCookieWithDefaultValue(): void
    {
        $request = new Request('get', '/', [], []);

        $foo = $request->cookie('foo', 'baz');

        $this->assertSame('baz', $foo);
    }

    /**
     * @return array<array{string, string}>
     */
    public function requestToPathProvider(): array
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
     * @return array<array{?string}>
     */
    public function invalidMethodProvider(): array
    {
        return [
            [''],
            ['invalid'],
            ['postpost'],
            [' get'],
        ];
    }

    /**
     * @return array<array{string, string, bool}>
     */
    public function isAcceptingProvider(): array
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
