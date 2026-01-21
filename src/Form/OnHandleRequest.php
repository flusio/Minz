<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Form;

/**
 * An attribute to declare methods that must be executed when handling request
 * in a form.
 *
 * @see \Minz\Form
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class OnHandleRequest
{
}
