<?php

namespace Minz\Output;

use Minz\Errors;

/**
 * Allow to return a file via a Response.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class File implements Output
{
    public const EXTENSION_TO_CONTENT_TYPE = [
        'css' => 'text/css',
        'js' => 'text/javascript',
        'pdf' => 'application/pdf',
    ];

    /** @var string */
    private $filepath;

    /** @var string */
    private $content_type;

    /**
     * @param string $filepath
     *
     * @throws \Minz\Errors\OutputError if the file doesn't exist
     */
    public function __construct($filepath)
    {
        if (!file_exists($filepath)) {
            throw new Errors\OutputError("{$filepath} file cannot be found.");
        }
        $this->setContentType($filepath);
        $this->filepath = $filepath;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return $this->content_type;
    }

    /**
     * @return string
     */
    public function render()
    {
        return file_get_contents($this->filepath);
    }

    /**
     * @param string $filepath
     *
     * @throws \Minz\Errors\OutputError if the file extension is not associated
     *                                  to a supported one
     */
    public function setContentType($filepath)
    {
        $file_extension = pathinfo($filepath, PATHINFO_EXTENSION);
        if (!isset(self::EXTENSION_TO_CONTENT_TYPE[$file_extension])) {
            throw new Errors\OutputError(
                "{$file_extension} is not a supported file extension."
            );
        }
        $this->content_type = self::EXTENSION_TO_CONTENT_TYPE[$file_extension];
    }
}
