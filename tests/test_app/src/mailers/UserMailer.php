<?php

namespace AppTest\mailers;

use Minz\Mailer;

class UserMailer extends Mailer
{
    public function sendEmail(): \Minz\Mailer\Email
    {
        $email = new Mailer\Email();
        $email->setSubject('Subject of the email');
        $email->setBody(
            'mailers/email.phtml',
            'mailers/email.txt',
        );

        $this->send($email, to: 'john@example.org');

        return $email;
    }

    public function sendEmailWithArgument(string $to): \Minz\Mailer\Email
    {
        $email = new Mailer\Email();
        $email->setSubject('Subject of the email');
        $email->setBody(
            'mailers/email.phtml',
            'mailers/email.txt',
        );

        $this->send($email, to: $to);

        return $email;
    }

    public function sendEmailWithError(): never
    {
        throw new \Exception('Mailer is failing');
    }
}
