<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use Minz\Errors;
use PHPMailer\PHPMailer;

/**
 * Allow to send emails.
 *
 * You should instantiate a Mailer\Email and then send it with the mailer
 * `send()` method:
 *
 * ```php
 * $email = \Minz\Mailer\Email();
 * $email->setSubject('Reset your password');
 * $email->setBody(
 *     'path/to/view.phtml',
 *     'path/to/view.txt',
 *     ['user => $user],
 * );
 *
 * $mailer = new \Minz\Mailer();
 * $mailer->send($email, to: $user->email);
 * ```
 *
 * Internally, the Email object is a PHPMailer instance and the Mailer
 * configures it with the parameters from the Configuration.
 *
 * An Errors\MailerError is raised if the mailer fails to send the email.
 *
 * You can also send emails asynchronously with the Mailer\Job class. First,
 * you must extend the Mailer class with your own class:
 *
 * ```php
 * class UserMailer extends \Minz\Mailer
 * {
 *     public function sendResetPasswordEmail($user_id): \Minz\Mailer\Email
 *     {
 *         $user = models\User::find($user_id);
 *
 *         $email = \Minz\Mailer\Email();
 *         $email->setSubject('Reset your password');
 *         $email->setBody(
 *             'path/to/view.phtml',
 *             'path/to/view.txt',
 *             ['user => $user],
 *         );
 *
 *         $this->send($email, to: $user->email);
 *
 *         return $email;
 *     }
 * }
 * ```
 *
 * As the mailer action and arguments will be stored in the database, make sure
 * that the parameters' types of the action are "string", "int", "bool" or
 * "null".
 *
 * Then, execute the mailer with the job:
 *
 * ```php
 * $mailer_job = new \Minz\Mailer\Job();
 * $mailer_job->performAsap(UserMailer::class, 'sendResetPasswordEmail', $user_id);
 * ```
 *
 * @see \Minz\Mailer\Email
 * @see \Minz\Mailer\Job
 */
class Mailer
{
    /**
     * Send an email.
     *
     * @throws \Minz\Errors\MailerError
     *
     * @param string|string[] $to
     * @param string|string[] $cc
     * @param string|string[] $bcc
     */
    public function send(Mailer\Email $email, mixed $to, mixed $cc = [], mixed $bcc = []): void
    {
        if (is_string($to)) {
            $to = [$to];
        }

        if (is_string($cc)) {
            $cc = [$cc];
        }

        if (is_string($bcc)) {
            $bcc = [$bcc];
        }

        $this->setupEmail($email);

        try {
            foreach ($to as $address) {
                $email->addAddress($address);
            }

            foreach ($cc as $address) {
                $email->addCC($address);
            }

            foreach ($bcc as $address) {
                $email->addBCC($address);
            }

            if (Configuration::$mailer['type'] === 'test') {
                Tests\Mailer::store($email);
            } else {
                $email->send();
            }
        } catch (PHPMailer\Exception $e) {
            // Do nothing on purpose, the $email->ErrorInfo should be set and
            // an error is raised below.
        }

        $this->cleanEmail($email);

        if ($email->ErrorInfo) {
            throw new Errors\MailerError($email->ErrorInfo);
        }
    }

    /**
     * Setup the SMTP configuration of the email.
     */
    private function setupEmail(Mailer\Email $email): void
    {
        PHPMailer\PHPMailer::$validator = 'html5';

        $mailer_configuration = Configuration::$mailer;

        $email->SMTPDebug = $mailer_configuration['debug'];
        $email->Debugoutput = 'error_log';
        $email->setFrom($mailer_configuration['from']);

        if (
            $mailer_configuration['type'] === 'smtp' &&
            isset($mailer_configuration['smtp'])
        ) {
            $smtp_config = $mailer_configuration['smtp'];
            $email->isSMTP();
            $email->Hostname = $smtp_config['domain'];
            $email->Host = $smtp_config['host'];
            $email->Port = $smtp_config['port'];
            $email->SMTPAuth = $smtp_config['auth'];
            $email->AuthType = $smtp_config['auth_type'];
            $email->Username = $smtp_config['username'];
            $email->Password = $smtp_config['password'];
            $email->SMTPSecure = $smtp_config['secure'];
        } else {
            $email->isMail();
        }

        $email->clearAddresses();
    }

    /**
     * Reset SMTP configuration of the email.
     */
    private function cleanEmail(Mailer\Email $email): void
    {
        $email->From = '';
        $email->Hostname = '';
        $email->Host = '';
        $email->Port = 25;
        $email->SMTPAuth = false;
        $email->AuthType = '';
        $email->Username = '';
        $email->Password = '';
        $email->SMTPSecure = '';

        $email->clearAddresses();
    }
}
