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
     * Assert a mailer to declare the given "to" email.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $mailer
     * @param string $to
     */
    public function assertEmailContainsTo($mailer, $to)
    {
        $this->assertContains($to, $mailer->getToAddresses()[0]);
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
