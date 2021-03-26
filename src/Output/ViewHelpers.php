<?php

/**
 * These functions are mean to be used inside View files. They are declared in
 * global namespace so we don't have to declare the namespaces in views.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */

/**
 * Alias for htmlspecialchars
 *
 * @see https://www.php.net/manual/function.htmlspecialchars.php
 *
 * @param string $variable
 *
 * @return string
 */
function protect($variable)
{
    return htmlspecialchars($variable, ENT_COMPAT, 'UTF-8');
}

/**
 * Alias for \Minz\Url::for
 *
 * @see \Minz\Url::for
 */
function url($action_pointer, $parameters = [])
{
    return protect(\Minz\Url::for($action_pointer, $parameters));
}

/**
 * Alias for \Minz\Url::absoluteFor
 *
 * @see \Minz\Url::absoluteFor
 */
function url_full($action_pointer, $parameters = [])
{
    return protect(\Minz\Url::absoluteFor($action_pointer, $parameters));
}

/**
 * Return the relative URL for a static file (under public/static/ folder)
 *
 * @param string $filename
 *
 * @return string
 */
function url_static($filename)
{
    $filepath = \Minz\Configuration::$app_path . '/public/static/' . $filename;
    $modification_time = @filemtime($filepath);

    $file_url = \Minz\Url::path() . '/static/' . $filename;
    if ($modification_time) {
        return protect($file_url . '?' . $modification_time);
    } else {
        return protect($file_url);
    }
}

/**
 * Return the absolute URL for a static file (under public/static/ folder)
 *
 * @param string $filename
 *
 * @return string
 */
function url_full_static($filename)
{
    return \Minz\Url::baseUrl() . url_static($filename);
}

/**
 * Return the relative URL for a public file (under public/ folder). Note you
 * should probably use url_static() if you target a file under public/static/.
 *
 * @param string $filename
 *
 * @return string
 */
function url_public($filename)
{
    return protect(\Minz\Url::path() . '/' . $filename);
}

/**
 * Return the absolute URL for a public file (under public/ folder). Note you
 * should probably use url_full_static() if you target a file under public/static/.
 *
 * @param string $filename
 *
 * @return string
 */
function url_full_public($filename)
{
    return \Minz\Url::baseUrl() . url_public($filename);
}

/**
 * Return a CSRF token
 *
 * @return string
 */
function csrf_token()
{
    return (new \Minz\CSRF())->generateToken();
}

/**
 * Return a formatted datetime
 *
 *
 * @see https://www.php.net/manual/en/function.strftime
 *
 * @param \DateTime $date The datetime to format
 * @param string $format Default is `%A %e %B`
 *
 * @return string
 */
function _d($date, $format = '%A %e %B')
{
    return strftime($format, $date->getTimestamp());
}

/**
 * Return a translated and formatted message
 *
 * @see https://www.php.net/manual/en/function.gettext
 * @see https://www.php.net/manual/en/function.vsprintf.php
 *
 * @param string $message
 * @param mixed $args,... Arguments to pass to the vsprintf function
 *
 * @return string
 */
function _f($message, ...$args)
{
    return vsprintf(gettext($message), $args);
}

/**
 * Alias for ngettext
 *
 * @see https://www.php.net/manual/en/function.ngettext
 *
 * @param string $message1
 * @param string $message2
 * @param integer $n
 *
 * @return string
 */
function _n($message1, $message2, $n)
{
    return ngettext($message1, $message2, $n);
}

/**
 * Combine _n and _f functions
 *
 * @param string $message1
 * @param string $message2
 * @param integer $n
 * @param mixed $args,... Arguments to pass to the vsprintf function
 *
 * @return string
 */
function _nf($message1, $message2, $n, ...$args)
{
    return _f(_n($message1, $message2, $n), ...$args);
}
