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
     * @dataProvider invalidUriProvider
     */
    public function testConstructorFailsIfInvalidUri($invalidUri)
    {
        $this->expectException(Errors\RequestError::class);
        $this->expectExceptionMessage("{$invalidUri} URI path cannot be parsed.");

        new Request('GET', $invalidUri);
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

    public function requestToPathProvider()
    {
        return [
            ['/', '/'],
            ['/rabbits', '/rabbits'],
            ['/rabbits/details.html', '/rabbits/details.html'],
            ['/rabbits?id=42', '/rabbits'],
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

    public function invalidUriProvider()
    {
        return [
            [''],
            [null],
            ['/////'],
        ];
    }
}
