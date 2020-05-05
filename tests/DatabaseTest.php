<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
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

    public function testGetWithSqlite()
    {
        Configuration::$database['type'] = 'sqlite';
        Configuration::$database['path'] = ':memory:';

        $database = Database::get();

        $foreign_keys_pragma = $database->query('PRAGMA foreign_keys')->fetchColumn();
        $this->assertInstanceOf(\PDO::class, $database);
        $this->assertSame('sqlite', $database->getAttribute(\PDO::ATTR_DRIVER_NAME));
        $this->assertSame(
            \PDO::FETCH_ASSOC,
            $database->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE)
        );
        $this->assertSame('1', $foreign_keys_pragma);
    }

    public function testGetAlwaysReturnSameInstance()
    {
        $initial_database = Database::get();

        $database = Database::get();

        $this->assertSame($initial_database, $database);
    }

    public function testGetFailsIfDatabaseIsntConfigured()
    {
        $this->expectException(Errors\DatabaseError::class);
        $this->expectExceptionMessage(
            'The database is not set in the configuration file.'
        );

        Configuration::$database = null;

        Database::get();
    }

    public function testConstructorFailsIfDatabaseIsBadlyConfigured()
    {
        $this->expectException(Errors\DatabaseError::class);
        $this->expectExceptionMessage(
            'An error occured during database initialization: invalid data source name.'
        );

        Configuration::$database['type'] = 'not a correct type';

        Database::get();
    }

    public function testDropIfSqlite()
    {
        $sqlite_file = tmpfile();
        $sqlite_filename = stream_get_meta_data($sqlite_file)['uri'];
        Configuration::$database['type'] = 'sqlite';
        Configuration::$database['path'] = $sqlite_filename;

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
        $this->assertNotSame($database, $new_database);
        $this->assertFalse($table);
    }

    public function testDropIfSqliteIsInMemory()
    {
        Configuration::$database['type'] = 'sqlite';
        Configuration::$database['path'] = ':memory:';

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
        $this->assertNotSame($database, $new_database);
        $this->assertFalse($table);
    }

    public function testDropReturnsFalseIfSqliteFileDoesNotExist()
    {
        Configuration::$database = [
            'type' => 'sqlite',
            'path' => '/missing/file.sqlite',
        ];

        $result = Database::drop();

        $this->assertFalse($result);
    }

    public function testDropFailsReturnsFalseIfDatabaseTypeIsntSupported()
    {
        Configuration::$database = [
            'type' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => 'testdb',
            'username' => '',
        ];

        $result = Database::drop();

        $this->assertFalse($result);
    }

    public function testDropFailsIfDatabaseIsntConfigured()
    {
        $this->expectException(Errors\DatabaseError::class);
        $this->expectExceptionMessage(
            'The database is not set in the configuration file.'
        );

        Configuration::$database = null;

        Database::drop();
    }
}
