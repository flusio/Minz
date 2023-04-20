<?php

namespace Minz;

/**
 * Wrapper around DateTime, to provide test capabilities.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Time
{
    public static ?\DateTimeImmutable $freezed_now = null;

    public static function now(): \DateTimeImmutable
    {
        if (self::$freezed_now) {
            return self::$freezed_now;
        } else {
            return new \DateTimeImmutable('now');
        }
    }

    /**
     * Return a datetime relative to now()
     *
     * @see https://www.php.net/manual/datetime.modify.php
     * @see https://www.php.net/manual/datetime.formats.relative.php
     */
    public static function relative(string $modifier): \DateTimeImmutable
    {
        return self::now()->modify($modifier);
    }

    /**
     * Return a datetime from the future.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     */
    public static function fromNow(int $number, string $unit): \DateTimeImmutable
    {
        return self::relative("+{$number} {$unit}");
    }

    /**
     * Return a datetime from the past.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     */
    public static function ago(int $number, string $unit): \DateTimeImmutable
    {
        return self::relative("-{$number} {$unit}");
    }

    /**
     * Delays the program execution for the given number of seconds.
     *
     * It calls the PHP `sleep()` function, unless the time is frozen. In this
     * case, it adds the number of seconds to the freezed time.
     *
     * @see https://www.php.net/manual/function.sleep.php
     */
    public static function sleep(int $seconds): bool
    {
        if (self::$freezed_now) {
            self::$freezed_now = self::$freezed_now->modify("+{$seconds} seconds");
            return true;
        } else {
            return sleep($seconds) === 0;
        }
    }

    /**
     * Freeze the time at a given datetime
     */
    public static function freeze(?\DateTimeInterface $datetime = null): void
    {
        if ($datetime === null) {
            $datetime = self::now();
        }

        self::$freezed_now = \DateTimeImmutable::createFromInterface($datetime);
    }

    /**
     * Unfreeze the time.
     */
    public static function unfreeze(): void
    {
        self::$freezed_now = null;
    }
}
