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

    public $status;

    public static function validateStatus($value)
    {
        return in_array($value, ['new', 'finished']);
    }
}
