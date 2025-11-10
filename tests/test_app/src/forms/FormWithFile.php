<?php

namespace AppTest\forms;

use Minz\Form;
use Minz\Validable;

class FormWithFile extends Form
{
    #[Form\Field]
    #[Validable\File(
        max_size: '1K',
        max_size_message: 'File cannot exceed {max_size}.',
        types: [
            'txt' => ['plain/text'],
        ],
        types_message: 'File type must be: {types}.',
        message: 'File cannot be uploaded (error {code}).',
    )]
    public \Minz\File $file;
}
