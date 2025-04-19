<?php

namespace AppTest\models;

use Minz\Database;
use Minz\Validable;

#[Database\Table(name: 'validable_unique_models')]
class ValidableUniqueModel
{
    use Database\Recordable;
    use Validable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    #[Validable\Unique(message: '"{value}" is already taken.')]
    public ?string $email = null;
}
