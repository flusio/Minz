<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

/**
 * An output class to return plain text to users.
 *
 * ```php
 * $text_output = new \Minz\Output\Text('some text');
 * $response = new Response(200, $text_output);
 * ```
 *
 * You should not have to initialize this output manually as it can be
 * shortened in:
 *
 * ```php
 * $response = Response::text(200, 'some text');
 * ```
 *
 * @see \Minz\Output
 * @see \Minz\Response
 */
class Text implements \Minz\Output
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
