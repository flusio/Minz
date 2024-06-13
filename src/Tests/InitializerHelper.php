<?php

namespace Minz\Tests;

/**
 * Make sure the context is correctly initialized before executing a test.
 * It (re)initializes the database, the session and the test mailer.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InitializerHelper
{
    protected static string $schema;

    /**
     * Load the schema from the configuration $schema_path.
     */
    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadSchema(): void
    {
        $schema_path = \Minz\Configuration::$schema_path;
        $schema = @file_get_contents($schema_path);

        if ($schema === false) {
            throw new \RuntimeException("SQL schema under {$schema_path} cannot be read.");
        }

        self::$schema = $schema;
    }

    /**
     * Reset the database and load the schema.
     */
    #[\PHPUnit\Framework\Attributes\Before]
    public function initDatabase(): void
    {
        if (\Minz\Configuration::$database && self::$schema) {
            \Minz\Database::reset();
            $database = \Minz\Database::get();
            $database->exec(self::$schema);
        }
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetSession(): void
    {
        session_unset();
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTestMailer(): void
    {
        Mailer::clear();
    }
}
