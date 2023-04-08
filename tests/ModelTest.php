<?php

namespace Minz;

use PHPUnit\Framework\TestCase;
use AppTest\models;

class ModelTest extends TestCase
{
    public function testPropertyDeclarations(): void
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
                'computed' => false,
            ],
        ], $property_declarations);
    }

    public function testPropertyDeclarationsWithValidTypes(): void
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
                'computed' => false,
            ],
            'string' => [
                'type' => 'string',
                'required' => false,
                'computed' => false,
            ],
            'datetime' => [
                'type' => 'datetime',
                'required' => false,
                'computed' => false,
                'format' => Model::DATETIME_FORMAT,
            ],
            'boolean' => [
                'type' => 'boolean',
                'required' => false,
                'computed' => false,
            ],
        ], $property_declarations);
    }

    public function testPropertyDeclarationsFailsIfPropertyTypeIsNotSupported(): void
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Model::ERROR_PROPERTY_TYPE_INVALID);
        $this->expectExceptionMessage('`not a type` is not a valid property type.');

        Model::declareProperties(
            models\BadType::class,
            models\BadType::PROPERTIES
        );
    }

    public function testToValues(): void
    {
        $model = new models\SimpleId();
        $model->id = 42;

        $values = $model->toValues();

        $this->assertSame(42, $values['id']);
    }

    public function testToValuesWithUnsetValue(): void
    {
        $model = new models\SimpleId();

        $values = $model->toValues();

        $this->assertNull($values['id']);
    }

    public function testToValuesWithUndeclaredProperty(): void
    {
        $model = new models\SimpleId();
        $model->foo = 'bar';

        $values = $model->toValues();

        $this->assertArrayNotHasKey('foo', $values);
    }

    public function testToValuesWithDatetimeProperty(): void
    {
        $model = new models\SimpleDateTime();
        $created_at = new \DateTime();
        $model->created_at = $created_at;

        $values = $model->toValues();

        $this->assertSame($created_at->format(Model::DATETIME_FORMAT), $values['created_at']);
    }

    public function testToValuesWithDatetimePropertyAndCustomFormat(): void
    {
        $model = new models\FormattedDateTime();
        $created_at = new \DateTime();
        $model->created_at = $created_at;

        $values = $model->toValues();

        $this->assertSame($created_at->format('Y-m-d'), $values['created_at']);
    }

    public function testToValuesWithUnsetDatetimeProperty(): void
    {
        $model = new models\SimpleDateTime();

        $values = $model->toValues();

        $this->assertNull($values['created_at']);
    }

    public function testToValuesWithComputedProperty(): void
    {
        $model = new models\Computed();
        $model->count = 42;

        $values = $model->toValues();

        $this->assertSame([], $values);
    }

    public function testToValuesWithTrueBooleanProperty(): void
    {
        $model = new models\ValidPropertyTypes();
        $model->boolean = true;

        $values = $model->toValues();

        $this->assertSame(1, $values['boolean']);
    }

    public function testToValuesWithFalseBooleanProperty(): void
    {
        $model = new models\ValidPropertyTypes();
        $model->boolean = false;

        $values = $model->toValues();

        $this->assertSame(0, $values['boolean']);
    }

    public function testFromValuesWithString(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['string' => 'foo']);

        $this->assertSame('foo', $model->string);
    }

    public function testFromValuesWithInteger(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['integer' => '42']);

        $this->assertSame(42, $model->integer);
    }

    public function testFromValuesWithIntegerZero(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['integer' => '0']);

        $this->assertSame(0, $model->integer);
    }

    public function testFromValuesWithBoolean(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['boolean' => 1]);

        $this->assertTrue($model->boolean);
    }

    public function testFromValuesWithBooleanFalse(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['boolean' => 0]);

        $this->assertFalse($model->boolean);
    }

    public function testFromValuesWithDatetime(): void
    {
        $model = new models\ValidPropertyTypes();
        $datetime = new \DateTime();

        $model->fromValues(['datetime' => $datetime]);

        $this->assertEquals($datetime, $model->datetime);
    }

    public function testFromValuesWithIso8601(): void
    {
        $model = new models\ValidPropertyTypes();
        $datetime = new \DateTime();
        $iso_8601 = $datetime->format(Model::DATETIME_FORMAT);

        $model->fromValues(['datetime' => $iso_8601]);

        // we must compare timestamps because ISO-8601 lose the microseconds
        // and so the two DateTime are, in fact, different.
        /** @var \DateTime $model_datetime */
        $model_datetime = $model->datetime;
        $this->assertEquals($datetime->getTimestamp(), $model_datetime->getTimestamp());
    }

    public function testFromValuesWithCustomFormat(): void
    {
        $model = new models\FormattedDateTime();
        $datetime = new \DateTime('2020-01-20');
        $formatted_datetime = $datetime->format('Y-m-d');

        $model->fromValues(['created_at' => $formatted_datetime]);

        /** @var \DateTime $created_at */
        $created_at = $model->created_at;
        $this->assertEquals($formatted_datetime, $created_at->format('Y-m-d'));
    }

    public function testFromValuesWhenIntegerTypeDoesNotMatch(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['integer' => 'not an integer']);

        $this->assertSame('not an integer', $model->integer);
    }

    public function testFromValuesWhenDatetimeTypeDoesNotMatch(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['datetime' => 'not a datetime']);

        $this->assertSame('not a datetime', $model->datetime);
    }

    public function testFromValuesWhenBooleanTypeDoesNotMatch(): void
    {
        $model = new models\ValidPropertyTypes();

        $model->fromValues(['boolean' => 'not a boolean']);

        $this->assertSame('not a boolean', $model->boolean);
    }

    public function testFromValuesWithComputedProperty(): void
    {
        $model = new models\Computed();

        $model->fromValues(['count' => '42']);

        $this->assertSame(42, $model->count);
    }

    public function testFromValuesFailsIfUndeclaredProperty(): void
    {
        $this->expectException(Errors\ModelPropertyError::class);
        $this->expectExceptionCode(Model::ERROR_PROPERTY_UNDECLARED);
        $this->expectExceptionMessage(
            '`not_a_property` property has not been declared.'
        );

        $model = new models\ValidPropertyTypes();

        $model->fromValues(['not_a_property' => 'foo']);
    }
}
