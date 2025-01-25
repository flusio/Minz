<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

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

    public function testDropIfSqlite(): void
    {
        $sqlite_filename = tempnam('/tmp', 'minz-db');
        assert($sqlite_filename !== false);
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

        $result = Database::drop();
        $this->assertTrue($result);

        $new_database = Database::get();
        /** @var \PDOStatement */
        $statement = $new_database->query("SELECT name FROM sqlite_master WHERE type='table'");
        $table = $statement->fetchColumn();
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

        $result = Database::drop();
        $this->assertTrue($result);

        $new_database = Database::get();
        $statement = $new_database->query("SELECT name FROM sqlite_master WHERE type='table'");
        $table = $statement->fetchColumn();
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

    public function testNestedTransactions(): void
    {
        $database = Database::get();

        $database->exec('CREATE TABLE my_table(value INTEGER)');
        $database->beginTransaction();
        $database->exec('INSERT INTO my_table VALUES (10)');
        $database->beginTransaction();
        $database->exec('INSERT INTO my_table VALUES (20)');
        $database->rollBack();
        $database->exec('INSERT INTO my_table VALUES (30)');
        $database->commit();

        $statement = $database->query('SELECT value FROM my_table ORDER BY value');
        $results = $statement->fetchAll();
        $this->assertSame(2, count($results));
        $this->assertSame(10, $results[0]['value']);
        $this->assertSame(30, $results[1]['value']);
    }
}
