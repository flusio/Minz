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

        $is_valid = $model->validate();

        $this->assertTrue($is_valid);
        $this->assertSame([], $model->errors(format: false));
    }

    public function testValidateDoesNotFailIfEmptyAndOptional(): void
    {
        $model = new models\ValidableOptionalModel();
        $model->nickname = '';

        $is_valid = $model->validate();

        $this->assertTrue($is_valid);
        $this->assertSame([], $model->errors(format: false));
    }

    public function testValidateFailsIfEmpty(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = '';

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'nickname' => [
                ['presence', 'Choose a nickname.'],
            ]
        ], $model->errors(format: false));
    }

    public function testValidateFailsIfTooLong(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = str_repeat('a', 50);

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'nickname' => [
                ['length', 'Choose a nickname between 2 and 42 characters.'],
            ]
        ], $model->errors(format: false));
    }

    public function testValidateFailsIfTooShort(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'A';

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'nickname' => [
                ['length', 'Choose a nickname between 2 and 42 characters.'],
            ]
        ], $model->errors(format: false));
    }

    public function testValidateFailsIfInvalid(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg';

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'nickname' => [
                ['format', 'Choose a nickname that only contains letters.'],
            ]
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidEmail(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->email = 'not an email';

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'email' => [
                ['email', 'Choose a valid email.'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidUrl(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->website = 'not an URL';

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'website' => [
                ['url', 'Choose a valid URL.'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidInclusion(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->role = 'not a role';

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'role' => [
                ['inclusion', 'Choose a valid role.'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidGreaterComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->greater = 42;

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'greater' => [
                ['comparison', 'Must be greater than 42'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidGreaterOrEqualComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->greater_or_equal = 41;

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'greater_or_equal' => [
                ['comparison', 'Must be greater than or equal to 42'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidEqualComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->equal = 41;

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'equal' => [
                ['comparison', 'Must be equal to 42'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidLessComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->less = 42;

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'less' => [
                ['comparison', 'Must be less than 42'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidLessOrEqualComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->less_or_equal = 43;

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'less_or_equal' => [
                ['comparison', 'Must be less than or equal to 42'],
            ],
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithInvalidOtherComparison(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';
        $model->other = 42;

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'other' => [
                ['comparison', 'Must be other than 42'],
            ],
        ], $model->errors(format: false));
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

        $is_valid_existing_model = $existing_model->validate();
        $is_valid_model = $model->validate();

        \Minz\Database::reset();

        $this->assertTrue($is_valid_existing_model);
        $this->assertFalse($is_valid_model);
        $this->assertEquals([], $existing_model->errors(format: false));
        $this->assertEquals([
            'email' => [
                ['unique', '"alix@example.org" is already taken.'],
            ]
        ], $model->errors(format: false));
    }

    public function testValidateFailsWithMultipleErrors(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'nickname' => [
                ['length', 'Choose a nickname between 2 and 42 characters.'],
                ['format', 'Choose a nickname that only contains letters.'],
            ]
        ], $model->errors(format: false));
    }

    public function testGetErrorsFormatsErrors(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $message = 'Choose a nickname between 2 and 42 characters. Choose a nickname that only contains letters.';
        $this->assertEquals(['nickname' => $message], $model->errors());
    }

    public function testGetErrorFormatsErrors(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $is_valid = $model->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals(
            'Choose a nickname between 2 and 42 characters. Choose a nickname that only contains letters.',
            $model->error('nickname')
        );
    }
}
