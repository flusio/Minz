<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

/**
 * Ease tests based on time by providing a freeze method and make sure to
 * unfreeze it at the beginning of each tests.
 *
 * @see \Minz\Time
 */
trait TimeHelper
{
    #[\PHPUnit\Framework\Attributes\After]
    public function unfreeze(): void
    {
        \Minz\Time::unfreeze();
    }

    public function freeze(?\DateTimeInterface $datetime = null): void
    {
        \Minz\Time::freeze($datetime);
    }
}
