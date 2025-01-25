<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a property value matches the given comparisons.
 *
 * The value can be compared with the given options:
 *
 * - greater: the value must be greater than the given value
 * - greater_or_equal: the value must be greater or equal than the given value
 * - equal: the value must be equal to the given value
 * - less: the value must be less than the given value
 * - less_or_equal: the value must be less or equal than the given value
 * - other: the value must be different than the given value
 *
 * The message accepts the {value}, {greater}, {greater_or_equal}, {equal},
 * {less}, {less_or_equal} and {other} placeholders. They will be replaced by
 * their real values in the final message.
 *
 *     use Minz\Validable;
 *
 *     class Payment
 *     {
 *         use Validable;
 *
 *         #[Validable\Comparison(
 *             greater_or_equal: 10,
 *             message: 'The price must be greater than or equal to {greater_or_equal} €.'
 *         )
 *         public string $price;
 *     }
 *
 * Note that the "null" value is considered as valid in order to accept
 * optional values.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Comparison extends Check
{
    public function __construct(
        string $message,
        public mixed $greater = null,
        public mixed $greater_or_equal = null,
        public mixed $equal = null,
        public mixed $less = null,
        public mixed $less_or_equal = null,
        public mixed $other = null,
    ) {
        parent::__construct($message);
    }

    public function assert(): bool
    {
        $value = $this->getValue();

        if ($value === null) {
            return true;
        }

        if ($this->greater !== null && $value <= $this->greater) {
            return false;
        }

        if ($this->greater_or_equal !== null && $value < $this->greater_or_equal) {
            return false;
        }

        if ($this->equal !== null && $value !== $this->equal) {
            return false;
        }

        if ($this->less !== null && $value >= $this->less) {
            return false;
        }

        if ($this->less_or_equal !== null && $value > $this->less_or_equal) {
            return false;
        }

        if ($this->other !== null && $value === $this->other) {
            return false;
        }

        return true;
    }

    public function getMessage(): string
    {
        $value = $this->getValue();

        return $this->formatMessage(
            $this->message,
            [
                '{value}',
                '{greater}',
                '{greater_or_equal}',
                '{equal}',
                '{less}',
                '{less_or_equal}',
                '{other}',
            ],
            [
                $value,
                $this->greater,
                $this->greater_or_equal,
                $this->equal,
                $this->less,
                $this->less_or_equal,
                $this->other,
            ],
        );
    }
}
