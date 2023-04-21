<?php

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
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Flash
{
    /**
     * Store a value as a flash message.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Return the flash message value and delete it.
     */
    public static function pop(string $key, mixed $default_value = null): mixed
    {
        if (self::has($key)) {
            $value = $_SESSION['_flash'][$key];
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
        return isset($_SESSION['_flash'][$key]);
    }
}
