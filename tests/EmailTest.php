<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testSanitize(): void
    {
        $email = " Charlie@Example.com \t";

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('charlie@example.com', $sanitized_email);
    }

    public function testSanitizeWithInternationalizedAddress(): void
    {
        $email = 'Δοκιμή@Παράδειγμα.δοκιμή';

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('δοκιμή@xn--hxajbheg2az3al.xn--jxalpdlp', $sanitized_email);
    }

    public function testSanitizeWithInvalidAddress(): void
    {
        $email = 'Not-an-email.com ';

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('not-an-email.com', $sanitized_email);
    }

    public function testSanitizeWithEmptyAddress(): void
    {
        $email = '';

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('', $sanitized_email);
    }

    public function testValidate(): void
    {
        $email = 'charlie@example.com';

        $result = Email::validate($email);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidAddress(): void
    {
        $email = 'example.com';

        $result = Email::validate($email);

        $this->assertFalse($result);
    }

    public function testValidateWithEmptyAddress(): void
    {
        $email = '';

        $result = Email::validate($email);

        $this->assertFalse($result);
    }

    public function testValidateWithInternationalizedAddress(): void
    {
        $email = 'charlie@Παράδειγμα.δοκιμή';

        $result = Email::validate($email);

        $this->assertFalse($result);
    }

    public function testValidateWithSanitizedInternationalizedAddress(): void
    {
        // it always fails if local part is internationalized
        $email = Email::sanitize('charlie@Παράδειγμα.δοκιμή');

        $result = Email::validate($email);

        $this->assertTrue($result);
    }

    public function testValidateWithLooseMode(): void
    {
        $email = 'Δοκιμή@Παράδειγμα.δοκιμή';

        $result = Email::validate($email, 'loose');

        $this->assertTrue($result);
    }
}
