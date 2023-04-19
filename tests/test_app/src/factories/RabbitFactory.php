<?php

namespace AppTest\factories;

use AppTest\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Rabbit>
 *
 * @phpstan-import-type ModelValue from Database\Recordable
 */
class RabbitFactory extends Database\Factory
{
    /**
     * @return class-string
     */
    public static function model(): string
    {
        return models\Rabbit::class;
    }

    /**
     * @return array<string, ModelValue|\Closure>
     */
    public static function values(): array
    {
        return [
            'name' => 'Albert',

            'id' => Database\Factory::sequence('rabbit 1', function ($value) {
                list($name, $count) = explode(' ', $value);
                $new_count = intval($count) + 1;
                return "{$name} {$new_count}";
            }),

            'friend_id' => function () {
                return FriendFactory::create()->id;
            },
        ];
    }
}
