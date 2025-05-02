<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Mailer;

use Minz\Output;
use PHPMailer\PHPMailer;

/**
 * Represent an email that can be sent by a Mailer.
 *
 * @see \Minz\Mailer
 *
 * @phpstan-import-type TemplateName from \Minz\Template\TemplateInterface
 * @phpstan-import-type TemplateContext from \Minz\Template\TemplateInterface
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
     * @param TemplateName $html_template_name
     * @param TemplateName $text_template_name
     * @param TemplateContext $context
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if one of the template name refers to an inexisting file.
     */
    public function setBody(
        string $html_template_name,
        string $text_template_name,
        array $context = [],
    ): void {
        $html_output = new Output\Template($html_template_name, $context);
        $text_output = new Output\Template($text_template_name, $context);

        $this->isHTML(true);
        $this->CharSet = 'utf-8';
        $this->Body = $html_output->render();
        $this->AltBody = $text_output->render();
    }
}
