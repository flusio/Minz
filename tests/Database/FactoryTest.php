<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use PHPUnit\Framework\TestCase;
use AppTest\factories;
use AppTest\models;

class FactoryTest extends TestCase
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

    public function testCreate(): void
    {
        $this->assertSame(0, models\Friend::count());

        $friend = factories\FriendFactory::create();

        $this->assertSame(1, models\Friend::count());
        $this->assertSame('Alix', $friend->name);
        $this->assertSame(['pet' => true], $friend->options);
    }

    public function testCreateWithSpecifyingValues(): void
    {
        $friend = factories\FriendFactory::create([
            'name' => 'Benedict',
            'address' => '42 rue du Terrier',
        ]);

        $this->assertSame(1, models\Friend::count());
        $this->assertSame('Benedict', $friend->name);
        $this->assertSame('42 rue du Terrier', $friend->address);
    }

    public function testCreateWithCallback(): void
    {
        $this->assertSame(0, models\Rabbit::count());

        $rabbit1 = factories\RabbitFactory::create();

        $this->assertSame(1, models\Rabbit::count());
        $this->assertSame(1, models\Friend::count());

        $friend = models\Friend::take();
        $this->assertNotNull($friend);

        $rabbit2 = factories\RabbitFactory::create([
            'friend_id' => $friend->id,
        ]);

        $this->assertSame(2, models\Rabbit::count());
        $this->assertSame(1, models\Friend::count());

        $this->assertSame('rabbit 1', $rabbit1->id);
        $this->assertSame($friend->id, $rabbit1->friend_id);
        $this->assertSame('rabbit 2', $rabbit2->id);
        $this->assertSame($friend->id, $rabbit2->friend_id);
    }
}
