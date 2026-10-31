<?php

namespace AppTest\models;

use Minz\Database;

class Cat extends Pet
{
    #[Database\Column]
    public int $lives = 9;
}
