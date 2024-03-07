<?php

namespace AppTest\forms;

use Minz\Form;

/**
 * @phpstan-extends Form<\stdClass>
 */
class FormWithCheck extends Form
{
    #[Form\Field]
    public string $name;

    #[Form\Check]
    public function checkName(): void
    {
        if ($this->name !== 'Bugs') {
            $this->addError('name', 'Name must be equal to "Bugs"');
        }
    }
}
