<?php

namespace Minz\Validable;

/**
 * Check that a property is not empty (null or empty string).
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Presence(message: 'Choose a nickname.')]
 *         public string $nickname;
 *     }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Presence extends Check
{
    public function assert(): bool
    {
        $value = $this->getValue();
        return $value !== null && $value !== '';
    }
}
