<?php

namespace Minz\Tests;

/**
 * Provide some assert methods to help to test the mailer.
 *
 * Some assertions expect a $mailer to be given. One can be fetched with
 * \Minz\Tests\Mailer::take().
 *
 * @see \Minz\Mailer
 * @see \Minz\Tests\Mailer
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait MailerAsserts
{
    /**
     * Assert that $count emails have been sent.
     *
     * @param integer $count
     */
    public function assertEmailsCount($count)
    {
        $this->assertSame($count, \Minz\Tests\Mailer::count());
    }

    /**
     * Assert a mailer to declare the given subject.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string $subject
     */
    public function assertEmailSubject($mailer, $subject)
    {
        $this->assertSame($subject, $mailer->Subject);
    }

    /**
     * Assert a mailer to declare the given "from" email.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string $from
     */
    public function assertEmailFrom($mailer, $from)
    {
        $this->assertSame($from, $mailer->From);
    }

    /**
     * Assert a mailer to declare the given "to" email.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string[] $to
     */
    public function assertEmailEqualsTo($mailer, $to)
    {
        $to_addresses = array_map(function ($address_array) {
            return $address_array[0];
        }, $mailer->getToAddresses());
        $this->assertEquals($to, $to_addresses);
    }

    /**
     * Assert a mailer to contain the given "to" email.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string $to
     */
    public function assertEmailContainsTo($mailer, $to)
    {
        $to_addresses = array_map(function ($address_array) {
            return $address_array[0];
        }, $mailer->getToAddresses());
        $this->assertContains($to, $to_addresses);
    }

    /**
     * Assert a mailer to declare the given "reply_to" email.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string $reply_to
     */
    public function assertEmailContainsReplyTo($mailer, $reply_to)
    {
        $this->assertArrayHasKey($reply_to, $mailer->getReplyToAddresses());
    }

    /**
     * Assert a mailer to contain the given content in body.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string $content
     */
    public function assertEmailContainsBody($mailer, $content)
    {
        $this->assertStringContainsString($content, $mailer->Body);
    }
}
