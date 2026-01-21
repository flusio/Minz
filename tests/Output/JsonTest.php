<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use PHPUnit\Framework\TestCase;

class JsonTest extends TestCase
{
    public function testConstructor(): void
    {
        $output = new Json(['foo' => 'bar']);

        $this->assertSame('application/json', $output->contentType());
    }

    public function testRender(): void
    {
        $output = new Json(['foo' => 'bar']);

        $this->assertSame('{"foo":"bar"}', $output->render());
    }
}
