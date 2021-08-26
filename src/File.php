<?php

namespace Minz;

/**
 * File helps to manipulate and tests upload of files.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class File
{
    /** @var string */
    public $filepath;

    /** @var string */
    public $source_name;

    /** @var integer */
    public $error;

    /**
     * Initialize a File object.
     *
     * @param array $file_info
     *     The array containing information about the file, from $_FILES.
     */
    public function __construct($file_info)
    {
        if (!isset($file_info['tmp_name'])) {
            throw new \RuntimeException('Invalid parameter: missing "tmp_name" key.');
        }

        if (!isset($file_info['error'])) {
            throw new \RuntimeException('Invalid parameter: missing "error" key.');
        }

        if ($file_info['error'] < UPLOAD_ERR_OK || $file_info['error'] > UPLOAD_ERR_EXTENSION) {
            throw new \RuntimeException('Invalid parameter: unknown error.');
        }

        $this->filepath = $file_info['tmp_name'];
        $this->source_name = $file_info['name'] ?? '';
        if ($file_info['error'] !== UPLOAD_ERR_OK) {
            $this->error = $file_info['error'];
        }

        if (!$this->error) {
            // We need to check a last error: if the file has NOT been uploaded
            // via a POST request. We need to use is_uploaded_file for that,
            // which makes tests hard to write (the function will always return
            // false). To handle that, we bypass the method during tests with
            // the possibility to simulate an error.
            $env_test = \Minz\Configuration::$environment === 'test';
            if ($env_test && isset($file_info['is_uploaded_file'])) {
                $this->error = $file_info['is_uploaded_file'] ? null : -1;
            } elseif (!$env_test && !is_uploaded_file($this->filepath)) {
                $this->error = -1;
            }
        }
    }

    /**
     * Return the content of the file.
     *
     * @return string|boolean
     *     Returns the content of the file, or false if there is an error.
     */
    public function content()
    {
        if ($this->error) {
            return false;
        }

        return @file_get_contents($this->filepath);
    }

    /**
     * Move the file to $file_destination.
     *
     * @param string $file_destination
     *
     * @return boolean True on success, false otherwise.
     */
    public function move($file_destination)
    {
        if ($this->error) {
            return false;
        }

        // Similarly to is_uploaded_file, move_uploaded_file will always fail
        // during tests, so we bypass the function.
        $env_test = \Minz\Configuration::$environment === 'test';
        if ($env_test) {
            $result = rename($this->filepath, $file_destination);
        } else {
            $result = move_uploaded_file($this->filepath, $file_destination);
        }

        if ($result) {
            $this->filepath = $file_destination;
        }

        return $result;
    }

    /**
     * Return whether the file is too large or not.
     *
     * @return boolean Return true if the file is too large.
     */
    public function isTooLarge()
    {
        return (
            $this->error === UPLOAD_ERR_INI_SIZE ||
            $this->error === UPLOAD_ERR_FORM_SIZE
        );
    }

    /**
     * Return whether the file is one of the given types.
     *
     * @param string[] $mime_types
     *
     * @return boolean Return true if the file type matches one of the given types.
     */
    public function isType($mime_types)
    {
        $current_mime_type = mime_content_type($this->filepath);
        return in_array($current_mime_type, $mime_types);
    }
}
