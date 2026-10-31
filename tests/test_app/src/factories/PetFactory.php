<?php

namespace AppTest\factories;

use AppTest\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Pet>
 *
 * @phpstan-import-type ModelValue from Database\Recordable
 */
class PetFactory extends Database\Factory
{
    /**
     * @return class-string
     */
    public static function model(): string
    {
        return models\Pet::class;
    }

    /**
     * @return array<string, ModelValue|\Closure>
     */
    public static function values(): array
    {
        return [
            'type' => 'cat',
            'name' => 'Méli',
        ];
    }
}
