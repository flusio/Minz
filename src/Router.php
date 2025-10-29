<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Router stores the different routes of the application and is responsible
 * of matching user request path with patterns.
 *
 * @phpstan-import-type Parameters from ParameterBag
 *
 * @phpstan-type RouteMethod value-of<Request::VALID_METHODS>
 *
 * @phpstan-type RouteName non-empty-string
 * @phpstan-type RoutePattern non-empty-string
 * @phpstan-type RouteAction non-empty-string
 * @phpstan-type Route array{
 *     'name': RouteName,
 *     'method': RouteMethod,
 *     'pattern': RoutePattern,
 *     'action': RouteAction,
 * }
 * @phpstan-type Routes array<RouteName, Route>
 */
class Router
{
    /**
     * Contain the routes of the application, indexed by their names.
     *
     * @var Routes
     */
    private array $routes;

    /**
     * Provide an index of the routes.
     *
     * @var array<RouteMethod, array<RoutePattern, RouteName>>
     */
    private array $routes_index;

    public function __construct()
    {
        $this->routes = [];
        foreach (Request::VALID_METHODS as $method) {
            $this->routes_index[$method] = [];
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
     * If the route name is not provided, it defaults to the action value.
     *
     * @param RouteMethod $method
     * @param RoutePattern $pattern
     * @param RouteAction $action
     * @param ?RouteName $name
     *
     * @throws \Minz\Errors\RoutingError if pattern is empty
     * @throws \Minz\Errors\RoutingError if pattern doesn't start by a slash
     * @throws \Minz\Errors\RoutingError if action is empty
     * @throws \Minz\Errors\RoutingError if action contains no hash
     * @throws \Minz\Errors\RoutingError if action contains more than one hash
     * @throws \Minz\Errors\RoutingError if method is invalid
     */
    public function addRoute(string $method, string $pattern, string $action, ?string $name = null): void
    {
        if ($pattern[0] !== '/') {
            throw new Errors\RoutingError('Route "pattern" must start by a slash (/).');
        }

        if (strpos($action, '#') === false) {
            throw new Errors\RoutingError(
                'Route "action" must contain a hash (#).'
            );
        }

        if (substr_count($action, '#') > 1) {
            throw new Errors\RoutingError(
                'Route "action" must contain at most one hash (#).'
            );
        }

        if (!in_array($method, Request::VALID_METHODS)) {
            $methods_as_string = implode(', ', Request::VALID_METHODS);
            throw new Errors\RoutingError(
                "{$method} method is invalid ({$methods_as_string})."
            );
        }

        if ($name === null) {
            $name = $action;
        }

        $this->routes[$name] = [
            'name' => $name,
            'method' => $method,
            'pattern' => $pattern,
            'action' => $action,
        ];
        $this->routes_index[$method][$pattern] = $name;
    }

    /**
     * Return the matching route and pattern variables for the given request
     * method and path.
     *
     * @param RouteMethod $method
     *
     * @throws \Minz\Errors\RoutingError if method is invalid
     * @throws \Minz\Errors\RouteNotFoundError if no patterns match with the path
     *
     * @return array{Route, Parameters}
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

        $method_routes = $this->routes_index[$method];
        foreach ($method_routes as $pattern => $name) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                $parameters = $this->extractParameters($path, $pattern);
                $route = $this->routes[$name];
                return [$route, $parameters];
            }
        }

        throw new Errors\RouteNotFoundError(
            "Path \"{$method} {$path}\" doesn’t match any route."
        );
    }

    /**
     * Return the list of routes.
     *
     * @return Routes
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Return the list of allowed methods for the given path.
     *
     * @return RouteMethod[]
     */
    public function allowedMethodsForPath(string $path): array
    {
        $allowed_methods = [];

        foreach (Request::VALID_METHODS as $method) {
            try {
                $this->match($method, $path);
                $allowed_methods[] = $method;
            } catch (Errors\RouteNotFoundError $e) {
            }
        }

        return $allowed_methods;
    }

    /**
     * Return whether the URI is redirectable or not.
     *
     * A URI is redirectable if it can be handled by a GET route within the
     * router. The URI can either be a URL containing the application domain,
     * or an absolute path.
     */
    public function isRedirectable(string $uri): bool
    {
        $base_url = \Minz\Url::baseUrl();

        if (str_starts_with($uri, $base_url)) {
            $uri = substr($uri, strlen($base_url));
        }

        if (str_starts_with($uri, '/')) {
            $allowed_methods = $this->allowedMethodsForPath($uri);
            return in_array('GET', $allowed_methods);
        } else {
            return false;
        }
    }

    /**
     * Return an URI by its name. It is generated with the given parameters.
     *
     * @param RouteName $name
     * @param Parameters $parameters
     *
     * @throws \Minz\Errors\RoutingError if required parameters are missing
     * @throws \Minz\Errors\RouteNotFoundError if name matches with no route
     */
    public function uriByName(string $name, array $parameters = []): string
    {
        if (!isset($this->routes[$name])) {
            throw new Errors\RouteNotFoundError(
                "Route named \"{$name}\" doesn’t match any route."
            );
        }

        $route = $this->routes[$name];
        return $this->patternToUri($route['pattern'], $parameters);
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
     * @return Parameters
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
     * @param Parameters $parameters
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
