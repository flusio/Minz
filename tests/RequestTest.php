<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * @dataProvider invalidMethodProvider
     */
    public function testConstructorFailsIfInvalidMethod($invalidMethod)
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage(
            "{$invalidMethod} method is invalid (get, post, patch, put, delete, cli)."
        );

        new Request($invalidMethod, '/');
    }

    /**
     * @dataProvider emptyUriProvider
     */
    public function testConstructorFailsIfUriIsEmpty($empty_uri)
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('URI cannot be empty.');

        new Request('GET', $empty_uri);
    }

    public function testConstructorFailsIfUriIsInvalid()
    {
        $invalid_uri = 'http:///';
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage("{$invalid_uri} URI path cannot be parsed.");

        new Request('GET', $invalid_uri);
    }

    public function testConstructorFailsIfUriPathDoesntStartWithSlash()
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('no_slash URI path must start with a slash.');

        new Request('GET', 'no_slash');
    }

    public function testConstructorFailsIfParametersIsntArray()
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage('Parameters are not in an array.');

        new Request('GET', '/', 'a parameter ?');
    }

    public function testMethod()
    {
        $request = new Request('GET', '/');

        $method = $request->method();

        $this->assertSame('get', $method);
    }

    /**
     * @dataProvider requestToPathProvider
     */
    public function testPath($requestUri, $expectedPath)
    {
        $request = new Request('GET', $requestUri);

        $path = $request->path();

        $this->assertSame($expectedPath, $path);
    }

    public function testParam()
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar'
        ]);

        $foo = $request->param('foo');

        $this->assertSame('bar', $foo);
    }

    public function testParamWithDefaultValue()
    {
        $request = new Request('GET', '/', []);

        $foo = $request->param('foo', 'bar');

        $this->assertSame('bar', $foo);
    }

    public function testParamWithArrayDefaultValue()
    {
        $request = new Request('GET', '/', [
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $request->param('foo', ['spam' => 'egg']);

        $this->assertSame([
            'spam' => 'egg',
            'bar' => 'baz',
        ], $foo);
    }

    public function testParamWithUnexpectedNonArrayValue()
    {
        // here, we set foo as a simple string (i.e. bar)
        $request = new Request('GET', '/', [
            'foo' => 'bar',
        ]);

        // but we set the default value to an array
        $foo = $request->param('foo', ['spam' => 'egg']);

        // because the two types don't match, we consider returning the correct
        // type is more important than returning the real value
        $this->assertSame(['spam' => 'egg'], $foo);
    }

    public function testSetParam()
    {
        $request = new Request('GET', '/', [
            'foo' => 'bar'
        ]);

        $request->setParam('foo', 'baz');

        $foo = $request->param('foo');
        $this->assertSame('baz', $foo);
    }

    public function testHeader()
    {
        $request = new Request('GET', '/', [], [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
        ]);

        $protocol = $request->header('SERVER_PROTOCOL');

        $this->assertSame('HTTP/1.1', $protocol);
    }

    public function testHeaderWithDefaultValue()
    {
        $request = new Request('GET', '/', [], []);

        $protocol = $request->header('SERVER_PROTOCOL', 'foo');

        $this->assertSame('foo', $protocol);
    }

    public function testCookie()
    {
        $request = new Request('GET', '/', [], [
            'COOKIE' => [
                'foo' => 'bar',
            ],
        ]);

        $foo = $request->cookie('foo');

        $this->assertSame('bar', $foo);
    }

    public function testCookieWithDefaultValue()
    {
        $request = new Request('GET', '/', [], []);

        $foo = $request->cookie('foo', 'baz');

        $this->assertSame('baz', $foo);
    }

    public function requestToPathProvider()
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

    public function invalidMethodProvider()
    {
        return [
            [''],
            [null],
            ['invalid'],
            ['postpost'],
            [' get'],
        ];
    }

    public function emptyUriProvider()
    {
        return [
            [''],
            [null],
        ];
    }
}
