<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class ParameterBagTest extends TestCase
{
    use Tests\FilesHelper;

    public function testHasWithExistingParam(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar'
        ]);

        $result = $bag->has('foo');

        $this->assertTrue($result);
    }

    public function testHasWithMissingParam(): void
    {
        $bag = new ParameterBag([]);

        $result = $bag->has('foo');

        $this->assertFalse($result);
    }

    public function testHasWithCaseInsensitive(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar'
        ], case_sensitive: false);

        $result = $bag->has('FOO');

        $this->assertTrue($result);
    }

    public function testGet(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar',
            'baz' => 42,
            'spam' => true,
        ]);

        $foo = $bag->get('foo');
        $baz = $bag->get('baz');
        $spam = $bag->get('spam');

        $this->assertSame('bar', $foo);
        $this->assertSame(42, $baz);
        $this->assertSame(true, $spam);
    }

    public function testGetWithMissingValue(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->get('foo');

        $this->assertNull($foo);
    }

    public function testGetWithCaseInsensitive(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar',
        ], case_sensitive: false);

        $foo = $bag->get('FOO');

        $this->assertSame('bar', $foo);
    }

    public function testGetString(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar',
            'baz' => 42,
            'spam' => true,
        ]);

        $foo = $bag->getString('foo');
        $baz = $bag->getString('baz');
        $spam = $bag->getString('spam');

        $this->assertSame('bar', $foo);
        $this->assertSame('42', $baz);
        $this->assertSame('1', $spam);
    }

    public function testGetStringWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->getString('foo', 'bar');

        $this->assertSame('bar', $foo);
    }

    public function testGetBoolean(): void
    {
        $bag = new ParameterBag([
            'foo' => 'true'
        ]);

        $foo = $bag->getBoolean('foo');

        $this->assertTrue($foo);
    }

    public function testGetBooleanWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->getBoolean('foo', true);

        $this->assertTrue($foo);
    }

    public function testGetInteger(): void
    {
        $bag = new ParameterBag([
            'foo' => '42'
        ]);

        $foo = $bag->getInteger('foo');

        $this->assertSame(42, $foo);
    }

    public function testGetIntegerWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->getInteger('foo', 42);

        $this->assertSame(42, $foo);
    }

    public function testGetDatetime(): void
    {
        $bag = new ParameterBag([
            'foo' => '2024-03-07T16:00'
        ]);

        $foo = $bag->getDatetime('foo');

        $this->assertInstanceOf(\DateTimeImmutable::class, $foo);
        $this->assertSame('1709827200', $foo->format('U'));
    }

    public function testGetDatetimeWithCustomFormat(): void
    {
        $bag = new ParameterBag([
            'foo' => '2024-03-07'
        ]);

        $foo = $bag->getDatetime('foo', format: 'Y-m-d');

        $this->assertInstanceOf(\DateTimeImmutable::class, $foo);
        $this->assertSame('2024-03-07', $foo->format('Y-m-d'));
    }

    public function testGetDatetimeWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);
        $default_value = new \DateTimeImmutable('2024-03-07');

        $foo = $bag->getDatetime('foo', default: $default_value);

        $this->assertInstanceOf(\DateTimeImmutable::class, $foo);
        $this->assertSame('2024-03-07', $foo->format('Y-m-d'));
    }

    public function testGetArray(): void
    {
        $bag = new ParameterBag([
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $bag->getArray('foo');

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testGetArrayWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->getArray('foo', ['spam' => 'egg']);

        $this->assertSame(['spam' => 'egg'], $foo);
    }

    public function testGetArrayWithDefaultValueMergesValues(): void
    {
        $bag = new ParameterBag([
            'foo' => ['bar' => 'baz'],
        ]);

        $foo = $bag->getArray('foo', ['spam' => 'egg']);

        $this->assertSame([
            'spam' => 'egg',
            'bar' => 'baz',
        ], $foo);
    }

    public function testGetArrayWithNonArrayValue(): void
    {
        // here, we set foo as a simple string (i.e. bar)
        $bag = new ParameterBag([
            'foo' => 'bar',
        ]);

        $foo = $bag->getArray('foo');

        // but getArray is always returning an array
        $this->assertSame(['bar'], $foo);
    }

    public function testGetJson(): void
    {
        $bag = new ParameterBag([
            'foo' => '{"bar": "baz"}',
        ]);

        $foo = $bag->getJson('foo');

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testGetJsonWithDefaultValue(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->getJson('foo', ['bar' => 'baz']);

        $this->assertSame([
            'bar' => 'baz',
        ], $foo);
    }

    public function testGetJsonWithNonArrayJson(): void
    {
        $bag = new ParameterBag([
            'foo' => 'null',
        ]);

        $foo = $bag->getJson('foo');

        $this->assertSame([null], $foo);
    }

    public function testGetJsonNonStringValue(): void
    {
        $bag = new ParameterBag([
            'foo' => 42,
        ]);

        $foo = $bag->getJson('foo');

        $this->assertNull($foo);
    }

    public function testGetJsonWithInvalidJson(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar',
        ]);

        $foo = $bag->getJson('foo');

        $this->assertNull($foo);
    }

    public function testGetFile(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $bag = new ParameterBag([
            'foo' => [
                'tmp_name' => $tmp_filepath,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);

        $foo = $bag->getFile('foo');

        $this->assertNotNull($foo);
        $this->assertSame($tmp_filepath, $foo->filepath);
        $this->assertNull($foo->error);
    }

    public function testGetFileReturnsNullIfFileInvalid(): void
    {
        $bag = new ParameterBag([
            'foo' => 'bar',
        ]);

        $foo = $bag->getFile('foo');

        $this->assertNull($foo);
    }

    public function testGetFileReturnsNullIfParamIsMissing(): void
    {
        $bag = new ParameterBag([]);

        $foo = $bag->getFile('foo');

        $this->assertNull($foo);
    }
}
