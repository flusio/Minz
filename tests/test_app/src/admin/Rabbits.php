<?php

namespace AppTest\admin;

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

        return Response::ok('admin/rabbits/items.phtml', [
            'rabbits' => $rabbits,
        ]);
    }
}
