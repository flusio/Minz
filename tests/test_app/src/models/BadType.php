<?php

namespace AppTest\models;

class BadType extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => 'not a type',
    ];
}
