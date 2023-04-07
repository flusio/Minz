<?php

namespace AppTest\models;

class Computed extends \Minz\Model
{
    public const PROPERTIES = [
        'count' => [
            'type' => 'integer',
            'computed' => true,
        ],
    ];

    public $count;
}
