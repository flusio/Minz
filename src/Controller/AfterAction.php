<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Controller;

/**
 * An attribute to define methods that must be executed after actions.
 *
 * For instance, to set the headers for all the actions of a controller:
 *
 *     use Minz\Controller;
 *     use Minz\Request;
 *     use Minz\Response;
 *
 *     class MyController
 *     {
 *         #[Controller\AfterAction]
 *         public function declareReferrerPolicy(Request $request, Response $response): void
 *         {
 *             $response->setHeader('Referrer-Policy', 'same-origin');
 *         }
 *
 *         public function myAction(Request $request): Response
 *         {
 *             return Response::text(200, 'ok');
 *         }
 *     }
 *
 * The methods defined with this attribute must accept a Request and a Response
 * and return nothing.
 *
 * You can pass the `only` parameter to the attribute in order to apply the
 * handler only for specific actions:
 *
 *     // The handler will only be used for the `myAction` action.
 *     #[Controller\AfterAction(only: ['myAction'])]
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class AfterAction extends Handler
{
}
