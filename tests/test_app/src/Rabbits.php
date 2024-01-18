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
