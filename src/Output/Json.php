<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

/**
 * An output class to return JSON content to users.
 *
 * ```php
 * $json_output = new \Minz\Output\Json(['foo' => 'bar']);
 * $response = new Response(200, $json_output);
 * ```
 *
 * You should not have to initialize this output manually as it can be
 * shortened in:
 *
 * ```php
 * $response = Response::json(200, ['foo' => 'bar']);
 * ```
 *
 * @see \Minz\Output
 * @see \Minz\Response
 */
class Json implements \Minz\Output
{
    private string $json;

    /**
     * @param mixed[] $values
     */
    public function __construct(array $values)
    {
        $json = json_encode($values);

        if (!$json) {
            $json = '';
        }

        $this->json = $json;
    }

    public function contentType(): string
    {
        return 'application/json';
    }

    public function render(): string
    {
        return $this->json;
    }
}
