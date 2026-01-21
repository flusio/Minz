<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;
use AppTest\jobs;

class JobTest extends TestCase
{
    public function setUp(): void
    {
        assert(\Minz\Configuration::$database !== null);

        $database_type = \Minz\Configuration::$database['type'];
        $sql_schema_path = \Minz\Configuration::$app_path . "/schema.{$database_type}.sql";
        $sql_schema = file_get_contents($sql_schema_path);

        assert($sql_schema !== false);

        $database = \Minz\Database::get();
        $database->exec($sql_schema);
    }

    public function tearDown(): void
    {
        \Minz\Database::reset();
    }

    public function testFindNextJobIdReturnsJobWithOldestPerformAt(): void
    {
        $job1 = new jobs\DummyJob();
        $job1->performLater(\Minz\Time::ago(5, 'minutes'));
        $job2 = new jobs\DummyJob();
        $job2->performLater(\Minz\Time::ago(10, 'minutes'));

        $next_job_id = Job::findNextJobId('all');

        $this->assertSame($job2->id, $next_job_id);
    }

    public function testFindNextJobIdReturnsJobsFromTheGivenQueue(): void
    {
        $job1 = new jobs\DummyJob();
        $job1->queue = 'foo';
        $job1->performLater(\Minz\Time::ago(5, 'minutes'));
        $job2 = new jobs\DummyJob();
        $job2->queue = 'bar';
        $job2->performLater(\Minz\Time::ago(10, 'minutes'));

        $next_job_id = Job::findNextJobId('foo');

        $this->assertSame($job1->id, $next_job_id);
    }

    public function testFindNextJobIdReturnsJobPerformedAsap(): void
    {
        $job = new jobs\DummyJob();
        $job->performAsap();

        $next_job_id = Job::findNextJobId('all');

        $this->assertSame($job->id, $next_job_id);
    }

    public function testFindNextJobIdDoesNotReturnJobsInFuture(): void
    {
        $job = new jobs\DummyJob();
        $job->performLater(\Minz\Time::fromNow(5, 'minutes'));

        $next_job_id = Job::findNextJobId('all');

        $this->assertNull($next_job_id);
    }

    public function testFindNextJobIdDoesNotReturnJobsWithMoreThan25Attempts(): void
    {
        $job = new jobs\DummyJob();
        $job->number_attempts = 26;
        $job->performAsap();

        $next_job_id = Job::findNextJobId('all');

        $this->assertNull($next_job_id);
    }

    public function testFindNextJobIdReturnsJobsWithMoreThan25AttemptsIfFrequencyIsSet(): void
    {
        $job = new jobs\DummyJob();
        $job->number_attempts = 26;
        $job->frequency = '+1 hour';
        $job->performAsap();

        $next_job_id = Job::findNextJobId('all');

        $this->assertSame($job->id, $next_job_id);
    }

    public function testFindNextJobIdDoesNotReturnLockedJobs(): void
    {
        $job = new jobs\DummyJob();
        $job->locked_at = \Minz\Time::now();
        $job->performAsap();

        $next_job_id = Job::findNextJobId('all');

        $this->assertNull($next_job_id);
    }

    public function testFindNextJobIdReturnsLockedJobsAfterAnHour(): void
    {
        $job = new jobs\DummyJob();
        $job->locked_at = \Minz\Time::ago(1, 'hour');
        $job->performAsap();

        $next_job_id = Job::findNextJobId('all');

        $this->assertSame($job->id, $next_job_id);
    }

    public function testRescheduleUpdatesPerformAtInTheFuture(): void
    {
        $job = new jobs\DummyJob();
        $job->frequency = '+1 hour';
        $job->perform_at = \Minz\Time::ago(5, 'hours');
        $expected_perform_at = $job->perform_at->modify('+6 hours');

        $job->reschedule();

        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $job->perform_at->getTimestamp()
        );
    }

    public function testRescheduleUpdatesPerformAtTakingCareOfDst(): void
    {
        assert(\Minz\Configuration::$database !== null);
        $database_type = \Minz\Configuration::$database['type'];

        $initial_timezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
        // In France, timezone offset was +01 on 25th March and +02 on 26th
        // March. This is because of Daylight Saving Time (DST).
        $now = new \DateTimeImmutable('2023-03-25 04:00:00+01:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->perform_at = $now;
        $job->frequency = '+1 day';

        // We want to make sure that the job is reloaded from the database as
        // it will lose the notion of timezone.
        $job->save();
        $job = jobs\DummyJob::find($job->id);

        $this->assertNotNull($job);
        if ($database_type === 'sqlite') {
            $this->assertSame(
                '2023-03-25 04:00:00+01:00',
                $job->perform_at->format(Database\Column::DATETIME_FORMAT)
            );
        } else {
            // PostgreSQL converts the datetime to UTC, so we lose the offset!
            $this->assertSame(
                '2023-03-25 03:00:00+00:00',
                $job->perform_at->format(Database\Column::DATETIME_FORMAT)
            );
        }

        $job->reschedule();

        \Minz\Time::unfreeze();
        date_default_timezone_set($initial_timezone);

        // If DST wasn't considered, the time would still be 04:00:00+01:00 or
        // 03:00:00+00:00
        $this->assertSame(
            '2023-03-26 04:00:00+02:00',
            $job->perform_at->format(Database\Column::DATETIME_FORMAT)
        );
    }

    public function testRescheduleDoesNothingIfFrequencyIsNotSet(): void
    {
        $job = new jobs\DummyJob();
        $job->perform_at = \Minz\Time::ago(5, 'hours');
        $expected_perform_at = $job->perform_at;

        $job->reschedule();

        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $job->perform_at->getTimestamp()
        );
    }

    public function testRescheduleFailsIfFrequencyGoesBackward(): void
    {
        $this->expectException(Errors\LogicException::class);
        $this->expectExceptionMessage('AppTest\\jobs\\DummyJob has a frequency going backward');

        $job = new jobs\DummyJob();
        $job->frequency = '-1 hour';
        $job->perform_at = \Minz\Time::ago(5, 'hours');

        $job->reschedule();
    }

    public function testFailSetsLastErrorAndReschedulesTheJob(): void
    {
        \Minz\Time::freeze();
        $now = \Minz\Time::now();

        $job = new jobs\DummyJob();
        $job->perform_at = $now;
        $error = 'It’s failing!';
        $expected_perform_at = \Minz\Time::fromNow(5, 'seconds');

        $job->fail($error);

        \Minz\Time::unfreeze();

        $this->assertSame($error, $job->last_error);
        $this->assertNotNull($job->failed_at);
        $this->assertSame(
            $now->getTimestamp(),
            $job->failed_at->getTimestamp()
        );
        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $job->perform_at->getTimestamp()
        );
    }

    public function testFailReschedulesTheJobExponentiallyBasedOnTheNumberOfAttempts(): void
    {
        \Minz\Time::freeze();
        $now = \Minz\Time::now();

        $job = new jobs\DummyJob();
        $job->perform_at = $now;
        $job->number_attempts = 10;
        $error = 'It’s failing!';
        $expected_perform_at = \Minz\Time::fromNow(10005, 'seconds');

        $job->fail($error);

        \Minz\Time::unfreeze();

        $this->assertSame($error, $job->last_error);
        $this->assertNotNull($job->failed_at);
        $this->assertSame(
            $now->getTimestamp(),
            $job->failed_at->getTimestamp()
        );
        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $job->perform_at->getTimestamp()
        );
    }

    public function testFailReschedulesTheJobUsingFrequencyIfSet(): void
    {
        \Minz\Time::freeze();
        $now = \Minz\Time::now();

        $job = new jobs\DummyJob();
        $job->perform_at = $now;
        $job->number_attempts = 10;
        $job->frequency = '+42 seconds';
        $error = 'It’s failing!';
        $expected_perform_at = \Minz\Time::fromNow(42, 'seconds');

        $job->fail($error);

        \Minz\Time::unfreeze();

        $this->assertSame($error, $job->last_error);
        $this->assertNotNull($job->failed_at);
        $this->assertSame(
            $now->getTimestamp(),
            $job->failed_at->getTimestamp()
        );
        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $job->perform_at->getTimestamp()
        );
    }
}
