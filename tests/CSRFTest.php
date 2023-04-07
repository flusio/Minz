<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class CSRFTest extends TestCase
{
    public function tearDown(): void
    {
        session_unset();
    }

    public function testGenerateToken(): void
    {
        $csrf = new CSRF();

        $token = $csrf->generateToken();

        $this->assertSame($_SESSION['CSRF'], $token);
    }

    public function testGenerateTokenTwiceDoesntChange(): void
    {
        $csrf = new CSRF();

        $first_token = $csrf->generateToken();
        $second_token = $csrf->generateToken();

        $this->assertSame($first_token, $second_token);
    }

    public function testGenerateTokenWhenTokenIsSetToEmpty(): void
    {
        $_SESSION['CSRF'] = '';
        $csrf = new CSRF();

        $token = $csrf->generateToken();

        $this->assertNotEmpty($_SESSION['CSRF']);
        $this->assertSame($_SESSION['CSRF'], $token);
    }

    public function testGenerateIsAliasOfGenerateToken(): void
    {
        $token = CSRF::generate();

        $this->assertSame($_SESSION['CSRF'], $token);
    }

    public function testValidateToken(): void
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $valid = $csrf->validateToken($token);

        $this->assertTrue($valid);
    }

    public function testValidateTokenWithEmptyToken(): void
    {
        $csrf = new CSRF();
        $_SESSION['CSRF'] = '';

        $valid = $csrf->validateToken('');

        $this->assertFalse($valid);
    }

    public function testValidateTokenWhenValidatingTwice(): void
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $csrf->validateToken($token);
        $valid = $csrf->validateToken($token);

        $this->assertTrue($valid);
    }

    public function testValidateTokenWhenTokenIsWrong(): void
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $valid = $csrf->validateToken('not the token');

        $this->assertFalse($valid);
    }

    public function testValidateTokenWhenValidatingAfterFirstWrongTry(): void
    {
        $csrf = new CSRF();
        $token = $csrf->generateToken();

        $csrf->validateToken('not the token');
        $valid = $csrf->validateToken($token);

        $this->assertTrue($valid);
    }

    public function testValidateIsAliasOfValidateToken(): void
    {
        $token = CSRF::generate();

        $valid = CSRF::validate($token);

        $this->assertTrue($valid);
    }

    public function testSetToken(): void
    {
        $csrf = new CSRF();
        $token = 'foo';

        $csrf->setToken($token);

        $valid = $csrf->validateToken($token);
        $this->assertTrue($valid);
    }

    public function testSetIsAliasOfSetToken(): void
    {
        $token = 'foo';

        CSRF::set($token);

        $valid = CSRF::validate($token);
        $this->assertTrue($valid);
    }

    public function testResetToken(): void
    {
        $csrf = new CSRF();
        $initial_token = $csrf->generateToken();

        $new_token = $csrf->resetToken();

        $this->assertNotSame($initial_token, $new_token);
        $valid = $csrf->validateToken($initial_token);
        $this->assertFalse($valid);
        $valid = $csrf->validateToken($new_token);
        $this->assertTrue($valid);
    }
}
