<?php

/**
 * These functions are meant to be used inside View files.
 *
 * They are declared in global namespace so we don't have to declare the
 * namespaces in views.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

if (!function_exists('protect')) {
    /**
     * @see \Minz\Output\ViewHelpers::protect
     */
    function protect(?string $variable): string
    {
        return \Minz\Output\ViewHelpers::protect($variable);
    }
}

if (!function_exists('url')) {
    /**
     * @see \Minz\Output\ViewHelpers::url
     *
     * @param non-empty-string $action_pointer_or_name
     * @param array<string, mixed> $parameters
     */
    function url(string $action_pointer_or_name, array $parameters = []): string
    {
        return \Minz\Output\ViewHelpers::url($action_pointer_or_name, $parameters);
    }
}

if (!function_exists('url_full')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlFull
     *
     * @param non-empty-string $action_pointer_or_name
     * @param array<string, mixed> $parameters
     */
    function url_full(string $action_pointer_or_name, array $parameters = []): string
    {
        return \Minz\Output\ViewHelpers::urlFull($action_pointer_or_name, $parameters);
    }
}

if (!function_exists('url_static')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlStatic
     */
    function url_static(string $filename): string
    {
        return \Minz\Output\ViewHelpers::urlStatic($filename);
    }
}

if (!function_exists('url_full_static')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlFullStatic
     */
    function url_full_static(string $filename): string
    {
        return \Minz\Output\ViewHelpers::urlFullStatic($filename);
    }
}

if (!function_exists('url_public')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlPublic
     */
    function url_public(string $filename): string
    {
        return \Minz\Output\ViewHelpers::urlPublic($filename);
    }
}

if (!function_exists('url_full_public')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlFullPublic
     */
    function url_full_public(string $filename): string
    {
        return \Minz\Output\ViewHelpers::urlFullPublic($filename);
    }
}

if (!function_exists('_d')) {
    /**
     * @see \Minz\Output\ViewHelpers::formatDate
     */
    function _d(\DateTimeInterface $date, string $format = 'EEEE d MMMM', ?string $locale = null): string
    {
        return \Minz\Output\ViewHelpers::formatDate($date, $format, $locale);
    }
}

if (!function_exists('_f')) {
    /**
     * @see \Minz\Output\ViewHelpers::formatGettext
     */
    function _f(string $message, mixed ...$args): string
    {
        return \Minz\Output\ViewHelpers::formatGettext($message, ...$args);
    }
}

if (!function_exists('_n')) {
    /**
     * @see https://www.php.net/manual/function.ngettext
     */
    function _n(string $message1, string $message2, int $n): string
    {
        return ngettext($message1, $message2, $n);
    }
}

if (!function_exists('_nf')) {
    /**
     * @see \Minz\Output\ViewHelpers::formatNgettext
     */
    function _nf(string $message1, string $message2, int $n, mixed ...$args): string
    {
        return \Minz\Output\ViewHelpers::formatNgettext($message1, $message2, $n, ...$args);
    }
}
