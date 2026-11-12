<?php

namespace AppTest\forms;

use Minz\Form;

class FormWithNullableString extends Form
{
    #[Form\Field(invalid_message: 'Must be a string.')]
    public ?string $param_string = 'foo';

    #[Form\Field(transform: '\AppTest\forms\FormWithNullableString::trimString')]
    public ?string $param_trimmed_string = 'foo';

    #[Form\Field(transform: '\AppTest\forms\FormWithNullableString::defaultString')]
    public ?string $param_defaulted_string = 'foo';

    public static function trimString(string $value): string
    {
        return trim($value);
    }

    public static function defaultString(?string $value): string
    {
        return $value ?? 'bar';
    }
}
