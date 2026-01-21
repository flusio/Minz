<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
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
class Presence extends PropertyCheck
{
    public function assert(): bool
    {
        $value = $this->value();
        return $value !== null && $value !== '';
    }
}
