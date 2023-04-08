<?php

namespace Minz;

/**
 * Coordinate the different parts of the framework core.
 *
 * The engine is responsible to coordinate a request with a router, in order to
 * return a response to the user, based on the logic of the application's
 * actions.
 *
 * @phpstan-import-type ViewPointer from Output\View
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Engine
{
    private static ?Router $router = null;

    /**
     * @var array{
     *     'controller_namespace': ?string,
     *     'not_found_view_pointer': ?ViewPointer,
     *     'internal_server_error_view_pointer': ?ViewPointer,
     * }
     */
    private static array $options;

    /**
     * @param array{
     *     'controller_namespace'?: ?string,
     *     'not_found_view_pointer'?: ?ViewPointer,
     *     'internal_server_error_view_pointer'?: ?ViewPointer,
     * } $options
     */
    public static function init(Router $router, array $options = []): void
    {
        self::$router = $router;

        $clean_options = [];
        $clean_options['controller_namespace'] = $options['controller_namespace'] ?? null;
        $clean_options['not_found_view_pointer'] = $options['not_found_view_pointer'] ?? null;
        $clean_options['internal_server_error_view_pointer'] = $options['internal_server_error_view_pointer'] ?? null;
        self::$options = $clean_options;
    }

    public static function reset(): void
    {
        self::$router = null;
        self::$options = [
            'controller_namespace' => null,
            'not_found_view_pointer' => null,
            'internal_server_error_view_pointer' => null,
        ];
    }

    public static function router(): ?Router
    {
        return self::$router;
    }

    /**
     * This method tries to always return a response to the user. If an error
     * happens in the logic of the application, a response with the adequate
     * HTTP code and a pertinent view is returned.
     *
     * "Not found" and "Internal server" errors views can be chosen via the
     * options. You should make sure the view pointers you pass exist. By
     * default, the errors are returned as text.
     *
     * @return \Generator|Response
     */
    public static function run(Request $request): mixed
    {
        if (!self::$router) {
            $e = new \LogicException('The Engine must be initialized before running.');
            return self::internalServerErrorResponse($e);
        }

        try {
            list(
                $route_pointer,
                $parameters
            ) = self::$router->match($request->method(), $request->path());
        } catch (Errors\RouteNotFoundError $e) {
            return self::notFoundResponse($e);
        }

        foreach ($parameters as $param_name => $param_value) {
            $request->setParam($param_name, $param_value);
        }

        try {
            return self::executeRoutePointer($route_pointer, $request);
        } catch (\Exception $e) {
            Log::error((string)$e);
            return self::internalServerErrorResponse($e);
        }
    }

    /**
     * @return \Generator|Response
     */
    private static function executeRoutePointer(string $route_pointer, Request $request): mixed
    {
        $namespace = self::$options['controller_namespace'];

        list($controller_name, $action_name) = explode('#', $route_pointer);
        $controller_name = str_replace('/', '\\', $controller_name);

        if ($namespace === null) {
            $app_name = Configuration::$app_name;
            $namespace = "\\{$app_name}";
        }

        $namespaced_controller = "{$namespace}\\{$controller_name}";

        try {
            $controller = new $namespaced_controller();
        } catch (\Error $e) {
            throw new Errors\ControllerError(
                "{$namespaced_controller} controller class cannot be found."
            );
        }

        if (!is_callable([$controller, $action_name])) {
            throw new Errors\ActionError(
                "{$action_name} action cannot be called on {$namespaced_controller} controller."
            );
        }

        $response = $controller->$action_name($request);

        // Response can be yield, but in this case, its up to the developer to
        // check what is yield. I would not recommend to use that (it's not
        // even tested!), but eh, it can be convenient :)
        if (!($response instanceof Response) && !($response instanceof \Generator)) {
            throw new Errors\ActionError(
                "{$action_name} action in {$namespaced_controller} controller does not return a Response."
            );
        }

        return $response;
    }

    private static function notFoundResponse(\Exception $error): Response
    {
        if (self::$options['not_found_view_pointer']) {
            $output = new Output\View(
                self::$options['not_found_view_pointer'],
                ['error' => $error]
            );
        } else {
            $output = new Output\Text((string)$error);
        }

        return new Response(404, $output);
    }

    private static function internalServerErrorResponse(\Exception $error): Response
    {
        if (self::$options['internal_server_error_view_pointer']) {
            $output = new Output\View(
                self::$options['internal_server_error_view_pointer'],
                ['error' => $error]
            );
        } else {
            $output = new Output\Text((string)$error);
        }

        return new Response(500, $output);
    }
}
