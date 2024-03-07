<?php

namespace Minz\Form;

/**
 * A trait to check CSRF tokens.
 *
 * It is used in Form classes.
 *
 *     use Minz\Form;
 *
 *     class Article extends Form
 *     {
 *         use Form\Csrf;
 *
 *         // ...
 *     }
 *
 * Don't forget to include the CSRF token and errors in the form view:
 *
 *     <input type="hidden" name="csrf" value="<?= $csrf_token ?>" />
 *
 *     <?php if ($form->hasError('@global')): ?>
 *         <p>
 *             <?= $form->getError('@global') ?>
 *         </p>
 *     <?php endif ?>
 *
 * Note that the $csrf_token is generated with \Minz\Csrf::generate() in the
 * controller. The CSRF error is registered in the special "@global" error
 * namespace.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Csrf
{
    #[Field(bind_model: false)]
    public string $csrf = '';

    #[Check]
    public function checkCsrf(): void
    {
        if (!\Minz\Csrf::validate($this->csrf)) {
            $this->addError('@global', $this->csrfErrorMessage());
        }
    }

    public function csrfErrorMessage(): string
    {
        return 'The security token is invalid. Please try to submit the form again.';
    }
}
