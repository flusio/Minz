<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Form;

use Minz\Validable;

/**
 * A trait to check CSRF tokens.
 *
 * You can handle CSRF validation with this trait. It is recommended to declare
 * it in a BaseForm. It is possible to override the `csrfErrorMessage()` method
 * to customize the error message (e.g. to translate it).
 *
 *     use Minz\Form;
 *
 *     class BaseForm extends Form
 *     {
 *         use Form\Csrf;
 *
 *         public function csrfErrorMessage(): string
 *         {
 *             return _('CSRF token is invalid, try to resubmit the form.');
 *         }
 *     }
 *
 * Don't forget to include the CSRF token and errors in the form view:
 *
 *     <input type="hidden" name="csrf" value="<?= $csrf_token ?>" />
 *
 *     <?php if ($form->isInvalid('@base')): ?>
 *         <p>
 *             <?= $form->error('@base') ?>
 *         </p>
 *     <?php endif ?>
 *
 * Note that the $csrf_token should be generated with the `\Minz\Csrf::generate()`
 * method. The CSRF error is registered in the special "@base" error namespace.
 */
trait Csrf
{
    #[Field(bind: false)]
    public string $csrf = '';

    #[Validable\Check]
    public function checkCsrf(): void
    {
        if (!\Minz\Csrf::validate($this->csrf)) {
            $this->addError('@base', 'csrf', $this->csrfErrorMessage());
        }
    }

    public function csrfErrorMessage(): string
    {
        return 'The security token is invalid. Please try to submit the form again.';
    }
}
