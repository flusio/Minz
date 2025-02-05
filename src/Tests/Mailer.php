<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use Minz\Mailer\Email;

/**
 * Allow to access sent emails during tests.
 *
 * If configuration mailer type is `test`, the Email object is stored in the
 * \Minz\Tests\Mailer::$emails static attribute and can be accessed then to
 * test different values.
 */
class Mailer
{
    /** @var Email[] */
    public static array $emails = [];

    /**
     * Store an email object in $emails.
     */
    public static function store(Email $email): void
    {
        self::$emails[] = clone($email);
    }

    /**
     * Clear the list of emails.
     */
    public static function clear(): void
    {
        self::$emails = [];
    }

    /**
     * Return the number of sent emails.
     */
    public static function count(): int
    {
        return count(self::$emails);
    }

    /**
     * Return the $n email.
     */
    public static function take(int $n = 0): ?Email
    {
        if (isset(self::$emails[$n])) {
            return self::$emails[$n];
        } else {
            return null;
        }
    }
}
