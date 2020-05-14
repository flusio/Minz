<?php

namespace Minz;

/**
 * The Router stores the different routes of the application and is responsible
 * of matching user request path with patterns.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Router
{
    /** @var string[] */
    public const VALID_VIAS = ['get', 'post', 'patch', 'put', 'delete', 'cli'];

    /**
     * @var string[][] Contains the routes of the application. First level is
     *                 indexed by "vias" (it is initialized in constructor),
     *                 second level is indexed by the paths and values are the
     *                 routes destinations.
     */
    private $routes = [];

    /**
     * @var array Contains the routes indexed by their names
     */
    private $routes_by_names = [];

    public function __construct()
    {
        foreach (self::VALID_VIAS as $via) {
            $this->routes[$via] = [];
        }
    }

    /**
     * Register a new route in the router.
     *
     * The pattern must always start by a slash and represents an URI. It is a
     * simple pattern where you can precise a variable by starting a section of
     * the URI by `:`. For instance: a `/rabbits/42` path will match the
     * `/rabbits/:id` pattern. Sections are splitted on slashes.
     *
     * The action pointer represents a combination of a controller name and an
     * action name, separated by a hash. For instance, `rabbits#items` points
     * to the `items` action of the `rabbits` controller.
     *
     * @param string $via The valid via(s) of the route
     * @param string $pattern The path pattern of the new route
     * @param string $action_pointer The destination of the route
     * @param string $route_name An optional name for the route
     *
     * @throws \Minz\Errors\RoutingError if pattern is empty
     * @throws \Minz\Errors\RoutingError if pattern doesn't start by a slash
     * @throws \Minz\Errors\RoutingError if action_pointer is empty
     * @throws \Minz\Errors\RoutingError if action_pointer contains no hash
     * @throws \Minz\Errors\RoutingError if action_pointer contains more than one hash
     * @throws \Minz\Errors\RoutingError if via is invalid
     *
     * @return void
     */
    public function addRoute($via, $pattern, $action_pointer, $route_name = '')
    {
        if (!$pattern) {
            throw new Errors\RoutingError('Route "pattern" cannot be empty.');
        }

        if ($pattern[0] !== '/') {
            throw new Errors\RoutingError('Route "pattern" must start by a slash (/).');
        }

        if (!$action_pointer) {
            throw new Errors\RoutingError('Route "action_pointer" cannot be empty.');
        }

        if (strpos($action_pointer, '#') === false) {
            throw new Errors\RoutingError(
                'Route "action_pointer" must contain a hash (#).'
            );
        }

        if (substr_count($action_pointer, '#') > 1) {
            throw new Errors\RoutingError(
                'Route "action_pointer" must contain at most one hash (#).'
            );
        }

        if (!in_array($via, self::VALID_VIAS)) {
            $vias_as_string = implode(', ', self::VALID_VIAS);
            throw new Errors\RoutingError(
                "{$via} via is invalid ({$vias_as_string})."
            );
        }

        $this->routes[$via][$pattern] = $action_pointer;
        if ($route_name) {
            $this->routes_by_names[$route_name] = [
                'via' => $via,
                'pattern' => $pattern,
                'action_pointer' => $action_pointer,
            ];
        }
    }

    /**
     * Return the matching action pointer for given request via and path.
     *
     * @param string $via
     * @param string $path
     *
     * @throws \Minz\Errors\RoutingError if via is invalid
     * @throws \Minz\Errors\RouteNotFoundError if no patterns match with the path
     *
     * @return array An array of 2 elements: the first one is action pointer,
     *               the second is the list of matching parameters
     */
    public function match($via, $path)
    {
        if (!in_array($via, self::VALID_VIAS)) {
            $vias_as_string = implode(', ', self::VALID_VIAS);
            throw new Errors\RoutingError(
                "{$via} via is invalid ({$vias_as_string})."
            );
        }

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $via_routes = $this->routes[$via];
        foreach ($via_routes as $pattern => $action_pointer) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                $parameters = $this->extractParameters($path, $pattern);
                $parameters['_action_pointer'] = $action_pointer;
                return [$action_pointer, $parameters];
            }
        }

        throw new Errors\RouteNotFoundError(
            "Path \"{$via} {$path}\" doesn’t match any route."
        );
    }

    /**
     * @return string[][] The list of registered routes
     */
    public function routes()
    {
        return array_filter($this->routes);
    }

    /**
     * @param string $name
     * @param array $parameters
     *
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     * @throws \Minz\Errors\RouteNotFoundError if name matches with no route
     *
     * @return string The URI corresponding to the action
     */
    public function uriByName($name, $parameters = [])
    {
        if (!isset($this->routes_by_names[$name])) {
            throw new Errors\RouteNotFoundError(
                "Route named \"{$name}\" doesn’t match any route."
            );
        }

        $route = $this->routes_by_names[$name];
        $path = Configuration::$url_options['path'];
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        return $path . $this->patternToUri($route['pattern'], $parameters);
    }

    /**
     * @param string $via
     * @param string $action_pointer
     * @param array $parameters
     *
     * @throws \Minz\Errors\RoutingError if via is invalid
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     * @throws \Minz\Errors\RouteNotFoundError if action pointer matches with no route
     *
     * @return string The URI corresponding to the action
     */
    public function uriByPointer($via, $action_pointer, $parameters = [])
    {
        if (!in_array($via, self::VALID_VIAS)) {
            $vias_as_string = implode(', ', self::VALID_VIAS);
            throw new Errors\RoutingError(
                "{$via} via is invalid ({$vias_as_string})."
            );
        }

        $path = Configuration::$url_options['path'];
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }

        $via_routes = $this->routes[$via];
        foreach ($via_routes as $pattern => $route_action_pointer) {
            if ($action_pointer === $route_action_pointer) {
                return $path . $this->patternToUri($pattern, $parameters);
            }
        }

        throw new Errors\RouteNotFoundError(
            "Action pointer \"{$via} {$action_pointer}\" doesn’t match any route."
        );
    }

    /**
     * Check if a path matches with a pattern.
     *
     * @param string $path
     * @param string $pattern
     *
     * @return boolean Return true if the path matches with the pattern, or
     *                 false otherwise.
     */
    private function pathMatchesPattern($path, $pattern)
    {
        $pattern_exploded = explode('/', $pattern);
        $path_exploded = explode('/', $path);

        for ($i = 0; $i < count($pattern_exploded); $i++) {
            if (!isset($path_exploded[$i])) {
                return false;
            }

            $pattern_element = $pattern_exploded[$i];
            $path_element = $path_exploded[$i];

            // If the pattern is *, the rest of the path is considered as
            // matching
            if ($pattern_element && $pattern_element === '*') {
                return true;
            }

            // In the pattern /rabbits/:id, :id is a variable name, which is
            // replaced by a real value in the URI (e.g. /rabbits/42).
            // We can't check the equality of :id and 42 but there are
            // "equivalent" from a routing point of view.
            $pattern_is_variable = $pattern_element && $pattern_element[0] === ':';
            if (!$pattern_is_variable && $pattern_element !== $path_element) {
                return false;
            }
        }

        // we still have to check that the path has no more elements than the pattern
        return count($pattern_exploded) === count($path_exploded);
    }

    /**
     * Extract from a path a list of parameters matching a pattern.
     *
     * @param string $path
     * @param string $pattern
     *
     * @return array The list of matching parameters where keys are extracted
     *               from the pattern, and values from the path.
     */
    private function extractParameters($path, $pattern)
    {
        $parameters = [];

        $pattern_exploded = explode('/', $pattern);
        $path_exploded = explode('/', $path);

        for ($i = 0; $i < count($pattern_exploded); $i++) {
            $pattern_element = $pattern_exploded[$i];
            $path_element = $path_exploded[$i];

            if ($pattern_element && $pattern_element === '*') {
                // the rest of the path matches with the pattern wildcard, so
                // we rebuild this part of the path
                $rest_of_path = array_slice($path_exploded, $i);
                $parameters['*'] = implode('/', $rest_of_path);
                break;
            }

            $pattern_is_variable = $pattern_element && $pattern_element[0] === ':';
            if (!$pattern_is_variable) {
                continue;
            }

            $parameter_name = substr($pattern_element, 1);
            $parameters[$parameter_name] = $path_element;
        }

        return $parameters;
    }

    /**
     * Replace variables of a pattern by the given values and return
     * corresponding URI.
     *
     * If given parameters don't correspond to a pattern variable, they are
     * added as a query string (e.g. `?id=value`).
     *
     * @param string $pattern
     * @param array $parameters
     *
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     *
     * @return string
     */
    private function patternToUri($pattern, $parameters = [])
    {
        $uri_elements = [];

        $pattern_elements = explode('/', $pattern);
        foreach ($pattern_elements as $pattern_element) {
            if (!$pattern_element) {
                continue;
            }

            $element_is_variable = $pattern_element[0] === ':';
            if ($element_is_variable) {
                $variable = substr($pattern_element, 1);
                if (!isset($parameters[$variable])) {
                    throw new Errors\RoutingError(
                        "Required `{$variable}` parameter is missing."
                    );
                }

                $uri_elements[] = $parameters[$variable];
                unset($parameters[$variable]);
            } else {
                $uri_elements[] = $pattern_element;
            }
        }

        $query_string = '';
        if ($parameters) {
            $query_string = '?' . http_build_query($parameters);
        }

        return '/' . implode('/', $uri_elements) . $query_string;
    }
}
