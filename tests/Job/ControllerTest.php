<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Job;

use AppTest\jobs;
use Minz\Database;
use Minz\Job;
use Minz\Request;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    use \Minz\Tests\ResponseAsserts;

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
        \Minz\Time::unfreeze();
    }

    public function testIndex(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job1 = new jobs\DummyJob();
        $job1->performLater(\Minz\Time::ago(5, 'minutes'));
        $job1->lock();
        $job2 = new jobs\DummyJob();
        $job2->performLater(\Minz\Time::ago(10, 'minutes'));
        $job2->fail('oops');
        $job3 = new jobs\DummyJob();
        $job3->frequency = '+1 hour';
        $job3->performLater(\Minz\Time::fromNow(10, 'minutes'));

        $request = new Request('CLI', '/');
        $controller = new Controller();
        $response = $controller->index($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            job#1 AppTest\jobs\DummyJob at 2023-04-20 11:55:00+00:00, 0 attempts (locked)
            job#2 AppTest\jobs\DummyJob at 2023-04-20 12:00:05+00:00, 0 attempts (failed)
            job#3 AppTest\jobs\DummyJob scheduled each +1 hour, next at 2023-04-20 12:10:00+00:00
            TEXT);
    }

    public function testShow(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->frequency = '+1 hour';
        $job->performAsap('foo', 42, true);
        $job->fail('oops');
        $job->lock();

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->show($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            id: 1
            name: AppTest\jobs\DummyJob
            args: 'foo', 42, true
            perform: 2023-04-20 13:00:00+00:00
            attempts: 0
            queue: default
            repeat: +1 hour
            created: 2023-04-20 12:00:00+00:00
            updated: 2023-04-20 12:00:00+00:00
            locked: 2023-04-20 12:00:00+00:00
            failed: 2023-04-20 12:00:00+00:00
            oops
            TEXT);
    }

    public function testShowWhenJobDoesNotExist(): void
    {
        $request = new Request('CLI', '/', [
            'id' => 42,
        ]);
        $controller = new Controller();
        $response = $controller->show($request);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, <<<TEXT
            Job 42 does not exist.
            TEXT);
    }

    public function testUnlock(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->performLater(\Minz\Time::ago(5, 'minutes'));
        $job->lock();

        $this->assertTrue($job->isLocked());

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->unlock($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            Job 1 lock has been released.
            TEXT);
        $job = $job->reload();
        $this->assertFalse($job->isLocked());
    }

    public function testUnlockWhenJobIsNotLocked(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->performLater(\Minz\Time::ago(5, 'minutes'));

        $this->assertFalse($job->isLocked());

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->unlock($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            Job 1 was not locked.
            TEXT);
        $job = $job->reload();
        $this->assertFalse($job->isLocked());
    }

    public function testUnlockWhenJobDoesNotExist(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $request = new Request('CLI', '/', [
            'id' => 42,
        ]);
        $controller = new Controller();
        $response = $controller->unlock($request);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, <<<TEXT
            Job 42 does not exist.
            TEXT);
    }

    public function testUnfail(): void
    {
        $job = new jobs\DummyJob();
        $job->performAsap();
        $job->fail('oops');

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->unfail($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            Job 1 is no longer failing, was:
            oops
            TEXT);
        $job = $job->reload();
        $this->assertSame('', $job->last_error);
        $this->assertNull($job->failed_at);
    }

    public function testUnfailWhenJobHasNotFailed(): void
    {
        $job = new jobs\DummyJob();
        $job->performAsap();

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->unfail($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            Job 1 has not failed.
            TEXT);
    }

    public function testUnfailWhenJobDoesNotExist(): void
    {
        $request = new Request('CLI', '/', [
            'id' => 42,
        ]);
        $controller = new Controller();
        $response = $controller->unfail($request);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, <<<TEXT
            Job 42 does not exist.
            TEXT);
    }

    public function testRun(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->performLater(\Minz\Time::ago(5, 'minutes'));

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->run($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, <<<TEXT
            job#1 (AppTest\jobs\DummyJob): done
            TEXT);
        $this->assertFalse(Job::exists($job->id));
    }

    public function testRunWhenFrequencyIsSet(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->frequency = '+1 hour';
        $job->performLater(\Minz\Time::ago(5, 'minutes'));

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->run($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, <<<TEXT
            job#1 (AppTest\jobs\DummyJob): done
            TEXT);
        $job = $job->reload();
        $this->assertSame(
            '2023-04-20 12:55:00+00:00',
            $job->perform_at->format(Database\Column::DATETIME_FORMAT)
        );
    }

    public function testRunWhenTheJobDoesNotExist(): void
    {
        $request = new Request('CLI', '/', [
            'id' => 42,
        ]);
        $controller = new Controller();
        $response = $controller->run($request);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'Job 42 does not exist.');
    }

    public function testRunWhenJobIsLocked(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->performLater(\Minz\Time::ago(5, 'minutes'));
        $job->lock();

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->run($request);

        $this->assertResponseCode($response, 500);
        $this->assertTrue(Job::exists($job->id));
    }

    public function testRunWhenJobFails(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        // The perform method takes a "should_fail" parameter. It raises an
        // exception if it's set to true.
        $job->performLater(\Minz\Time::ago(5, 'minutes'), true);

        $request = new Request('CLI', '/', [
            'id' => $job->id,
        ]);
        $controller = new Controller();
        $response = $controller->run($request);

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, <<<TEXT
            job#1 (AppTest\jobs\DummyJob): failed
            TEXT);
        $job = $job->reload();
        $this->assertSame(
            '2023-04-20 12:00:06+00:00',
            $job->perform_at->format(Database\Column::DATETIME_FORMAT)
        );
        $this->assertSame(
            '2023-04-20 12:00:00+00:00',
            $job->failed_at?->format(Database\Column::DATETIME_FORMAT)
        );
        $this->assertStringContainsString('oops', $job->last_error);
    }

    public function testWatch(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job1 = new jobs\DummyJob();
        $job1->performLater(\Minz\Time::ago(10, 'minutes'));
        $job2 = new jobs\DummyJob();
        $job2->performLater(\Minz\Time::ago(5, 'minutes'));

        $request = new Request('CLI', '/', [
            'stop-after' => 1,
        ]);
        $controller = new Controller();
        $response = $controller->watch($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (all) started]');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'job#1 (AppTest\jobs\DummyJob): done');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (all) stopped]');
    }

    public function testWatchWhenQueueIsGiven(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job1 = new jobs\DummyJob();
        $job1->queue = 'foo';
        $job1->performLater(\Minz\Time::ago(5, 'minutes'));

        $job2 = new jobs\DummyJob();
        $job2->queue = 'bar';
        $job2->performLater(\Minz\Time::ago(10, 'minutes'));

        $request = new Request('CLI', '/', [
            'queue' => 'foo',
            'stop-after' => 1,
        ]);
        $controller = new Controller();
        $response = $controller->watch($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (foo) started]');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'job#1 (AppTest\jobs\DummyJob): done');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (foo) stopped]');
    }

    public function testWatchWhenQueueWithNumberIsGiven(): void
    {
        $now = new \DateTimeImmutable('2023-04-20 12:00');
        \Minz\Time::freeze($now);

        $job = new jobs\DummyJob();
        $job->queue = 'foo';
        $job->performLater(\Minz\Time::ago(5, 'minutes'));

        $request = new Request('CLI', '/', [
            // watch() should ignore the number at the end, so we can identify
            // different watchers dedicated to the same queue.
            'queue' => 'foo42',
            'stop-after' => 1,
        ]);
        $controller = new Controller();
        $response = $controller->watch($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (foo) started]');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'job#1 (AppTest\jobs\DummyJob): done');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '[Job worker (foo) stopped]');
    }
}
