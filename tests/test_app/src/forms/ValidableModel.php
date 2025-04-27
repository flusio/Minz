<?php

namespace AppTest\forms;

use AppTest\models;
use Minz\Form;

/**
 * @phpstan-extends Form<models\ValidableModel>
 */
class ValidableModel extends Form
{
    #[Form\Field]
    public string $nickname;

    #[Form\Field]
    public int $email;
}
