<?php

namespace Minz\Form;

/**
 * An attribute to allow to run custom checks in forms.
 * It is used in Form classes.
 *
 *     use App\models;
 *     use Minz\Form;
 *
 *     class Registration extends Form
 *     {
 *         #[Form\Field]
 *         public string $username = '';
 *
 *         #[Form\Check]
 *         public function checkUniqueUsername(): void
 *         {
 *             if (models\User::existsBy(['username' => $this->username])) {
 *                 $this->addError('username', 'The username must be unique.');
 *             }
 *         }
 *     }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Check
{
}
