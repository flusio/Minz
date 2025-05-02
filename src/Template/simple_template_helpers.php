<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * These functions are meant to be used inside Simple template files.
 *
 * They are declared in the global namespace so we don't have to declare the
 * namespaces in templates.
 */

if (!function_exists('protect')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::protect
     */
    function protect(?string $variable): string
    {
        return \Minz\Template\SimpleTemplateHelpers::protect($variable);
    }
}

if (!function_exists('url')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::url
     *
     * @param non-empty-string $action_pointer_or_name
     * @param array<string, mixed> $parameters
     */
    function url(string $action_pointer_or_name, array $parameters = []): string
    {
        return \Minz\Template\SimpleTemplateHelpers::url($action_pointer_or_name, $parameters);
    }
}

if (!function_exists('url_full')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::urlFull
     *
     * @param non-empty-string $action_pointer_or_name
     * @param array<string, mixed> $parameters
     */
    function url_full(string $action_pointer_or_name, array $parameters = []): string
    {
        return \Minz\Template\SimpleTemplateHelpers::urlFull($action_pointer_or_name, $parameters);
    }
}

if (!function_exists('url_static')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::urlStatic
     */
    function url_static(string $filename): string
    {
        return \Minz\Template\SimpleTemplateHelpers::urlStatic($filename);
    }
}

if (!function_exists('url_full_static')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::urlFullStatic
     */
    function url_full_static(string $filename): string
    {
        return \Minz\Template\SimpleTemplateHelpers::urlFullStatic($filename);
    }
}

if (!function_exists('url_public')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::urlPublic
     */
    function url_public(string $filename): string
    {
        return \Minz\Template\SimpleTemplateHelpers::urlPublic($filename);
    }
}

if (!function_exists('url_full_public')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::urlFullPublic
     */
    function url_full_public(string $filename): string
    {
        return \Minz\Template\SimpleTemplateHelpers::urlFullPublic($filename);
    }
}

if (!function_exists('_d')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::formatDate
     */
    function _d(\DateTimeInterface $date, string $format = 'EEEE d MMMM', ?string $locale = null): string
    {
        return \Minz\Template\SimpleTemplateHelpers::formatDate($date, $format, $locale);
    }
}

if (!function_exists('_f')) {
    /**
     * @see \Minz\Template\SimpleTemplateHelpers::formatGettext
     *
     * @param bool|float|int|string|null ...$args
     */
    function _f(string $message, mixed ...$args): string
    {
        return \Minz\Template\SimpleTemplateHelpers::formatGettext($message, ...$args);
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
     * @see \Minz\Template\SimpleTemplateHelpers::formatNgettext
     *
     * @param bool|float|int|string|null ...$args
     */
    function _nf(string $message1, string $message2, int $n, mixed ...$args): string
    {
        return \Minz\Template\SimpleTemplateHelpers::formatNgettext($message1, $message2, $n, ...$args);
    }
}
