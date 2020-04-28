<?php

namespace AppTest\controllers\admin\rabbits;

use Minz\Response;

function items($request)
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
