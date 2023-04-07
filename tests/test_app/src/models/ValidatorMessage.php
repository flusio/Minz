<?php

namespace AppTest\models;

class ValidatorMessage extends \Minz\Model
{
    public const PROPERTIES = [
        'status' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\AppTest\models\ValidatorMessage::validateStatus',
        ],
    ];

    public ?string $status;

    public static function validateStatus(string $value): mixed
    {
        if (in_array($value, ['new', 'finished'])) {
            return true;
        } else {
            return 'must be either new or finished';
        }
    }
}
