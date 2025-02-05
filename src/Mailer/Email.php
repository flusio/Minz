<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Mailer;

use Minz\Output;
use PHPMailer\PHPMailer;

/**
 * Represent an email that can be sent by a Mailer.
 *
 * @see \Minz\Mailer
 *
 * @phpstan-import-type ViewPointer from Output\View
 * @phpstan-import-type ViewVariables from Output\View
 */
class Email extends PHPMailer\PHPMailer
{
    /**
     * Set the subject of the email.
     */
    public function setSubject(string $subject): void
    {
        $this->Subject = $subject;
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

        $this->isHTML(true);
        $this->CharSet = 'utf-8';
        $this->Body = $html_output->render();
        $this->AltBody = $text_output->render();
    }
}
