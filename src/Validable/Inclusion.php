<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a property value is part of a given set.
 *
 * The message accepts the {value} placeholder. It will be replaced by the
 * real value in the final message.
 *
 *     use Minz\Validable;
 *
 *     class Message
 *     {
 *         use Validable;
 *
 *         #[Validable\Inclusion(in: ['request', 'incident'], message: '{value} is not a valid message type.')]
 *         public string $type;
 *     }
 *
 * By default, the value is checked against the values of the $in array. You
 * can pass "keys" as $mode parameter to check against the keys of the array.
 *
 * Note that the "null" value is considered as valid in order to accept
 * optional values.
 *
 * @phpstan-type InclusionMode 'values'|'keys'
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Inclusion extends PropertyCheck
{
    /** @var mixed[] */
    public array $in;

    /** @var InclusionMode */
    public string $mode;

    /**
     * @param mixed[] $in
     * @param InclusionMode $mode
     */
    public function __construct(string $message, array $in, string $mode = 'values')
    {
        parent::__construct($message);
        $this->in = $in;
        $this->mode = $mode;
    }

    public function assert(): bool
    {
        $value = $this->value();

        if ($value === null) {
            return true;
        }

        if ($this->mode === 'values') {
            $accepted_values = array_values($this->in);
        } else {
            $accepted_values = array_keys($this->in);
        }

        return in_array($value, $accepted_values);
    }
}
