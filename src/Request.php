<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
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
 * `/foo`) and some parameters, headers, cookies, etc. It’s one of the first
 * object to initialize in an application. For instance, in `public/index.php`:
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
 * $foo = $request->parameters->getString('foo');
 * $bar = $request->parameters->getString('bar', 'a default value');
 * ```
 *
 * You also can automatically cast parameters to the desire types:
 *
 * ```php
 * $boolean = $request->parameters->getBoolean('boolean-param');
 * $integer = $request->parameters->getInteger('integer-param');
 * $datetime = $request->parameters->getDatetime('datetime-param');
 * $array = $request->parameters->getArray('array-param');
 * $json = $request->parameters->getJson('json-param');
 * $file = $request->parameters->getFile('file-param');
 * ```
 *
 * Headers, cookies and server information can be retrieved in a similar way:
 *
 * ```php
 * $accept_header = $request->headers->getString('Accept');
 * $my_cookie = $request->cookies->getString('my_cookie');
 * $request_uri = $request->server->getString('REQUEST_URI');
 * ```
 *
 * The Request object provides a special parameter, prefixed by an underscore (_):
 * "_action_pointer" which is the current Router pointer.
 *
 * @phpstan-type RequestMethod value-of<Request::VALID_METHODS>
 *
 * @phpstan-import-type Route from Router
 * @phpstan-import-type Parameters from ParameterBag
 */
class Request
{
    public const VALID_HTTP_METHODS = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'];
    public const VALID_METHODS = ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS', 'CLI'];

    /** @var RequestMethod */
    private string $method;

    private string $path;

    private string $self_uri;

    /** @var Route */
    private array $route;

    public readonly ParameterBag $parameters;

    public readonly ParameterBag $headers;

    public readonly ParameterBag $cookies;

    public readonly ParameterBag $server;

    /**
     * Create a Request reading the global variables.
     *
     * @throws \Minz\Errors\RequestError
     *     Raised if the REQUEST_METHOD or REQUEST_URI variables are invalid.
     */
    public static function initFromGlobals(): Request
    {
        if (!isset($_SERVER['REQUEST_METHOD']) || !is_string($_SERVER['REQUEST_METHOD'])) {
            throw new Errors\RequestError('The server REQUEST_METHOD is invalid.');
        }

        if (!isset($_SERVER['REQUEST_URI']) || !is_string($_SERVER['REQUEST_URI'])) {
            throw new Errors\RequestError('The server REQUEST_URI is invalid.');
        }

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

        if (function_exists('getallheaders')) {
            $http_headers = \getallheaders();
        } else {
            $http_headers = [];
        }

        return new Request(
            $http_method,
            $http_uri,
            parameters: $http_parameters,
            headers: $http_headers,
            cookies: $_COOKIE,
            server: $_SERVER,
        );
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

        return new \Minz\Request(
            'CLI',
            $request_uri,
            parameters: $parameters,
            server: $_SERVER,
        );
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
     * @param Parameters $parameters
     *     The parameters of the request where keys are the names of the parameters.
     *     For HTTP requests, its value usually is a merge of `$_GET`, `$_POST`
     *     and `$_FILE` global variables.
     *     CLI must build the array by itself (cf. example above)
     * @param Parameters $headers
     *     The headers of the request where keys are the names of the headers.
     *     For HTTP requests, its value usually comes from `getallheaders()`.
     *     CLI requests don’t have headers.
     * @param Parameters $cookies
     *     The cookies of the request where keys are the names of the cookies.
     *     For HTTP requests, its value usually comes from the `$_COOKIE`
     *     global variable. CLI requests don’t have cookies.
     * @param Parameters $server
     *     The information of the server running the request. Its value usually
     *     comes from the `$_SERVER` global variable.
     *
     * @throws \Minz\Errors\RequestError
     *     Raised if the uri is empty or invalid.
     *
     * @see \Minz\Configuration::$url_options
     * @see https://developer.mozilla.org/docs/Web/HTTP/Methods
     * @see https://developer.mozilla.org/docs/Web/HTTP/Headers
     * @see https://developer.mozilla.org/docs/Web/HTTP/Overview#requests
     */
    public function __construct(
        string $method,
        string $uri,
        array $parameters = [],
        array $headers = [],
        array $cookies = [],
        array $server = [],
    ) {
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
            $self_uri = $path;

            $pos_hash = strpos($path, '#');
            if ($pos_hash !== false) {
                $path = substr($path, 0, $pos_hash);
                $self_uri = $path;
            }

            $pos_query = strpos($path, '?');
            if ($pos_query !== false) {
                $path = substr($path, 0, $pos_query);
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

            $self_uri = $path;

            $query = $uri_components['query'] ?? null;
            if ($query !== null) {
                $self_uri .= "?{$query}";
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
        // $self_uri only contains /path and ?query to make sure to point on the
        // same server, and because #fragment shouldn't be sent to the server
        // (so we shoudn't know about it).
        $this->self_uri = $self_uri;

        $this->parameters = new ParameterBag($parameters);
        $this->headers = new ParameterBag($headers, case_sensitive: false);
        $this->cookies = new ParameterBag($cookies);
        $this->server = new ParameterBag($server);
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

    public function selfUri(): string
    {
        return $this->self_uri;
    }

    public function ip(): string
    {
        return $this->server->getString('REMOTE_ADDR', '');
    }

    /**
     * @return Route
     */
    public function route(): array
    {
        return $this->route;
    }

    /**
     * @param Route $route
     * @param Parameters $parameters
     */
    public function setRoute(array $route, array $parameters = []): void
    {
        $this->route = $route;

        foreach ($parameters as $name => $value) {
            $this->parameters->set($name, $value);
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
        $accept_header = $this->headers->getString('Accept', '*/*');

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
