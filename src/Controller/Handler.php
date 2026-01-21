<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Controller;

/**
 * An attribute to define methods that must be executed at certain moments of
 * an action.
 *
 * It cannot be used directly.
 *
 * The `only` parameter of the attribute defines the actions on which the
 * attribute applies (if empty, it applies to all).
 *
 * @see \Minz\Controller\BeforeAction
 * @see \Minz\Controller\AfterAction
 * @see \Minz\Controller\ErrorHandler
 */
abstract class Handler
{
    /**
     * @param string[] $only
     */
    public function __construct(
        public array $only = []
    ) {
    }
}
