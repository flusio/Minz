<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

/**
 * Allow to create objects in database with default values.
 *
 * During tests, it is often needed to create objects to test a behaviour that
 * depends on existing data. It makes the tests harder to write and read
 * because database constraints imply to set data that are not important to the
 * current tested code.
 *
 * The Database\Factory class allows to setup default values when creating such
 * objects.
 *
 * A factory can be declared by extending the class and implementing both
 * values() and model() methods:
 *
 *     use Minz\Database\Factory;
 *
 *     class RabbitFactory extends Factory
 *     {
 *         public static function model(): string
 *         {
 *             return \App\models\Rabbit::class;
 *         }
 *
 *         public static function values(): array
 *         {
 *             return [
 *                 'name' => 'Bugs',
 *                 'age' => 1,
 *             ];
 *         }
 *     }
 *
 * The model() method must return the name of a class implementing the
 * Database\Recordable trait.
 *
 * The values() method must return the list of columns that you want to give a
 * default value.
 *
 * Then, you can use it directly:
 *
 *     $rabbit = RabbitFactory::create();
 *
 *     $this->assertSame('Bugs', $rabbit->name);
 *     $this->assertSame(1, $rabbit->age);
 *
 * The create method takes values that override the default ones. It allows to
 * set data that are really important while letting uninteresting database
 * constraints to the factory. It just merges default values with the given
 * ones and delegate the creation to the Recordable class.
 *
 *     $rabbit = RabbitFactory::create([
 *         'age' => 2,
 *     ]);
 *
 *     $this->assertSame(2, $rabbit->age);
 *
 * A default value can contain a callable function to be executed at the
 * runtime. It is useful to create references to other factories:
 *
 *     class FriendFactory extends Factory
 *     {
 *         public static function model(): string
 *         {
 *             return \App\models\Friend::class;
 *         }
 *
 *         public static function values(): array
 *         {
 *             return [
 *                 'name' => 'Martin',
 *                 'rabbit_id' => function () {
 *                     return RabbitFactory::create()->id;
 *                 },
 *             ];
 *         }
 *     }
 *
 * If you need sequential values, you can use the sequence() method. It is
 * especially useful if you need to generate unique values.
 *
 *     class SequenceFactory extends Factory
 *     {
 *         public static function model(): string
 *         {
 *             return \App\models\SomeModel::class;
 *         }
 *
 *         public static function values(): array
 *         {
 *             return [
 *                 'int_sequence' => Factory::sequence(),
 *                 'string_sequence' => Factory::sequence('a'),
 *                 'custom_sequence' => Factory::sequence(1, function ($n) {
 *                     return $n * 2;
 *                 }),
 *             ];
 *         }
 *     }
 *
 * If you need to build a model (i.e. without saving it in database), use the
 * build() method. It accepts the same values as create():
 *
 *     $rabbit = RabbitFactory::build([
 *         'age' => 2,
 *     ]);
 *
 *     $this->assertFalse($rabbit->isPersisted());
 *
 * Note that the callables of the default values are still executed, so they
 * may create other models in database.
 *
 * By default, the models are instantiated without calling their constructor.
 * If a model must be instantiated with its constructor, you can override the
 * instantiate() method:
 *
 *     protected static function instantiate(array $values): object
 *     {
 *         $user = new \App\models\User($values['email'], $values['password']);
 *         $user->nickname = $values['nickname'];
 *         return $user;
 *     }
 *
 * You can also override the following hooks:
 *
 * - beforeBuild() to change the values before building the model;
 * - afterBuild() to act on the model once it is built;
 * - beforeCreate() to act on the built model before creating it in database;
 * - afterCreate() to act on the model once it is created in database.
 *
 *     class RabbitFactory extends Factory
 *     {
 *         public static function model(): string
 *         {
 *             return \App\models\Rabbit::class;
 *         }
 *
 *         public static function values(): array
 *         {
 *             return [
 *                 'friend_id' => function () {
 *                     return FriendFactory::create()->id;
 *                 },
 *             ];
 *         }
 *
 *         protected static function beforeBuild(array $values): array
 *         {
 *             if (!array_key_exists('name', $values)) {
 *                 $friend = \App\models\Friend::require($values['friend_id']);
 *                 $values['name'] = "Rabbit of {$friend->name}";
 *             }
 *
 *             return $values;
 *         }
 *
 *         protected static function afterBuild(object $rabbit): void
 *         {
 *             $rabbit->nickname = strtolower($rabbit->name);
 *         }
 *
 *         protected static function beforeCreate(object $rabbit): void
 *         {
 *             $rabbit->created_at = \Minz\Time::now();
 *         }
 *
 *         protected static function afterCreate(object $rabbit): void
 *         {
 *             CarrotFactory::create([
 *                 'rabbit_id' => $rabbit->id,
 *             ]);
 *         }
 *     }
 *
 * General recommendations when using factories are:
 *
 * - to give default values only for required data (e.g. for columns that have
 *   a NOT NULL constraint, or which must be unique)
 * - to never relies on default values in your tests. Or, in other words, to
 *   always make explicit the values you are testing
 *
 * @template TModel of object
 *
 * @phpstan-import-type ModelValue from Recordable
 * @phpstan-import-type ModelValues from Recordable
 *
 * @phpstan-type FactoryValues array<string, ModelValue|\Closure>
 */
abstract class Factory
{
    /**
     * We need to store the default values of the factories in a static
     * variable. This is because the sequence() method keeps an internal state.
     * If we didn't do that, the sequence() method would be reset each time we
     * call create() and thus, the same value would be generated each time.
     *
     * @var array<string, FactoryValues>
     */
    private static array $default_values = [];

    /**
     * Build a model with the default values, without saving it in database.
     *
     * Note that the default values can still create other models in database
     * (e.g. if they are callables calling other factories).
     *
     * @param ModelValues $values
     *
     * @return TModel
     *
     * @phpstan-impure
     */
    public static function build(array $values = []): object
    {
        $factory_class_name = static::class;

        if (isset(self::$default_values[$factory_class_name])) {
            $default_values = self::$default_values[$factory_class_name];
        } else {
            $default_values = static::values();
            self::$default_values[$factory_class_name] = $default_values;
        }

        foreach ($default_values as $property => $value) {
            if (is_callable($value) && !array_key_exists($property, $values)) {
                $value = $value();
            }
            $default_values[$property] = $value;
        }

        /** @var ModelValues $values */
        $values = array_merge($default_values, $values);
        $values = static::beforeBuild($values);

        $model = static::instantiate($values);

        static::afterBuild($model);

        return $model;
    }

    /**
     * Build a model with the default values, and create it in database.
     *
     * @param ModelValues $values
     *
     * @return TModel
     *
     * @phpstan-impure
     */
    public static function create(array $values = []): object
    {
        $model = static::build($values);

        static::beforeCreate($model);

        assert(method_exists($model, 'toValues'));

        $model_class_name = $model::class;
        $pk_value = $model_class_name::create($model->toValues());

        $model = $model_class_name::require($pk_value);

        static::afterCreate($model);

        return $model;
    }

    /**
     * Instantiate the model from the given values.
     *
     * By default, the model constructor is not called. You can override this
     * method if your model must be instantiated with its constructor.
     *
     * @param ModelValues $values
     *
     * @return TModel
     */
    protected static function instantiate(array $values): object
    {
        $model_class_name = static::model();

        assert(method_exists($model_class_name, 'fromValues'));

        return $model_class_name::fromValues($values);
    }

    /**
     * Change the values before building the model.
     *
     * @param ModelValues $values
     *
     * @return ModelValues
     */
    protected static function beforeBuild(array $values): array
    {
        return $values;
    }

    /**
     * Act on the model once it is built.
     *
     * @param TModel $model
     */
    protected static function afterBuild(object $model): void
    {
    }

    /**
     * Act on the built model before creating it in database.
     *
     * @param TModel $model
     */
    protected static function beforeCreate(object $model): void
    {
    }

    /**
     * Act on the model once it is created in database.
     *
     * @param TModel $model
     */
    protected static function afterCreate(object $model): void
    {
    }

    /**
     * Return a callable function that generates a sequence of values.
     *
     * @param integer|string $value
     *     The initial value of the sequence
     * @param ?callable $callback
     *     A function to transform the value (default is incrementing)
     */
    public static function sequence(mixed $value = 1, ?callable $callback = null): \Closure
    {
        $callback ??= function (int|string $n): int|string {
            $n++;
            return $n;
        };

        return function () use (&$value, $callback) {
            $current_value = $value;
            $value = $callback($value);
            return $current_value;
        };
    }

    /**
     * @return FactoryValues
     */
    abstract public static function values(): array;

    /**
     * @return class-string<TModel>
     */
    abstract public static function model(): string;
}
