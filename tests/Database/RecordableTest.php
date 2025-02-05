<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use Minz\Errors;
use PHPUnit\Framework\TestCase;
use AppTest\models;

class RecordableTest extends TestCase
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

        $id = models\Friend::create([
            'name' => 'Alix',
        ]);

        $this->assertSame(1, models\Friend::count());
        $this->assertSame(1, $id);
    }

    public function testCreateWithSpecifyingId(): void
    {
        $this->assertSame(0, models\Rabbit::count());

        $friend_id = models\Friend::create([
            'name' => 'Alix',
        ]);
        $rabbit_id = \Minz\Random::hex(16);

        $id = models\Rabbit::create([
            'id' => $rabbit_id,
            'name' => 'Benedict',
            'friend_id' => $friend_id,
        ]);

        $this->assertSame(1, models\Rabbit::count());
        $this->assertSame($rabbit_id, $id);
    }

    public function testCreateWithJson(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
            'options' => ['pet' => true],
        ]);

        /** @var models\Friend $friend */
        $friend = models\Friend::find($id);
        $this->assertEquals(['pet' => true], $friend->options);
    }

    public function testCreateWithDateTime(): void
    {
        $datetime = \Minz\Time::now();
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
            'created_at' => $datetime,
        ]);

        $this->assertSame(1, models\Friend::count());
        $friend = models\Friend::find($id);
        $this->assertNotNull($friend);
        $this->assertTrue($friend->created_at instanceof \DateTimeImmutable);
        $this->assertEquals($datetime->getTimestamp(), $friend->created_at->getTimestamp());
    }

    public function testCreateWithBoolean(): void
    {
        $datetime = \Minz\Time::now();
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
            'is_kind' => false,
        ]);

        $this->assertSame(1, models\Friend::count());
        /** @var models\Friend $friend */
        $friend = models\Friend::find($id);
        $this->assertFalse($friend->is_kind);
    }

    public function testCreateFailsWhenUnknownPropertyIsSpecified(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage("AppTest\\models\\Friend doesn't define a foo property");

        models\Friend::create([
            'foo' => 'bar',
        ]);
    }

    public function testListAll(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $friends = models\Friend::listAll();

        $this->assertSame(2, count($friends));
        $this->assertSame('Alix', $friends[0]->name);
        $this->assertTrue($friends[0]->isPersisted());
        $this->assertSame('Benedict', $friends[1]->name);
        $this->assertTrue($friends[1]->isPersisted());
    }

    public function testListAllWithOrderBy(): void
    {
        models\Friend::create([
            'name' => 'Benedict',
        ]);
        models\Friend::create([
            'name' => 'Alix',
        ]);

        $friends = models\Friend::listAll('name ASC');

        $this->assertSame(2, count($friends));
        $this->assertSame('Alix', $friends[0]->name);
        $this->assertSame('Benedict', $friends[1]->name);
    }

    public function testListBy(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $friends = models\Friend::listBy([
            'name' => 'Alix',
        ]);

        $this->assertSame(1, count($friends));
        $this->assertSame('Alix', $friends[0]->name);
        $this->assertTrue($friends[0]->isPersisted());
    }

    public function testListByWithArray(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);
        models\Friend::create([
            'name' => 'Charlie',
        ]);

        $friends = models\Friend::listBy([
            'name' => ['Alix', 'Charlie'],
        ]);

        $this->assertSame(2, count($friends));
        $this->assertSame('Alix', $friends[0]->name);
        $this->assertTrue($friends[0]->isPersisted());
        $this->assertSame('Charlie', $friends[1]->name);
        $this->assertTrue($friends[1]->isPersisted());
    }

    public function testListByWithNull(): void
    {
        models\Friend::create([
            'name' => 'Alix',
            'address' => null,
        ]);
        models\Friend::create([
            'name' => 'Benedict',
            'address' => '42 rue du Terrier',
        ]);

        $friends = models\Friend::listBy([
            'address' => null,
        ]);

        $this->assertSame(1, count($friends));
        $this->assertSame('Alix', $friends[0]->name);
        $this->assertTrue($friends[0]->isPersisted());
    }

    public function testListByWithFalse(): void
    {
        models\Friend::create([
            'name' => 'Alix',
            'is_kind' => true,
        ]);
        models\Friend::create([
            'name' => 'Benedict',
            'is_kind' => false,
        ]);

        $friends = models\Friend::listBy([
            'is_kind' => false,
        ]);

        $this->assertSame(1, count($friends));
        $this->assertSame('Benedict', $friends[0]->name);
        $this->assertTrue($friends[0]->isPersisted());
    }

    public function testFind(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $friend = models\Friend::find($id);

        $this->assertNotNull($friend);
        $this->assertSame('Alix', $friend->name);
        $this->assertTrue($friend->isPersisted());
    }

    public function testFindWithWrongId(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $friend = models\Friend::find(42);

        $this->assertNull($friend);
    }

    public function testFindBy(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $friend = models\Friend::findBy([
            'name' => 'Alix',
        ]);

        $this->assertNotNull($friend);
        $this->assertSame('Alix', $friend->name);
        $this->assertTrue($friend->isPersisted());
    }

    public function testFindByWithNoMatchingCriteria(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $friend = models\Friend::findBy([
            'name' => 'Charlie',
        ]);

        $this->assertNull($friend);
    }

    public function testFindOrCreateBy(): void
    {
        $this->assertSame(0, models\Friend::count());

        $friend = models\Friend::findOrCreateBy([
            'name' => 'Alix',
        ], [
            'address' => '42 rue du Terrier',
        ]);

        $this->assertSame(1, models\Friend::count());
        $this->assertSame('Alix', $friend->name);
        $this->assertSame('42 rue du Terrier', $friend->address);
    }

    public function testFindOrCreateByWithExistingModel(): void
    {
        models\Friend::create([
            'name' => 'Alix',
            'address' => '24 rue du Clapier',
        ]);

        $friend = models\Friend::findOrCreateBy([
            'name' => 'Alix',
        ], [
            'address' => '42 rue du Terrier',
        ]);

        $this->assertSame(1, models\Friend::count());
        $this->assertSame('Alix', $friend->name);
        $this->assertSame('24 rue du Clapier', $friend->address);
    }

    public function testTake(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);

        $friend = models\Friend::take(0);

        $this->assertNotNull($friend);
        $this->assertSame($id, $friend->id);
    }

    public function testTakeWithNOutOfBound(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);

        $friend = models\Friend::take(1);

        $this->assertNull($friend);
    }

    public function testExists(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);

        $exists = models\Friend::exists($id);

        $this->assertTrue($exists);
    }

    public function testExistsWithNonExistingId(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);

        $exists = models\Friend::exists(42);

        $this->assertFalse($exists);
    }

    public function testExistsBy(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);

        $exists = models\Friend::existsBy([
            'name' => 'Alix',
        ]);

        $this->assertTrue($exists);
    }

    public function testExistsByWithNonExistingName(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);

        $exists = models\Friend::existsBy([
            'name' => 'Benedict',
        ]);

        $this->assertFalse($exists);
    }

    public function testCount(): void
    {
        $this->assertSame(0, models\Friend::count());

        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $count = models\Friend::count();

        $this->assertSame(2, $count);
    }

    public function testCountBy(): void
    {
        $this->assertSame(0, models\Friend::count());

        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $count = models\Friend::countBy([
            'name' => 'Alix',
        ]);

        $this->assertSame(1, $count);
    }

    public function testUpdate(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);

        $result = models\Friend::update($id, [
            'name' => 'Benedict',
        ]);

        $this->assertTrue($result);
        $friend = models\Friend::find($id);
        $this->assertNotNull($friend);
        $this->assertSame('Benedict', $friend->name);
        $this->assertTrue($friend->isPersisted());
    }

    public function testDelete(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);

        $this->assertTrue(models\Friend::exists($id));

        $result = models\Friend::delete($id);

        $this->assertTrue($result);
        $this->assertFalse(models\Friend::exists($id));
    }

    public function testDeleteBy(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $result = models\Friend::deleteBy([
            'name' => 'Alix',
        ]);

        $this->assertTrue($result);
        $this->assertSame(1, models\Friend::count());
        $this->assertFalse(models\Friend::exists($id));
    }

    public function testDeleteByWithEmptyCriteria(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $result = models\Friend::deleteBy([
        ]);

        $this->assertTrue($result);
        $this->assertSame(2, models\Friend::count());
    }

    public function testDeleteAll(): void
    {
        models\Friend::create([
            'name' => 'Alix',
        ]);
        models\Friend::create([
            'name' => 'Benedict',
        ]);

        $result = models\Friend::deleteAll();

        $this->assertSame(2, $result);
        $this->assertSame(0, models\Friend::count());
    }

    public function testSaveCreatesNewModel(): void
    {
        $datetime = new \DateTimeImmutable('2023-04-18');
        \Minz\Time::freeze($datetime);

        $friend = new models\Friend();
        $friend->name = 'Alix';

        $this->assertSame(0, models\Friend::count());
        $this->assertFalse($friend->isPersisted());

        $friend->save();

        \Minz\Time::unfreeze();

        $this->assertSame(1, models\Friend::count());
        $this->assertTrue($friend->isPersisted());
        $this->assertNotNull($friend->created_at);
        $this->assertSame(
            $datetime->getTimestamp(),
            $friend->created_at->getTimestamp(),
        );
        $this->assertNotNull($friend->updated_at);
        $this->assertSame(
            $datetime->getTimestamp(),
            $friend->updated_at->getTimestamp(),
        );
    }

    public function testSaveUpdatesExistingModel(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
            'created_at' => new \DateTimeImmutable('2023-04-01'),
            'updated_at' => new \DateTimeImmutable('2023-04-01'),
        ]);

        $datetime = new \DateTimeImmutable('2023-04-18');
        \Minz\Time::freeze($datetime);

        /** @var models\Friend $friend */
        $friend = models\Friend::find($id);
        $friend->name = 'Benedict';

        $friend->save();

        \Minz\Time::unfreeze();

        $this->assertTrue(models\Friend::existsBy([
            'name' => 'Benedict',
        ]));
        $this->assertNotNull($friend->updated_at);
        $this->assertSame(
            $datetime->getTimestamp(),
            $friend->updated_at->getTimestamp(),
        );
    }

    public function testReloadLoadsTheModelFromDatabase(): void
    {
        $friend = new models\Friend();
        $friend->name = 'Alix';
        $friend->save();
        $friend->name = 'Benedict';

        $friend = $friend->reload();

        $this->assertSame('Alix', $friend->name);
    }

    public function testReloadFailsIfTheModelIsNotPersisted(): void
    {
        $this->expectException(Errors\LogicException::class);
        $this->expectExceptionMessage("AppTest\\models\\Friend model is not persisted");

        $friend = new models\Friend();

        $friend->reload();
    }

    public function testReloadFailsIfTheModelHasBeenDeleted(): void
    {
        $this->expectException(Errors\RuntimeException::class);
        $this->expectExceptionMessage("AppTest\\models\\Friend model #1 no longer exists");

        $friend = new models\Friend();
        $friend->name = 'Alix';
        $friend->save();
        models\Friend::delete($friend->id);

        $friend->reload();
    }

    public function testRemoveDeletesTheModel(): void
    {
        /** @var int $id */
        $id = models\Friend::create([
            'name' => 'Alix',
        ]);
        /** @var models\Friend $friend */
        $friend = models\Friend::find($id);

        $this->assertSame(1, models\Friend::count());
        $this->assertTrue($friend->isPersisted());

        $result = $friend->remove();

        $this->assertTrue($result);
        $this->assertSame(0, models\Friend::count());
        $this->assertFalse($friend->isPersisted());
    }

    public function testRemoveDoesNothingIfTheModelIsNotPersisted(): void
    {
        $friend = new models\Friend();

        $result = $friend->remove();

        $this->assertTrue($result);
        $this->assertSame(0, models\Friend::count());
        $this->assertFalse($friend->isPersisted());
    }

    public function testToDbValues(): void
    {
        $created_at = \Minz\Time::now();
        $friend = new models\Friend();
        $friend->name = 'Alix';
        $friend->address = null;
        $friend->created_at = $created_at;
        $friend->is_kind = true;
        $friend->options = ['pet' => true];

        $values = $friend->toDbValues();

        $this->assertSame('Alix', $values['name']);
        $this->assertNull($values['address']);
        $this->assertSame(
            $created_at->format(Column::DATETIME_FORMAT),
            $values['created_at']
        );
        $this->assertSame(1, $values['is_kind']);
        $this->assertSame('{"pet":true}', $values['options']);
    }
}
