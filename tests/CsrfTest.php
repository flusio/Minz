<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    public function tearDown(): void
    {
        session_unset();
    }

    public function testGenerate(): void
    {
        $token = Csrf::generate();

        $this->assertSame($_SESSION['_csrf'], $token);
    }

    public function testGenerateTwiceDoesntChange(): void
    {
        $first_token = Csrf::generate();
        $second_token = Csrf::generate();

        $this->assertSame($first_token, $second_token);
    }

    public function testGenerateWhenTokenIsSetToEmpty(): void
    {
        $_SESSION['_csrf'] = '';

        $token = Csrf::generate();

        $this->assertTrue(isset($_SESSION['_csrf']));
        $this->assertSame($_SESSION['_csrf'], $token);
    }

    public function testValidate(): void
    {
        $token = Csrf::generate();

        $valid = Csrf::validate($token);

        $this->assertTrue($valid);
    }

    public function testValidateWithEmptyToken(): void
    {
        $_SESSION['_csrf'] = '';

        $valid = Csrf::validate('');

        $this->assertFalse($valid);
    }

    public function testValidateWhenValidatingTwice(): void
    {
        $token = Csrf::generate();

        Csrf::validate($token);
        $valid = Csrf::validate($token);

        $this->assertTrue($valid);
    }

    public function testValidateWhenTokenIsWrong(): void
    {
        $token = Csrf::generate();

        $valid = Csrf::validate('not the token');

        $this->assertFalse($valid);
    }

    public function testValidateWhenValidatingAfterFirstWrongTry(): void
    {
        $token = Csrf::generate();

        Csrf::validate('not the token');
        $valid = Csrf::validate($token);

        $this->assertTrue($valid);
    }

    public function testSet(): void
    {
        $token = 'foo';

        Csrf::set($token);

        $valid = Csrf::validate($token);
        $this->assertTrue($valid);
    }

    public function testReset(): void
    {
        $initial_token = Csrf::generate();

        $new_token = Csrf::reset();

        $this->assertNotSame($initial_token, $new_token);
        $valid = Csrf::validate($initial_token);
        $this->assertFalse($valid);
        $valid = Csrf::validate($new_token);
        $this->assertTrue($valid);
    }
}
