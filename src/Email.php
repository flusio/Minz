<?php

namespace Minz;

/**
 * Provide utility functions related to email addresses.
 *
 * @phpstan-type EmailMode 'php'|'loose'
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Email
{
    /**
     * Sanitize an email address (trim, lowercase and punycode)
     */
    public static function sanitize(string $email): string
    {
        return mb_strtolower(self::emailToPunycode(trim($email)));
    }

    /**
     * Encode an email with Punycode.
     *
     * @see https://en.wikipedia.org/wiki/Punycode
     */
    public static function emailToPunycode(string $email): string
    {
        $at_position = strrpos($email, '@');

        if ($at_position === false || !function_exists('idn_to_ascii')) {
            return $email;
        }

        $domain = substr($email, $at_position + 1);
        $domain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if ($domain !== false) {
            $email = substr($email, 0, $at_position + 1) . $domain;
        }

        return $email;
    }

    /**
     * Return wheter an email address is valid or not.
     *
     * You can pass "loose" as $mode parameter to check the email with a simple
     * regex expression.
     *
     * @param EmailMode $mode
     */
    public static function validate(string $email, string $mode = 'php'): bool
    {
        if ($mode === 'php') {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        } else {
            return preg_match('/^\S+\@\S+$/', $email) === 1;
        }
    }
}
