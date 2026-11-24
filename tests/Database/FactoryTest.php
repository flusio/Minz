<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
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
        factories\BurrowFactory::$hooks_calls = [];
    }

    public function testBuild(): void
    {
        $friend = factories\FriendFactory::build([
            'address' => '42 rue du Terrier',
        ]);

        $this->assertSame(0, models\Friend::count());
        $this->assertFalse($friend->isPersisted());
        $this->assertSame('Alix', $friend->name);
        $this->assertSame('42 rue du Terrier', $friend->address);
        $this->assertSame(['pet' => true], $friend->options);
    }

    public function testBuildDoesNotCallTheModelConstructor(): void
    {
        $burrow = factories\BurrowFactory::build();

        $this->assertSame(1, $burrow->depth);
    }

    public function testBuildWithOverriddenInstantiate(): void
    {
        $burrow = factories\ConstructedBurrowFactory::build();

        $this->assertSame(3, $burrow->depth);
    }

    public function testBuildWithInheritance(): void
    {
        $pet = factories\PetFactory::build();
        $this->assertInstanceOf(models\Cat::class, $pet);

        $pet = factories\PetFactory::build(['type' => 'dog']);
        $this->assertInstanceOf(models\Dog::class, $pet);
    }

    public function testBuildFailsWithUnknownProperty(): void
    {
        $this->expectException(\Minz\Errors\DatabaseModelError::class);
        $this->expectExceptionMessage('AppTest\models\Friend doesn\'t define a not_a_property property');

        factories\FriendFactory::build([
            'not_a_property' => 'foo',
        ]);
    }

    public function testBuildCallsTheBuildHooks(): void
    {
        factories\BurrowFactory::build();

        $this->assertSame(['beforeBuild', 'afterBuild'], factories\BurrowFactory::$hooks_calls);
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

    public function testCreateWithInheritance(): void
    {
        $pet = factories\PetFactory::create([
            'type' => 'dog',
        ]);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertSame(1, models\Dog::count());
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

    public function testCreateCallsBeforeBuildWithAllTheValues(): void
    {
        $friend = factories\FriendFactory::create([
            'name' => 'Benedict',
        ]);

        $rabbit = factories\RabbitFactory::create([
            'friend_id' => $friend->id,
        ]);

        $this->assertSame('Rabbit of Benedict', $rabbit->name);
    }

    public function testCreateCallsBeforeBuildWithTheDefaultValues(): void
    {
        $rabbit = factories\RabbitFactory::create();

        $this->assertSame('Rabbit of Alix', $rabbit->name);
    }

    public function testCreateCallsAfterCreateWithTheCreatedModel(): void
    {
        $rabbit = factories\RabbitFactory::create([
            'name' => 'Albert',
        ]);

        $friend = models\Friend::require($rabbit->friend_id);
        $this->assertSame('Burrow of Albert', $friend->address);
    }

    public function testCreateCallsAllTheHooks(): void
    {
        factories\BurrowFactory::create();

        $this->assertSame(
            ['beforeBuild', 'afterBuild', 'beforeCreate', 'afterCreate'],
            factories\BurrowFactory::$hooks_calls,
        );
    }
}
