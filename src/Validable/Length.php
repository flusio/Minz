<?php

namespace Minz\Validable;

/**
 * Check that a property length is greater or less than specified limits.
 *
 * You can specify both a minimum and a maximum length, or just one of the two.
 *
 * The message accepts the {min}, {max} and {length} placeholders. They will be
 * replaced by their real values in the final message.
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Length(max: 42, message: 'Choose a nickname of less than {max} characters.')]
 *         public string $nickname;
 *     }
 *
 * The check works with any type of value (integers as well), but note that the
 * string representation will be used. For instance the length of the number 42
 * is 2.
 *
 * Note that the "null" and empty values are considered as valid in order to
 * accept optional values.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Length extends Check
{
    public ?int $min;

    public ?int $max;

    public function __construct(string $message, ?int $min = null, ?int $max = null)
    {
        parent::__construct($message);
        $this->min = $min;
        $this->max = $max;
    }

    public function assert(): bool
    {
        $value = $this->getValue();

        if ($value === null || $value === '') {
            return true;
        }

        $length = $this->getLength();

        if ($this->min !== null && $length < $this->min) {
            return false;
        }

        if ($this->max !== null && $length > $this->max) {
            return false;
        }

        return true;
    }

    public function getMessage(): string
    {
        $value = $this->getValue();
        $length = $this->getLength();

        return str_replace(
            ['{value}', '{min}', '{max}', '{length}'],
            [$value, $this->min, $this->max, $length],
            $this->message,
        );
    }

    private function getLength(): int
    {
        $value = $this->getValue();

        if ($value === null) {
            return 0;
        }

        if (!is_float($value) && !is_integer($value) && !is_string($value)) {
            return 0;
        }

        return mb_strlen(strval($value));
    }
}
