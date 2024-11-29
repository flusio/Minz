<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPMailer\PHPMailer;

/**
 * Allow to send emails easily with PHPMailer
 *
 * It allows to automatically configure a PHPMailer instance with
 * Configuration. Body can easily be set with both HTML and text content via
 * two Output\View.
 *
 * This class can be inherited in order to specialize it into smaller Mailers.
 *
 * ```php
 * class UserMailer extends \Minz\Mailer
 * {
 *     public function sendResetPasswordEmail($user_id): bool
 *     {
 *         $user = models\User::find($user_id);
 *
 *         $subject = 'Reset your password';
 *         $this->setBody(
 *             'path/to/view.phtml',
 *             'path/to/view.txt',
 *             ['user => $user],
 *         );
 *
 *         return $this->send($user->email, $subject);
 *     }
 * }
 * ```
 *
 * @phpstan-import-type ViewVariables from Output\View
 *
 * @phpstan-import-type ViewPointer from Output\View
 */
class Mailer
{
    public PHPMailer\PHPMailer $mailer;

    /**
     * Setup the PHPMailer mailer with the application configuration.
     */
    public function __construct()
    {
        PHPMailer\PHPMailer::$validator = 'html5';

        $mailer = new PHPMailer\PHPMailer(true);

        $mailer_configuration = Configuration::$mailer;
        $mailer->SMTPDebug = $mailer_configuration['debug'];
        $mailer->Debugoutput = 'error_log';

        $mailer->setFrom($mailer_configuration['from']);

        if (
            $mailer_configuration['type'] === 'smtp' &&
            isset($mailer_configuration['smtp'])
        ) {
            $smtp_config = $mailer_configuration['smtp'];
            $mailer->isSMTP();
            $mailer->Hostname = $smtp_config['domain'];
            $mailer->Host = $smtp_config['host'];
            $mailer->Port = $smtp_config['port'];
            $mailer->SMTPAuth = $smtp_config['auth'];
            $mailer->AuthType = $smtp_config['auth_type'];
            $mailer->Username = $smtp_config['username'];
            $mailer->Password = $smtp_config['password'];
            $mailer->SMTPSecure = $smtp_config['secure'];
        } else {
            $mailer->isMail();
        }

        $this->mailer = $mailer;
    }

    /**
     * Set the body with both HTML and text content.
     *
     * @param ViewPointer $html_view_pointer
     * @param ViewPointer $text_view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError if one of the pointers is invalid
     */
    public function setBody(string $html_view_pointer, string $text_view_pointer, array $variables = []): void
    {
        $html_output = new Output\View($html_view_pointer, $variables);
        $text_output = new Output\View($text_view_pointer, $variables);

        $this->mailer->isHTML(true);
        $this->mailer->CharSet = 'utf-8';
        $this->mailer->Body = $html_output->render();
        $this->mailer->AltBody = $text_output->render();
    }

    /**
     * Send an email.
     */
    public function send(string $to, string $subject): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            if (Configuration::$mailer['type'] === 'test') {
                Tests\Mailer::store($this->mailer);
            } else {
                $this->mailer->send();
            }
            return true;
        } catch (PHPMailer\Exception $e) {
            Log::error('Mailer cannot send a message: ' . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
