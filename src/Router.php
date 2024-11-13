<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Router stores the different routes of the application and is responsible
 * of matching user request path with patterns.
 *
 * @phpstan-import-type RequestMethod from Request
 *
 * @phpstan-import-type RequestParameters from Request
 *
 * @phpstan-type Routes array<RoutePattern, RoutePointer>
 *
 * @phpstan-type Route array{
 *     'method': RequestMethod,
 *     'pattern': RoutePattern,
 *     'action_pointer': RoutePointer,
 * }
 *
 * @phpstan-type RoutePattern non-empty-string
 *
 * @phpstan-type RoutePointer non-empty-string
 *
 * @phpstan-type RouteName non-empty-string
 */
class Router
{
    /**
     * Contains the routes of the application. First level is indexed by
     * request methods (it is initialized in constructor), second level is
     * indexed by the paths and values are the routes destinations.
     *
     * @var array<RequestMethod, Routes>
     */
    private array $routes = [];

    /**
     * Contains the routes indexed by their names
     *
     * @var array<RouteName, Route>
     */
    private array $routes_by_names = [];

    public function __construct()
    {
        foreach (Request::VALID_METHODS as $method) {
            $this->routes[$method] = [];
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
     * @param RequestMethod $method
     * @param RoutePattern $pattern
     * @param RoutePointer $action_pointer
     * @param ?RouteName $route_name
     *
     * @throws \Minz\Errors\RoutingError if pattern is empty
     * @throws \Minz\Errors\RoutingError if pattern doesn't start by a slash
     * @throws \Minz\Errors\RoutingError if action_pointer is empty
     * @throws \Minz\Errors\RoutingError if action_pointer contains no hash
     * @throws \Minz\Errors\RoutingError if action_pointer contains more than one hash
     * @throws \Minz\Errors\RoutingError if method is invalid
     */
    public function addRoute(string $method, string $pattern, string $action_pointer, ?string $route_name = null): void
    {
        if ($pattern[0] !== '/') {
            throw new Errors\RoutingError('Route "pattern" must start by a slash (/).');
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

        if (!in_array($method, Request::VALID_METHODS)) {
            $methods_as_string = implode(', ', Request::VALID_METHODS);
            throw new Errors\RoutingError(
                "{$method} method is invalid ({$methods_as_string})."
            );
        }

        $this->routes[$method][$pattern] = $action_pointer;
        if ($route_name) {
            $this->routes_by_names[$route_name] = [
                'method' => $method,
                'pattern' => $pattern,
                'action_pointer' => $action_pointer,
            ];
        }
    }

    /**
     * Return the matching action pointer for given request method and path.
     *
     * @param RequestMethod $method
     *
     * @throws \Minz\Errors\RoutingError if method is invalid
     * @throws \Minz\Errors\RouteNotFoundError if no patterns match with the path
     *
     * @return array{RoutePointer, RequestParameters}
     */
    public function match(string $method, string $path): array
    {
        if (!in_array($method, Request::VALID_METHODS)) {
            $methods_as_string = implode(', ', Request::VALID_METHODS);
            throw new Errors\RoutingError(
                "{$method} method is invalid ({$methods_as_string})."
            );
        }

        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $method_routes = $this->routes[$method];
        foreach ($method_routes as $pattern => $action_pointer) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                $parameters = $this->extractParameters($path, $pattern);
                $parameters['_action_pointer'] = $action_pointer;
                return [$action_pointer, $parameters];
            }
        }

        throw new Errors\RouteNotFoundError(
            "Path \"{$method} {$path}\" doesn’t match any route."
        );
    }

    /**
     * Return the list of routes.
     *
     * @return array<RequestMethod, Routes>
     */
    public function routes(): array
    {
        return array_filter($this->routes);
    }

    /**
     * Return an URI by its name. It is generated with the given parameters.
     *
     * @param RouteName $name
     * @param RequestParameters $parameters
     *
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     * @throws \Minz\Errors\RouteNotFoundError if name matches with no route
     */
    public function uriByName(string $name, array $parameters = []): string
    {
        if (!isset($this->routes_by_names[$name])) {
            throw new Errors\RouteNotFoundError(
                "Route named \"{$name}\" doesn’t match any route."
            );
        }

        $route = $this->routes_by_names[$name];
        return $this->patternToUri($route['pattern'], $parameters);
    }

    /**
     * Return an URI by its pointer. It is generated with the given parameters.
     *
     * @param RequestMethod $method
     * @param RoutePointer $action_pointer
     * @param RequestParameters $parameters
     *
     * @throws \Minz\Errors\RoutingError if method is invalid
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     * @throws \Minz\Errors\RouteNotFoundError if action pointer matches with no route
     */
    public function uriByPointer(string $method, string $action_pointer, array $parameters = []): string
    {
        if (!in_array($method, Request::VALID_METHODS)) {
            $methods_as_string = implode(', ', Request::VALID_METHODS);
            throw new Errors\RoutingError(
                "{$method} method is invalid ({$methods_as_string})."
            );
        }

        $method_routes = $this->routes[$method];
        foreach ($method_routes as $pattern => $route_action_pointer) {
            if ($action_pointer === $route_action_pointer) {
                return $this->patternToUri($pattern, $parameters);
            }
        }

        throw new Errors\RouteNotFoundError(
            "Action pointer \"{$method} {$action_pointer}\" doesn’t match any route."
        );
    }

    /**
     * Return true if the path matches with the pattern, or false otherwise.
     *
     * @param RoutePattern $pattern
     */
    private function pathMatchesPattern(string $path, string $pattern): bool
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
     * Extract a list of parameters from a path that matches a pattern.
     *
     * @param RoutePattern $pattern
     *
     * @return RequestParameters
     */
    private function extractParameters(string $path, string $pattern): array
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
     * @param RoutePattern $pattern
     * @param RequestParameters $parameters
     *
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     */
    private function patternToUri(string $pattern, array $parameters = []): string
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
