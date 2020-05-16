<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testConstuctor()
    {
        $model = new Model(['property' => 'string']);

        $this->assertNull($model->property);
    }

    /**
     * @dataProvider validTypesProvider
     */
    public function testConstructorWithValidPropertyTypes($type)
    {
        $model = new Model(['property' => $type]);

        $property_declarations = $model->propertyDeclarations();
        $this->assertSame($type, $property_declarations['property']['type']);
    }

    public function testConstructorFailsIfPropertyTypeIsNotSupported()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_TYPE_INVALID);
        $this->expectExceptionMessage('`not a type` is not a valid property type.');

        new Model(['id' => 'not a type']);
    }

    public function testConstructorFailsIfValidatorIsUncallable()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_VALIDATOR_INVALID);
        $this->expectExceptionMessage('`not_callable` validator cannot be called.');

        new Model([
            'id' => [
                'type' => 'integer',
                'validator' => 'not_callable',
            ],
        ]);
    }

    public function testPropertyDeclarations()
    {
        $model = new Model(['id' => 'integer']);

        $property_declarations = $model->propertyDeclarations();

        $this->assertSame([
            'id' => [
                'type' => 'integer',
                'required' => false,
                'validator' => null,
            ],
        ], $property_declarations);
    }

    public function testToValues()
    {
        $model = new Model(['id' => 'integer']);
        $model->id = 42;

        $values = $model->toValues();

        $this->assertSame(42, $values['id']);
    }

    public function testToValuesWithUnsetValue()
    {
        $model = new Model(['id' => 'integer']);

        $values = $model->toValues();

        $this->assertNull($values['id']);
    }

    public function testToValuesWithUndeclaredProperty()
    {
        $model = new Model();
        $model->id = 42;

        $values = $model->toValues();

        $this->assertSame([], $values);
    }

    public function testToValuesWithDatetimeProperty()
    {
        $model = new Model(['created_at' => 'datetime']);
        $created_at = new \DateTime();
        $created_at->setTimestamp(1000);
        $model->created_at = $created_at;

        $values = $model->toValues();

        $this->assertSame($created_at->format(Model::DATETIME_FORMAT), $values['created_at']);
    }

    public function testToValuesWithUnsetDatetimeProperty()
    {
        $model = new Model(['created_at' => 'datetime']);

        $values = $model->toValues();

        $this->assertNull($values['created_at']);
    }

    public function testSetPropertyWithStringType()
    {
        $model = new Model(['foo' => 'string']);

        $model->setProperty('foo', 'bar');

        $this->assertSame('bar', $model->foo);
    }

    public function testSetPropertyWithIntegerType()
    {
        $model = new Model(['id' => 'integer']);

        $model->setProperty('id', 42);

        $this->assertSame(42, $model->id);
    }

    public function testSetPropertyWithIntegerTypeAndNull()
    {
        $model = new Model(['id' => 'integer']);

        $model->setProperty('id', null);

        $this->assertNull($model->id);
    }

    public function testSetPropertyWithBooleanType()
    {
        $model = new Model(['is_cool' => 'boolean']);

        $model->setProperty('is_cool', true);

        $this->assertTrue($model->is_cool);
    }

    public function testSetPropertyWithBooleanTypeAndNull()
    {
        $model = new Model(['is_cool' => 'boolean']);

        $model->setProperty('is_cool', null);

        $this->assertNull($model->is_cool);
    }

    public function testSetPropertyWithDatetimeType()
    {
        $model = new Model(['created_at' => 'datetime']);
        $date = new \Datetime();
        $date->setTimestamp(1000);

        $model->setProperty('created_at', $date);

        $this->assertSame(1000, $model->created_at->getTimestamp());
    }

    public function testSetPropertyWithDatetimeTypeAndNull()
    {
        $model = new Model(['created_at' => 'datetime']);

        $model->setProperty('created_at', null);

        $this->assertNull($model->created_at);
    }

    public function testSetPropertyWithValidator()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);

        $model->setProperty('status', 'new');

        $this->assertSame('new', $model->status);
    }

    public function testSetPropertyWithValidatorIsNotCalledIfNull()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return 'An error? Na, the validator is not called!';
                },
            ],
        ]);

        $model->setProperty('status', null);

        $this->assertNull($model->status);
    }

    public function testSetPropertyFailsIfRequiredPropertyIsNull()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_REQUIRED);
        $this->expectExceptionMessage('Required `id` property is missing.');

        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $model->setProperty('id', null);
    }

    public function testSetPropertyFailsIfRequiredStringPropertyIsEmpty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_REQUIRED);
        $this->expectExceptionMessage('Required `id` property is missing.');

        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);

        $model->setProperty('id', '');
    }

    public function testSetPropertyFailsIfValidatorReturnsFalse()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_INVALID);
        $this->expectExceptionMessage('`status` property is invalid (not valid).');

        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);

        $model->setProperty('status', 'not valid');
    }

    public function testSetPropertyFailsIfValidatorReturnsCustomMessage()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::VALUE_INVALID);
        $this->expectExceptionMessage(
            '`status` property is invalid (new): a custom message error.'
        );

        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return 'a custom message error';
                },
            ],
        ]);

        $model->setProperty('status', 'new');
    }

    public function testSetPropertyFailsIfUndeclaredProperty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_UNDECLARED);
        $this->expectExceptionMessage(
            '`status` property has not been declared.'
        );

        $model = new Model([]);

        $model->setProperty('status', 'new');
    }

    public function testFromValuesWithString()
    {
        $model = new Model(['foo' => 'string']);

        $model->fromValues(['foo' => 'bar']);

        $this->assertSame('bar', $model->foo);
    }

    public function testFromValuesWithInteger()
    {
        $model = new Model(['id' => 'integer']);

        $model->fromValues(['id' => '42']);

        $this->assertSame(42, $model->id);
    }

    public function testFromValuesWithIntegerZero()
    {
        $model = new Model(['id' => 'integer']);

        $model->fromValues(['id' => '0']);

        $this->assertSame(0, $model->id);
    }

    public function testFromValuesWithBoolean()
    {
        $model = new Model(['is_cool' => 'boolean']);

        // @todo check value returned by SQLite
        $model->fromValues(['is_cool' => 'true']);

        $this->assertTrue($model->is_cool);
    }

    public function testFromValuesWithBooleanFalse()
    {
        $model = new Model(['is_cool' => 'boolean']);

        // @todo check value returned by SQLite
        $model->fromValues(['is_cool' => 'false']);

        $this->assertFalse($model->is_cool);
    }

    public function testFromValuesWithDatetime()
    {
        $model = new Model(['created_at' => 'datetime']);
        $created_at = new \DateTime();

        $model->fromValues(['created_at' => $created_at]);

        $this->assertEquals($created_at, $model->created_at);
    }

    public function testFromValuesWithIso8601()
    {
        $model = new Model(['created_at' => 'datetime']);
        $created_at = new \DateTime();
        $iso_8601 = $created_at->format(Model::DATETIME_FORMAT);

        $model->fromValues(['created_at' => $iso_8601]);

        // we must compare timestamps because ISO-8601 lose the microseconds
        // and so the two DateTime are, in fact, different.
        $this->assertEquals($created_at->getTimestamp(), $model->created_at->getTimestamp());
    }

    public function testFromValuesWhenIntegerTypeDoesNotMatch()
    {
        $model = new Model(['id' => 'integer']);

        $model->fromValues(['id' => 'not an integer']);

        $this->assertSame('not an integer', $model->id);
    }

    public function testFromValuesWhenDatetimeTypeDoesNotMatch()
    {
        $model = new Model(['created_at' => 'datetime']);

        $model->fromValues(['created_at' => 'not a timestamp']);

        $this->assertSame('not a timestamp', $model->created_at);
    }

    public function testFromValuesWhenBooleanTypeDoesNotMatch()
    {
        $model = new Model(['is_cool' => 'boolean']);

        $model->fromValues(['is_cool' => 'not a boolean']);

        $this->assertSame('not a boolean', $model->is_cool);
    }

    public function testFromValuesFailsIfUndeclaredProperty()
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Errors\ModelPropertyError::PROPERTY_UNDECLARED);
        $this->expectExceptionMessage(
            '`status` property has not been declared.'
        );

        $model = new Model([]);

        $model->fromValues(['status' => 'new']);
    }

    public function testValidateReturnsNoErrorsIfValid()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'required' => true,
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);
        $model->status = 'new';

        $errors = $model->validate();

        $this->assertEmpty($errors);
    }

    public function testValidateReturnsErrorIfRequiredPropertyIsNull()
    {
        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);
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
        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
        ]);
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
        $model = new Model(['id' => 'integer']);
        $model->id = 'not an integer';

        $errors = $model->validate();

        $this->assertSame(
            [
                'id' => [
                    'code' => Model::ERROR_VALUE_TYPE_INVALID,
                    'description' => '`id` property must be an integer.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfDatetimeTypeDoesNotMatch()
    {
        $model = new Model(['created_at' => 'datetime']);
        $model->created_at = 'not a datetime';

        $errors = $model->validate();

        $this->assertSame(
            [
                'created_at' => [
                    'code' => Model::ERROR_VALUE_TYPE_INVALID,
                    'description' => '`created_at` property must be a \DateTime.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfBooleanTypeDoesNotMatch()
    {
        $model = new Model(['is_cool' => 'boolean']);
        $model->is_cool = 'not a boolean';

        $errors = $model->validate();

        $this->assertSame(
            [
                'is_cool' => [
                    'code' => Model::ERROR_VALUE_TYPE_INVALID,
                    'description' => '`is_cool` property must be a boolean.',
                ],
            ],
            $errors
        );
    }

    public function testValidateReturnsErrorIfValidatorReturnsFalse()
    {
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);
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
        $model = new Model([
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    if (in_array($value, ['new', 'finished'])) {
                        return true;
                    } else {
                        return 'must be either new or finished';
                    }
                },
            ],
        ]);
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
        $model = new Model([
            'id' => [
                'type' => 'string',
                'required' => true,
            ],
            'created_at' => [
                'type' => 'datetime',
                'required' => true,
            ],
            'status' => [
                'type' => 'string',
                'validator' => function ($value) {
                    return in_array($value, ['new', 'finished']);
                },
            ],
        ]);
        $model->id = 'an id';
        $model->created_at = null;
        $model->status = 'not valid';

        $errors = $model->validate();

        $this->assertSame(
            [
                'created_at' => [
                    'code' => Model::ERROR_REQUIRED,
                    'description' => 'Required `created_at` property is missing.',
                ],
                'status' => [
                    'code' => Model::ERROR_VALUE_INVALID,
                    'description' => '`status` property is invalid (not valid).',
                ],
            ],
            $errors
        );
    }

    public function validTypesProvider()
    {
        return [
            ['string'],
            ['integer'],
            ['datetime'],
            ['boolean'],
        ];
    }
}
