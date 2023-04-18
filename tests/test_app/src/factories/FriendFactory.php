<?php

namespace AppTest\factories;

use AppTest\models;
use Minz\Database\Factory;

/**
 * @extends Factory<models\Friend>
 */
class FriendFactory extends Factory
{
    public static function model(): string
    {
        return models\Friend::class;
    }

    public static function values(): array
    {
        return [
            'name' => 'Alix',
            'options' => ['pet' => true],
        ];
    }
}
