<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use Minz\Errors;
use Minz\Form\Csrf;

/**
 * Helper to generate CSRF tokens during tests.
 *
 * @see \Minz\Form\Csrf
 */
trait CsrfHelper
{
    /**
     * Return a valid CSRF token for the given form class.
     *
     * @param class-string $form_class_name
     * @return non-empty-string
     */
    public function csrfToken(string $form_class_name): string
    {
        $form = new $form_class_name();

        if (!is_callable([$form, 'csrfToken'])) {
            $trait = Csrf::class;
            throw new Errors\LogicException("The given class must use the trait '{$trait}'");
        }

        $csrf_token = $form->csrfToken();

        if (!is_string($csrf_token) || !$csrf_token) {
            throw new Errors\LogicException("The given class csrfToken method must return a non-empty string");
        }

        return $csrf_token;
    }
}
