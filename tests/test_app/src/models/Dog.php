<?php

namespace AppTest\models;

use Minz\Database;

class Dog extends Pet
{
    #[Database\Column]
    public bool $is_playful = true;
}
