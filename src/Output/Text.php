<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

/**
 * An output Text class allows to return plain text easily to users.
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
