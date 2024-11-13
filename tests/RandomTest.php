<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class RandomTest extends TestCase
{
    use Tests\TimeHelper;

    public function testHex(): void
    {
        $random = Random::hex(20);

        $this->assertSame(1, preg_match('/^[0-9a-f]{20}$/', $random));
    }

    public function testHexFailsIfLengthIs0(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Length must be a positive integer');

        Random::hex(0);
    }

    public function testTimebased(): void
    {
        $datetime = new \DateTime('2023-04-08 20:30:00');

        $random = Random::timebased($datetime);

        $this->assertSame(1, preg_match('/^[0-9]{19}$/', $random));
        $this->assertSame('176264136622', substr($random, 0, 12));
    }

    public function testTimebasedUsesCurrentDateByDefault(): void
    {
        $datetime = new \DateTime('2023-04-08 20:30:00');
        self::freeze($datetime);

        $random = Random::timebased();

        $this->assertSame(1, preg_match('/^[0-9]{19}$/', $random));
        $this->assertSame('176264136622', substr($random, 0, 12));
    }
}
