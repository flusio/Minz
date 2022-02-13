<?php

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
 * $http_method = strtolower($_SERVER['REQUEST_METHOD']);
 * $request_method = $http_method === 'head' ? 'get' : $http_method;
 * $request_path = $_SERVER['REQUEST_URI'];
 * $request_parameters = array_merge($_GET, $_POST, $_FILES);
 * $request_headers = array_merge($_SERVER, ['COOKIE' => $_COOKIE]);
 *
 * $request = new \Minz\Request(
 *     $request_method,
 *     $request_path,
 *     $request_parameters,
 *     $request_headers
 * );
 * ```
 *
 * Requests can also be used to handle command line (e.g. in a `cli.php` file):
 *
 * ```php
 * $cli_command = [];
 * $request_parameters = [];
 *
 * // First argument is skipped since it’s the name of the script
 * $arguments = array_slice($argv, 1);
 * foreach ($arguments as $argument) {
 *     $parameter_regex = '/^--(?P<name>\w+)(=(?P<value>.+))?$/sm';
 *     $result = preg_match($parameter_regex, $argument, $matches);
 *     if ($result) {
 *         $request_parameters[$matches['name']] = $matches['value'] ?? true;
 *     } else {
 *         $cli_command[] = $argument;
 *     }
 * }
 *
 * $request_path = implode('/', $cli_command);
 * if (!$request_path) {
 *     $request_path = '/';
 * } elseif ($request_path[0] !== '/') {
 *     $request_path = '/' . $request_path;
 * }
 *
 * $request = new \Minz\Request('cli', $request_path, $request_parameters);
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
 * $request = new \Minz\Request('cli', '/some/command', [
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
 * $array = $request->paramArray('array-param');
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
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Request
{
    public const VALID_METHODS = ['get', 'post', 'patch', 'put', 'delete', 'cli'];

    /** @var string */
    private $method;

    /** @var string */
    private $path;

    /** @var mixed[] */
    private $parameters;

    /** @var mixed[] */
    private $headers;

    /**
     * Create a Request
     *
     * @param string $method
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
     * @param mixed[] $parameters
     *     The parameters of the request where keys are the names of the parameters.
     *     The parameters can be retrieved with the `param*()` methods.
     *     For HTTP requests, its value usually is a merge of `$_GET`, `$_POST`
     *     and `$_FILE` global variables.
     *     CLI must build the array by itself (cf. example above)
     * @param mixed[] $headers
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
    public function __construct($method, $uri, $parameters = [], $headers = [])
    {
        if ($method) {
            $method = strtolower($method);
        }

        if (!in_array($method, self::VALID_METHODS)) {
            $methods_as_string = implode(', ', self::VALID_METHODS);
            throw new Errors\RequestError(
                "`{$method}` method is invalid (accepted methods: {$methods_as_string})."
            );
        }

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

        if (!is_array($parameters)) {
            throw new Errors\RequestError('Parameters are not in an array.');
        }

        if (!is_array($headers)) {
            throw new Errors\RequestError('Headers are not in an array.');
        }

        // If a path is specified in url_options, we must remove its value
        // from the beginning of the request path because routes are relative
        // to the url_options path.
        $url_options_path = Configuration::$url_options['path'];
        if (
            $url_options_path !== '/' &&
            substr($path, 0, strlen($url_options_path)) === $url_options_path
        ) {
            $path = substr($path, strlen($url_options_path));
        }

        $this->method = $method;
        $this->path = $path;
        $this->parameters = $parameters;
        $this->headers = $headers;
    }

    /**
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Set a parameter.
     *
     * @param string $name
     *     The name of the parameter to set.
     * @param mixed $value
     *     The new value of the parameter.
     */
    public function setParam($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Return a parameter value.
     *
     * @param string $name
     *     The name of the parameter to get.
     * @param mixed $default
     *     A default value to return if the parameter name doesn't exist.
     *
     * @return mixed
     */
    public function param($name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as a boolean.
     *
     * @param string $name
     *     The name of the parameter to get.
     * @param boolean $default
     *     A default value to return if the parameter name doesn't exist
     *     (default is false).
     *
     * @return boolean
     */
    public function paramBoolean($name, $default = false)
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
     * @param string $name
     *     The name of the parameter to get.
     * @param integer $default
     *     A default value to return if the parameter name doesn't exist
     *     (default is null).
     *
     * @return integer|null
     */
    public function paramInteger($name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return intval($this->parameters[$name]);
        } else {
            return $default;
        }
    }

    /**
     * Return a parameter value as an array.
     *
     * If the parameter isn’t an array, it’s placed into one.
     *
     * @param string $name
     *     The name of the parameter to get.
     * @param array $default
     *     A default value to return if the parameter name doesn't exist
     *     (default is empty array). Array is merged with the parameter value.
     *
     * @return array
     */
    public function paramArray($name, $default = [])
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
     * Return a parameter value as a \Minz\File.
     *
     * The parameter must be an array containing at least a `tmp_name` and an
     * `error` keys, or a null value will be returned.
     *
     * @see https://www.php.net/manual/features.file-upload.post-method.php
     *
     * @param string $name
     *     The name of the parameter to get.
     *
     * @return \Minz\File|null
     */
    public function paramFile($name)
    {
        if (!isset($this->parameters[$name])) {
            return null;
        }

        try {
            return new File($this->parameters[$name]);
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * Return the value of a header.
     *
     * @param string $name
     *     The name of the header to get.
     * @param mixed $default
     *     A default value to return if the header name doesn't exist.
     *
     * @return mixed
     */
    public function header($name, $default = null)
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
     *
     * @param string $name
     *     The name of the cookie to get.
     * @param mixed $default
     *     A default value to return if the cookie name doesn't exist.
     *
     * @return mixed
     */
    public function cookie($name, $default = null)
    {
        if (
            isset($this->headers['COOKIE']) &&
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
     *
     * @param string $media
     *     The media type/subtype to look for in the Accept headers.
     *
     * @return boolean
     *     Return true if the client accepts the given media, false otherwise.
     */
    public function isAccepting($media)
    {
        // No Accept header implies the user agent accepts any media type (cf.
        // the RFC 7231)
        $accept_header = $this->header('HTTP_ACCEPT', '*/*');
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
