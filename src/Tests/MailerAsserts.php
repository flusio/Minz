<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use PHPMailer\PHPMailer;

/**
 * Provide some assert methods to help to test the mailer.
 *
 * Some assertions expect a $mailer to be given. One can be fetched with
 * \Minz\Tests\Mailer::take().
 *
 * @see \Minz\Mailer
 * @see \Minz\Tests\Mailer
 */
trait MailerAsserts
{
    /**
     * Assert that $count emails have been sent.
     */
    public function assertEmailsCount(int $count): void
    {
        $this->assertSame($count, \Minz\Tests\Mailer::count());
    }

    /**
     * Assert a mailer to declare the given subject.
     */
    public function assertEmailSubject(PHPMailer\PHPMailer $mailer, string $subject): void
    {
        $this->assertSame($subject, $mailer->Subject);
    }

    /**
     * Assert a mailer to declare the given "from" email.
     */
    public function assertEmailFrom(PHPMailer\PHPMailer $mailer, string $from): void
    {
        $this->assertSame($from, $mailer->From);
    }

    /**
     * Assert a mailer to declare the given "to" email.
     *
     * @param string[] $to
     */
    public function assertEmailEqualsTo(PHPMailer\PHPMailer $mailer, array $to): void
    {
        $to_addresses = array_map(function ($address_array) {
            return $address_array[0];
        }, $mailer->getToAddresses());
        $this->assertEquals($to, $to_addresses);
    }

    /**
     * Assert a mailer to contain the given "to" email.
     */
    public function assertEmailContainsTo(PHPMailer\PHPMailer $mailer, string $to): void
    {
        $to_addresses = array_map(function ($address_array) {
            return $address_array[0];
        }, $mailer->getToAddresses());
        $this->assertContains($to, $to_addresses);
    }

    /**
     * Assert a mailer to declare the given "reply_to" email.
     */
    public function assertEmailContainsReplyTo(PHPMailer\PHPMailer $mailer, string $reply_to): void
    {
        $this->assertArrayHasKey($reply_to, $mailer->getReplyToAddresses());
    }

    /**
     * Assert a mailer to contain the given content in body.
     */
    public function assertEmailContainsBody(PHPMailer\PHPMailer $mailer, string $content): void
    {
        $this->assertStringContainsString($content, $mailer->Body);
    }
}
