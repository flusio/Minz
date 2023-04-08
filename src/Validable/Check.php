<?php

namespace Minz\Validable;

/**
 * The base class for various checks that are validable with the Validable
 * trait.
 *
 * It cannot be used directly.
 *
 * The child classes must be declared as being usable as PHP Attribute. They
 * also have to implements the `assert()` method:
 *
 *     #[\Attribute(\Attribute::TARGET_PROPERTY)]
 *     class MyCheck extends \Minz\Check
 *     {
 *         public function assert(): bool
 *         {
 *             // check the value of the property and return a boolean
 *         }
 *     }
 *
 * The property value is accessible with the `getValue()` method.
 * The property itself is accessible as a `ReflectionProperty` with
 * `$this->property` while the instance of the object is accessible with
 * `$this->instance`.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Check
{
    public string $message = 'Invalid value';

    public \ReflectionProperty $property;

    public object $instance;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function assert(): bool
    {
        throw new \BadMethodCallException("Method assert must be implemented");
    }

    public function getValue(): mixed
    {
        return $this->property->getValue($this->instance);
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
