<?php

namespace Minz;

/**
 * The Request class represents the request of a user. It represents basically
 * some headers, and GET / POST parameters.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Request
{
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
     * @param string $method Usually the method from $_SERVER['REQUEST_METHOD']
     * @param string $uri Usually the method from $_SERVER['REQUEST_URI']
     * @param mixed[] $parameters Usually a merged array of $_GET and $_POST
     * @param mixed[] $headers Usually the $_SERVER array
     */
    public function __construct($method, $uri, $parameters = [], $headers = [])
    {
        $method = strtolower($method);
        if (!in_array($method, Router::VALID_VIAS)) {
            $vias_as_string = implode(', ', Router::VALID_VIAS);
            throw new Errors\RequestError(
                "{$method} method is invalid ({$vias_as_string})."
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

        $this->method = $method;
        $this->path = $path;
        $this->parameters = $parameters;
        $this->headers = $headers;
    }

    /**
     * @return string The HTTP method/verb of the user request
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * @return string The path of the request (without the query part, after
     *                the question mark)
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Set a parameter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParam($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Return a parameter value from $_GET or $_POST.
     *
     * @param string $name The name of the parameter to get
     * @param mixed $default A default value to return if the parameter doesn't exist
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
     * Return a parameter value from $_GET or $_POST as a boolean.
     *
     * @param string $name
     *     The name of the parameter to get.
     * @param boolean $default
     *     A default value to return if the parameter doesn't exist (default is
     *     false).
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
     * Return a parameter value from $_GET or $_POST as an integer.
     *
     * @param string $name
     *     The name of the parameter to get.
     * @param integer $default
     *     A default value to return if the parameter doesn't exist (default is
     *     null).
     *
     * @return boolean
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
     * Return a parameter value from $_GET or $_POST as an array.
     *
     * @param string $name The name of the parameter to get
     * @param array $default
     *     A default value to return if the parameter doesn't exist (default is
     *     empty array). Array is merged with the parameter value.
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
     * Return a parameter value from $_FILES as a File.
     *
     * @param string $name The name of the parameter to get
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
     * Return a parameter value from the headers array.
     *
     * @param string $name The name of the parameter to get
     * @param mixed $default A default value to return if the parameter doesn't exist
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
     * Cookies must be passed during Request initialization as
     * $headers['COOKIE'] parameter to be returned.
     *
     * @param string $name The name of the cookie to get
     * @param mixed $default A default value to return if the cookie doesn't exist
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
}
