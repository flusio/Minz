<?php

namespace Minz;

/**
 * Wrapper around `date_create()` function, to provide test capabilities.
 */
class Time
{
    /** @var \DateTime|integer|null */
    public static $freezed_timestamp;

    /**
     * @return \DateTime
     */
    public static function now()
    {
        if (self::$freezed_timestamp && is_int(self::$freezed_timestamp)) {
            $date = new \DateTime();
            $date->setTimestamp(self::$freezed_timestamp);
            return $date;
        } elseif (self::$freezed_timestamp && self::$freezed_timestamp instanceof \DateTime) {
            return clone self::$freezed_timestamp;
        } else {
            return \date_create();
        }
    }

    /**
     * Return a datetime relative to now()
     *
     * @see https://www.php.net/manual/datetime.modify.php
     * @see https://www.php.net/manual/datetime.formats.relative.php
     *
     * @param string $modifier
     *
     * @return \DateTime
     */
    public static function relative($modifier)
    {
        $datetime = self::now();
        $datetime->modify($modifier);
        return $datetime;
    }

    /**
     * Return a datetime from the future.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param integer $number
     * @param string $unit
     *
     * @return \DateTime
     */
    public static function fromNow($number, $unit)
    {
        return self::relative("+{$number} {$unit}");
    }

    /**
     * Return a datetime from the past.
     *
     * @see https://www.php.net/manual/en/datetime.formats.relative.php
     *
     * @param integer $number
     * @param string $unit
     *
     * @return \DateTime
     */
    public static function ago($number, $unit)
    {
        return self::relative("-{$number} {$unit}");
    }

    /**
     * Freeze the time at a given datetime (can also be a timestamp)
     *
     * @param \DateTime|integer $datetime
     */
    public static function freeze($datetime)
    {
        self::$freezed_timestamp = $datetime;
    }

    /**
     * Unfreeze the time.
     */
    public static function unfreeze()
    {
        self::$freezed_timestamp = null;
    }
}
