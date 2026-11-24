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
class ConstructedBurrowFactory extends Database\Factory
{
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
        ];
    }

    /**
     * @param ModelValues $values
     */
    protected static function instantiate(array $values): object
    {
        $address = $values['address'];
        assert(is_string($address));

        return new models\Burrow($address);
    }
}
