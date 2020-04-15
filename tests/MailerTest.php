<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $mailer = new Mailer();

        $phpmailer = $mailer->mailer;
        $this->assertSame(0, $phpmailer->SMTPDebug);
        $this->assertSame('error_log', $phpmailer->Debugoutput);
        $this->assertSame('root@localhost', $phpmailer->From);
        $this->assertSame('mail', $phpmailer->Mailer);
    }

    public function testConfigurationWithSMTP()
    {
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

    public function testSetBody()
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
}
