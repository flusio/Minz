<?php

namespace Minz;

use PHPUnit\Framework\TestCase;
use AppTest\models;

class ModelTest extends TestCase
{
    public function testPropertyDeclarations()
    {
        Model::declareProperties(
            models\SimpleId::class,
            models\SimpleId::PROPERTIES
        );

        $property_declarations = Model::propertyDeclarations(models\SimpleId::class);

        $this->assertSame([
            'id' => [
                'type' => 'integer',
                'required' => false,
                'validator' => null,
            ],
        ], $property_declarations);
    }

    public function testPropertyDeclarationsWithValidTypes()
    {
        Model::declareProperties(
            models\ValidPropertyTypes::class,
            models\ValidPropertyTypes::PROPERTIES
        );

        $property_declarations = Model::propertyDeclarations(
            models\ValidPropertyTypes::class
        );

        $this->assertSame([
            'integer' => [
                'type' => 'integer',
                'required' => false,
                'validator' => null,
            ],
            'string' => [
                'type' => 'string',
                'required' => false,
                'validator' => null,
            ],
            'datetime' => [
                'type' => 'datetime',
                'required' => false,
                'validator' => null,
                'format' => Model::DATETIME_FORMAT,
            ],
            'boolean' => [
                'type' => 'boolean',
                'required' => false,
                'validator' => null,
            ],
        ], $property_declarations);
    }

    public function testPropertyDeclarationsFailsIfPropertyTypeIsNotSupported()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Model::ERROR_PROPERTY_TYPE_INVALID);
        $this->expectExceptionMessage('`not a type` is not a valid property type.');

        Model::declareProperties(
            models\BadType::class,
            models\BadType::PROPERTIES
        );
    }

    public function testPropertyDeclarationsFailsIfValidatorIsUncallable()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Model::ERROR_PROPERTY_VALIDATOR_INVALID);
        $this->expectExceptionMessage('`not_callable` validator cannot be called.');

        Model::declareProperties(
            models\BadValidator::class,
            models\BadValidator::PROPERTIES
        );
    }

    public function testToValues()
    {
        $model = new models\SimpleId();
        $model->id = 42;

        $values = $model->toValues();

        $this->assertSame(42, $values['id']);
    }

    public function testToValuesWithUnsetValue()
    {
        $model = new models\SimpleId();

        $values = $model->toValues();

        $this->assertArrayNotHasKey('id', $values);
    }

    public function testToValuesWithUndeclaredProperty()
    {
        $model = new models\SimpleId();
        $model->foo = 'bar';

        $values = $model->toValues();

        $this->assertArrayNotHasKey('foo', $values);
    }

    public function testToValuesWithDatetimeProperty()
    {
        $model = new models\SimpleDateTime();
        $created_at = new \DateTime();
        $model->created_at = $created_at;

        $values = $model->toValues();

        $this->assertSame($created_at->format(Model::DATETIME_FORMAT), $values['created_at']);
    }

    public function testToValuesWithDatetimePropertyAndCustomFormat()
    {
        $model = new models\FormattedDateTime();
        $created_at = new \DateTime();
        $model->created_at = $created_at;

        $values = $model->toValues();

        $this->assertSame($created_at->format('Y-m-d'), $values['created_at']);
    }

    public function testToValuesWithUnsetDatetimeProperty()
    {
        $model = new models\SimpleDateTime();

        $values = $model->toValues();

        $this->assertArrayNotHasKey('created_at', $values);
    }

    public function testFromValuesWithString()
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['string' => 'foo']);

        $this->assertSame('foo', $model->string);
    }

    public function testFromValuesWithInteger()
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['integer' => '42']);

        $this->assertSame(42, $model->integer);
    }

    public function testFromValuesWithIntegerZero()
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['integer' => '0']);

        $this->assertSame(0, $model->integer);
    }

    public function testFromValuesWithBoolean()
    {
        $model = new models\ValidPropertyTypes();

        // @todo check value returned by SQLite and PGSQL
        $model->fromValues(['boolean' => 'true']);

        $this->assertTrue($model->boolean);
    }

    public function testFromValuesWithBooleanFalse()
    {
        $model = new models\ValidPropertyTypes();

        // @todo check value returned by SQLite and PGSQL
        $model->fromValues(['boolean' => 'false']);

        $this->assertFalse($model->boolean);
    }

    public function testFromValuesWithDatetime()
    {
        $model = new models\ValidPropertyTypes();
        $datetime = new \DateTime();

        $model->fromValues(['datetime' => $datetime]);

        $this->assertEquals($datetime, $model->datetime);
    }

    public function testFromValuesWithIso8601()
    {
        $model = new models\ValidPropertyTypes();
        $datetime = new \DateTime();
        $iso_8601 = $datetime->format(Model::DATETIME_FORMAT);

        $model->fromValues(['datetime' => $iso_8601]);

        // we must compare timestamps because ISO-8601 lose the microseconds
        // and so the two DateTime are, in fact, different.
        $this->assertEquals($datetime->getTimestamp(), $model->datetime->getTimestamp());
    }

    public function testFromValuesWithCustomFormat()
    {
        $model = new models\FormattedDateTime();
        $datetime = new \DateTime('2020-01-20');
        $formatted_datetime = $datetime->format('Y-m-d');

        $model->fromValues(['created_at' => $formatted_datetime]);

        $this->assertEquals($formatted_datetime, $model->created_at->format('Y-m-d'));
    }

    public function testFromValuesWhenIntegerTypeDoesNotMatch()
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['integer' => 'not an integer']);

        $this->assertSame('not an integer', $model->integer);
    }

    public function testFromValuesWhenDatetimeTypeDoesNotMatch()
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['datetime' => 'not a datetime']);

        $this->assertSame('not a datetime', $model->datetime);
    }

    public function testFromValuesWhenBooleanTypeDoesNotMatch()
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['boolean' => 'not a boolean']);

        $this->assertSame('not a boolean', $model->boolean);
    }

    public function testFromValuesFailsIfUndeclaredProperty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Model::ERROR_PROPERTY_UNDECLARED);
        $this->expectExceptionMessage(
            '`not_a_property` property has not been declared.'
        );

        $model = new models\ValidPropertyTypes();

        $model->fromValues(['not_a_property' => 'foo']);
    }

    public function testValidateReturnsNoErrorsIfValid()
    {
        $model = new models\Validator();
        $model->status = 'new';

        $errors = $model->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateReturnsErrorIfRequiredPropertyIsNull()
    {
        $model = new models\Required();
        $model->id = null;

        $errors = $model->validate();

        $this->assertSame(
            [
                'id' => [
                    'code' => Model::ERROR_REQUIRED,
                    'description' => 'Required `id` property is missing.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfRequiredStringPropertyIsEmpty()
    {
        $model = new models\Required();
        $model->id = '';

        $errors = $model->validate();

        $this->assertSame(
            [
                'id' => [
                    'code' => Model::ERROR_REQUIRED,
                    'description' => 'Required `id` property is missing.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfIntegerTypeDoesNotMatch()
    {
        $model = new models\ValidPropertyTypes();
        $model->integer = 'not an integer';

        $errors = $model->validate();

        $this->assertSame(
            [
                'integer' => [
                    'code' => Model::ERROR_VALUE_TYPE_INVALID,
                    'description' => '`integer` property must be an integer.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfDatetimeTypeDoesNotMatch()
    {
        $model = new models\ValidPropertyTypes();
        $model->datetime = 'not a datetime';

        $errors = $model->validate();

        $this->assertSame(
            [
                'datetime' => [
                    'code' => Model::ERROR_VALUE_TYPE_INVALID,
                    'description' => '`datetime` property must be a \DateTime.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfBooleanTypeDoesNotMatch()
    {
        $model = new models\ValidPropertyTypes();
        $model->boolean = 'not a boolean';

        $errors = $model->validate();

        $this->assertSame(
            [
                'boolean' => [
                    'code' => Model::ERROR_VALUE_TYPE_INVALID,
                    'description' => '`boolean` property must be a boolean.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfValidatorReturnsFalse()
    {
        $model = new models\Validator();
        $model->status = 'not valid';

        $errors = $model->validate();

        $this->assertSame(
            [
                'status' => [
                    'code' => Model::ERROR_VALUE_INVALID,
                    'description' => '`status` property is invalid (not valid).',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfValidatorReturnsCustomMessage()
    {
        $model = new models\ValidatorMessage();
        $model->status = 'not valid';

        $errors = $model->validate();

        $this->assertSame(
            [
                'status' => [
                    'code' => Model::ERROR_VALUE_INVALID,
                    'description' => 'must be either new or finished',
                ],
            ],
            $errors
        );
    }

    public function testValidateCanReturnSeveralErrors()
    {
        $model = new models\Friend();
        $model->id = 'an id';
        $model->created_at = null;
        $model->name = 'not valid';

        $errors = $model->validate();

        $this->assertSame(
            [
                'created_at' => [
                    'code' => Model::ERROR_REQUIRED,
                    'description' => 'Required `created_at` property is missing.',
                ],
                'name' => [
                    'code' => Model::ERROR_VALUE_INVALID,
                    'description' => '`name` property is invalid (not valid).',
                ],
            ],
            $errors
        );
    }

    public function testValidateDoesNotCallValidatorIfStringValueIsEmpty()
    {
        $model = new models\Validator();
        $model->status = '';

        $errors = $model->validate();

        $this->assertEmpty($errors);
    }
}
