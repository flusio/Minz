<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Migration;

use Minz\Request;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    use \Minz\Tests\ResponseAsserts;

    private static Controller $controller;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadController(): void
    {
        self::$controller = new \AppTest\Migrations();
    }

    public function tearDown(): void
    {
        $migrations_version_path = self::$controller->migrationsVersionPath();
        @unlink($migrations_version_path);

        \Minz\Database::drop();
    }

    public function testSetupWhenFirstTime(): void
    {
        $migrations_version_path = self::$controller->migrationsVersionPath();

        $this->assertFalse(file_exists($migrations_version_path));

        $request = new Request('CLI', '/');
        $response = self::$controller->setup($request);

        $this->assertNotNull($response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'The system has been initialized.');
        $this->assertTrue(file_exists($migrations_version_path));
    }

    public function testSetupWhenCallingTwice(): void
    {
        $request = new Request('CLI', '/');
        $response = self::$controller->setup($request);
        $response->current(); // action is not called if generator is not executed

        $response = self::$controller->setup($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Your system is already up to date.');
    }

    public function testSetupWithMigrations(): void
    {
        $migrations_version_path = self::$controller->migrationsVersionPath();
        touch($migrations_version_path);
        \Minz\Database::create();

        $request = new Request('CLI', '/');
        $response = self::$controller->setup($request);

        $this->assertResponseCode($response, 200);
    }

    public function testSetupWithSeed(): void
    {
        $migrations_version_path = self::$controller->migrationsVersionPath();

        $request = new Request('CLI', '/', [
            'seed' => true,
        ]);
        $response = self::$controller->setup($request);

        $this->assertNotNull($response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'The system has been initialized.');
        $this->assertTrue(file_exists($migrations_version_path));

        $response->next();
        $this->assertNotNull($response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Seeds loaded.');
    }

    public function testRollback(): void
    {
        // Setup the application first.
        $request = new Request('CLI', '/');
        $response = self::$controller->setup($request);
        $response->current(); // action is not called if generator is not executed

        $request = new Request('CLI', '/');
        $response = self::$controller->rollback($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            Migration201912220002Bar: OK
            TEXT);
    }

    public function testIndex(): void
    {
        $request = new Request('CLI', '/');
        $response = self::$controller->index($request);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
            Migration201912220001Foo (not applied)
            Migration201912220002Bar (not applied)
            TEXT);
    }

    public function testCreateGeneratesANewMigrationAndRendersCorrectly(): void
    {
        $now = \Minz\Time::now();
        \Minz\Time::freeze($now);

        $migrations_path = self::$controller->migrationsPath();
        $name = 'CreateUsers';
        $expected_version = "Migration{$now->format('Ymd')}0001{$name}";
        $migration_path = "{$migrations_path}/{$expected_version}.php";

        $request = new Request('CLI', '/', [
            'name' => $name,
        ]);
        $response = self::$controller->create($request);

        $migrator = new Migrator($migrations_path);
        $last_version = $migrator->lastVersion();

        \Minz\Time::unfreeze();
        @unlink($migration_path);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "The migration {$expected_version} has been created.");
        $this->assertSame($last_version, $expected_version);
    }

    public function testCreateAdaptsVersionNumberWhenCalledSeveralTimes(): void
    {
        $now = \Minz\Time::now();
        \Minz\Time::freeze($now);

        $migrations_path = self::$controller->migrationsPath();
        $name = 'CreateUsers';
        $expected_version_1 = "Migration{$now->format('Ymd')}0001{$name}";
        $expected_version_2 = "Migration{$now->format('Ymd')}0002{$name}";
        $migration_path_1 = "{$migrations_path}/{$expected_version_1}.php";
        $migration_path_2 = "{$migrations_path}/{$expected_version_2}.php";

        $request = new Request('CLI', '/', [
            'name' => $name,
        ]);
        self::$controller->create($request);
        $response = self::$controller->create($request);

        $migrator = new Migrator($migrations_path);
        $last_version = $migrator->lastVersion();

        \Minz\Time::unfreeze();

        @unlink($migration_path_1);
        @unlink($migration_path_2);
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "The migration {$expected_version_2} has been created.");
        $this->assertSame($last_version, $expected_version_2);
    }

    public function testCreateFailsIfNameIsEmpty(): void
    {
        $name = '';

        $request = new Request('CLI', '/', [
            'name' => $name,
        ]);
        $response = self::$controller->create($request);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The migration name cannot be empty.');
    }
}
