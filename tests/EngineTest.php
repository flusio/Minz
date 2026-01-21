<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

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
        $this->assertSame('42', $request->parameters->getString('id'));
    }

    public function testRunWithBeforeAndAfterHandlers(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits/before-after-handlers', 'Rabbits#beforeAndAfterHandled');
        \Minz\Engine::init($router);
        $request = new \Minz\Request('GET', '/rabbits/before-after-handlers');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('The text is set in before action', $response->render());
        $this->assertSame('The header is set in after action', $response->headers(raw: true)['Custom-Header']);
    }

    public function testRunWithErrorHandler(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits/with-error', 'Rabbits#handledError');
        \Minz\Engine::init($router);
        $request = new \Minz\Request('GET', '/rabbits/with-error');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $output = $response->render();
        $this->assertSame(404, $response->code());
        $this->assertSame('Rabbit not found.', $output);
    }

    public function testRunReturnsErrorIfRouteNotFound(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#items');
        \Minz\Engine::init($router, [
            'not_found_template' => 'not_found.phtml',
        ]);
        $request = new \Minz\Request('GET', '/not-found');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(404, $response->code());
        /** @var \Minz\Output\Template */
        $output = $response->output();
        $this->assertSame('not_found.phtml', $output->name());
    }

    public function testRunReturnsErrorIfControllerFileIsMissing(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Missing#items');
        \Minz\Engine::init($router, [
            'internal_server_error_template' => 'internal_server_error.phtml',
        ]);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(500, $response->code());
        /** @var \Minz\Output\Template */
        $output = $response->output();
        $this->assertSame('internal_server_error.phtml', $output->name());
    }

    public function testRunReturnsErrorIfActionIsMissing(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#missing');
        \Minz\Engine::init($router, [
            'internal_server_error_template' => 'internal_server_error.phtml',
        ]);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(500, $response->code());
        /** @var \Minz\Output\Template */
        $output = $response->output();
        $this->assertSame('internal_server_error.phtml', $output->name());
    }

    public function testRunReturnsErrorIfTemplateFileIsMissing(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'Rabbits#missingTemplateFile');
        \Minz\Engine::init($router, [
            'internal_server_error_template' => 'internal_server_error.phtml',
        ]);
        $request = new \Minz\Request('GET', '/rabbits');

        /** @var Response */
        $response = \Minz\Engine::run($request);

        $this->assertSame(500, $response->code());
        /** @var \Minz\Output\Template */
        $output = $response->output();
        $this->assertSame('internal_server_error.phtml', $output->name());
    }
}
