<?php

namespace AppTest\models;

class Friend extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],
        'created_at' => [
            'type' => 'datetime',
            'required' => true,
        ],
        'name' => [
            'type' => 'string',
            'validator' => '\AppTest\models\Friend::validateName',
        ],
    ];

    public static function validateName($value)
    {
        return $value === 'Alice';
    }
}
