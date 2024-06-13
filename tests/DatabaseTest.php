<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type ConfigurationDatabase from \Minz\Configuration
 */
class DatabaseTest extends TestCase
{
    /**
     * @var ?ConfigurationDatabase
     */
    private ?array $initial_configuration;

    public function setUp(): void
    {
        $this->initial_configuration = Configuration::$database;
    }

    public function tearDown(): void
    {
        if (Configuration::$database) {
            Database::reset();
        }
        Configuration::$database = $this->initial_configuration;
    }

    public function testGetWithSqlite(): void
    {
        Database::resetInstance();
        /** @var ConfigurationDatabase */
        $configuration = Configuration::$database;
        $configuration['type'] = 'sqlite';
        $configuration['path'] = ':memory:';
        Configuration::$database = $configuration;

        $database = Database::get();

        /** @var \PDOStatement */
        $statement = $database->query('PRAGMA foreign_keys');
        $foreign_keys_pragma = $statement->fetchColumn();
        $this->assertSame('sqlite', $database->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame(
            \PDO::FETCH_ASSOC,
            $database->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE)
        );
        $this->assertSame(1, intval($foreign_keys_pragma));
    }

    public function testGetAlwaysReturnSameInstance(): void
    {
        Database::resetInstance();
        $initial_database = Database::get();

        $database = Database::get();

        $this->assertSame($initial_database, $database);
    }

    public function testGetFailsIfDatabaseIsntConfigured(): void
    {
        Database::resetInstance();
        $this->expectException(Errors\DatabaseError::class);
        $this->expectExceptionMessage(
            'The database is not set in the configuration file.'
        );

        Configuration::$database = null;

        Database::get();
    }

    public function testConstructorFailsIfDatabaseIsBadlyConfigured(): void
    {
        Database::resetInstance();
        $this->expectException(Errors\DatabaseError::class);
        if (PHP_VERSION_ID < 80000) {
            $this->expectExceptionMessage(
                'An error occured during database initialization: invalid data source name.'
            );
        } else {
            $this->expectExceptionMessage(
                'An error occured during database initialization: ' .
                'PDO::__construct(): Argument #1 ($dsn) must be a valid data source name.'
            );
        }

        // @phpstan-ignore-next-line
        Configuration::$database['type'] = 'not a correct type';

        Database::get();
    }

    public function testDropIfSqlite(): void
    {
        $sqlite_file = tmpfile();
        assert($sqlite_file !== false);
        $sqlite_metadata = stream_get_meta_data($sqlite_file);
        assert(isset($sqlite_metadata['uri']));
        $sqlite_filename = $sqlite_metadata['uri'];
        /** @var ConfigurationDatabase */
        $configuration = Configuration::$database;
        $configuration['type'] = 'sqlite';
        $configuration['path'] = $sqlite_filename;
        Configuration::$database = $configuration;

        $database = Database::get();
        $schema = <<<'SQL'
            CREATE TABLE rabbits (
                id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                name varchar(255) NOT NULL
            )
        SQL;
        $result = $database->exec($schema);
        $error_if_any = $database->errorInfo()[2];
        $this->assertTrue(
            $result !== false,
            "An error occured when initializing a database: {$error_if_any}."
        );

        $result = Database::drop();

        $new_database = Database::get();
        /** @var \PDOStatement */
        $statement = $new_database->query("SELECT name FROM sqlite_master WHERE type='table'");
        $table = $statement->fetchColumn();
        $this->assertTrue($result);
        $this->assertFalse($table);
    }

    public function testDropIfSqliteIsInMemory(): void
    {
        /** @var ConfigurationDatabase */
        $configuration = Configuration::$database;
        $configuration['type'] = 'sqlite';
        $configuration['path'] = ':memory:';
        Configuration::$database = $configuration;

        $database = Database::get();
        $schema = <<<'SQL'
            CREATE TABLE rabbits (
                id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                name varchar(255) NOT NULL
            )
        SQL;
        $result = $database->exec($schema);
        $error_if_any = $database->errorInfo()[2];
        $this->assertTrue(
            $result !== false,
            "An error occured when initializing a database: {$error_if_any}."
        );

        $result = Database::drop();

        $new_database = Database::get();
        $statement = $new_database->query("SELECT name FROM sqlite_master WHERE type='table'");
        $table = $statement->fetchColumn();
        $this->assertTrue($result);
        $this->assertFalse($table);
    }

    public function testDropReturnsFalseIfSqliteFileDoesNotExist(): void
    {
        /** @var ConfigurationDatabase */
        $configuration = Configuration::$database;
        $configuration['type'] = 'sqlite';
        $configuration['path'] = '/missing/file.sqlite';
        Configuration::$database = $configuration;

        $result = Database::drop();

        $this->assertFalse($result);
    }

    public function testDropFailsReturnsFalseIfDatabaseTypeIsntSupported(): void
    {
        /** @var ConfigurationDatabase */
        $configuration = Configuration::$database;
        $configuration['type'] = 'mysql';
        // @phpstan-ignore-next-line
        Configuration::$database = $configuration;

        $result = Database::drop();

        $this->assertFalse($result);
    }

    public function testDropFailsIfDatabaseIsntConfigured(): void
    {
        $this->expectException(Errors\DatabaseError::class);
        $this->expectExceptionMessage(
            'The database is not set in the configuration file.'
        );

        Configuration::$database = null;

        Database::drop();
    }
}
