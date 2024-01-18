<?php

namespace Minz\Controller;

/**
 * An attribute to define methods that must be executed on specific errors.
 *
 * You can catch errors triggered in controllers' actions in order to execute
 * specific code. For instance, if your action requires that the user must be
 * logged in:
 *
 *     use Minz\Controller;
 *     use Minz\Request;
 *     use Minz\Response;
 *
 *     class MyController
 *     {
 *         public function myAction(Request $request): Response
 *         {
 *             $current_user = $this->requireCurrentUser();
 *
 *             // ...
 *         }
 *
 *         // This code can be put in a trait in order to be reused accross
 *         // your controllers.
 *         public function requireCurrentUser(): User
 *         {
 *             // Load the user from the database
 *             $current_user = CurrentUser::get();
 *
 *             if (!$current_user) {
 *                 // This error must inherit from \Exception or its children.
 *                 throw new MissingCurrentUserError();
 *             }
 *
 *             return $current_user;
 *         }
 *
 *         #[Controller\ErrorHandler(MissingCurrentUserError::class)]
 *         public function redirectToLogin(Request $request): Response
 *         {
 *             return Response::redirect('login');
 *         }
 *     }
 *
 * The methods defined with this attribute must accept a Request as a unique
 * parameter, and can return a Response. If it returns a Response, the Engine
 * will immediately stop and not execute the following handlers, if any.
 *
 * You can pass the `only` parameter to the attribute in order to apply the
 * handler only for specific actions:
 *
 *     // The handler will only be used for the `myAction` action.
 *     #[Controller\ErrorHandler(MissingCurrentUserError::class, only: ['myAction'])]
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ErrorHandler
{
    /** @var class-string<\Exception> */
    public string $class_error;

    /** @var string[] */
    public array $only = [];

    /**
     * @param class-string<\Exception> $class_error
     * @param string[] $only
     */
    public function __construct(string $class_error, array $only = [])
    {
        $this->class_error = $class_error;
        $this->only = $only;
    }
}
