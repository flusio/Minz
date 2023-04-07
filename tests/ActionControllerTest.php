<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ActionControllerTest extends TestCase
{
    public function testConstruct(): void
    {
        $action_controller = new ActionController('Rabbits#items');

        $this->assertSame('Rabbits', $action_controller->controllerName());
        $this->assertSame('items', $action_controller->actionName());
    }

    public function testExecute(): void
    {
        $request = new Request('get', '/');
        $action_controller = new ActionController('Rabbits#items');

        /** @var Response */
        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        $this->assertSame([
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => [
                'default-src' => "'self'",
            ],
        ], $response->headers(true));
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('rabbits/items.phtml', $output->pointer());
    }

    public function testExecuteWithSubDirectory(): void
    {
        $request = new Request('get', '/');
        $action_controller = new ActionController('admin/Rabbits#items');

        /** @var Response */
        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('admin/rabbits/items.phtml', $output->pointer());
    }

    public function testExecuteWithNamespace(): void
    {
        $request = new Request('get', '/');
        $action_controller = new ActionController('Rabbits#items', '\\AppTest\\admin');

        /** @var Response */
        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('admin/rabbits/items.phtml', $output->pointer());
    }

    public function testExecuteFailsIfControllerDoesntExist(): void
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage('Missing controller class cannot be found.');

        $request = new Request('get', '/');
        $action_controller = new ActionController('Missing#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfControllerPathIsDirectory(): void
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage(
            'Controller_as_directory controller class cannot be found.'
        );

        $request = new Request('get', '/');
        $action_controller = new ActionController('Controller_as_directory#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionIsNotCallable(): void
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            'uncallable action cannot be called on Rabbits controller.'
        );

        $request = new Request('get', '/');
        $action_controller = new ActionController('Rabbits#uncallable');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionDoesNotReturnResponse(): void
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            'noResponse action in Rabbits controller does not return a Response.'
        );

        $request = new Request('get', '/');
        $action_controller = new ActionController('Rabbits#noResponse');

        $action_controller->execute($request);
    }
}
