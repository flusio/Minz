<?php

namespace AppTest\admin;

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
}
