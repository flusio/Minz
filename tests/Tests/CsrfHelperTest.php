<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use Minz\Errors;
use Minz\Form\CsrfToken;
use PHPUnit\Framework\TestCase;

class CsrfHelperTest extends TestCase
{
    use CsrfHelper;

    public function testCsrfToken(): void
    {
        $form = new \AppTest\forms\Rabbit();
        $csrf = new CsrfToken($form->csrfSessionId());

        $csrf_token = $this->csrfToken(\AppTest\forms\Rabbit::class);

        $result = $csrf->validate($csrf_token, $form->csrfTokenName());
        $this->assertTrue($result);
    }

    public function testCsrfTokenWithClassNotUsingCsrfTrait(): void
    {
        $this->expectException(Errors\LogicException::class);
        $this->expectExceptionMessage("The given class must use the trait 'Minz\\Form\\Csrf'");

        $this->csrfToken(\AppTest\forms\FormWithCheck::class);
    }
}
