<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * Handle the database requests.
 *
 * This class makes sure that you initialize only one PDO connection to the
 * database and provides some additional utility functions.
 *
 * The database is usually accessed as:
 *
 * ```php
 * $database = \Minz\Database::get();
 * $statement = $database->query('SELECT 1');
 * ```
 *
 * Most of the PDO class methods can be called directly on the Database object.
 *
 * @see https://www.php.net/manual/class.pdo.php
 *
 * @phpstan-import-type ConfigurationDatabase from Configuration
 */
class Database
{
    private static ?Database $instance = null;

    private ?\PDO $pdo_connection = null;

    private bool $connect_to_db;

    private int $transaction_depth = 0;

    /**
     * Initialize a database. Note it is private, you must use `\Minz\Database::get`
     * to get an instance.
     *
     * You can specify if the connection must be done on the database. Else,
     * the dbname will not be included in the DSN. It is useful if you need to
     * drop or create a database. It has no effects with SQLite.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     */
    private function __construct(bool $connect_to_db = true)
    {
        $this->connect_to_db = $connect_to_db;
        $this->start();
    }

    /**
     * Start the PDO connection.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     */
    public function start(): void
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        $dsn = self::buildDsn($database_configuration, $this->connect_to_db);
        $username = $database_configuration['username'];
        $password = $database_configuration['password'];
        $options = $database_configuration['options'];
        $database_type = strstr($dsn, ':', true);

        // Force some options values
        $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
        $options[\PDO::ATTR_EMULATE_PREPARES] = false;
        $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

        try {
            $this->pdo_connection = new \PDO($dsn, $username, $password, $options);
            if ($database_type === 'sqlite') {
                $this->pdo_connection->exec('PRAGMA foreign_keys = ON;');
            }
        } catch (\PDOException $e) {
            throw new Errors\DatabaseError(
                "An error occured during database initialization: {$e->getMessage()}."
            );
        }
    }

    /**
     * Close the PDO connection.
     */
    public function close(): void
    {
        // When unassigning a PDO object, it closes the connection to the
        // database.
        $this->pdo_connection = null;
    }

    /**
     * @see \PDO::errorCode https://www.php.net/manual/pdo.errorcode.php
     */
    public function errorCode(): ?string
    {
        /** @var ?string $result */
        $result = $this->pdoCall('errorCode');
        return $result;
    }

    /**
     * @see \PDO::errorInfo https://www.php.net/manual/pdo.errorinfo.php
     *
     * @return array{
     *     0: string,
     *     1: ?string,
     *     2: string,
     * }
     */
    public function errorInfo(): array
    {
        /** @var array{
         *     0: string,
         *     1: ?string,
         *     2: string,
         * } $result
        */
        $result = $this->pdoCall('errorInfo');
        return $result;
    }

    /**
     * @see \PDO::exec() https://www.php.net/manual/pdo.exec.php
     */
    public function exec(string $sql_statement): int
    {
        /** @var int $result */
        $result = $this->pdoCall('exec', $sql_statement);
        return $result;
    }

    /**
     * @see \PDO::getAttribute() https://www.php.net/manual/pdo.getattribute.php
     *
     * @param \PDO::ATTR_* $attribute
     */
    public function getAttribute(int $attribute): mixed
    {
        return $this->pdoCall('getAttribute', $attribute);
    }

    /**
     * @see \PDO::lastInsertId() https://www.php.net/manual/pdo.lastinsertid.php
     */
    public function lastInsertId(?string $name = null): string
    {
        /** @var string $result */
        $result = $this->pdoCall('lastInsertId', $name);
        return $result;
    }

    /**
     * @see \PDO::prepare() https://www.php.net/manual/pdo.prepare.php
     *
     * @param mixed[] $options
     */
    public function prepare(string $sql_statement, array $options = []): \PDOStatement
    {
        /** @var \PDOStatement $result */
        $result = $this->pdoCall('prepare', $sql_statement, $options);
        return $result;
    }

    /**
     * @see \PDO::query() https://www.php.net/manual/pdo.query.php
     */
    public function query(string $sql_statement, ?int $fetch_mode = null): \PDOStatement
    {
        /** @var \PDOStatement $result */
        $result = $this->pdoCall('query', $sql_statement, $fetch_mode);
        return $result;
    }

    /**
     * @see \PDO::quote https://www.php.net/manual/pdo.quote.php
     */
    public function quote(string $string, int $parameter_type = \PDO::PARAM_STR): string
    {
        /** @var string $result */
        $result = $this->pdoCall('quote', $string, $parameter_type);
        return $result;
    }

    /**
     * Start a transaction or create a savepoint, allowing for nested
     * transactions.
     *
     * @see \PDO::beginTransaction https://www.php.net/manual/pdo.begintransaction.php
     */
    public function beginTransaction(): bool
    {
        if ($this->transaction_depth === 0) {
            /** @var bool $result */
            $result = $this->pdoCall('beginTransaction');
        } else {
            $savepoint_name = "savepoint_{$this->transaction_depth}";
            $this->exec("SAVEPOINT {$savepoint_name}");
            $result = true;
        }

        $this->transaction_depth += 1;

        return $result;
    }

    /**
     * Commit a transaction or release a savepoint.
     *
     * @see \PDO::commit https://www.php.net/manual/pdo.commit.php
     */
    public function commit(): bool
    {
        $this->transaction_depth = max(0, $this->transaction_depth - 1);

        if ($this->transaction_depth === 0) {
            /** @var bool $result */
            $result = $this->pdoCall('commit');
        } else {
            $savepoint_name = "savepoint_{$this->transaction_depth}";
            $this->exec("RELEASE SAVEPOINT {$savepoint_name}");
            $result = true;
        }

        return $result;
    }

    /**
     * Rollback a transaction or a savepoint.
     *
     * @see \PDO::rollBack https://www.php.net/manual/pdo.rollback.php
     */
    public function rollBack(): bool
    {
        $this->transaction_depth = max(0, $this->transaction_depth - 1);

        if ($this->transaction_depth === 0) {
            /** @var bool $result */
            $result = $this->pdoCall('rollBack');
        } else {
            $savepoint_name = "savepoint_{$this->transaction_depth}";
            $this->exec("ROLLBACK TO SAVEPOINT {$savepoint_name}");
            $result = true;
        }

        return $result;
    }

    /**
     * @see \PDO::inTransaction() https://www.php.net/manual/pdo.intransaction.php
     */
    public function inTransaction(): bool
    {
        /** @var bool $result */
        $result = $this->pdoCall('inTransaction');
        return $result;
    }

    /**
     * @see \PDO::setAttribute() https://www.php.net/manual/pdo.setattribute.php
     */
    public function setAttribute(int $attribute, mixed $value): bool
    {
        /** @var bool $result */
        $result = $this->pdoCall('setAttribute', $attribute, $value);
        return $result;
    }

    /**
     * Transfer method calls to the PDO connection. It starts the connection if
     * it has been stopped.
     */
    public function pdoCall(string $name, mixed ...$arguments): mixed
    {
        if (!$this->pdo_connection) {
            $this->start();
        }

        if (!is_callable([$this->pdo_connection, $name])) {
            throw new \BadMethodCallException('Call to undefined method ' . get_called_class() . '::' . $name);
        }

        return $this->pdo_connection->$name(...$arguments);
    }

    /**
     * Return an instance of Database.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     */
    public static function get(): Database
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Drop the entire database. Hell yeah!
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occurs on drop
     */
    public static function drop(): bool
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        if (self::$instance) {
            // We'll drop the database, so we must be sure the current
            // connection is closed
            self::$instance->close();
        }

        $database_type = $database_configuration['type'];
        if ($database_type === 'sqlite' && isset($database_configuration['path'])) {
            $database_path = $database_configuration['path'];
            if ($database_path === ':memory:') {
                return true;
            } else {
                return @unlink($database_path);
            }
        } elseif ($database_type === 'pgsql') {
            $database = new self(false);
            $result = $database->exec("DROP DATABASE IF EXISTS {$database_configuration['dbname']}");
            $database->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create a database.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occurs on create
     */
    public static function create(): bool
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        $database_type = $database_configuration['type'];
        if ($database_type === 'sqlite' && isset($database_configuration['path'])) {
            $database_path = $database_configuration['path'];
            if ($database_path === ':memory:') {
                return true;
            } else {
                return @touch($database_path);
            }
        } elseif ($database_type === 'pgsql') {
            $database = new self(false);
            $result = $database->exec("CREATE DATABASE {$database_configuration['dbname']} ENCODING 'UTF8'");
            $database->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reset the whole database
     *
     * @see \Minz\Database::drop
     * @see \Minz\Database::create
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occurs on create
     */
    public static function reset(): void
    {
        self::drop();
        self::create();
    }

    /**
     * Return a DSN string to initialize PDO (can include the database name or
     * not).
     *
     * @param ConfigurationDatabase $database_configuration
     */
    private static function buildDsn(array $database_configuration, bool $with_dbname): string
    {
        if ($database_configuration['type'] === 'sqlite') {
            return 'sqlite:' . $database_configuration['path'];
        } elseif ($database_configuration['type'] === 'pgsql') {
            $dsn = 'pgsql:';
            $dsn .= 'host=' . $database_configuration['host'];
            $dsn .= ';port=' . $database_configuration['port'];
            if ($with_dbname) {
                $dsn .= ';dbname=' . $database_configuration['dbname'];
            }
            return $dsn;
        } else {
            return '';
        }
    }

    /**
     * Reset the Database instance.
     *
     * You must probably DON'T want to use this method: it's only useful to
     * test the `get()` method.
     *
     * It tries to stop the PDO connection as well, but it seems it doesn't
     * work because I unassign the instance just after. Damn you PDO!
     */
    public static function resetInstance(): void
    {
        if (self::$instance) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
