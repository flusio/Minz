<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a string property matches a given pattern.
 *
 * You must pass a Regex pattern to the check. It is then checked with the
 * PHP `preg_match` function.
 *
 * If the value is not a string, the check returns false.
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Format(pattern: '/^[\w]+$/', message: 'Choose a nickname that only contains letters.')]
 *         public string $nickname;
 *     }
 *
 * Note that the "null" and empty values are considered as valid in order to
 * accept optional values.
 *
 * @see https://www.php.net/manual/function.preg-match.php
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Format extends Check
{
    public string $pattern;

    public function __construct(string $message, string $pattern)
    {
        parent::__construct($message);
        $this->pattern = $pattern;
    }

    public function assert(): bool
    {
        $value = $this->getValue();

        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        return preg_match($this->pattern, $value) === 1;
    }
}
