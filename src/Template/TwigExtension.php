<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Template;

use Minz\Errors;
use Twig\Attribute\AsTwigFunction;

/**
 * Declare several Twig functions to be used in templates.
 *
 * @phpstan-import-type RouteName from \Minz\Router
 * @phpstan-import-type UrlParameters from \Minz\Url
 */
class TwigExtension
{
    /**
     * Return a relative URL.
     *
     * @see \Minz\Url::for
     *
     * @param RouteName $name
     * @param UrlParameters $parameters
     */
    #[AsTwigFunction('url')]
    public static function url(string $name, array $parameters = []): string
    {
        return \Minz\Url::for($name, $parameters);
    }

    /**
     * Return an absolute URL.
     *
     * @see \Minz\Url::for
     *
     * @param RouteName $name
     * @param UrlParameters $parameters
     */
    #[AsTwigFunction('url_full')]
    public static function urlFull(string $name, array $parameters = []): string
    {
        return \Minz\Url::absoluteFor($name, $parameters);
    }

    /**
     * Return a relative URL for a static file (under public/ folder).
     */
    #[AsTwigFunction('url_static')]
    public static function urlStatic(string $filename, bool $hash = true): string
    {
        $url_static = \Minz\Url::path() . "/{$filename}";

        if (!$hash) {
            return $url_static;
        }

        $filepath = \Minz\Configuration::$app_path . '/public/' . $filename;
        $modification_time = @filemtime($filepath);

        if (!$modification_time) {
            return $url_static;
        }

        return $url_static . '?' . $modification_time;
    }

    /**
     * Return an absolute URL for a static file (under public/ folder).
     */
    #[AsTwigFunction('url_full_static')]
    public static function urlFullStatic(string $filename, bool $hash = true): string
    {
        return \Minz\Url::baseUrl() . self::urlStatic($filename, $hash);
    }

    /**
     * Return whether the given environment is the actual one.
     */
    #[AsTwigFunction('is_environment')]
    public static function isEnvironment(string $environment): bool
    {
        return \Minz\Configuration::$environment === $environment;
    }

    /**
     * Return a translated date with the given format.
     */
    #[AsTwigFunction('d')]
    public static function translateDate(
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
     * Return a translated message.
     *
     * The function can be called in three different ways:
     *
     * - t('Hello World')
     * - t('Hello %s', [name])
     * - t('%d apple', '%d apples', count, [count])
     */
    #[AsTwigFunction('t')]
    public static function translate(string $message, mixed ...$args): string
    {
        $count_args = count($args);

        if ($count_args === 0) {
            return gettext($message);
        }

        if ($count_args === 1) {
            $args = $args[0];

            if (!is_array($args)) {
                throw new Errors\LogicException(
                    'trans must be called with a message and an array as second argument.'
                );
            }

            $args = array_filter($args, function ($arg): bool {
                return is_string($arg) || is_int($arg) || is_float($arg);
            });

            return vsprintf(gettext($message), $args);
        }

        if ($count_args === 3) {
            $message2 = $args[0];
            $n = $args[1];
            $args = $args[2];

            if (
                !is_string($message2) ||
                !is_int($n) ||
                !is_array($args)
            ) {
                throw new Errors\LogicException(
                    'trans must be called with two string messages, an int and an array as arguments.'
                );
            }

            $args = array_filter($args, function ($arg): bool {
                return is_string($arg) || is_int($arg) || is_float($arg);
            });

            return vsprintf(ngettext($message, $message2, $n), $args);
        }

        throw new Errors\LogicException('trans must be called with either 1, 2 or 4 arguments');
    }
}
