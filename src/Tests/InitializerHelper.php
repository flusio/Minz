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
    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initDatabase(): void
    {
        if (!\Minz\Configuration::$database) {
            return;
        }

        $schema_path = \Minz\Configuration::$schema_path;
        $schema = @file_get_contents($schema_path);

        if ($schema === false) {
            throw new \RuntimeException("SQL schema under {$schema_path} cannot be read.");
        }

        \Minz\Database::reset();

        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function beginDatabaseTransaction(): void
    {
        $database = \Minz\Database::get();
        $database->beginTransaction();
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function rollbackDatabaseTransaction(): void
    {
        $database = \Minz\Database::get();
        $database->rollBack();
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
