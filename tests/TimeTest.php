<?php

namespace Minz;

class TimeTest extends \PHPUnit\Framework\TestCase
{
    public function testNow()
    {
        $now = new \DateTime('now');

        $result = Time::now();

        $this->assertGreaterThanOrEqual($now, $result);
    }

    public function testNowReturnsFreezedTime()
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::now();

        $this->assertEquals($freezed_datetime, $result);
    }

    public function testRelative()
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::relative('next day');

        $this->assertEquals('2021-01-21', $result->format('Y-m-d'));
    }

    public function testFromNow()
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::fromNow(1, 'day');

        $this->assertEquals('2021-01-21', $result->format('Y-m-d'));
    }

    public function testAgo()
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::ago(1, 'day');

        $this->assertEquals('2021-01-19', $result->format('Y-m-d'));
    }

    public function testSleepWithFreezedTime()
    {
        $freezed_datetime = new \DateTime('2021-01-20');
        Time::freeze($freezed_datetime);

        $result = Time::sleep(10);

        $now = \Minz\Time::now();
        $this->assertTrue($result);
        $this->assertEquals(10, $now->getTimestamp() - $freezed_datetime->getTimestamp());
    }
}
