<?php

namespace AppTest;

use Minz\Response;

class Rabbits
{
    public function items($request)
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

    public function show($request)
    {
        $rabbit_number = 'Rabbit #' . $request->param('id');
        return Response::ok('rabbits/show.phtml', [
            'rabbit' => $rabbit_number,
        ]);
    }

    public function missingViewFile($request)
    {
        return Response::ok('rabbits/missing.phtml');
    }

    public function noResponse($request)
    {
        return 'It’s a string, not a Response!';
    }
}
