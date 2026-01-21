<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Mailer;

use AppTest\mailers;
use Minz\Errors;
use Minz\Tests;
use PHPUnit\Framework\TestCase;

class JobTest extends TestCase
{
    use Tests\MailerAsserts;

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTestMailer(): void
    {
        Tests\Mailer::clear();
    }

    public function testPerform(): void
    {
        $mailer_job = new Job();

        $mailer_job->perform(mailers\UserMailer::class, 'sendEmail');

        $this->assertEmailsCount(1);
        $email_sent = Tests\Mailer::take(0);
        $this->assertInstanceOf(Email::class, $email_sent);
        $this->assertEmailSubject($email_sent, 'Subject of the email');
        $this->assertEmailEqualsTo($email_sent, ['john@example.org']);
        $this->assertEmailContainsBody($email_sent, 'The content of the email.');
    }

    public function testPerformWithInvalidAction(): void
    {
        $this->expectException(Errors\InvalidMailerError::class);
        $this->expectExceptionMessage('Call to undefined method AppTest\mailers\UserMailer::notAnAction()');

        $mailer_job = new Job();

        $mailer_job->perform(mailers\UserMailer::class, 'notAnAction');
    }

    public function testPerformWithInvalidArgument(): void
    {
        $this->expectException(Errors\InvalidMailerError::class);
        $this->expectExceptionMessage(
            'Too few arguments to function AppTest\mailers\UserMailer::sendEmailWithArgument()'
        );

        $mailer_job = new Job();

        $mailer_job->perform(mailers\UserMailer::class, 'sendEmailWithArgument');
    }

    public function testPerformWithActionFailing(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Mailer is failing');

        $mailer_job = new Job();

        $mailer_job->perform(mailers\UserMailer::class, 'sendEmailWithError');
    }
}
