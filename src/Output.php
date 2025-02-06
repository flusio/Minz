<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * An Output represents the content returned to a user.
 *
 * It specifies the interface a class must implement to be usable by the
 * application.
 *
 * An Output is attached to a Response in order to be displayed to the user.
 *
 * ```php
 * $text_output = new \Minz\Output\Text('some text');
 * $response = new Response(200, $text_output);
 * ```
 *
 * @see \Minz\Output\File
 * @see \Minz\Output\Json
 * @see \Minz\Output\Text
 * @see \Minz\Output\View
 * @see \Minz\Response
 */
interface Output
{
    /**
     * Generate and return the content.
     */
    public function render(): string;

    /**
     * Returns the content type to set in HTTP headers
     */
    public function contentType(): string;
}
