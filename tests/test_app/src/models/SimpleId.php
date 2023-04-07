<?php

namespace AppTest\models;

class SimpleId extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => 'integer',
    ];

    public $id;

    public $foo;
}
