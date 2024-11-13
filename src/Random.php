<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

class Random
{
    /**
     * Return a random cryptographically secure string containing characters in
     * range 0-9a-f.
     *
     * @throws \InvalidArgumentException
     *     If the length is less than 1
     *
     * @see https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
     */
    public static function hex(int $length): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be a positive integer');
        }

        $string = '';
        $alphabet = '0123456789abcdef';
        $alphamax = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $string .= $alphabet[random_int(0, $alphamax)];
        }

        return $string;
    }

    /**
     * Return a random cryptographically secure integer where first bits are
     * the current timestamp in milliseconds and last 20 bits are random.
     *
     * Please note the result is returned as a string.
     */
    public static function timebased(?\DateTimeInterface $datetime = null): string
    {
        if (!$datetime) {
            $datetime = Time::now();
        }

        $microtime = (float) $datetime->format('U.u');
        $milliseconds = (int) ($microtime * 1000);
        $time_part = $milliseconds << 20;
        $random_part = random_int(0, pow(2, 20) - 1); // max number on 20 bits
        $random_string = strval($time_part | $random_part);
        return str_pad($random_string, 19, '0', STR_PAD_LEFT);
    }
}
