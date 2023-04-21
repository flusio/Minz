<?php

namespace Minz;

use PHPUnit\Framework\TestCase;
use AppTest\models;

class ValidableTest extends TestCase
{
    public function testValidate(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix';

        $errors = $model->validate();

        $this->assertSame([], $errors);
    }

    public function testValidateFailsIfEmpty(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = '';

        $errors = $model->validate();

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

        $errors = $model->validate();

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Length', 'Choose a nickname of less than 42 characters.'],
            ]
        ], $errors);
    }

    public function testValidateFailsIfInvalid(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg';

        $errors = $model->validate();

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Format', 'Choose a nickname that only contains letters.'],
            ]
        ], $errors);
    }

    public function testValidateFailsWithMultipleErrors(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $errors = $model->validate();

        $this->assertEquals([
            'nickname' => [
                ['Minz\\Validable\\Length', 'Choose a nickname of less than 42 characters.'],
                ['Minz\\Validable\\Format', 'Choose a nickname that only contains letters.'],
            ]
        ], $errors);
    }

    public function testValidateCanFormatErrors(): void
    {
        $model = new models\ValidableModel();
        $model->nickname = 'Alix Hambourg' . str_repeat('a', 50);

        $errors = $model->validate(format: true);

        $this->assertEquals([
            'nickname' => (
                'Choose a nickname of less than 42 characters. ' .
                'Choose a nickname that only contains letters.'
            )
        ], $errors);
    }
}