<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use AppTest\factories;
use AppTest\models;
use Minz\Errors;
use PHPUnit\Framework\TestCase;

class InheritanceTest extends TestCase
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

    public function testListAllOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pets = models\Pet::listAll('name');

        $this->assertSame(2, count($pets));
        $this->assertInstanceOf(models\Cat::class, $pets[0]);
        $this->assertInstanceOf(models\Dog::class, $pets[1]);
    }

    public function testListAllOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pets = models\Dog::listAll();

        $this->assertSame(1, count($pets));
        $this->assertSame($dog_id, $pets[0]->id);
    }

    public function testListAllOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pets = models\Dog::listAll('name');

        $this->assertSame(2, count($pets));
        $this->assertInstanceOf(models\Dog::class, $pets[0]);
        $this->assertInstanceOf(models\Puppy::class, $pets[1]);
    }

    public function testListAllOnUndeclaredClassCannotQueryTheTable(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\UndeclaredPet::listAll();
    }

    public function testListByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Méli',
        ]);

        $pets = models\Pet::listBy(['name' => 'Méli'], 'id');

        $this->assertSame(2, count($pets));
        $this->assertInstanceOf(models\Cat::class, $pets[0]);
        $this->assertInstanceOf(models\Dog::class, $pets[1]);
    }

    public function testListByOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Méli',
        ]);

        $pets = models\Dog::listBy(['name' => 'Méli']);

        $this->assertSame(1, count($pets));
        $this->assertSame($dog_id, $pets[0]->id);
    }

    public function testListByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Méli',
        ]);

        $pets = models\Dog::listBy(['name' => 'Méli'], 'id');

        $this->assertSame(2, count($pets));
        $this->assertInstanceOf(models\Dog::class, $pets[0]);
        $this->assertInstanceOf(models\Puppy::class, $pets[1]);
    }

    public function testFindOnParentConsidersAllTheClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
            'lives' => 7,
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Mélo',
            'is_playful' => false,
        ]);

        $cat = models\Pet::find($cat_id);
        $dog = models\Pet::find($dog_id);

        $this->assertInstanceOf(models\Cat::class, $cat);
        $this->assertSame(7, $cat->lives);
        $this->assertInstanceOf(models\Dog::class, $dog);
        $this->assertFalse($dog->is_playful);
    }

    public function testFindOnChildIgnoresTheOtherClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::find($cat_id);

        $this->assertNull($pet);
    }

    public function testFindOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        /** @var int $puppy_id */
        $puppy_id = models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::find($puppy_id);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testFindByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Pet::findBy(['name' => 'Mélo']);

        $this->assertInstanceOf(models\Dog::class, $pet);
    }

    public function testFindByOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::findBy(['name' => 'Méli']);

        $this->assertNull($pet);
    }

    public function testFindByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::findBy(['name' => 'Mélo']);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testFindByOnChildCombinesWithTheGivenCriteria(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::findBy(['type' => 'cat']);

        $this->assertNull($pet);
    }

    public function testRequireOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Pet::require($dog_id);

        $this->assertInstanceOf(models\Dog::class, $pet);
    }

    public function testRequireOnChildIgnoresTheOtherClasses(): void
    {
        $this->expectException(Errors\MissingRecordError::class);

        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        models\Dog::require($cat_id);
    }

    public function testRequireOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        /** @var int $puppy_id */
        $puppy_id = models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::require($puppy_id);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testRequireByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Pet::requireBy(['name' => 'Mélo']);

        $this->assertInstanceOf(models\Dog::class, $pet);
    }

    public function testRequireByOnChildIgnoresTheOtherClasses(): void
    {
        $this->expectException(Errors\MissingRecordError::class);

        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        models\Dog::requireBy(['name' => 'Méli']);
    }

    public function testRequireByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::requireBy(['name' => 'Mélo']);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testTakeOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet_1 = models\Pet::take(0);
        $pet_2 = models\Pet::take(1);

        $this->assertInstanceOf(models\Cat::class, $pet_1);
        $this->assertInstanceOf(models\Dog::class, $pet_2);
    }

    public function testTakeOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet_1 = models\Dog::take(0);
        $pet_2 = models\Dog::take(1);

        $this->assertSame($dog_id, $pet_1?->id);
        $this->assertNull($pet_2);
    }

    public function testTakeOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pet_1 = models\Dog::take(0);
        $pet_2 = models\Dog::take(1);

        $this->assertInstanceOf(models\Dog::class, $pet_1);
        $this->assertInstanceOf(models\Puppy::class, $pet_2);
    }

    public function testExistsOnParentConsidersAllTheClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $exists = models\Pet::exists($cat_id);

        $this->assertTrue($exists);
    }

    public function testExistsOnChildIgnoresTheOtherClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $exists = models\Dog::exists($cat_id);

        $this->assertFalse($exists);
    }

    public function testExistsOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        /** @var int $puppy_id */
        $puppy_id = models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $exists = models\Dog::exists($puppy_id);

        $this->assertTrue($exists);
    }

    public function testExistsByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $exists = models\Pet::existsBy(['name' => 'Méli']);

        $this->assertTrue($exists);
    }

    public function testExistsByOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $exists = models\Dog::existsBy(['name' => 'Méli']);

        $this->assertFalse($exists);
    }

    public function testExistsByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $exists = models\Dog::existsBy(['name' => 'Mélo']);

        $this->assertTrue($exists);
    }

    public function testCountOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $count = models\Pet::count();

        $this->assertSame(2, $count);
    }

    public function testCountOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $count = models\Dog::count();

        $this->assertSame(1, $count);
    }

    public function testCountOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $count = models\Dog::count();

        $this->assertSame(2, $count);
    }

    public function testCountByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Méli',
        ]);

        $count = models\Pet::countBy(['name' => 'Méli']);

        $this->assertSame(2, $count);
    }

    public function testCountByOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Méli',
        ]);

        $count = models\Dog::countBy(['name' => 'Méli']);

        $this->assertSame(1, $count);
    }

    public function testCountByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Méli',
        ]);

        $count = models\Dog::countBy(['name' => 'Méli']);

        $this->assertSame(2, $count);
    }

    public function testCreateOnParentUsesTheClassOfTheType(): void
    {
        /** @var int $pet_id */
        $pet_id = models\Pet::create([
            'type' => 'dog',
            'name' => 'Méli',
            'is_playful' => false,
        ]);

        $pet = models\Pet::find($pet_id);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertFalse($pet->is_playful);
    }

    public function testCreateOnParentFailsWithoutType(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Pet::create([
            'name' => 'Méli',
        ]);
    }

    public function testCreateOnChildSetsTheType(): void
    {
        /** @var int $pet_id */
        $pet_id = models\Dog::create([
            'name' => 'Méli',
        ]);

        $pet = models\Pet::find($pet_id);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertSame('dog', $pet->type);
    }

    public function testCreateOnChildUsesTheTypeOfItsChildren(): void
    {
        /** @var int $pet_id */
        $pet_id = models\Dog::create([
            'type' => 'puppy',
            'name' => 'Méli',
        ]);

        $pet = models\Pet::find($pet_id);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testCreateOnChildFailsWithTheTypeOfAnotherClass(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Dog::create([
            'type' => 'cat',
            'name' => 'Méli',
        ]);
    }

    public function testCreateOnChildFailsWithAColumnOfAnotherClass(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Dog::create([
            'name' => 'Méli',
            'lives' => 7,
        ]);
    }

    public function testFactoryCreatesTheClassOfTheType(): void
    {
        $cat = factories\PetFactory::create();
        $dog = factories\PetFactory::create(['type' => 'dog']);

        $this->assertInstanceOf(models\Cat::class, $cat);
        $this->assertInstanceOf(models\Dog::class, $dog);
    }

    public function testFindOrCreateByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Pet::findOrCreateBy(['name' => 'Mélo']);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertSame(2, models\Pet::count());
    }

    public function testFindOrCreateByOnParentUsesTheClassOfTheType(): void
    {
        $pet = models\Pet::findOrCreateBy(['type' => 'dog', 'name' => 'Méli']);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertSame(1, models\Dog::count());
    }

    public function testFindOrCreateByOnParentFailsWithoutType(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Pet::findOrCreateBy(['name' => 'Méli']);
    }

    public function testFindOrCreateByOnChildIgnoresTheOtherClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::findOrCreateBy(['name' => 'Méli']);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertSame(3, models\Pet::count());
    }

    public function testFindOrCreateByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $pet = models\Dog::findOrCreateBy(['name' => 'Mélo']);

        $this->assertInstanceOf(models\Puppy::class, $pet);
        $this->assertSame(2, models\Pet::count());
    }

    public function testFindOrCreateByOnChildSetsTheType(): void
    {
        $pet = models\Dog::findOrCreateBy(['name' => 'Méli']);

        $this->assertSame('dog', $pet->type);
    }

    public function testFindOrCreateByOnChildUsesTheTypeOfItsChildren(): void
    {
        $pet = models\Dog::findOrCreateBy(['type' => 'puppy', 'name' => 'Méli']);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testFindOrCreateByOnChildFailsWithTheTypeOfAnotherClass(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Dog::findOrCreateBy(['type' => 'cat', 'name' => 'Méli']);
    }

    public function testUpdateOnParentConsidersAllTheClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        models\Pet::update($cat_id, ['name' => 'Rajah']);

        $this->assertSame('Rajah', models\Pet::require($cat_id)->name);
    }

    public function testUpdateOnChildIgnoresTheOtherClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        models\Dog::update($cat_id, ['name' => 'Rajah']);

        $this->assertSame('Méli', models\Pet::require($cat_id)->name);
    }

    public function testUpdateOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        /** @var int $puppy_id */
        $puppy_id = models\Puppy::create([
            'name' => 'Mélo',
        ]);

        models\Dog::update($puppy_id, ['name' => 'Rajah']);

        $this->assertSame('Rajah', models\Pet::require($puppy_id)->name);
    }

    public function testDeleteOnParentConsidersAllTheClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        models\Pet::delete($cat_id);

        $this->assertFalse(models\Pet::exists($cat_id));
    }

    public function testDeleteOnChildIgnoresTheOtherClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        models\Dog::delete($cat_id);

        $this->assertTrue(models\Pet::exists($cat_id));
    }

    public function testDeleteOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        /** @var int $puppy_id */
        $puppy_id = models\Puppy::create([
            'name' => 'Mélo',
        ]);

        models\Dog::delete($puppy_id);

        $this->assertFalse(models\Pet::exists($puppy_id));
    }

    public function testDeleteByOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Méli',
        ]);

        models\Pet::deleteBy(['name' => 'Méli']);

        $this->assertSame(0, models\Pet::count());
    }

    public function testDeleteByOnChildIgnoresTheOtherClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Méli',
        ]);

        models\Dog::deleteBy(['name' => 'Méli']);

        $this->assertTrue(models\Pet::exists($cat_id));
        $this->assertFalse(models\Pet::exists($dog_id));
    }

    public function testDeleteByOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Méli',
        ]);

        models\Dog::deleteBy(['name' => 'Méli']);

        $this->assertSame(0, models\Pet::count());
    }

    public function testDeleteAllOnParentConsidersAllTheClasses(): void
    {
        models\Cat::create([
            'name' => 'Méli',
        ]);
        models\Dog::create([
            'name' => 'Mélo',
        ]);

        $count = models\Pet::deleteAll();

        $this->assertSame(2, $count);
        $this->assertSame(0, models\Pet::count());
    }

    public function testDeleteAllOnChildIgnoresTheOtherClasses(): void
    {
        /** @var int $cat_id */
        $cat_id = models\Cat::create([
            'name' => 'Méli',
        ]);
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Mélo',
        ]);

        $count = models\Dog::deleteAll();

        $this->assertSame(1, $count);
        $this->assertTrue(models\Pet::exists($cat_id));
        $this->assertFalse(models\Pet::exists($dog_id));
    }

    public function testDeleteAllOnChildIncludesItsChildren(): void
    {
        models\Dog::create([
            'name' => 'Méli',
        ]);
        models\Puppy::create([
            'name' => 'Mélo',
        ]);

        $count = models\Dog::deleteAll();

        $this->assertSame(2, $count);
        $this->assertSame(0, models\Pet::count());
    }

    public function testSaveSetsTheType(): void
    {
        $dog = new models\Dog();
        $dog->name = 'Méli';

        $dog->save();

        $this->assertSame('dog', $dog->type);
        $this->assertInstanceOf(models\Dog::class, models\Pet::find($dog->id));
    }

    public function testSaveCannotChangeTheType(): void
    {
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Méli',
        ]);
        $dog = models\Dog::require($dog_id);

        $dog->type = 'cat';
        $dog->save();

        $this->assertSame('dog', $dog->type);
        $this->assertInstanceOf(models\Dog::class, models\Pet::find($dog_id));
    }

    public function testSaveUpdatesTheColumnsOfTheClass(): void
    {
        /** @var int $dog_id */
        $dog_id = models\Dog::create([
            'name' => 'Méli',
        ]);
        $dog = models\Dog::require($dog_id);
        $this->assertInstanceOf(models\Dog::class, $dog);

        $dog->is_playful = false;
        $dog->save();

        $pet = models\Pet::find($dog_id);
        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertFalse($pet->is_playful);
    }

    public function testSaveOnUndeclaredClassCannotSaveTheModel(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);
        $this->expectExceptionMessage(
            'AppTest\models\UndeclaredPet is not declared in the Inheritance attribute'
        );

        $pet = new models\UndeclaredPet();
        $pet->name = 'Méli';

        $pet->save();
    }

    public function testFromDatabaseRowOnParentUsesTheClassOfTheType(): void
    {
        $pet = models\Pet::fromDatabaseRow([
            'id' => 1,
            'type' => 'dog',
            'name' => 'Méli',
        ]);

        $this->assertInstanceOf(models\Dog::class, $pet);
    }

    public function testFromDatabaseRowOnParentFailsWithoutType(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Pet::fromDatabaseRow([
            'id' => 1,
            'name' => 'Méli',
        ]);
    }

    public function testFromDatabaseRowOnParentFailsWithAnUnknownType(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Pet::fromDatabaseRow([
            'id' => 1,
            'type' => 'hamster',
            'name' => 'Méli',
        ]);
    }

    public function testFromDatabaseRowOnChildUsesTheTypeOfItsChildren(): void
    {
        $pet = models\Dog::fromDatabaseRow([
            'id' => 1,
            'type' => 'puppy',
            'name' => 'Méli',
        ]);

        $this->assertInstanceOf(models\Puppy::class, $pet);
    }

    public function testFromDatabaseRowOnChildAcceptsARowWithoutType(): void
    {
        $pet = models\Dog::fromDatabaseRow([
            'id' => 1,
            'name' => 'Méli',
        ]);

        $this->assertInstanceOf(models\Dog::class, $pet);
    }

    public function testFromDatabaseRowOnChildFailsWithTheTypeOfAnotherClass(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Dog::fromDatabaseRow([
            'id' => 1,
            'type' => 'cat',
            'name' => 'Méli',
        ]);
    }

    public function testFromDatabaseRowOnChildFailsWithTheTypeOfItsParent(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Puppy::fromDatabaseRow([
            'id' => 1,
            'type' => 'dog',
            'name' => 'Méli',
        ]);
    }

    public function testFromDatabaseRowOnChildIgnoresTheColumnsOfTheOtherClasses(): void
    {
        $pet = models\Dog::fromDatabaseRow([
            'id' => 1,
            'type' => 'dog',
            'name' => 'Méli',
            'is_playful' => false,
            'lives' => 7,
        ]);

        $this->assertInstanceOf(models\Dog::class, $pet);
        $this->assertFalse($pet->is_playful);
        $this->assertFalse(property_exists($pet, 'lives'));
    }

    public function testFromDatabaseRowOnChildFailsWithAnUnknownColumn(): void
    {
        $this->expectException(Errors\DatabaseModelError::class);

        models\Dog::fromDatabaseRow([
            'id' => 1,
            'type' => 'dog',
            'name' => 'Méli',
            'unknown' => 'value',
        ]);
    }

    public function testFromDatabaseRowsOnParentUsesTheClassOfTheTypes(): void
    {
        $pets = models\Pet::fromDatabaseRows([
            ['id' => 1, 'type' => 'cat', 'name' => 'Méli'],
            ['id' => 2, 'type' => 'dog', 'name' => 'Mélo'],
        ]);

        $this->assertInstanceOf(models\Cat::class, $pets[0]);
        $this->assertInstanceOf(models\Dog::class, $pets[1]);
    }
}
