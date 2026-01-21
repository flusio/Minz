<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use Minz\Errors;

/**
 * An output class that read and return the content of a file to users.
 *
 * ```php
 * $file_output = new \Minz\Output\File('/path/to/a/file.pdf');
 * $response = new Response(200, $file_output);
 * ```
 *
 * For now, it can only serve CSS, JS, PDF and ZIP files.
 *
 * @see \Minz\Output
 * @see \Minz\Response
 */
class File implements \Minz\Output
{
    public const EXTENSION_TO_CONTENT_TYPE = [
        'css' => 'text/css',
        'js' => 'text/javascript',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
    ];

    private string $filepath;

    private string $content_type;

    /**
     * @throws \Minz\Errors\OutputError
     *     If the file doesn't exist
     */
    public function __construct(string $filepath)
    {
        if (!file_exists($filepath)) {
            throw new Errors\OutputError("{$filepath} file cannot be found.");
        }
        $this->setContentType($filepath);
        $this->filepath = $filepath;
    }

    public function contentType(): string
    {
        return $this->content_type;
    }

    public function render(): string
    {
        $output = file_get_contents($this->filepath);

        if ($output === false) {
            throw new Errors\OutputError("{$this->filepath} file cannot be read.");
        };

        return $output;
    }

    /**
     * @throws \Minz\Errors\OutputError
     *     If the file extension is not associated to a supported one
     */
    public function setContentType(string $filepath): void
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
