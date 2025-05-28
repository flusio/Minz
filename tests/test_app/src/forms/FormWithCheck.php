<?php

namespace AppTest\forms;

use Minz\Form;
use Minz\Validable;

class FormWithCheck extends Form
{
    #[Form\Field]
    public string $name;

    #[Validable\Check]
    public function checkName(): void
    {
        if ($this->name !== 'Bugs') {
            $this->addError('name', 'checkName', 'Name must be equal to "Bugs"');
        }
    }
}
