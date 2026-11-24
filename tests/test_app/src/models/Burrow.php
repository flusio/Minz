<?php

namespace AppTest\models;

use Minz\Database;

#[Database\Table(name: 'burrows')]
class Burrow
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public string $address;

    #[Database\Column]
    public int $depth;

    public function __construct(string $address)
    {
        $this->address = $address;
        $this->depth = 3;
    }
}
