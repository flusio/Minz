<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

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
 *     class MyCheck extends \Minz\PropertyCheck
 *     {
 *         public function assert(): bool
 *         {
 *             // check the value of the property and return a boolean
 *         }
 *     }
 *
 * The property value is accessible with the `value()` method.
 * The property itself is accessible as a `ReflectionProperty` with
 * `$this->property` while the instance of the object is accessible with
 * `$this->instance`.
 *
 * You can mark and translate the messages with the Translatable class.
 *
 *     use Minz\Translatable;
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Email(
 *             message: new Translatable('Enter a valid email address.'),
 *         )]
 *         public string $email;
 *     }
 *
 * @see \Minz\Validable
 * @see \Minz\Validable\Comparison
 * @see \Minz\Validable\Email
 * @see \Minz\Validable\Format
 * @see \Minz\Validable\Inclusion
 * @see \Minz\Validable\Length
 * @see \Minz\Validable\Presence
 * @see \Minz\Validable\Unique
 * @see \Minz\Validable\Url
 * @see \Minz\Translatable
 */
abstract class PropertyCheck
{
    public string $message = 'Invalid value';

    public \ReflectionProperty $property;

    public object $instance;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Assert that the value is valid.
     */
    abstract public function assert(): bool;

    /**
     * Return the value of the property concerned by this check.
     */
    public function value(): mixed
    {
        return $this->property->getValue($this->instance);
    }

    /**
     * Return the error code.
     *
     * It removes the current namespace from the check class, and change the
     * name to snake_case.
     *
     * The method can be overwritten in custom checks.
     */
    public function code(): string
    {
        $code = get_class($this);
        $code = str_replace(__NAMESPACE__ . '\\', '', $code);
        $code = preg_replace('/(?<!^)[A-Z]/', '_$0', $code);
        assert($code !== null);
        $code = strtolower($code);
        return $code;
    }

    /**
     * Return the formatted error message if the check doesn't assert.
     *
     * This can be overidden by child classes to adapt the message format by
     * calling `formatMessage` with different parameters.
     */
    public function message(): string
    {
        $value = $this->value();

        return $this->formatMessage(
            $this->message,
            ['{value}'],
            [$value],
        );
    }

    /**
     * Format a message by replacing the $search strings by the $replace values.
     *
     * @param string[] $search
     * @param mixed[] $replace
     */
    protected function formatMessage(string $message, array $search, array $replace): string
    {
        $replace = array_map(function ($value): string {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }

            if (is_object($value)) {
                if ($value instanceof \Stringable) {
                    return $value->__toString();
                }

                return 'object';
            }

            if (is_array($value)) {
                return 'array';
            }

            if (is_resource($value)) {
                return 'resource';
            }

            if ($value === null) {
                return 'null';
            }

            if ($value === false) {
                return 'false';
            }

            if ($value === true) {
                return 'true';
            }

            if (is_string($value) || is_integer($value) || is_float($value)) {
                return (string) $value;
            }

            return '';
        }, $replace);

        return str_replace(
            $search,
            $replace,
            $message,
        );
    }
}
