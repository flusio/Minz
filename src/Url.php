<?php

namespace Minz;

/**
 * The Url class provides helper functions to build internal URLs.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Url
{
    /** @var \Minz\Router|null */
    private static $router;

    /**
     * Set the router so Url class can lookup for action pointers. It needs to
     * be called first.
     *
     * @param \Minz\Router|null $router
     */
    public static function setRouter($router)
    {
        self::$router = $router;
    }

    /**
     * Return the relative URL corresponding to an action.
     *
     * @param string $action_pointer_or_name
     * @param array $parameters
     *
     * @throws \Minz\Errors\UrlError if router has not been registered
     * @throws \Minz\Errors\UrlError if the action pointer has not be added to
     *                               the router
     * @throws \Minz\Errors\UrlError if required parameter is missing
     *
     * @return string The URL corresponding to the action
     */
    public static function for($action_pointer_or_name, $parameters = [])
    {
        if (!self::$router) {
            throw new Errors\UrlError(
                'You must set a Router to the Url class before using it.'
            );
        }

        try {
            $uri = self::$router->uriByName($action_pointer_or_name, $parameters);
            return self::path() . $uri;
        } catch (Errors\RouteNotFoundError $e) {
            // Do nothing on purpose
        } catch (Errors\RoutingError $e) {
            throw new Errors\UrlError($e->getMessage());
        }

        $vias = Router::VALID_VIAS;
        foreach ($vias as $via) {
            try {
                $uri = self::$router->uriByPointer($via, $action_pointer_or_name, $parameters);
                return self::path() . $uri;
            } catch (Errors\RouteNotFoundError $e) {
                // Do nothing on purpose
            } catch (Errors\RoutingError $e) {
                throw new Errors\UrlError($e->getMessage());
            }
        }

        throw new Errors\UrlError(
            "{$action_pointer_or_name} action pointer or route name does not exist in the router."
        );
    }

    /**
     * Return the absolute URL corresponding to an action.
     *
     * @param string $action_pointer
     * @param array $parameters
     *
     * @throws \Minz\Errors\UrlError if router has not been registered
     * @throws \Minz\Errors\UrlError if the action pointer has not be added to
     *                               the router
     * @throws \Minz\Errors\UrlError if required parameter is missing
     *
     * @return string The URL corresponding to the action
     */
    public static function absoluteFor($action_pointer, $parameters = [])
    {
        $relative_url = self::for($action_pointer, $parameters);
        return self::baseUrl() . $relative_url;
    }

    /**
     * Return the URL of the server, based on Configuration url_options.
     *
     * @return string
     */
    public static function baseUrl()
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
     *
     * @return string
     */
    public static function path()
    {
        $path = Configuration::$url_options['path'];
        if (substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        return $path;
    }
}
