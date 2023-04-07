<?php

namespace AppTest\models;

class FormattedDateTime extends \Minz\Model
{
    public const PROPERTIES = [
        'created_at' => [
            'type' => 'datetime',
            'format' => 'Y-m-d',
        ],
    ];

    public $created_at;
}
