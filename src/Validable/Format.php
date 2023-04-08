<?php

namespace Minz\Validable;

/**
 * Check that a string property matches a given pattern.
 *
 * You must pass a Regex pattern to the check. It is then checked with the
 * PHP `preg_match` function.
 *
 * If the value is not a string, the check returns false.
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Format(pattern: '/^[\w]+$/', message: 'Choose a nickname that only contains letters.')]
 *         public string $nickname;
 *     }
 *
 * @see https://www.php.net/manual/function.preg-match.php
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Format extends Check
{
    public string $pattern;

    public function __construct(string $message, string $pattern)
    {
        parent::__construct($message);
        $this->pattern = $pattern;
    }

    public function assert(): bool
    {
        $value = $this->getValue();

        if (!is_string($value)) {
            return false;
        }

        return preg_match($this->pattern, $value) === 1;
    }
}
