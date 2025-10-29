<?php

namespace AppTest\models;

use Minz\Database;

#[Database\Table(name: 'rabbits')]
class Rabbit
{
    use Database\Recordable;
    use Database\Resource;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public string $name;

    #[Database\Column]
    public int $friend_id;
}
