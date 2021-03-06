<?php

namespace Minz;

/**
 * The Response represents the answer given to a Request, and returned to the
 * user.
 *
 * An Output can be attached to a Response. This is destined to generate the
 * content to return to the user.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Response
{
    /** @var integer[] */
    public const VALID_HTTP_CODES = [
        100, 101,
        200, 201, 202, 203, 204, 205, 206,
        300, 301, 302, 303, 304, 305, 306, 307,
        400, 401, 402, 403, 404, 405, 406, 407, 408, 409,
        410, 411, 412, 413, 414, 415, 416, 417,
        500, 501, 502, 503, 504, 505,
    ];

    public const DEFAULT_CSP = [
        'default-src' => "'self'",
    ];

    /**
     * Create a successful response (HTTP 200) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function ok($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(200, $view);
    }

    /**
     * Create a created response (HTTP 201) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function created($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(201, $view);
    }

    /**
     * Create an accepted response (HTTP 202) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function accepted($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(202, $view);
    }

    /**
     * Create a no content response (HTTP 204) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function noContent()
    {
        return new Response(204);
    }

    /**
     * Create a found response (HTTP 302).
     *
     * @param string $url
     *
     * @return \Minz\Response
     */
    public static function found($url)
    {
        $response = new Response(302);
        $response->setHeader('Location', $url);
        return $response;
    }

    /**
     * Create a found response (HTTP 302) with internal action pointer.
     *
     * @param string $action_pointer
     * @param array $parameters
     *
     * @return \Minz\Response
     */
    public static function redirect($action_pointer, $parameters = [])
    {
        $url = Url::for($action_pointer, $parameters);
        return self::found($url);
    }

    /**
     * Create a bad request response (HTTP 400) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function badRequest($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(400, $view);
    }

    /**
     * Create an unauthorized response (HTTP 401) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function unauthorized($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(401, $view);
    }

    /**
     * Create a not found response (HTTP 404) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function notFound($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(404, $view);
    }

    /**
     * Create an internal server error response (HTTP 500) with a Output\View.
     *
     * @param string $view_pointer
     * @param mixed[] $variables
     *
     * @throws \Minz\Errors\OutputError
     *
     * @return \Minz\Response
     */
    public static function internalServerError($view_pointer = '', $variables = [])
    {
        if ($view_pointer) {
            $view = new Output\View($view_pointer, $variables);
        } else {
            $view = null;
        }
        return new Response(500, $view);
    }

    /**
     * Create a text response with the given HTTP code.
     *
     * @param integer $code
     * @param string $text
     *
     * @return \Minz\Response
     */
    public static function text($code, $text)
    {
        $output = new \Minz\Output\Text($text);
        return new Response($code, $output);
    }

    /**
     * Create a Response from a HTTP status code.
     *
     * @param integer $code The HTTP code to set for the response
     * @param \Minz\Output\Output $output The output to set to the response (optional)
     *
     * @throws \Minz\Errors\ResponseError if the code is not a valid HTTP status code
     */
    public function __construct($code, $output = null)
    {
        $this->setCode($code);
        $this->setOutput($output);
        if ($output) {
            $content_type = $output->contentType();
        } else {
            $content_type = 'text/plain';
        }
        $this->setHeader('Content-Type', $content_type);
        $this->setHeader('Content-Security-Policy', self::DEFAULT_CSP);
    }

    /** @var integer */
    private $code;

    /** @var string[] */
    private $headers = [];

    /** @var array */
    private $cookies = [];

    /** @var \Minz\Output\Output */
    private $output;

    /**
     * @return \Minz\Output\Output The current output
     */
    public function output()
    {
        return $this->output;
    }

    /**
     * @param \Minz\Output\Output $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return integer The current HTTP code
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * @param integer $code
     *
     * @throws \Minz\Errors\ResponseError if the code is not a valid HTTP status code
     *
     * @return void
     */
    public function setCode($code)
    {
        if (!in_array($code, self::VALID_HTTP_CODES)) {
            throw new Errors\ResponseError("{$code} is not a valid HTTP code.");
        }

        $this->code = $code;
    }

    /**
     * Add or replace a HTTP header.
     *
     * @param string $header
     * @param string $value
     *
     * @return void
     */
    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Helper to change CSP header
     *
     * @param string $directive
     * @param string $sources
     */
    public function setContentSecurityPolicy($directive, $sources)
    {
        $this->headers['Content-Security-Policy'][$directive] = $sources;
    }

    /**
     * Return the headers as strings to be passed to the PHP header function.
     *
     * @param boolean $raw True to return the raw array (false by default)
     *
     * @return string[] The list of actual headers
     */
    public function headers($raw = false)
    {
        if ($raw) {
            return $this->headers;
        }

        $headers = [];
        foreach ($this->headers as $header => $header_value) {
            if (is_array($header_value)) {
                $values = [];
                foreach ($header_value as $key => $value) {
                    $values[] = $key . ' ' . $value;
                }
                $header_value = implode('; ', $values);
            }
            $headers[] = "{$header}: {$header_value}";
        }
        return $headers;
    }

    /**
     * Set a cookie to the response.
     *
     * Parameters are similar to setcookie PHP function but default options are
     * more robust to improve the security.
     *
     * Default options are:
     *
     * - expires: 0
     * - domain: the value from url_options configuration (unless if it's
     *   localhost)
     * - path: the value from url_options configuration
     * - secure: true if url_options protocol is https
     * - httponly: true
     * - samesite: Strict
     *
     * Also, the cookies are not immediately sent to let the developer to
     * choose the correct moment.
     *
     * @see https://www.php.net/manual/function.setcookie.php
     *
     * @param string $name
     * @param string $value
     * @param array $options
     */
    public function setCookie($name, $value, $options = [])
    {
        $url_options = Configuration::$url_options;
        $default_options = [
            'expires' => 0,
            'path' => $url_options['path'],
            'secure' => $url_options['protocol'] === 'https',
            'httponly' => true,
            'samesite' => 'Strict',
        ];

        // Some browsers don't accept cookies if domain is localhost
        // @see https://stackoverflow.com/a/1188145
        if ($url_options['host'] !== 'localhost') {
            $default_options['domain'] = $url_options['host'];
        }

        $this->cookies[$name] = [
            'name' => $name,
            'value' => $value,
            'options' => array_merge($default_options, $options),
        ];
    }

    /**
     * Send instructions to the browser to remove a cookie.
     *
     * @param string $name
     * @param array $options
     */
    public function removeCookie($name, $options = [])
    {
        $options['expires'] = Time::ago(1, 'year')->getTimestamp();
        $this->setCookie($name, '', $options);
    }

    /**
     * Return the list of cookies
     *
     * The cookies are represented as an array with the following keys: name,
     * value and options. They can be passed as setcookie function arguments.
     *
     * @return array
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Generate and return the content of the output.
     *
     * @return string Return the output, or an empty string if no outputs are attached.
     */
    public function render()
    {
        if ($this->output) {
            return $this->output->render();
        } else {
            return '';
        }
    }
}
