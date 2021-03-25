<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ActionControllerTest extends TestCase
{
    public function testConstruct()
    {
        $action_controller = new ActionController('Rabbits#items');

        $this->assertSame('Rabbits', $action_controller->controllerName());
        $this->assertSame('items', $action_controller->actionName());
    }

    public function testExecute()
    {
        $request = new Request('GET', '/');
        $action_controller = new ActionController('Rabbits#items');

        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        $this->assertSame([
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => [
                'default-src' => "'self'",
            ],
        ], $response->headers(true));
        $this->assertSame('rabbits/items.phtml', $response->output()->pointer());
    }

    public function testExecuteWithSubDirectory()
    {
        $request = new Request('GET', '/');
        $action_controller = new ActionController('admin/Rabbits#items');

        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('admin/rabbits/items.phtml', $response->output()->pointer());
    }

    public function testExecuteWithNamespace()
    {
        $request = new Request('GET', '/');
        $action_controller = new ActionController('Rabbits#items', '\\AppTest\\admin');

        $response = $action_controller->execute($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('admin/rabbits/items.phtml', $response->output()->pointer());
    }

    public function testExecuteFailsIfControllerDoesntExist()
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage('Missing controller class cannot be found.');

        $request = new Request('GET', '/');
        $action_controller = new ActionController('Missing#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfControllerPathIsDirectory()
    {
        $this->expectException(Errors\ControllerError::class);
        $this->expectExceptionMessage(
            'Controller_as_directory controller class cannot be found.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('Controller_as_directory#items');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionIsNotCallable()
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            'uncallable action cannot be called on Rabbits controller.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('Rabbits#uncallable');

        $action_controller->execute($request);
    }

    public function testExecuteFailsIfActionDoesNotReturnResponse()
    {
        $this->expectException(Errors\ActionError::class);
        $this->expectExceptionMessage(
            'noResponse action in Rabbits controller does not return a Response.'
        );

        $request = new Request('GET', '/');
        $action_controller = new ActionController('Rabbits#noResponse');

        $action_controller->execute($request);
    }
}
