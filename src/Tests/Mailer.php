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
        self::$emails[] = $phpmailer;
    }

    /**
     * Clear the list of emails.
     */
    public static function clear()
    {
        self::$emails = [];
    }
}
