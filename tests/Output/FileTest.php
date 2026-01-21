<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use PHPUnit\Framework\TestCase;
use Minz\Errors;

class FileTest extends TestCase
{
    public function testConstructor(): void
    {
        $output = new File(\Minz\Configuration::$data_path . '/empty.pdf');

        $this->assertSame('application/pdf', $output->contentType());
    }

    public function testConstructorFailsIfFileDoesntExist(): void
    {
        $this->expectException(Errors\OutputError::class);
        $this->expectExceptionMessage(
            'data/missing.pdf file cannot be found.'
        );

        new File(\Minz\Configuration::$data_path . '/missing.pdf');
    }

    public function testConstructorFailsIfExtensionIsNotSupported(): void
    {
        $this->expectException(Errors\OutputError::class);
        $this->expectExceptionMessage(
            'html is not a supported file extension.'
        );

        new File(\Minz\Configuration::$data_path . '/unsupported.html');
    }

    public function testRender(): void
    {
        $output = new File(\Minz\Configuration::$data_path . '/empty.pdf');

        $this->assertNotEmpty($output->render());
    }
}
