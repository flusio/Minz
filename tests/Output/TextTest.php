<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testConstructor(): void
    {
        $output = new Text('Hello World!');

        $this->assertSame('text/plain', $output->contentType());
    }

    public function testRender(): void
    {
        $output = new Text('Hello World!');

        $this->assertSame('Hello World!', $output->render());
    }
}
