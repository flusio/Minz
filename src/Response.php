<?php

namespace Minz;

use Minz\Output;

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
 * @see \Minz\Output
 * @see \Minz\Output\File
 * @see \Minz\Output\Text
 * @see \Minz\Output\View
 *
 * The responses are returned to the calling script which must generate the
 * corresponding headers, cookies and content. For instance, in `public/index.php`:
 *
 * ```php
 * $application = new \App\Application();
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
 *
 * // Can be shortened in
 * \Minz\Response::sendByHttp($response);
 * ```
 *
 * In a CLI script, it is quite different since cookies and headers aren't
 * relevant:
 *
 * ```php
 * $application = new \App\Application();
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
 *
 * // Can be shortened in
 * \Minz\Response::sendToCli($response);
 * ```
 *
 * @phpstan-import-type UrlPointer from Url
 *
 * @phpstan-import-type UrlParameters from Url
 *
 * @phpstan-import-type ViewPointer from Output\View
 *
 * @phpstan-import-type ViewVariables from Output\View
 *
 * @phpstan-type ResponseHttpCode value-of<Response::VALID_HTTP_CODES>
 *
 * @phpstan-type ResponseHeaders array<string, ResponseHeader>
 *
 * @phpstan-type ResponseHeader string|array<string, string>
 *
 * @phpstan-type ResponseCookies array<string, ResponseCookie>
 *
 * @phpstan-type ResponseCookie array{
 *     'name': string,
 *     'value': string,
 *     'options': array{
 *         'expires': int,
 *         'path': string,
 *         'secure': bool,
 *         'httponly': bool,
 *         'samesite': 'Strict'|'Lax'|'None',
 *         'domain'?: string,
 *     },
 * }
 *
 * @phpstan-type CookieOptions array{
 *     'expires'?: int,
 *     'path'?: string,
 *     'secure'?: bool,
 *     'httponly'?: bool,
 *     'samesite'?: 'Strict'|'Lax'|'None',
 *     'domain'?: string,
 * }
 *
 * @phpstan-type ResponseGenerator \Generator<int, Response, void, void>
 *
 * @phpstan-type ResponseReturnable Response|ResponseGenerator
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

    /** @var ResponseHttpCode */
    private int $code;

    /** @var ResponseHeaders */
    private array $headers = [];

    /** @var ResponseCookies */
    private array $cookies = [];

    private ?Output $output;

    /**
     * Create a OK response (HTTP 200) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function ok(?string $view_pointer = null, array $variables = []): Response
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
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function created(?string $view_pointer = null, array $variables = []): Response
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
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function accepted(?string $view_pointer = null, array $variables = []): Response
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
     */
    public static function noContent(): Response
    {
        return new Response(204);
    }

    /**
     * Create a moved permanently response (HTTP 301).
     */
    public static function movedPermanently(string $url): Response
    {
        $response = new Response(301);
        $response->setHeader('Location', $url);
        return $response;
    }

    /**
     * Create a found response (HTTP 302).
     */
    public static function found(string $url): Response
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
     * @param UrlPointer $pointer
     * @param UrlParameters $parameters
     */
    public static function redirect(string $pointer, array $parameters = []): Response
    {
        $url = Url::for($pointer, $parameters);
        return self::found($url);
    }

    /**
     * Create a bad request response (HTTP 400) with an optional Output\View.
     *
     * @see \Minz\Output\View
     *
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function badRequest(?string $view_pointer = null, array $variables = []): Response
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
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function unauthorized(?string $view_pointer = null, array $variables = []): Response
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
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function notFound(?string $view_pointer = null, array $variables = []): Response
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
     * @param ?ViewPointer $view_pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the view file doesn't exist.
     */
    public static function internalServerError(?string $view_pointer = null, array $variables = []): Response
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
     * @param ResponseHttpCode $code
     */
    public static function text(int $code, string $text): Response
    {
        $output = new \Minz\Output\Text($text);
        return new Response($code, $output);
    }

    /**
     * Create a Json response with the given HTTP code.
     *
     * @see https://www.php.net/manual/function.json-encode.php
     *
     * @param ResponseHttpCode $code
     * @param mixed[] $values
     */
    public static function json(int $code, array $values): Response
    {
        $json = json_encode($values);
        if (!$json) {
            $json = '';
        }
        $output = new \Minz\Output\Text($json);
        $response = new Response($code, $output);
        $response->setHeader('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Create a Response from a HTTP status code and an optional output.
     *
     * @param ResponseHttpCode $code
     *
     * @throws \Minz\Errors\ResponseError
     *     Raised if the code is not a valid HTTP status code.
     */
    public function __construct(int $code, ?Output $output = null)
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

    public function output(): ?Output
    {
        return $this->output;
    }

    public function setOutput(?Output $output): void
    {
        $this->output = $output;
    }

    /**
     * @return ResponseHttpCode
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * @param ResponseHttpCode $code
     *
     * @throws \Minz\Errors\ResponseError
     *     Raised if the code is not a valid HTTP status code.
     */
    public function setCode(int $code): void
    {
        if (!in_array($code, self::VALID_HTTP_CODES)) {
            throw new Errors\ResponseError("{$code} is not a valid HTTP code.");
        }

        $this->code = $code;
    }

    /**
     * Add or replace a HTTP header.
     *
     * @param ResponseHeader $value
     */
    public function setHeader(string $name, mixed $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Change sources of a Content-Security-Policy directive.
     *
     * `Content-Security-Policy` header allows to control resources a client is
     * allowed to load.
     *
     * @see https://developer.mozilla.org/docs/Web/HTTP/Headers/Content-Security-Policy
     */
    public function setContentSecurityPolicy(string $directive, string $sources): void
    {
        if (!is_array($this->headers['Content-Security-Policy'])) {
            $this->headers['Content-Security-Policy'] = [];
        }

        $this->headers['Content-Security-Policy'][$directive] = $sources;
    }

    /**
     * Add sources to a Content-Security-Policy directive.
     *
     * Contrary to the setContentSecurityPolicy, it doesn't override the
     * previous directive.
     */
    public function addContentSecurityPolicy(string $directive, string $sources): void
    {
        if (!is_array($this->headers['Content-Security-Policy'])) {
            $this->headers['Content-Security-Policy'] = [];
        }

        $previous_sources = $this->headers['Content-Security-Policy'][$directive] ?? '';
        $sources = trim($previous_sources . ' ' . $sources);
        $this->setContentSecurityPolicy($directive, $sources);
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
     * @return ResponseHeaders|array<string>
     */
    public function headers(bool $raw = false): array
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
     * @param CookieOptions $options
     */
    public function setCookie(string $name, string $value, array $options = []): void
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
     * @param CookieOptions $options
     */
    public function removeCookie(string $name, array $options = []): void
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
     * @return ResponseCookies
     */
    public function cookies(): array
    {
        return $this->cookies;
    }

    /**
     * Generate and return the content of the output, or an empty string if
     * no Output is set.
     */
    public function render(): string
    {
        if ($this->output) {
            return $this->output->render();
        } else {
            return '';
        }
    }

    /**
     * Send a Response with HTTP headers and cookies.
     *
     * If the given argument is a Generator, only the first Response headers
     * are sent, but all the outputs are echoed in a loop until the Generator
     * returns no Response.
     *
     * You can pass a second argument to not output the response. This is
     * useful for HEAD requests. For instance:
     *
     *     $is_head = strtoupper($_SERVER['REQUEST_METHOD']) === 'HEAD';
     *     \Minz\Response::sendByHttp($response, echo_output: !$is_head);
     *
     * @param ResponseReturnable $response
     */
    public static function sendByHttp(mixed $response, bool $echo_output = true): void
    {
        if ($response instanceof \Generator) {
            $response_generator = $response;
            $response = $response_generator->current();
        }

        http_response_code($response->code());

        foreach ($response->cookies() as $cookie) {
            setcookie($cookie['name'], $cookie['value'], $cookie['options']);
        }

        /** @var string[] */
        $headers = $response->headers();
        foreach ($headers as $header) {
            header($header);
        }

        if (!$echo_output) {
            return;
        }

        if (isset($response_generator)) {
            foreach ($response_generator as $response_part) {
                /** @var Response */
                $response_part = $response_part;
                echo $response_part->render();
            }
        } else {
            echo $response->render();
        }
    }

    /**
     * Echo a Response to standard output and exit with an error code.
     *
     * If the Response code is 2xx, the program will exit with code 0.
     * Otherwise, it will exit with the corresponding code.
     *
     * If the given argument is a Generator, only the first Response code is
     * considered, but all the outputs are echoed in a loop until the Generator
     * returns no Response.
     *
     * @param ResponseReturnable $response
     */
    public static function sendToCli(mixed $response): void
    {
        if ($response instanceof \Generator) {
            $response_generator = $response;
            $response = $response_generator->current();
        }

        $code = $response->code();

        if (isset($response_generator)) {
            foreach ($response_generator as $response_part) {
                /** @var Response */
                $response_part = $response_part;
                $output = $response_part->render();
                if ($output && $output[-1] === "\n") {
                    echo $output;
                } elseif ($output) {
                    echo $output . "\n";
                }
            }
        } else {
            $output = $response->render();
            if ($output && $output[-1] === "\n") {
                echo $output;
            } elseif ($output) {
                echo $output . "\n";
            }
        }

        if ($code >= 200 && $code < 300) {
            exit(0);
        } else {
            exit($code);
        }
    }
}
