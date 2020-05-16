<?php

namespace Minz\Errors;

/**
 * Exception raised for erroneous property declaration.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ModelPropertyError extends \LogicException
{
    private $property;

    public function __construct($property, $code, $message)
    {
        parent::__construct($message);
        $this->property = $property;
        $this->code = $code;
    }

    public function property()
    {
        return $this->property;
    }
}
