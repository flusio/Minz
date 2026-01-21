<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Flash utility provides methods to pass messages from a page to another,
 * through redirections.
 *
 * The messages are saved into the $_SESSION.
 *
 * First, declare a message in a controller action:
 *
 *     \Minz\Flash::set('error', 'This is not working');
 *
 * Then, load the message after a redirection:
 *
 *     $error = \Minz\Flash::pop('error');
 *
 * When calling pop(), the message is removed from the session. If you want to
 * get the message without erasing it, use get() instead.
 *
 * You also can test if a message exists with the has() method.
 */
class Flash
{
    /**
     * Store a value as a flash message.
     */
    public static function set(string $key, mixed $value): void
    {
        if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
            $_SESSION['_flash'] = [];
        }

        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Return the flash message value and delete it.
     */
    public static function pop(string $key, mixed $default_value = null): mixed
    {
        if (self::has($key)) {
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $value = $_SESSION['_flash'][$key];

            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            unset($_SESSION['_flash'][$key]);

            return $value;
        } else {
            return $default_value;
        }
    }

    /**
     * Return the flash message value.
     */
    public static function get(string $key, mixed $default_value = null): mixed
    {
        if (self::has($key)) {
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            return $_SESSION['_flash'][$key];
        } else {
            return $default_value;
        }
    }

    /**
     * Return whether the flash message exists.
     */
    public static function has(string $key): bool
    {
        return (
            isset($_SESSION['_flash']) &&
            is_array($_SESSION['_flash']) &&
            isset($_SESSION['_flash'][$key])
        );
    }
}
