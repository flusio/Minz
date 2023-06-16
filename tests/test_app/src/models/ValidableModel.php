<?php

namespace AppTest\models;

use Minz\Validable;

class ValidableModel
{
    use Validable;

    #[Validable\Presence(message: 'Choose a nickname.')]
    #[Validable\Length(min: 2, max: 42, message: 'Choose a nickname between {min} and {max} characters.')]
    #[Validable\Format(pattern: '/^[\w]*$/', message: 'Choose a nickname that only contains letters.')]
    public string $nickname;

    #[Validable\Email(message: 'Choose a valid email.')]
    public ?string $email = null;

    #[Validable\Url(message: 'Choose a valid URL.')]
    public ?string $website = null;

    #[Validable\Inclusion(in: ['admin', 'user'], message: 'Choose a valid role.')]
    public ?string $role = null;

    #[Validable\Comparison(greater: 42, message: 'Must be greater than {greater}')]
    public ?int $greater = null;

    #[Validable\Comparison(greater_or_equal: 42, message: 'Must be greater than or equal to {greater_or_equal}')]
    public ?int $greater_or_equal = null;

    #[Validable\Comparison(equal: 42, message: 'Must be equal to {equal}')]
    public ?int $equal = null;

    #[Validable\Comparison(less: 42, message: 'Must be less than {less}')]
    public ?int $less = null;

    #[Validable\Comparison(less_or_equal: 42, message: 'Must be less than or equal to {less_or_equal}')]
    public ?int $less_or_equal = null;

    #[Validable\Comparison(other: 42, message: 'Must be other than {other}')]
    public ?int $other = null;
}
