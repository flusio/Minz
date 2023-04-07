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

    public ?string $id;
    public ?\DateTime $created_at;
    public ?string $name;

    public static function validateName(string $value): bool
    {
        return $value === 'Alice';
    }
}
