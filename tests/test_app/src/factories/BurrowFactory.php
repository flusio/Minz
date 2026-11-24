<?php

namespace AppTest\factories;

use AppTest\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Burrow>
 *
 * @phpstan-import-type ModelValue from Database\Recordable
 * @phpstan-import-type ModelValues from Database\Recordable
 */
class BurrowFactory extends Database\Factory
{
    /** @var string[] */
    public static array $hooks_calls = [];

    /**
     * @return class-string<models\Burrow>
     */
    public static function model(): string
    {
        return models\Burrow::class;
    }

    /**
     * @return array<string, ModelValue|\Closure>
     */
    public static function values(): array
    {
        return [
            'address' => '42 rue du Terrier',
            'depth' => 1,
        ];
    }

    /**
     * @param ModelValues $values
     *
     * @return ModelValues
     */
    protected static function beforeBuild(array $values): array
    {
        self::$hooks_calls[] = 'beforeBuild';
        return $values;
    }

    protected static function afterBuild(object $burrow): void
    {
        self::$hooks_calls[] = 'afterBuild';
    }

    protected static function beforeCreate(object $burrow): void
    {
        self::$hooks_calls[] = 'beforeCreate';
        $burrow->depth += 10;
    }

    protected static function afterCreate(object $burrow): void
    {
        self::$hooks_calls[] = 'afterCreate';
    }
}
