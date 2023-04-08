<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    public function testRun(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#items');
        \Minz\Engine::init($router);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $output = $response->render();
        $this->assertSame(200, $response->code());
        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testRunWithParamInRoute(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits/:id', 'Rabbits#show');
        \Minz\Engine::init($router);
        $request = new \Minz\Request('GET', '/rabbits/42');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(200, $response->code(), 'Rabbit #42');
        $this->assertSame('42', $request->param('id'));
    }

    public function testRunReturnsErrorIfRouteNotFound(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#items');
        \Minz\Engine::init($router, [
            'not_found_view_pointer' => 'not_found.phtml',
        ]);
        $request = new \Minz\Request('GET', '/not-found');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(404, $response->code());
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('not_found.phtml', $output->pointer());
    }

    public function testRunReturnsErrorIfControllerFileIsMissing(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Missing#items');
        \Minz\Engine::init($router, [
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
        ]);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(500, $response->code());
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('internal_server_error.phtml', $output->pointer());
    }

    public function testRunReturnsErrorIfActionIsMissing(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#missing');
        \Minz\Engine::init($router, [
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
        ]);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(500, $response->code());
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('internal_server_error.phtml', $output->pointer());
    }

    public function testRunReturnsErrorIfViewFileIsMissing(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#missingViewFile');
        \Minz\Engine::init($router, [
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
        ]);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(500, $response->code());
        /** @var \Minz\Output\View */
        $output = $response->output();
        $this->assertSame('internal_server_error.phtml', $output->pointer());
    }
}
