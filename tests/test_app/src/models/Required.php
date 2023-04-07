<?php

namespace AppTest\models;

class Required extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    public ?string $id;
}
