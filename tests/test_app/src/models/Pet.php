<?php

namespace AppTest\models;

use Minz\Database;

#[Database\Table(name: 'pets')]
#[Database\Inheritance(column: 'type', classes: [
    'cat' => Cat::class,
    'dog' => Dog::class,
    'puppy' => Puppy::class,
])]
abstract class Pet
{
    use Database\Recordable;
    use Database\Resource;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public string $type = 'unset';

    #[Database\Column]
    public string $name;
}
