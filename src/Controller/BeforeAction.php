<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Controller;

/**
 * An attribute to define methods that must be executed before actions.
 *
 * For instance, to load a model for all the actions of a controller:
 *
 *     use Minz\Controller;
 *     use Minz\Request;
 *     use Minz\Response;
 *
 *     class UsersController
 *     {
 *         private User $user;
 *
 *         #[Controller\BeforeAction]
 *         public function setUser(Request $request): void
 *         {
 *             $user_id = $request->param('id');
 *             $this->user = User::find($user_id);
 *         }
 *
 *         public function show(Request $request): Response
 *         {
 *             return Response::ok('users/show.phtml', [
 *                 'user' => $this->user,
 *             ]);
 *         }
 *     }
 *
 * The methods defined with this attribute must accept a Request and return
 * nothing.
 *
 * You can pass the `only` parameter to the attribute in order to apply the
 * handler only for specific actions:
 *
 *     // The handler will only be used for the `setUser` action.
 *     #[Controller\BeforeAction(only: ['setUser'])]
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class BeforeAction extends Handler
{
}
