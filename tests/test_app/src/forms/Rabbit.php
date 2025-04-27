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

    #[Form\Field(transform: '\AppTest\forms\Rabbit::transformName')]
    public string $name;

    #[Form\Field]
    public int $friend_id;

    public static function transformName(string $name): string
    {
        return trim($name);
    }
}
