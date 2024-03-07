<?php

namespace AppTest\forms;

use AppTest\models;
use Minz\Form;

/**
 * @phpstan-extends Form<models\Rabbit>
 */
class Rabbit extends Form
{
    use Form\Csrf;

    #[Form\Field(trim: true)]
    public string $name;

    #[Form\Field]
    public int $friend_id;
}
