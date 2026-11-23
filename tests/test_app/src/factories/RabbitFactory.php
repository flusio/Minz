<?php

namespace AppTest\factories;

use AppTest\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Rabbit>
 *
 * @phpstan-import-type ModelValue from Database\Recordable
 * @phpstan-import-type ModelValues from Database\Recordable
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
            'id' => Database\Factory::sequence('rabbit 1', function ($value): string {
                [$name, $count] = explode(' ', $value);
                $new_count = intval($count) + 1;
                return "{$name} {$new_count}";
            }),

            'friend_id' => fn() => FriendFactory::create()->id,
        ];
    }

    /**
     * @param ModelValues $values
     *
     * @return ModelValues
     */
    protected static function beforeCreate(array $values): array
    {
        if (!array_key_exists('name', $values)) {
            $friend_id = $values['friend_id'];
            assert(is_int($friend_id));

            $friend = models\Friend::require($friend_id);
            $values['name'] = "Rabbit of {$friend->name}";
        }

        return $values;
    }

    protected static function afterCreate(object $rabbit): void
    {
        models\Friend::update($rabbit->friend_id, [
            'address' => "Burrow of {$rabbit->name}",
        ]);
    }
}
