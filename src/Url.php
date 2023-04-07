<?php

namespace Minz;

/**
 * The Url class provides helper functions to build internal URLs.
 *
 * @phpstan-import-type RoutePointer from Router
 *
 * @phpstan-import-type RouteName from Router
 *
 * @phpstan-type UrlPointer RoutePointer|RouteName
 *
 * @phpstan-type UrlParameters array<string, mixed>
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Url
{
    private static ?Router $router;

    /**
     * Set the router so Url class can lookup for action pointers. It needs to
     * be called first.
     */
    public static function setRouter(?Router $router): void
    {
        self::$router = $router;
    }

    /**
     * Return the relative URL corresponding to an action.
     *
     * @param UrlPointer $pointer
     * @param UrlParameters $parameters
     *
     * @throws \Minz\Errors\UrlError
     *     If router has not been registered
     * @throws \Minz\Errors\UrlError
     *     If the action pointer has not be added to the router
     * @throws \Minz\Errors\UrlError
     *     If required parameter is missing
     */
    public static function for(string $pointer, array $parameters = []): string
    {
        if (!self::$router) {
            throw new Errors\UrlError(
                'You must set a Router to the Url class before using it.'
            );
        }

        try {
            $uri = self::$router->uriByName($pointer, $parameters);
            return self::path() . $uri;
        } catch (Errors\RouteNotFoundError $e) {
            // Do nothing on purpose
        } catch (Errors\RoutingError $e) {
            throw new Errors\UrlError($e->getMessage());
        }

        $methods = Request::VALID_METHODS;
        foreach ($methods as $method) {
            try {
                $uri = self::$router->uriByPointer($method, $pointer, $parameters);
                return self::path() . $uri;
            } catch (Errors\RouteNotFoundError $e) {
                // Do nothing on purpose
            } catch (Errors\RoutingError $e) {
                throw new Errors\UrlError($e->getMessage());
            }
        }

        throw new Errors\UrlError(
            "{$pointer} action pointer or route name does not exist in the router."
        );
    }

    /**
     * Return the absolute URL corresponding to an action.
     *
     * @param UrlPointer $pointer
     * @param UrlParameters $parameters
     *
     * @throws \Minz\Errors\UrlError
     *     If router has not been registered
     * @throws \Minz\Errors\UrlError
     *     If the action pointer has not be added to the router
     * @throws \Minz\Errors\UrlError
     *     If required parameter is missing
     */
    public static function absoluteFor(string $pointer, array $parameters = []): string
    {
        $relative_url = self::for($pointer, $parameters);
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
