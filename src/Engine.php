<?php

namespace Minz;

/**
 * Coordinate the different parts of the framework core.
 *
 * The engine is responsible to coordinate a request with a router, in order to
 * return a response to the user, based on the logic of the application's
 * actions.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Engine
{
    private const DEFAULT_OPTIONS = [
        'not_found_view_pointer' => null,
        'internal_server_error_view_pointer' => null,
    ];

    /** @var \Minz\Router */
    private $router;

    /**
     * @param \Minz\Router $router The router to use in the application
     */
    public function __construct($router)
    {
        $this->router = $router;
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
     * @param \Minz\Request $request The actual request from the user.
     * @param array $options An optional array of options (see self::DEFAULT_OPTIONS)
     *
     * @return \Minz\Response A response to return to the user.
     */
    public function run($request, $options = [])
    {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        try {
            list(
                $to,
                $parameters
            ) = $this->router->match($request->method(), $request->path());
        } catch (Errors\RouteNotFoundError $e) {
            if ($options['not_found_view_pointer']) {
                $output = new Output\View(
                    $options['not_found_view_pointer'],
                    ['error' => $e]
                );
            } else {
                $output = new Output\Text((string)$e);
            }
            return new Response(404, $output);
        }

        foreach ($parameters as $param_name => $param_value) {
            $request->setParam($param_name, $param_value);
        }

        try {
            $namespace = $options['controller_namespace'] ?? null;
            $action_controller = new ActionController($to, $namespace);
            return $action_controller->execute($request);
        } catch (\Exception $e) {
            Log::error((string)$e);
            if ($options['internal_server_error_view_pointer']) {
                $output = new Output\View(
                    $options['internal_server_error_view_pointer'],
                    ['error' => $e]
                );
            } else {
                $output = new Output\Text((string)$e);
            }
            return new Response(500, $output);
        }
    }
}
