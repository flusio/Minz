<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Errors;

/**
 * Exception raised when looking for an inexisting route.
 */
class RouteNotFoundError extends \UnexpectedValueException
{
}
