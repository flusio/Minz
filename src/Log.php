<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * Abstract the calls to the syslog function. It might allow to preformat
 * messages in the future.
 */
class Log
{
    public static function notice(string $message): void
    {
        syslog(LOG_NOTICE, $message);
    }

    public static function warning(string $message): void
    {
        syslog(LOG_WARNING, $message);
    }

    public static function error(string $message): void
    {
        syslog(LOG_ERR, $message);
    }
}
