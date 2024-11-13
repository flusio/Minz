<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer;

class MailerTest extends TestCase
{
    use Tests\MailerAsserts;

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTestMailer(): void
    {
        Tests\Mailer::clear();
    }

    public function testDefaultConfiguration(): void
    {
        $mailer = new Mailer();

        $phpmailer = $mailer->mailer;
        $this->assertSame(0, $phpmailer->SMTPDebug);
        $this->assertSame('error_log', $phpmailer->Debugoutput);
        $this->assertSame('root@localhost', $phpmailer->From);
        $this->assertSame('mail', $phpmailer->Mailer);
    }

    public function testConfigurationWithSmtp(): void
    {
        $initial_config_mailer = Configuration::$mailer;
        Configuration::$mailer['type'] = 'smtp';
        Configuration::$mailer['smtp'] = [
            'domain' => 'example.org',
            'host' => 'some.server.org',
            'port' => 465,
            'auth' => true,
            'auth_type' => 'LOGIN',
            'username' => 'user',
            'password' => 'secret',
            'secure' => 'ssl',
        ];

        $mailer = new Mailer();

        Configuration::$mailer = $initial_config_mailer;

        $phpmailer = $mailer->mailer;
        $this->assertSame('smtp', $phpmailer->Mailer);
        $this->assertSame('example.org', $phpmailer->Hostname);
        $this->assertSame('some.server.org', $phpmailer->Host);
        $this->assertSame(465, $phpmailer->Port);
        $this->assertTrue($phpmailer->SMTPAuth);
        $this->assertSame('LOGIN', $phpmailer->AuthType);
        $this->assertSame('user', $phpmailer->Username);
        $this->assertSame('secret', $phpmailer->Password);
        $this->assertSame('ssl', $phpmailer->SMTPSecure);
    }

    public function testSetBody(): void
    {
        $mailer = new Mailer();

        $mailer->setBody(
            'rabbits/items.phtml',
            'rabbits/items.txt',
            [
                'rabbits' => ['Pompom'],
            ]
        );

        $phpmailer = $mailer->mailer;
        $this->assertStringContainsString('Pompom', $phpmailer->Body);
        $this->assertStringContainsString('Pompom', $phpmailer->AltBody);
        $this->assertSame('utf-8', $phpmailer->CharSet);
        $this->assertSame('text/html', $phpmailer->ContentType);
    }

    public function testSend(): void
    {
        $mailer = new Mailer();
        $mailer->setBody(
            'rabbits/items.phtml',
            'rabbits/items.txt',
            [
                'rabbits' => ['Pompom'],
            ]
        );

        $this->assertEmailsCount(0);

        $result = $mailer->send('joe@doe.com', 'The subject');

        $this->assertTrue($result);
        $this->assertEmailsCount(1);
        /** @var PHPMailer\PHPMailer $email_sent */
        $email_sent = \Minz\Tests\Mailer::take(0);
        $this->assertEmailSubject($email_sent, 'The subject');
        $this->assertEmailEqualsTo($email_sent, ['joe@doe.com']);
        $this->assertEmailContainsBody($email_sent, 'Pompom');
    }

    public function testSendClearAddressesBetweenTwoSend(): void
    {
        $mailer = new Mailer();
        $mailer->setBody(
            'rabbits/items.phtml',
            'rabbits/items.txt',
            [
                'rabbits' => ['Pompom'],
            ]
        );

        $this->assertEmailsCount(0);

        $mailer->send('joe@doe.com', 'The subject');
        $mailer->send('jane@doe.com', 'The subject');

        $this->assertEmailsCount(2);
        /** @var PHPMailer\PHPMailer $email_sent_1 */
        $email_sent_1 = \Minz\Tests\Mailer::take(0);
        $this->assertEmailEqualsTo($email_sent_1, ['joe@doe.com']);
        /** @var PHPMailer\PHPMailer $email_sent_2 */
        $email_sent_2 = \Minz\Tests\Mailer::take(1);
        $this->assertEmailEqualsTo($email_sent_2, ['jane@doe.com']);
    }
}
