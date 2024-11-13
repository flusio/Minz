<?php

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
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
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
     * @param ModelValues $values
     *
     * @return TModel
     */
    public static function create(array $values = []): object
    {
        $factory_class_name = static::class;
        $model_class_name = static::model();

        if (isset(self::$default_values[$factory_class_name])) {
            $default_values = self::$default_values[$factory_class_name];
        } else {
            $default_values = static::values();
            self::$default_values[$factory_class_name] = $default_values;
        }

        foreach ($default_values as $property => $value) {
            if (is_callable($value) && !isset($values[$property])) {
                $value = $value();
            }
            $default_values[$property] = $value;
        }

        $values = array_merge($default_values, $values);

        $pk_value = $model_class_name::create($values);

        return $model_class_name::find($pk_value);
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
        if ($callback === null) {
            $callback = function (int|string $n): int|string {
                $n++;
                return $n;
            };
        }

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
     * @return class-string
     */
    abstract public static function model(): string;
}
