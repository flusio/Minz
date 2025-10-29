<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Url class provides helper functions to build internal URLs.
 *
 * @phpstan-type UrlParameters array<string, mixed>
 *
 * @phpstan-import-type RouteName from Router
 */
class Url
{
    /**
     * Return the relative URL corresponding to a route name.
     *
     * @param RouteName $name
     * @param UrlParameters $parameters
     *
     * @throws \Minz\Errors\LogicException
     *     Raised if the router has not been registered.
     * @throws \Minz\Errors\UrlError
     *     Raised if the name doesn't exist, or if a required parameter is missing.
     */
    public static function for(string $name, array $parameters = []): string
    {
        $router = Engine::router();

        try {
            $uri = $router->uriByName($name, $parameters);
            return self::path() . $uri;
        } catch (Errors\RouteNotFoundError $e) {
            throw new Errors\UrlError("{$name} route does not exist in the router.");
        } catch (Errors\RoutingError $e) {
            throw new Errors\UrlError($e->getMessage());
        }
    }

    /**
     * Return the absolute URL corresponding to a route name.
     *
     * @param RouteName $name
     * @param UrlParameters $parameters
     *
     * @throws \Minz\Errors\UrlError
     *     Raised if the router has not been registered, if the name doesn't
     *     exist, or if a required parameter is missing.
     */
    public static function absoluteFor(string $name, array $parameters = []): string
    {
        $relative_url = self::for($name, $parameters);
        return self::baseUrl() . $relative_url;
    }

    /**
     * Return the URL of the server, based on Configuration url_options.
     */
    public static function baseUrl(): string
    {
        $url_options = Configuration::$url_options;
        $absolute_url = $url_options['protocol'] . '://';
        $absolute_url .= $url_options['host'];
        if (
            !($url_options['protocol'] === 'https' && $url_options['port'] === 443) &&
            !($url_options['protocol'] === 'http' && $url_options['port'] === 80)
        ) {
            $absolute_url .= ':' . $url_options['port'];
        }

        return $absolute_url;
    }

    /**
     * Return the path as specified in the Configuration url_options.
     *
     * It always removes the final character if the path ends with a slash (/).
     */
    public static function path(): string
    {
        $path = Configuration::$url_options['path'];
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        return $path;
    }
}
