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

    public mixed $integer;
    public mixed $string;
    public mixed $datetime;
    public mixed $boolean;
}
