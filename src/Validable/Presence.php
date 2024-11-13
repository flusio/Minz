<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a property is not empty (null or empty string).
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Presence(message: 'Choose a nickname.')]
 *         public string $nickname;
 *     }
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Presence extends Check
{
    public function assert(): bool
    {
        $value = $this->getValue();
        return $value !== null && $value !== '';
    }
}
