<?php

namespace AppTest\forms;

use Minz\Form;

class FormWithFile extends Form
{
    #[Form\Field]
    public \Minz\File $file;
}
