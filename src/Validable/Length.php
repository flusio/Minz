<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a property length is greater or less than specified limits.
 *
 * You can specify both a minimum and a maximum length, or just one of the two.
 *
 * The message accepts the {min}, {max} and {length} placeholders. They will be
 * replaced by their real values in the final message.
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Length(max: 42, message: 'Choose a nickname of less than {max} characters.')]
 *         public string $nickname;
 *     }
 *
 * The check works with any type of value (integers as well), but note that the
 * string representation will be used. For instance the length of the number 42
 * is 2.
 *
 * Note that the "null" and empty values are considered as valid in order to
 * accept optional values.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Length extends PropertyCheck
{
    public ?int $min;

    public ?int $max;

    public function __construct(string $message, ?int $min = null, ?int $max = null)
    {
        parent::__construct($message);
        $this->min = $min;
        $this->max = $max;
    }

    public function assert(): bool
    {
        $value = $this->value();

        if ($value === null || $value === '') {
            return true;
        }

        $length = $this->length();

        if ($this->min !== null && $length < $this->min) {
            return false;
        }

        if ($this->max !== null && $length > $this->max) {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        $value = $this->value();

        $length = $this->length();

        return $this->formatMessage(
            $this->message,
            ['{value}', '{min}', '{max}', '{length}'],
            [$value, $this->min, $this->max, $length],
        );
    }

    private function length(): int
    {
        $value = $this->value();

        if ($value === null) {
            return 0;
        }

        if (!is_float($value) && !is_integer($value) && !is_string($value)) {
            return 0;
        }

        return mb_strlen(strval($value));
    }
}
