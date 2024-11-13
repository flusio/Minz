<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    public function testSet(): void
    {
        Flash::set('foo', 'bar');

        $this->assertTrue(Flash::has('foo'));
        $this->assertSame('bar', Flash::get('foo'));
        $this->assertTrue(Flash::has('foo'));
        $this->assertSame('bar', Flash::pop('foo'));
        $this->assertFalse(Flash::has('foo'));
    }

    public function testSetStoresInSession(): void
    {
        Flash::set('foo', 'bar');

        $this->assertTrue(isset($_SESSION['_flash']));
        $this->assertTrue(isset($_SESSION['_flash']['foo']));

        $this->assertSame('bar', Flash::pop('foo'));

        $this->assertTrue(isset($_SESSION['_flash']));
        $this->assertFalse(isset($_SESSION['_flash']['foo']));
    }

    public function testPopReturnsDefaultValue(): void
    {
        $this->assertFalse(Flash::has('foo'));
        $this->assertSame('bar', Flash::pop('foo', 'bar'));
    }

    public function testGetReturnsDefaultValue(): void
    {
        $this->assertFalse(Flash::has('foo'));
        $this->assertSame('bar', Flash::get('foo', 'bar'));
    }
}
