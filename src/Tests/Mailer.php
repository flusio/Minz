<?php

namespace Minz\Tests;

/**
 * Allow to access sent emails during tests. If configuration mailer type is
 * `test`, the PHPMailer object is stored in the \Minz\Tests\Mailer::$emails
 * static attribute and can be accessed then to test different values.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Mailer
{
    /** @var \PHPMailer\PHPMailer\PHPMailer[] */
    public static $emails = [];

    /**
     * Store a PHPMailer object in $emails.
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
     */
    public static function store($phpmailer)
    {
        self::$emails[] = clone($phpmailer);
    }

    /**
     * Clear the list of emails.
     */
    public static function clear()
    {
        self::$emails = [];
    }

    /**
     * Return the number of sent emails.
     *
     * @return integer
     */
    public static function count()
    {
        return count(self::$emails);
    }

    /**
     * Return the $n email.
     *
     * @param integer $n (default is 0)
     *
     * @param \PHPMailer\PHPMailer\PHPMailer|null
     */
    public static function take($n = 0)
    {
        if (isset(self::$emails[$n])) {
            return self::$emails[$n];
        } else {
            return null;
        }
    }
}
