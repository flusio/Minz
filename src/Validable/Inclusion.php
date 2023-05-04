<?php

namespace Minz\Validable;

/**
 * Check that a property value is part of a given set.
 *
 * The message accepts the {value} placeholder. It will be replaced by the
 * real value in the final message.
 *
 *     use Minz\Validable;
 *
 *     class Message
 *     {
 *         use Validable;
 *
 *         #[Validable\Inclusion(in: ['request', 'incident'], message: '{value} is not a valid message type.')]
 *         public string $type;
 *     }
 *
 * By default, the value is checked against the values of the $in array. You
 * can pass "keys" as $mode parameter to check against the keys of the array.
 *
 * Note that the "null" value is considered as valid in order to accept
 * optional values.
 *
 * @phpstan-type InclusionMode 'values'|'keys'
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Inclusion extends Check
{
    /** @var mixed[] */
    public array $in;

    /** @var InclusionMode */
    public string $mode;

    /**
     * @param mixed[] $in
     * @param InclusionMode $mode
     */
    public function __construct(string $message, array $in, string $mode = 'values')
    {
        parent::__construct($message);
        $this->in = $in;
        $this->mode = $mode;
    }

    public function assert(): bool
    {
        $value = $this->getValue();

        if ($value === null) {
            return true;
        }

        if ($this->mode === 'values') {
            $accepted_values = array_values($this->in);
        } else {
            $accepted_values = array_keys($this->in);
        }

        return in_array($value, $accepted_values);
    }

    public function getMessage(): string
    {
        $value = $this->getValue();

        return str_replace(
            ['{value}'],
            [$value],
            $this->message,
        );
    }
}
