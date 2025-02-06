<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a string property is a valid email address.
 *
 * Note that it only checks the format of the string, not that the email really
 * exists!
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Email(message: 'Enter a valid email address.')]
 *         public string $email;
 *     }
 *
 * By default, the email is checked with the PHP filter_var() function. You can
 * pass "loose" as $mode parameter to check the email with a simpler regex
 * expression.
 *
 * Note that the "null" and empty values are considered as valid in order to
 * accept optional values.
 *
 * @phpstan-import-type EmailMode from \Minz\Email
 *
 * @see \Minz\Email::validate
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Email extends Check
{
    /** @var EmailMode */
    public string $mode;

    /**
     * @param EmailMode $mode
     */
    public function __construct(string $message, string $mode = 'php')
    {
        parent::__construct($message);
        $this->mode = $mode;
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

        return \Minz\Email::validate($value, $this->mode);
    }
}
