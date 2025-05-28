<?php

namespace AppTest\forms;

use Minz\Form;

class FormWithBool extends Form
{
    #[Form\Field]
    public bool $param_bool;
}
