<?php

namespace Minz\Output;

/**
 * An output Text class allows to return plain text easily to users.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Text implements Output
{
    private string $text;

    public function __construct(string $text)
    {
        $this->text = $text;
    }

    public function contentType(): string
    {
        return 'text/plain';
    }

    public function render(): string
    {
        return $this->text;
    }
}
