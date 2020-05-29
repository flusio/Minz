<?php

namespace AppTest\models;

class BadValidator extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
            'validator' => 'not_callable',
        ],
    ];
}
