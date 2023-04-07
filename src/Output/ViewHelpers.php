<?php

namespace Minz\Output;

/**
 * The ViewHelpers class defines functions to be used inside View files.
 *
 * They are redeclared as functions with no namespaces in the view_helpers.php
 * file so we don't have to declare the namespaces in views.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ViewHelpers
{
    /**
     * Alias for htmlspecialchars.
     *
     * @see https://www.php.net/manual/function.htmlspecialchars.php
     *
     * @param string|null $variable
     *
     * @return string
     */
    public static function protect($variable)
    {
        if (!$variable) {
            return '';
        }

        return htmlspecialchars($variable, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Return a protected relative URL (safe to display in views).
     *
     * @see \Minz\Url::for
     */
    public static function url($action_pointer, $parameters = [])
    {
        return self::protect(\Minz\Url::for($action_pointer, $parameters));
    }

    /**
     * Return a protected absolute URL (safe to display in views).
     *
     * @see \Minz\Url::absoluteFor
     */
    public static function urlFull($action_pointer, $parameters = [])
    {
        return self::protect(\Minz\Url::absoluteFor($action_pointer, $parameters));
    }

    /**
     * Return a protected relative URL for a static file (under public/static/ folder).
     *
     * @param string $filename
     *
     * @return string
     */
    public static function urlStatic($filename)
    {
        $filepath = \Minz\Configuration::$app_path . '/public/static/' . $filename;
        $modification_time = @filemtime($filepath);

        $file_url = \Minz\Url::path() . '/static/' . $filename;
        if ($modification_time) {
            return self::protect($file_url . '?' . $modification_time);
        } else {
            return self::protect($file_url);
        }
    }

    /**
     * Return a protected absolute URL for a static file (under public/static/ folder).
     *
     * @param string $filename
     *
     * @return string
     */
    public static function urlFullStatic($filename)
    {
        return \Minz\Url::baseUrl() . self::urlStatic($filename);
    }

    /**
     * Return a protected relative URL for a public file (under public/ folder).
     *
     * Note you should use self::urlStatic() if you target a file under public/static/.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function urlPublic($filename)
    {
        return self::protect(\Minz\Url::path() . '/' . $filename);
    }

    /**
     * Return a protected absolute URL for a public file (under public/ folder).
     *
     * Note you should use self::urlFullStatic() if you target a file under public/static/.
     *
     * @param string $filename
     *
     * @return string
     */
    public static function urlFullPublic($filename)
    {
        return \Minz\Url::baseUrl() . self::urlPublic($filename);
    }

    /**
     * Return a formatted and translated datetime.
     *
     * @see https://www.php.net/manual/class.intldateformatter.php
     * @see https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
     *
     * @param \DateTime $date
     *     The datetime to format.
     * @param string $format
     *     The expected format (default is `EEEE d MMMM`).
     * @param string $locale
     *     The locale to use to translate (null by default, it considers the
     *     value of the INI `intl.default_locale` value).
     *
     * @return string
     */
    public static function formatDate($date, $format = 'EEEE d MMMM', $locale = null)
    {
        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            null,
            null,
            $format
        );
        return $formatter->format($date);
    }

    /**
     * Return a translated and formatted message.
     *
     * @see https://www.php.net/manual/function.gettext
     * @see https://www.php.net/manual/function.vsprintf.php
     *
     * @param string $message
     * @param mixed $args,... Arguments to pass to the vsprintf function
     *
     * @return string
     */
    public static function formatGettext($message, ...$args)
    {
        return vsprintf(gettext($message), $args);
    }

    /**
     * Return a translated and formatted message (plural version).
     *
     * @see https://www.php.net/manual/function.ngettext
     * @see https://www.php.net/manual/function.vsprintf.php
     *
     * @param string $message1
     * @param string $message2
     * @param integer $n
     * @param mixed $args,... Arguments to pass to the vsprintf function
     *
     * @return string
     */
    public static function formatNgettext($message1, $message2, $n, ...$args)
    {
        return vsprintf(ngettext($message1, $message2, $n), $args);
    }
}
