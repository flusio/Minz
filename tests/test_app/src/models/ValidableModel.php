<?php

namespace AppTest\models;

use Minz\Validable;

class ValidableModel
{
    use Validable;

    #[Validable\Presence(message: 'Choose a nickname.')]
    #[Validable\Length(max: 42, message: 'Choose a nickname of less than {max} characters.')]
    #[Validable\Format(pattern: '/^[\w]*$/', message: 'Choose a nickname that only contains letters.')]
    public string $nickname;
}
