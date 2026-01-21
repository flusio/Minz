<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Form;

use PHPUnit\Framework\TestCase;

class CsrfTokenTest extends TestCase
{
    public function testValidate(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get();

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate($token);

        $this->assertTrue($result);
    }

    public function testValidateWithName(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get(name: 'foo');

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate($token, name: 'foo');

        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseIfSessionIdsAreDifferent(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get();

        $csrf = new CsrfToken(session_id: '21');
        $result = $csrf->validate($token);

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseIfNamesAreDifferent(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get(name: 'foo');

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate($token, name: 'bar');

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseIfTokenHmacIsInvalid(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get();
        $token_parts = explode('.', $token);

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate('notthehmac.' . $token_parts[1]);

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseIfTokenRandomValueIsInvalid(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get();
        $token_parts = explode('.', $token);

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate($token_parts[0] . '.nottherandomvalue');

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseIfHmacAndRandomValueAreEmpty(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get();

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate('.');

        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseIfTokenIsEmpty(): void
    {
        $csrf = new CsrfToken(session_id: '42');
        $token = $csrf->get();

        $csrf = new CsrfToken(session_id: '42');
        $result = $csrf->validate('');

        $this->assertFalse($result);
    }
}
