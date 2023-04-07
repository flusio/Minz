<?php

namespace Minz;

/**
 * Abstract the calls to the syslog function. It might allow to preformat
 * messages in the future.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
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
