<?php

namespace Minz;

/**
 * The Response represents the answer given to a Request. It is returned to a client.
 *
 * The response is initialized and returned by a controller action. It contains
 * a status code, headers and optional cookies and output.
 *
 * The code must be a valid HTTP code, even in CLI mode.
 *
 * A response almost always declares a Content-Type and a Content-Security-Policy
 * headers (except when it's not pertinent).
 *
 * An Output can be attached to a Response. The output is in charge of
 * generating the content to return to the client. There are three kind of
 * output implementing the Output interface:
 *
 * - \Minz\Output\File: to serve a file;
 * - \Minz\Output\Text: to render text;
 * - \Minz\Output\View: to generate more complex structures (e.g. HTML) based
 *   on a simple template system.
 *
 * For instance, to generate a text response:
 *
 * ```php
 * $text_output = new \Minz\Output\Text('some text');
 * $response = new Response(200, $text_output);
 *
 * // Can be shortened in
 * $response = Response::text(200, 'some text');
 * ```
 *
 * Or to generate a HTML response:
 *
 * ```php
 * $view_output = new Output\View('pointer/to/view.phtml', [
 *     'foo' => 'bar',
 * ]);
 * $response = new Response(200, $view_output);
 *
 * // Can be shortened in
 * $response = Response::ok('pointer/to/view.phtml', [
 *     'foo' => 'bar',
 * ]);
 * ```
 *
 * You most probably want to use the short versions to generate responses since
 * they are a lot easier to use. It’s important to know the mechanics though.
 * It can be useful to create new kind of outputs for instance.
 *
 * @see \Minz\Output\File
 * @see \Minz\Output\Text
 * @see \Minz\Output\View
 * @see \Minz\Output\Output
 *
 * The responses are returned to the calling script which must generate the
 * corresponding headers, cookies and content. For instance, in `public/index.php`:
 *
 * ```php
 * $application = new \myapp\Application();
 * $response = $application->run($request);
 *
 * http_response_code($response->code());
 *
 * foreach ($response->cookies() as $cookie) {
 *     setcookie($cookie['name'], $cookie['value'], $cookie['options']);
 * }
 *
 * foreach ($response->headers() as $header) {
 *     header($header);
 * }
 *
 * echo $response->render();
 * ```
 *
 * In a CLI script, it is quite different since cookies and headers aren't
 * relevant:
 *
 * ```php
 * $application = new \myapp\cli\Application();
 * $response = $application->run($request);
 *
 * echo $response->render() . "\n";
 *
 * $code = $response->code();
 * if ($code >= 200 && $code < 300) {
 *     exit(0);
 * } else {
 *     exit(1);
 * }
 * ```
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Response
{
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

    /** @var integer */
    private $code;

    /** @var array<string, mixed> */
    private $headers = [];

    /** @var array */
    private $cookies = [];

    /** @var \Minz\Output\Output|null */
    private $output;

    /**
     * Create a OK response (HTTP 200) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * Create a created response (HTTP 201) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * Create an accepted response (HTTP 202) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * Create a no content response (HTTP 204).
     *
     * @return \Minz\Response
     */
    public static function noContent()
    {
        return new Response(204);
    }

    /**
     * Create a moved permanently response (HTTP 301).
     *
     * @param string $url
     *     The url to redirect to.
     *
     * @return \Minz\Response
     */
    public static function movedPermanently($url)
    {
        $response = new Response(301);
        $response->setHeader('Location', $url);
        return $response;
    }

    /**
     * Create a found response (HTTP 302).
     *
     * @param string $url
     *     The url to redirect to.
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
     * Create a found response (HTTP 302) to an action pointer.
     *
     * It is a shortcut to generate internal redirections. For instance:
     *
     * ```php
     * // In Application.php
     * $router = new \Minz\Router();
     * $router->addRoute('get', '/', 'Pages#Home', 'home');
     * \Minz\Url::setRouter($router);
     *
     * // In a controller action
     * $response = \Minz\Response::redirect('home');
     * ```
     *
     * @param string $action_pointer_or_name
     *     An action pointer or action name declared in the router.
     * @param array $parameters
     *     The parameters to build the action URL (empty array by default).
     *
     * @return \Minz\Response
     */
    public static function redirect($action_pointer_or_name, $parameters = [])
    {
        $url = Url::for($action_pointer_or_name, $parameters);
        return self::found($url);
    }

    /**
     * Create a bad request response (HTTP 400) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * Create an unauthorized response (HTTP 401) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * Create a not found response (HTTP 404) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * Create an internal server error response (HTTP 500) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param string $view_pointer
     *     A pointer to an existing view file under src/views (default is an
     *     empty string).
     * @param mixed[] $variables
     *     The variables to pass to the View (default is an empty array)
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
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
     * @see \Minz\Output\Text
     *
     * @param integer $code
     *     The HTTP status code.
     * @param string $text
     *     The text to render.
     *
     * @return \Minz\Response
     */
    public static function text($code, $text)
    {
        $output = new \Minz\Output\Text($text);
        return new Response($code, $output);
    }

    /**
     * Create a Json response with the given HTTP code.
     *
     * @see https://www.php.net/manual/function.json-encode.php
     *
     * @param integer $code
     *     The HTTP status code.
     * @param mixed $value
     *     The value to encode with json_encode.
     *
     * @return \Minz\Response
     */
    public static function json($code, $value)
    {
        $json = json_encode($value);
        $output = new \Minz\Output\Text($json);
        $response = new Response($code, $output);
        $response->setHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Create a Response from a HTTP status code and an optional output.
     *
     * @param integer $code
     *     The HTTP status code.
     * @param \Minz\Output\Output|null $output
     *     The Output to set to the response (optional).
     *
     * @throws \Minz\Errors\ResponseError
     *     Raised if the code is not a valid HTTP status code.
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

    /**
     * @return \Minz\Output\Output
     */
    public function output()
    {
        return $this->output;
    }

    /**
     * @param \Minz\Output\Output|null $output
     *
     * @return void
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

    /**
     * @return integer
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * @param integer $code
     *
     * @throws \Minz\Errors\ResponseError
     *     Raised if the code is not a valid HTTP status code.
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
     * @param string $name
     *     The header name to set.
     * @param mixed $value
     *     The value of the header.
     *
     * @return void
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Change a Content-Security-Policy directive.
     *
     * `Content-Security-Policy` header allows to control resources a client is
     * allowed to load.
     *
     * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Security-Policy
     *
     * @param string $directive
     *     The CSP directive name.
     * @param string $sources
     *     The sources for which the directive is allowed.
     *
     * @return void
     */
    public function setContentSecurityPolicy($directive, $sources)
    {
        $this->headers['Content-Security-Policy'][$directive] = $sources;
    }

    /**
     * Return the headers of the Response.
     *
     * Headers can be returned as strings via the `$raw` parameter in order to
     * be passed to the PHP `header()` function. For instance:
     *
     * ```php
     * $response->setHeader('Content-Type', 'text/plain');
     * $response->setContentSecurityPolicy('default-src', "'self'");
     * $response->setContentSecurityPolicy('style-src', "'self' 'unsafe-inline'");
     *
     * $response->headers(true);
     * // will return
     * [
     *     'Content-Type' => 'text/plain',
     *     'Content-Security-Policy' => [
     *         'default-src' => "'self'",
     *         'style-src' => "'self' 'unsafe-inline'",
     *     ],
     * ]
     *
     * $response->headers(false);
     * // will return
     * [
     *     'Content-Type: text/plain',
     *     "Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'",
     * ]
     * ```
     *
     * @param boolean $raw
     *     True indicates that headers should be returned as is, false (default)
     *     that they should be processed in order to be passed to the PHP
     *     `header()` function.
     *
     * @return array
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
     * Parameters are similar to `setcookie()` PHP function but default options
     * are more robust to improve the security.
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
     * Note that `setcookie()` is not called by this method. It must be called
     * manually in the `public/index.php` file for instance. Per consequence,
     * it doesn't generate a `E_WARNING` error if options are invalid and it
     * doesn't return any boolean.
     *
     * @see https://www.php.net/manual/function.setcookie.php
     *
     * @param string $name
     *     The name of the cookie.
     * @param string $value
     *     The value of the cookie.
     * @param array $options
     *     Additional options
     *
     * @return void
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
     * To remove the cookie, the expiration date is set to 1 year in the past.
     *
     * @param string $name
     *     The name of the cookie to remove.
     * @param array $options
     *     Additional options to pass to the cookie.
     *
     * @return void
     */
    public function removeCookie($name, $options = [])
    {
        $options['expires'] = Time::ago(1, 'year')->getTimestamp();
        $this->setCookie($name, '', $options);
    }

    /**
     * Return the list of cookies.
     *
     * The cookies are represented as an array with the following keys: name,
     * value and options. They can be passed as arguments to the `setcookie()`
     * function.
     *
     * @see https://www.php.net/manual/function.setcookie.php
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
     * @return string
     *     Return the output, or an empty string if the response doesn't have
     *     any output.
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
