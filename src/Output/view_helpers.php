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
    function protect($variable)
    {
        return \Minz\Output\ViewHelpers::protect($variable);
    }
}

if (!function_exists('url')) {
    /**
     * @see \Minz\Output\ViewHelpers::url
     */
    function url($action_pointer, $parameters = [])
    {
        return \Minz\Output\ViewHelpers::url($action_pointer, $parameters);
    }
}

if (!function_exists('url_full')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlFull
     */
    function url_full($action_pointer, $parameters = [])
    {
        return \Minz\Output\ViewHelpers::urlFull($action_pointer, $parameters);
    }
}

if (!function_exists('url_static')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlStatic
     */
    function url_static($filename)
    {
        return \Minz\Output\ViewHelpers::urlStatic($filename);
    }
}

if (!function_exists('url_full_static')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlFullStatic
     */
    function url_full_static($filename)
    {
        return \Minz\Output\ViewHelpers::urlFullStatic($filename);
    }
}

if (!function_exists('url_public')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlPublic
     */
    function url_public($filename)
    {
        return \Minz\Output\ViewHelpers::urlPublic($filename);
    }
}

if (!function_exists('url_full_public')) {
    /**
     * @see \Minz\Output\ViewHelpers::urlFullPublic
     */
    function url_full_public($filename)
    {
        return \Minz\Output\ViewHelpers::urlFullPublic($filename);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Deprecated, CSRF token should be pass to the view as a variable.
     *
     * @see \Minz\CSRF::generate
     */
    function csrf_token()
    {
        \Minz\Log::notice('csrf_token() view function is deprecated.');
        return \Minz\CSRF::generate();
    }
}

if (!function_exists('_d')) {
    /**
     * @see \Minz\Output\ViewHelpers::formatDate
     */
    function _d($date, $format = 'EEEE d MMMM', $locale = null)
    {
        return \Minz\Output\ViewHelpers::formatDate($date, $format, $locale);
    }
}

if (!function_exists('_f')) {
    /**
     * @see \Minz\Output\ViewHelpers::formatGettext
     */
    function _f($message, ...$args)
    {
        return \Minz\Output\ViewHelpers::formatGettext($message, ...$args);
    }
}

if (!function_exists('_n')) {
    /**
     * @see https://www.php.net/manual/function.ngettext
     */
    function _n($message1, $message2, $n)
    {
        return ngettext($message1, $message2, $n);
    }
}

if (!function_exists('_nf')) {
    /**
     * @see \Minz\Output\ViewHelpers::formatNgettext
     */
    function _nf($message1, $message2, $n, ...$args)
    {
        return \Minz\Output\ViewHelpers::formatNgettext($message1, $message2, $n, ...$args);
    }
}
