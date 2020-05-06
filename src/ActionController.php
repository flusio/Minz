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
    private $controller_name;

    /** @var string */
    private $action_name;

    /**
     * @param string $to A string representation of controller#action
     */
    public function __construct($to)
    {
        list($controller_name, $action_name) = explode('#', $to);
        $controller_name = str_replace('/', '\\', $controller_name);

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
        $namespaced_controller = "\\{$app_name}\\{$this->controller_name}";
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

        if (!($response instanceof Response)) {
            throw new Errors\ActionError(
                "{$action} action in {$this->controller_name} controller does not return a Response."
            );
        }

        return $response;
    }
}
