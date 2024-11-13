<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use PHPMailer\PHPMailer;

/**
 * Allow to access sent emails during tests. If configuration mailer type is
 * `test`, the PHPMailer object is stored in the \Minz\Tests\Mailer::$emails
 * static attribute and can be accessed then to test different values.
 */
class Mailer
{
    /** @var PHPMailer\PHPMailer[] */
    public static array $emails = [];

    /**
     * Store a PHPMailer object in $emails.
     */
    public static function store(PHPMailer\PHPMailer $phpmailer): void
    {
        self::$emails[] = clone($phpmailer);
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
    public static function take(int $n = 0): ?PHPMailer\PHPMailer
    {
        if (isset(self::$emails[$n])) {
            return self::$emails[$n];
        } else {
            return null;
        }
    }
}
