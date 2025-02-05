<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    use Tests\MailerAsserts;

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTestMailer(): void
    {
        Tests\Mailer::clear();
    }

    public function testSend(): void
    {
        $email = new Mailer\Email();
        $email->setBody(
            'rabbits/items.phtml',
            'rabbits/items.txt',
            [
                'rabbits' => ['Pompom'],
            ]
        );
        $email->setSubject('The subject');

        $this->assertEmailsCount(0);

        $mailer = new Mailer();
        $mailer->send($email, to: 'joe@doe.com');

        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take(0);
        $this->assertInstanceOf(Mailer\Email::class, $email_sent);
        $this->assertEmailSubject($email_sent, 'The subject');
        $this->assertEmailEqualsTo($email_sent, ['joe@doe.com']);
        $this->assertEmailContainsBody($email_sent, 'Pompom');
    }

    public function testSendClearAddressesBetweenTwoSend(): void
    {
        $email = new Mailer\Email();
        $email->setBody(
            'rabbits/items.phtml',
            'rabbits/items.txt',
            [
                'rabbits' => ['Pompom'],
            ]
        );
        $email->setSubject('The subject');

        $this->assertEmailsCount(0);

        $mailer = new Mailer();
        $mailer->send($email, to: 'joe@doe.com');
        $mailer->send($email, to: 'jane@doe.com');

        $this->assertEmailsCount(2);
        $email_sent_1 = \Minz\Tests\Mailer::take(0);
        $this->assertInstanceOf(Mailer\Email::class, $email_sent_1);
        $this->assertEmailEqualsTo($email_sent_1, ['joe@doe.com']);
        $email_sent_2 = \Minz\Tests\Mailer::take(1);
        $this->assertInstanceOf(Mailer\Email::class, $email_sent_2);
        $this->assertEmailEqualsTo($email_sent_2, ['jane@doe.com']);
    }
}
