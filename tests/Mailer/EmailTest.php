<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Mailer;

use Minz\Mailer\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testSetBody(): void
    {
        $email = new Email();

        $email->setBody(
            'rabbits/items.phtml',
            'rabbits/items.txt',
            [
                'rabbits' => ['Pompom'],
            ]
        );

        $this->assertStringContainsString('Pompom', $email->Body);
        $this->assertStringContainsString('Pompom', $email->AltBody);
        $this->assertSame('utf-8', $email->CharSet);
        $this->assertSame('text/html', $email->ContentType);
    }
}
