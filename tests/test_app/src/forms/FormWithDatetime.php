<?php

namespace AppTest\forms;

use Minz\Form;

/**
 * @phpstan-extends Form<null>
 */
class FormWithDatetime extends Form
{
    #[Form\Field]
    public \DateTimeImmutable $default_field_datetime;

    #[Form\Field(format: 'Y-m-d')]
    public \DateTimeImmutable $custom_field_datetime;
}
