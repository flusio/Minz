<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;
use AppTest\models;

class ValidableTest extends TestCase
{
    public function testValidate(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';

        $errors = $model->validate(format: false);

        $this->assertSame([], $errors);
    }

    public function testValidateDoesNotFailIfEmptyAndOptional(): void
    {
        $model = new models\ValidableOptionalModel();
        $model->nickname = '';

        $errors = $model->validate(format: false);

        $this->assertEquals([], $errors);
    }

    public function testValidateFailsIfEmpty(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = '';

        $errors = $model->validate(format: false);

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Presence', 'Choose a nickname.'],
            ]
        ], $errors);
    }

    public function testValidateFailsIfTooLong(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = str_repeat('a', 50);

        $errors = $model->validate(format: false);

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Length', 'Choose a nickname between 2 and 42 characters.'],
            ]
        ], $errors);
    }

    public function testValidateFailsIfTooShort(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'A';

        $errors = $model->validate(format: false);

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Length', 'Choose a nickname between 2 and 42 characters.'],
            ]
        ], $errors);
    }

    public function testValidateFailsIfInvalid(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg';

        $errors = $model->validate(format: false);

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Format', 'Choose a nickname that only contains letters.'],
            ]
        ], $errors);
    }

    public function testValidateFailsWithInvalidEmail(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->email = 'not an email';

        $errors = $model->validate(format: false);

        $this->assertSame([
            'email' => [
                ['Minz\\Validable\\Email', 'Choose a valid email.'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidUrl(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->website = 'not an URL';

        $errors = $model->validate(format: false);

        $this->assertSame([
            'website' => [
                ['Minz\\Validable\\Url', 'Choose a valid URL.'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidInclusion(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->role = 'not a role';

        $errors = $model->validate(format: false);

        $this->assertSame([
            'role' => [
                ['Minz\\Validable\\Inclusion', 'Choose a valid role.'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidGreaterComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->greater = 42;

        $errors = $model->validate(format: false);

        $this->assertSame([
            'greater' => [
                ['Minz\\Validable\\Comparison', 'Must be greater than 42'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidGreaterOrEqualComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->greater_or_equal = 41;

        $errors = $model->validate(format: false);

        $this->assertSame([
            'greater_or_equal' => [
                ['Minz\\Validable\\Comparison', 'Must be greater than or equal to 42'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidEqualComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->equal = 41;

        $errors = $model->validate(format: false);

        $this->assertSame([
            'equal' => [
                ['Minz\\Validable\\Comparison', 'Must be equal to 42'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidLessComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->less = 42;

        $errors = $model->validate(format: false);

        $this->assertSame([
            'less' => [
                ['Minz\\Validable\\Comparison', 'Must be less than 42'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidLessOrEqualComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->less_or_equal = 43;

        $errors = $model->validate(format: false);

        $this->assertSame([
            'less_or_equal' => [
                ['Minz\\Validable\\Comparison', 'Must be less than or equal to 42'],
            ],
        ], $errors);
    }

    public function testValidateFailsWithInvalidOtherComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->other = 42;

        $errors = $model->validate(format: false);

        $this->assertSame([
            'other' => [
                ['Minz\\Validable\\Comparison', 'Must be other than 42'],
            ],
        ], $errors);
    }

    public function testValidateFailsIfNotUnique(): void
    {
        assert(\Minz\Configuration::$database !== null);

        $database_type = \Minz\Configuration::$database['type'];
        $sql_schema_path = \Minz\Configuration::$app_path . "/schema.{$database_type}.sql";
        $sql_schema = file_get_contents($sql_schema_path);

        assert($sql_schema !== false);

        $database = \Minz\Database::get();
        $database->exec($sql_schema);

        $existing_model = new models\ValidableUniqueModel();
        $existing_model->email = 'alix@example.org';
        $existing_model->save();
        $model = new models\ValidableUniqueModel();
        $model->email = 'alix@example.org';

        $errors_existing_model = $existing_model->validate(format: false);
        $errors_model = $model->validate(format: false);

        \Minz\Database::reset();

        $this->assertEquals([], $errors_existing_model);
        $this->assertEquals([
            'email' => [
                ['Minz\\Validable\\Unique', '"alix@example.org" is already taken.'],
            ]
        ], $errors_model);
    }

    public function testValidateFailsWithMultipleErrors(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $errors = $model->validate(format: false);

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Length', 'Choose a nickname between 2 and 42 characters.'],
                ['Minz\\Validable\\Format', 'Choose a nickname that only contains letters.'],
            ]
        ], $errors);
    }

    public function testValidateFormatErrorsByDefault(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $errors = $model->validate();

        $this->assertEquals([
            'nickname' => (
                'Choose a nickname between 2 and 42 characters. ' .
                'Choose a nickname that only contains letters.'
            )
        ], $errors);
    }
}
