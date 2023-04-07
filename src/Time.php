<?php

namespace Minz;

/**
 * Wrapper around `date_create()` function, to provide test capabilities.
 */
class Time
{
    /** @var \DateTime|integer|null */
    public static mixed $freezed_timestamp = null;

    public static function now(): \DateTime
    {
        if (self::$freezed_timestamp && is_int(self::$freezed_timestamp)) {
            $date = new \DateTime();
            $date->setTimestamp(self::$freezed_timestamp);
            return $date;
        } elseif (self::$freezed_timestamp && self::$freezed_timestamp instanceof \DateTime) {
            return clone self::$freezed_timestamp;
        } else {
            return new \DateTime('now');
        }
    }

    /**
     * Return a datetime relative to now()
     *
     * @see https://www.php.net/manual/datetime.modify.php
     * @see https://www.php.net/manual/datetime.formats.relative.php
     */
    public static function relative(string $modifier): \DateTime
    {
        $datetime = self::now();
        $datetime->modify($modifier);
        return $datetime;
    }

    /**
     * Return a datetime from the future.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     */
    public static function fromNow(int $number, string $unit): \DateTime
    {
        return self::relative("+{$number} {$unit}");
    }

    /**
     * Return a datetime from the past.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     */
    public static function ago(int $number, string $unit): \DateTime
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
        if (self::$freezed_timestamp && is_int(self::$freezed_timestamp)) {
            self::$freezed_timestamp += $seconds;
            return true;
        } elseif (self::$freezed_timestamp && self::$freezed_timestamp instanceof \DateTime) {
            $new_freezed_timestamp = clone self::$freezed_timestamp;
            $new_freezed_timestamp->modify("+{$seconds} seconds");
            self::$freezed_timestamp = $new_freezed_timestamp;
            return true;
        } else {
            return sleep($seconds) === 0;
        }
    }

    /**
     * Freeze the time at a given datetime (can also be a timestamp)
     *
     * @param \DateTime|integer $datetime
     */
    public static function freeze(mixed $datetime): void
    {
        self::$freezed_timestamp = $datetime;
    }

    /**
     * Unfreeze the time.
     */
    public static function unfreeze(): void
    {
        self::$freezed_timestamp = null;
    }
}
