<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Errors;

/**
 * Exception raised for erroneous property declaration.
 */
class ModelPropertyError extends \LogicException
{
    private string $property;

    public function __construct(string $property, string $code, string $message)
    {
        parent::__construct($message);
        $this->property = $property;
        $this->code = $code;
    }

    public function property(): string
    {
        return $this->property;
    }
}
