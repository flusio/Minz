<?php

namespace AppTest\forms;

use Minz\Form;

/**
 * @phpstan-extends Form<\stdClass>
 */
class FormWithBool extends Form
{
    #[Form\Field]
    public bool $param_bool;
}
