<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Mailer;

use Minz\Errors;

/**
 * The Mailer job allows to execute a Mailer asynchronously.
 */
class Job extends \Minz\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->queue = 'mailers';
    }

    /**
     * Execute the given mailer action.
     *
     * @param class-string<\Minz\Mailer> $mailer_class_name
     */
    public function perform(string $mailer_class_name, string $mailer_action_name, mixed ...$args): void
    {
        try {
            $mailer = new $mailer_class_name();
            $mailer->$mailer_action_name(...$args);
        } catch (\Error $e) {
            throw new Errors\InvalidMailerError(
                "{$mailer_class_name}::{$mailer_action_name} mailer cannot be called: {$e->getMessage()}"
            );
        } catch (\Exception $e) {
            throw new Errors\MailerError(
                "{$mailer_class_name}::{$mailer_action_name} mailer failed while sending email: {$e->getMessage()}"
            );
        }
    }
}
