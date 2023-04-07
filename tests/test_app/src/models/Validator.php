<?php

namespace AppTest\models;

class Validator extends \Minz\Model
{
    public const PROPERTIES = [
        'status' => [
            'type' => 'string',
            'validator' => '\AppTest\models\Validator::validateStatus',
        ],
    ];

    public ?string $status;

    public static function validateStatus(string $value): bool
    {
        return in_array($value, ['new', 'finished']);
    }
}
