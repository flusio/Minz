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
    /** @var string */
    protected static $schema;

    /**
     * Load the schema from the configuration $schema_path.
     *
     * @beforeClass
     */
    public static function loadSchema()
    {
        self::$schema = @file_get_contents(\Minz\Configuration::$schema_path);
    }

    /**
     * Reset the database and load the schema.
     *
     * @before
     */
    public function initDatabase()
    {
        if (\Minz\Configuration::$database && self::$schema) {
            \Minz\Database::reset();
            $database = \Minz\Database::get();
            $database->exec(self::$schema);
        }
    }

    /**
     * @before
     */
    public function resetSession()
    {
        session_unset();
    }

    /**
     * @before
     */
    public function resetTestMailer()
    {
        Mailer::clear();
    }
}
