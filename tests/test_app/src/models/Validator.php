<?php

namespace AppTest\models;

class Validator extends \Minz\Model
{
    public const PROPERTIES = [
        'status' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\AppTest\models\Validator::validateStatus',
        ],
    ];

    public static function validateStatus($value)
    {
        return in_array($value, ['new', 'finished']);
    }
}
