<?php

namespace Minz;

/**
 * The CSRF class is a helper to create secure forms.
 *
 * @see https://en.wikipedia.org/wiki/Cross-site_request_forgery
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CSRF
{
    /**
     * Static alias of generateToken
     *
     * @see \Minz\CSRF::generateToken
     */
    public static function generate(): string
    {
        $csrf = new \Minz\CSRF();
        return $csrf->generateToken();
    }

    /**
     * Static alias of validateToken
     *
     * @see \Minz\CSRF::validateToken
     */
    public static function validate(string $token): bool
    {
        $csrf = new \Minz\CSRF();
        return $csrf->validateToken($token);
    }

    /**
     * Static alias of setToken
     *
     * @see \Minz\CSRF::setToken
     */
    public static function set(string $token): void
    {
        $csrf = new \Minz\CSRF();
        $csrf->setToken($token);
    }

    /**
     * Store a CSRF hexadecimal token in session and return it.
     *
     * No tokens are generated if $_SESSION['CSRF'] already contains a token.
     */
    public function generateToken(): string
    {
        if (!isset($_SESSION['CSRF']) || !$_SESSION['CSRF']) {
            $_SESSION['CSRF'] = \bin2hex(\random_bytes(32));
        }
        return $_SESSION['CSRF'];
    }

    /**
     * Validate a token against the session-stored one.
     *
     * The token cannot be empty or the method will always return false.
     */
    public function validateToken(string $token): bool
    {
        if (isset($_SESSION['CSRF'])) {
            $expected_token = $_SESSION['CSRF'];
        } else {
            $expected_token = '';
        }

        if (!$token) {
            return false;
        }

        if (\hash_equals($expected_token, $token)) {
            return true;
        } else {
            Log::notice(
                "[CSRF#validateToken] Failed: got {$token}, expected {$expected_token}"
            );
            return false;
        }
    }

    /**
     * Force the value of the CSRF token
     */
    public function setToken(string $token): void
    {
        $_SESSION['CSRF'] = $token;
    }

    /**
     * Generate a new CSRF token
     */
    public function resetToken(): string
    {
        unset($_SESSION['CSRF']);
        return $this->generateToken();
    }
}
