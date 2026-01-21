<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
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
    /**
     * Unfreeze the time at the end of each test.
     */
    #[\PHPUnit\Framework\Attributes\After]
    public function unfreeze(): void
    {
        \Minz\Time::unfreeze();
    }

    /**
     * Freeze the time at the given date. If no date is passed, freeze the
     * time at the current time.
     */
    public function freeze(?\DateTimeInterface $datetime = null): void
    {
        \Minz\Time::freeze($datetime);
    }
}
