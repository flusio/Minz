<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Request class abstracts a request from a user client.
 *
 * It generally represents HTTP requests, but it also can represent a request
 * from the CLI. Abstracting requests makes it easy to test applications based
 * on Minz.
 *
 * The main idea of Minz is to transform Request into Response via a controller
 * action. Thus, it’s essential to understand these classes.
 *
 * @see \Minz\Response
 *
 * A request is represented by a method (e.g. `GET`, `POST`), a path (e.g.
 * `/foo`) and some parameters and headers. It’s one of the first object to
 * initialize in an application. For instance, in `public/index.php`:
 *
 * ```php
 * $request = \Minz\Request::initFromGlobals();
 * ```
 *
 * Requests can also be used to handle command line (e.g. in a `cli.php` file):
 *
 * ```php
 * $request = \Minz\Request::initFromCli($argv);
 * ```
 *
 * With the above code, you can accept commands of this form:
 *
 * ```console
 * $ php cli.php some command --foo=bar --spam
 * ```
 *
 * It will generate the following request:
 *
 * ```php
 * $request = new \Minz\Request('CLI', '/some/command', [
 *     'foo' => 'bar',
 *     'spam' => true,
 * ]);
 * ```
 *
 * The request is compared by the Engine to the routes declared in the Router.
 * If it finds a correspondance, it executes the corresponding controller
 * action and pass the request as a parameter.
 *
 * @see \Minz\Engine
 * @see \Minz\Router
 *
 * You can get the parameters of a request very simply:
 *
 * ```php
 * $foo = $request->param('foo');
 * $bar = $request->param('bar', 'a default value');
 * ```
 *
 * You also can automatically cast parameters to the desire types:
 *
 * ```php
 * $boolean = $request->paramBoolean('boolean-param');
 * $integer = $request->paramInteger('integer-param');
 * $datetime = $request->paramDatetime('datetime-param');
 * $array = $request->paramArray('array-param');
 * $json = $request->paramJson('json-param');
 * $file = $request->paramFile('file-param');
 * ```
 *
 * Headers and cookies can be retrieved in a similar way (except that there are
 * no cast-methods):
 *
 * ```php
 * $accept_header = $request->header('HTTP_ACCEPT');
 * $my_cookie = $request->cookie('my_cookie');
 * ```
 *
 * @phpstan-type RequestMethod value-of<Request::VALID_METHODS>
 *
 * @phpstan-type RequestParameters array<string, mixed>
 *
 * @phpstan-type RequestHeaders array<string, mixed>
 */
class Request
{
    public const VALID_HTTP_METHODS = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'];
    public const VALID_METHODS = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'CLI'];

    /** @var RequestMethod */
    private string $method;

    private string $path;

    /** @var RequestParameters */
    private array $parameters;

    /** @var RequestHeaders */
    private array $headers;

    /**
     * Create a Request reading the global variables.
     *
     * @throws \Minz\Errors\RequestError
     *     Raised if the REQUEST_METHOD variable is invalid.
     */
    public static function initFromGlobals(): Request
    {
        $request_method = strtoupper($_SERVER['REQUEST_METHOD']);

        $http_method = $request_method === 'HEAD' ? 'GET' : $request_method;
        if (!in_array($http_method, self::VALID_HTTP_METHODS)) {
            throw new Errors\RequestError("The HTTP method '{$http_method}' is not supported.");
        }

        /** @var RequestMethod */
        $http_method = $http_method;

        $http_uri = $_SERVER['REQUEST_URI'];
        $http_parameters = array_merge(
            $_GET,
            $_POST,
            $_FILES,
            ['@input' => @file_get_contents('php://input')],
        );
        $http_headers = array_merge($_SERVER, [
            'COOKIE' => $_COOKIE,
        ]);

        return new Request($http_method, $http_uri, $http_parameters, $http_headers);
    }

    /**
     * Create a Request reading the CLI arguments.
     *
     * @param non-empty-list<string> $argv
     */
    public static function initFromCli(array $argv): Request
    {
        // Read command line parameters to create a Request
        $command = [];
        $parameters = [];

        // We need to skip the first argument which is the name of the script
        $arguments = array_slice($argv, 1);
        foreach ($arguments as $argument) {
            $result = preg_match('/^--(?P<option>[\w\-]+)(=(?P<argument>.+))?$/sm', $argument, $matches);
            if ($result) {
                $parameters[$matches['option']] = $matches['argument'] ?? true;
            } else {
                $command[] = $argument;
            }
        }

        $request_uri = implode('/', $command);
        if (!$request_uri) {
            $request_uri = '/help';
        } elseif ($request_uri[0] !== '/') {
            $request_uri = '/' . $request_uri;
        }

        $parameters['bin'] = $argv[0];

        return new \Minz\Request('CLI', $request_uri, $parameters);
    }

    /**
     * Create a Request
     *
     * @param RequestMethod $method
     *     The method that is executing the request. Its value must be one of
     *     the Request::VALID_METHODS. Valid methods are equivalent to a subset
     *     of HTTP verbs + the `cli` value (to handle requests from the CLI).
     *     `head` HTTP requests must be passed as `get` requests (they only
     *     differ at rendering). The method is lowercased before being compared
     *     to valid methods.
     *     For HTTP requests, its value usually comes from `$_SERVER['REQUEST_METHOD']`.
     *     For CLI requests, it always must be `cli`.
     * @param string $uri
     *     The URI that is executing the request. It can be a path starting by
     *     a slash (/), or a full URL from which the path will be extracted. If
     *     `Configuration::url_options['path']` is set, its value is removed
     *     from the beginning of the extracted path.
     *     For HTTP requests, its value usually comes from `$_SERVER['REQUEST_URI']`.
     *     CLI must respect this format as well and build the path by itself (cf.
     *     example above)
     * @param RequestParameters $parameters
     *     The parameters of the request where keys are the names of the parameters.
     *     The parameters can be retrieved with the `param*()` methods.
     *     For HTTP requests, its value usually is a merge of `$_GET`, `$_POST`
     *     and `$_FILE` global variables.
     *     CLI must build the array by itself (cf. example above)
     * @param RequestHeaders $headers
     *     The headers of the request where keys are the names of the headers.
     *     Cookies must be associated to the `COOKIE` key. Headers can be
     *     retrieved with the `header()` method, while cookies are retrieved
     *     with the `cookie()` one.
     *     For HTTP requests, its value usually is a merge of `$_SERVER` and
     *     `$_COOKIE` global variables.
     *     CLI requests usually don’t have headers.
     *
     * @throws \Minz\Errors\RequestError
     *     Raised if the method is invalid, if uri is empty or invalid, or if
     *     parameters or headers aren't arrays.
     *
     * @see \Minz\Configuration::$url_options
     * @see https://developer.mozilla.org/docs/Web/HTTP/Methods
     * @see https://developer.mozilla.org/docs/Web/HTTP/Headers
     * @see https://developer.mozilla.org/docs/Web/HTTP/Overview#requests
     */
    public function __construct(string $method, string $uri, array $parameters = [], array $headers = [])
    {
        if (!$uri) {
            throw new Errors\RequestError('URI cannot be empty.');
        }

        if ($uri[0] === '/') {
            // parse_url() has issues to parse URLs starting with multiple
            // leading slashes:
            // - it considers legitimately "foo" is the domain in "//foo", but
            //   it's very unlikely that the server will pass such a domain
            //   without expliciting the protocol;
            // - it simply fails for URLs starting with 3 slashes or more.
            // For these reasons, we consider all URIs starting by a slash to
            // be a path and we remove query and hash manually.
            $path = $uri;
            $pos_query = strpos($path, '?');
            if ($pos_query !== false) {
                $path = substr($path, 0, $pos_query);
            }
            $pos_hash = strpos($path, '#');
            if ($pos_hash !== false) {
                $path = substr($path, 0, $pos_hash);
            }
        } else {
            // In other cases, the URI probably contains the protocol and
            // domain, so we let parse_url to do its job.
            $uri_components = parse_url($uri);

            if (!$uri_components) {
                throw new Errors\RequestError("{$uri} URI path cannot be parsed.");
            }

            if (empty($uri_components['path'])) {
                $path = '/';
            } else {
                $path = $uri_components['path'];
            }

            if ($path[0] !== '/') {
                throw new Errors\RequestError("{$uri} URI path must start with a slash.");
            }
        }

        // If a path is specified in url_options, we must remove its value
        // from the beginning of the request path because routes are relative
        // to the url_options path.
        $url_options_path = Configuration::$url_options['path'];
        if ($url_options_path !== '/' && str_starts_with($path, $url_options_path)) {
            $path = substr($path, strlen($url_options_path));
        }

        $this->method = $method;
        $this->path = $path;
        $this->parameters = $parameters;
        $this->headers = $headers;
    }

    /**
     * @return RequestMethod
     */
    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function setParam(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function hasParam(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    /**
     * @template T of ?string
     *
     * @param T $default
     *
     * @return string|T
     *
     * @see Request::paramString
     */
    public function param(string $name, ?string $default = null): mixed
    {
        return $this->paramString($name, $default);
    }

    /**
     * @template T of ?string
     *
     * @param T $default
     *
     * @return string|T
     */
    public function paramString(string $name, ?string $default = null): mixed
    {
        if (isset($this->parameters[$name])) {
            $value = $this->parameters[$name];

            if (
                !is_bool($value) &&
                !is_float($value) &&
                !is_integer($value) &&
                !is_string($value)
            ) {
                return $default;
            }

            return strval($value);
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as a boolean.
     */
    public function paramBoolean(string $name, bool $default = false): bool
    {
        if (isset($this->parameters[$name])) {
            return filter_var($this->parameters[$name], FILTER_VALIDATE_BOOLEAN);
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as an integer.
     *
     * @template T of ?int
     *
     * @param T $default
     *
     * @return int|T
     */
    public function paramInteger(string $name, ?int $default = null): ?int
    {
        if (isset($this->parameters[$name])) {
            $value = $this->parameters[$name];

            if (!is_float($value) && !is_integer($value) && !is_string($value)) {
                return $default;
            }

            return intval($value);
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as a DateTimeImmutable.
     *
     * @template T of ?\DateTimeImmutable
     *
     * @param T $default
     *
     * @return \DateTimeImmutable|T
     */
    public function paramDatetime(
        string $name,
        ?\DateTimeImmutable $default = null,
        string $format = 'Y-m-d\\TH:i'
    ): ?\DateTimeImmutable {
        if (isset($this->parameters[$name])) {
            $value = $this->parameters[$name];

            if (!is_string($value)) {
                return $default;
            }

            $datetime = \DateTimeImmutable::createFromFormat($format, $value);

            if ($datetime === false) {
                return $default;
            }

            return $datetime;
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as an array.
     *
     * If the parameter isn’t an array, it’s placed into one.
     *
     * The default value is merged with the parameter value.
     *
     * @param mixed[] $default
     *
     * @return mixed[]
     */
    public function paramArray(string $name, array $default = []): array
    {
        if (isset($this->parameters[$name])) {
            $value = $this->parameters[$name];
            if (!is_array($value)) {
                $value = [$value];
            }

            return array_merge($default, $value);
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as a Json array.
     *
     * If the value is equal to true, false or null, it returns the value in
     * an array.
     *
     * If the parameter cannot be parsed as Json, default value is returned.
     *
     * @template T of mixed[]|null
     *
     * @param T $default
     *
     * @return mixed[]|T
     */
    public function paramJson(string $name, mixed $default = null): ?array
    {
        if (!isset($this->parameters[$name])) {
            return $default;
        }

        $value = $this->parameters[$name];

        if (!is_string($value)) {
            return $default;
        }

        $json_value = json_decode($value, true);

        if ($json_value === null && $value !== 'null') {
            return $default;
        }

        if (!is_array($json_value)) {
            $json_value = [$json_value];
        }

        return $json_value;
    }

    /**
     * Return a parameter value as a \Minz\File.
     *
     * The parameter must be an array containing at least a `tmp_name` and an
     * `error` keys, or a null value will be returned.
     *
     * @see https://www.php.net/manual/features.file-upload.post-method.php
     */
    public function paramFile(string $name): ?\Minz\File
    {
        if (!isset($this->parameters[$name])) {
            return null;
        }

        $parameter = $this->parameters[$name];

        if (!is_array($parameter)) {
            return null;
        }

        $file_info = [
            'tmp_name' => $parameter['tmp_name'] ?? '',
            'error' => $parameter['error'] ?? -1,
            'name' => $parameter['name'] ?? '',
        ];

        if (isset($parameter['is_uploaded_file'])) {
            $file_info['is_uploaded_file'] = $parameter['is_uploaded_file'];
        };

        try {
            return new File($file_info);
        } catch (Errors\RuntimeException $e) {
            return null;
        }
    }

    public function header(string $name, mixed $default = null): mixed
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        } else {
            return $default;
        }
    }

    /**
     * Return the value of a cookie.
     *
     * Cookies must be passed during Request initialization as $headers['COOKIE'].
     */
    public function cookie(string $name, mixed $default = null): mixed
    {
        if (
            isset($this->headers['COOKIE']) &&
            is_array($this->headers['COOKIE']) &&
            isset($this->headers['COOKIE'][$name])
        ) {
            return $this->headers['COOKIE'][$name];
        } else {
            return $default;
        }
    }

    /**
     * Return whether the given media is accepted by the client or not.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc7231#section-5.3.2
     */
    public function isAccepting(string $media): bool
    {
        // No Accept header implies the user agent accepts any media type (cf.
        // the RFC 7231)
        $accept_header = $this->header('HTTP_ACCEPT', '*/*');

        if (!is_string($accept_header)) {
            return false;
        }

        $accept_medias = explode(',', $accept_header);

        list($media_type, $media_subtype) = explode('/', $media);

        foreach ($accept_medias as $accept_media) {
            // We only want to know if the media is included in the Accept
            // header, we can remove the preference weight (i.e. "q" parameter)
            $semicolon_position = strpos($accept_media, ';');
            if ($semicolon_position !== false) {
                $accept_media = substr($accept_media, 0, $semicolon_position);
            }
            $accept_media = trim($accept_media);

            list($accept_type, $accept_subtype) = explode('/', $accept_media);

            if (
                ($accept_type === $media_type && $accept_subtype === '*') ||
                $accept_media === '*/*' ||
                $accept_media === $media
            ) {
                return true;
            }
        }

        return false;
    }
}
