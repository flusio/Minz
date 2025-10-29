<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use Minz\Request;
use Minz\Errors;
use PHPUnit\Framework\TestCase;
use AppTest\models;

class ResourceTest extends TestCase
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

    public function testLoadFromRequest(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        $request = new Request('GET', '/', parameters: [
            'id' => $id,
        ]);

        $friend = models\Friend::loadFromRequest($request);

        $this->assertNotNull($friend);
        $this->assertSame('Alix', $friend->name);
    }

    public function testLoadFromRequestWithWrongId(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        $request = new Request('GET', '/', parameters: [
            'id' => 42,
        ]);

        $friend = models\Friend::loadFromRequest($request);

        $this->assertNull($friend);
    }

    public function testRequireFromRequest(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        $request = new Request('GET', '/', parameters: [
            'id' => $id,
        ]);

        $friend = models\Friend::requireFromRequest($request);

        $this->assertSame('Alix', $friend->name);
    }

    public function testRequireFromRequestWithUnknownId(): void
    {
        $this->expectException(Errors\MissingRecordError::class);
        $this->expectExceptionMessage("AppTest\\models\\Friend model #42 does not exist");

        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        $request = new Request('GET', '/', parameters: [
            'id' => 42,
        ]);

        models\Friend::requireFromRequest($request);
    }
}
