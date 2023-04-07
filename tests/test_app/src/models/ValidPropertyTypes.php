<?php

namespace AppTest\models;

class ValidPropertyTypes extends \Minz\Model
{
    public const PROPERTIES = [
        'integer' => 'integer',
        'string' => 'string',
        'datetime' => 'datetime',
        'boolean' => 'boolean',
    ];

    public $integer;
    public $string;
    public $datetime;
    public $boolean;
}
