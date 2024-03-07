<?php

namespace AppTest\forms;

use Minz\Form;

/**
 * @phpstan-extends Form<\stdClass>
 */
class FormWithDatetime extends Form
{
    #[Form\Field(format: 'Y-m-d')]
    public \DateTimeImmutable $param_datetime;
}
