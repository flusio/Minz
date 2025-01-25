<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
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
 */
abstract class Check
{
    public string $message = 'Invalid value';

    public \ReflectionProperty $property;

    public object $instance;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    abstract public function assert(): bool;

    public function getValue(): mixed
    {
        return $this->property->getValue($this->instance);
    }

    public function getMessage(): string
    {
        $value = $this->getValue();

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
