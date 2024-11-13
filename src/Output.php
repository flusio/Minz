<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * An Output represents the content returned to a user.
 *
 * It specifies the interface a class must implement to be usable by the
 * application.
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
