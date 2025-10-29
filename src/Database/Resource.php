<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use Minz\Request;

/**
 * A trait allowing to load easily a model from a Request.
 *
 * This trait must be used alongside with the Recordable trait.
 *
 * For instance, considering the following model:
 *
 *     use Minz\Database;
 *
 *     #[Database\Table(name: 'users')]
 *     class User
 *     {
 *         use Database\Recordable;
 *         use Database\Resource;
 *
 *         #[Database\Column]
 *         public int $id;
 *
 *         #[Database\Column]
 *         public string $nickname;
 *     }
 *
 * You can load/require it in a controller easily:
 *
 *     public function show(Request $request): Response
 *     {
 *         $user = User::requireFromRequest($request);
 *
 *         return Response::ok('users/show.phtml', [
 *             'user' => $user,
 *         ]);
 *     }
 *
 * In this example, as the requireFromRequest method raises an error when the
 * model is unknown, you can catch the Errors\MissingRecordError with a
 * Controller\ErrorHandler in order to return a 404 response.
 */
trait Resource
{
    /**
     * Return a Recordable model by using a request parameter as primary key
     * value, if any.
     */
    public static function loadFromRequest(Request $request, string $parameter = 'id'): ?self
    {
        $pk_value = $request->parameters->getString($parameter, '');
        return self::find($pk_value);
    }

    /**
     * Return a Recordable model by using a request parameter as primary key
     * value, or fail if none is found.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the model doesn't exist.
     */
    public static function requireFromRequest(Request $request, string $parameter = 'id'): self
    {
        $pk_value = $request->parameters->getString($parameter, '');
        return self::require($pk_value);
    }
}
