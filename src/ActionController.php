<?php

namespace Minz;

/**
 * Represent an action to execute within a controller.
 *
 * Actions are the core of Minz. They manage and coordinate models and
 * determinate what should be returned to the users. They take a Request as an
 * input and return a Response. They are contained within controllers files to
 * organized the logic of the application.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ActionController
{
    /** @var string */
    private $controller_namespace;

    /** @var string */
    private $controller_name;

    /** @var string */
    private $action_name;

    /**
     * @param string $to
     *     A string representation of controller#action
     * @param string $namespace
     *     Namespace where to find the controller (default is \$app_name)
     */
    public function __construct($to, $namespace = null)
    {
        list($controller_name, $action_name) = explode('#', $to);
        $controller_name = str_replace('/', '\\', $controller_name);

        if ($namespace === null) {
            $app_name = Configuration::$app_name;
            $namespace = "\\{$app_name}";
        }

        $this->controller_namespace = $namespace;
        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
    }

    /**
     * @return string The name of the action's controller
     */
    public function controllerName()
    {
        return $this->controller_name;
    }

    /**
     * @return string The name of the action to execute
     */
    public function actionName()
    {
        return $this->action_name;
    }

    /**
     * Call the controller's action, passing a request in parameter and
     * returning a response for the user.
     *
     * @param \Minz\Request $request A request against which the action must be executed
     *
     * @throws \Minz\Errors\ControllerError if the controller's file cannot be loaded
     * @throws \Minz\Errors\ActionError if the action cannot be called
     * @throws \Minz\Errors\ActionError if the action doesn't return a Response
     *
     * @return \Minz\Response The response to return to the user
     */
    public function execute($request)
    {
        $app_name = Configuration::$app_name;
        $namespaced_controller = "{$this->controller_namespace}\\{$this->controller_name}";
        $action = $this->action_name;

        try {
            $controller = new $namespaced_controller();
        } catch (\Error $e) {
            throw new Errors\ControllerError(
                "{$this->controller_name} controller class cannot be found."
            );
        }

        if (!is_callable([$controller, $action])) {
            throw new Errors\ActionError(
                "{$action} action cannot be called on {$this->controller_name} controller."
            );
        }

        $response = $controller->$action($request);

        // Response can be yield, but in this case, its up to the developer to
        // check what is yield. I would not recommend to use that (it's not
        // even tested!), but eh, it can be convenient :)
        if (!($response instanceof Response) && !($response instanceof \Generator)) {
            throw new Errors\ActionError(
                "{$action} action in {$this->controller_name} controller does not return a Response."
            );
        }

        return $response;
    }
}
