<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Csrf class allows to protect easily against Cross-site Request Forgery
 * attacks.
 *
 * First, you should generate the CSRF token and pass it to the views so they
 * can include it in the forms. This can be done by declaring it as default
 * variable (in your Application class for instance):
 *
 *     \Minz\Output\View::declareDefaultVariables([
 *         'csrf_token' => \Minz\Csrf::generate(),
 *     ]);
 *
 * Then, include the token in your forms:
 *
 *     <input type="hidden" name="csrf" value="<?= $csrf_token ?>" />
 *
 * Finally, check the value of the CSRF token in your controller:
 *
 *     $csrf = $request->param('csrf');
 *     if (!\Minz\Csrf::validate($csrf)) {
 *         return \Minz\Response::badRequest();
 *     }
 *
 * The token is valid per-session.
 *
 * @see https://en.wikipedia.org/wiki/Cross-site_request_forgery
 */
class Csrf
{
    /**
     * Store a CSRF hexadecimal token in session and return it.
     *
     * No tokens are generated if $_SESSION['_csrf'] already contains a token.
     */
    public static function generate(): string
    {
        $csrf = $_SESSION['_csrf'] ?? '';

        if (!is_string($csrf) || !$csrf) {
            $csrf = Random::hex(64);
            self::set($csrf);
        }

        return $csrf;
    }

    /**
     * Validate a token against the session-stored one.
     *
     * The token cannot be empty or the method will always return false.
     */
    public static function validate(string $token): bool
    {
        $expected_token = $_SESSION['_csrf'] ?? '';

        if (!is_string($expected_token) || !$token) {
            return false;
        }

        if (\hash_equals($expected_token, $token)) {
            return true;
        } else {
            Log::notice(
                "[Csrf#validateToken] Failed: got {$token}, expected {$expected_token}"
            );
            return false;
        }
    }

    /**
     * Force the value of the CSRF token
     */
    public static function set(string $token): void
    {
        $_SESSION['_csrf'] = $token;
    }

    /**
     * Generate a new CSRF token
     */
    public static function reset(): string
    {
        unset($_SESSION['_csrf']);
        return self::generate();
    }
}
