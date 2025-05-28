<?php

namespace AppTest\forms;

use Minz\Form;

class FormWithDatetime extends Form
{
    #[Form\Field]
    public \DateTimeImmutable $default_field_datetime;

    #[Form\Field(format: 'Y-m-d')]
    public \DateTimeImmutable $custom_field_datetime;
}
