<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

class TimeTest extends \PHPUnit\Framework\TestCase
{
    public function testNow(): void
    {
        $now = new \DateTime('now');

        $result = Time::now();

        $this->assertGreaterThanOrEqual($now, $result);
    }

    public function testNowReturnsFreezedTime(): void
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::now();

        $this->assertEquals($freezed_datetime, $result);
    }

    public function testRelative(): void
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::relative('next day');

        $this->assertEquals('2021-01-21', $result->format('Y-m-d'));
    }

    public function testFromNow(): void
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::fromNow(1, 'day');

        $this->assertEquals('2021-01-21', $result->format('Y-m-d'));
    }

    public function testAgo(): void
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::ago(1, 'day');

        $this->assertEquals('2021-01-19', $result->format('Y-m-d'));
    }

    public function testSleepWithFreezedTime(): void
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::sleep(10);

        $now = \Minz\Time::now();
        $this->assertTrue($result);
        $this->assertEquals(10, $now->getTimestamp() - $freezed_datetime->getTimestamp());
    }
}
