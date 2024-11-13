<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

/**
 * The ViewHelpers class defines functions to be used inside View files.
 *
 * They are redeclared as functions with no namespaces in the view_helpers.php
 * file so we don't have to declare the namespaces in views.
 *
 * @phpstan-import-type UrlPointer from \Minz\Url
 *
 * @phpstan-import-type UrlParameters from \Minz\Url
 */
class ViewHelpers
{
    /**
     * Alias for htmlspecialchars.
     *
     * @see https://www.php.net/manual/function.htmlspecialchars.php
     */
    public static function protect(?string $variable): string
    {
        if (!$variable) {
            return '';
        }

        return htmlspecialchars($variable, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Return a protected relative URL (safe to display in views).
     *
     * @see \Minz\Url::for
     *
     * @param UrlPointer $pointer
     * @param UrlParameters $parameters
     */
    public static function url(string $pointer, array $parameters = []): string
    {
        return self::protect(\Minz\Url::for($pointer, $parameters));
    }

    /**
     * Return a protected absolute URL (safe to display in views).
     *
     * @see \Minz\Url::absoluteFor
     *
     * @param UrlPointer $pointer
     * @param UrlParameters $parameters
     */
    public static function urlFull(string $pointer, array $parameters = []): string
    {
        return self::protect(\Minz\Url::absoluteFor($pointer, $parameters));
    }

    /**
     * Return a protected relative URL for a static file (under public/static/ folder).
     */
    public static function urlStatic(string $filename): string
    {
        $filepath = \Minz\Configuration::$app_path . '/public/static/' . $filename;
        $modification_time = @filemtime($filepath);

        $file_url = \Minz\Url::path() . '/static/' . $filename;
        if ($modification_time) {
            return self::protect($file_url . '?' . $modification_time);
        } else {
            return self::protect($file_url);
        }
    }

    /**
     * Return a protected absolute URL for a static file (under public/static/ folder).
     */
    public static function urlFullStatic(string $filename): string
    {
        return \Minz\Url::baseUrl() . self::urlStatic($filename);
    }

    /**
     * Return a protected relative URL for a public file (under public/ folder).
     *
     * Note you should use self::urlStatic() if you target a file under public/static/.
     */
    public static function urlPublic(string $filename): string
    {
        return self::protect(\Minz\Url::path() . '/' . $filename);
    }

    /**
     * Return a protected absolute URL for a public file (under public/ folder).
     *
     * Note you should use self::urlFullStatic() if you target a file under public/static/.
     */
    public static function urlFullPublic(string $filename): string
    {
        return \Minz\Url::baseUrl() . self::urlPublic($filename);
    }

    /**
     * Return a formatted and translated datetime.
     *
     * @see https://www.php.net/manual/class.intldateformatter.php
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     */
    public static function formatDate(
        \DateTimeInterface $date,
        string $format = 'EEEE d MMMM',
        ?string $locale = null
    ): string {
        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            null,
            null,
            $format
        );

        $formatted_date = $formatter->format($date);
        if ($formatted_date) {
            return $formatted_date;
        } else {
            return '';
        }
    }

    /**
     * Return a translated and formatted message.
     *
     * @see https://www.php.net/manual/function.gettext
     * @see https://www.php.net/manual/function.vsprintf.php
     *
     * @param bool|float|int|string|null ...$args
     */
    public static function formatGettext(string $message, mixed ...$args): string
    {
        return vsprintf(gettext($message), $args);
    }

    /**
     * Return a translated and formatted message (plural version).
     *
     * @see https://www.php.net/manual/function.ngettext
     * @see https://www.php.net/manual/function.vsprintf.php
     *
     * @param bool|float|int|string|null ...$args
     */
    public static function formatNgettext(string $message1, string $message2, int $n, mixed ...$args): string
    {
        return vsprintf(ngettext($message1, $message2, $n), $args);
    }
}
