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
    public string $filepath;

    public string $source_name;

    public ?int $error = null;

    /**
     * Initialize a File object.
     *
     * @param array{
     *     'name': string,
     *     'tmp_name': string,
     *     'error': int,
     *     'is_uploaded_file'?: bool,
     * } $file_info
     */
    public function __construct(array $file_info)
    {
        if ($file_info['error'] < UPLOAD_ERR_OK || $file_info['error'] > UPLOAD_ERR_EXTENSION) {
            throw new \RuntimeException('Invalid parameter: unknown error.');
        }

        $this->filepath = $file_info['tmp_name'];
        $this->source_name = $file_info['name'];
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
     * Return the content of the file, or false if there is an error.
     *
     * @return string|boolean
     */
    public function content(): mixed
    {
        if ($this->error) {
            return false;
        }

        return @file_get_contents($this->filepath);
    }

    /**
     * Move the file to a destination.
     */
    public function move(string $file_destination): bool
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
     */
    public function isTooLarge(): bool
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
     */
    public function isType(array $mime_types): bool
    {
        $current_mime_type = mime_content_type($this->filepath);
        return in_array($current_mime_type, $mime_types);
    }
}
