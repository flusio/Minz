<?php

namespace AppTest;

use Minz\Controller;
use Minz\Request;
use Minz\Response;

class Rabbits
{
    public function items(Request $request): Response
    {
        $rabbits = [
            'Bugs',
            'Clémentine',
            'Jean-Jean',
        ];

        return Response::ok('rabbits/items.phtml', [
            'rabbits' => $rabbits,
        ]);
    }

    public function show(Request $request): Response
    {
        $rabbit_number = 'Rabbit #' . $request->param('id');
        return Response::ok('rabbits/show.phtml', [
            'rabbit' => $rabbit_number,
        ]);
    }

    public function missingViewFile(Request $request): Response
    {
        return Response::ok('rabbits/missing.phtml');
    }

    public function noResponse(Request $request): string
    {
        return 'It’s a string, not a Response!';
    }

    public function beforeAndAfterHandled(Request $request): Response
    {
        return Response::text(200, $request->param('text', ''));
    }

    #[Controller\BeforeAction(only: ['beforeAndAfterHandled'])]
    public function setBeforeActionTextContent(Request $request): void
    {
        $request->setParam('text', 'The text is set in before action');
    }

    #[Controller\AfterAction(only: ['beforeAndAfterHandled'])]
    public function setAfterActionHeader(Request $request, Response $response): void
    {
        $response->setHeader('Custom-Header', 'The header is set in after action');
    }

    public function handledError(Request $request): Response
    {
        throw new RabbitNotFoundError();
    }

    #[Controller\ErrorHandler(\Exception::class, only: ['handledError'])]
    public function returnNotFound(): Response
    {
        return Response::text(404, 'Rabbit not found.');
    }
}
