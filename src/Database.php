<?php

namespace Minz;

class Database extends \PDO
{
    /** @var \Minz\Database */
    private static $instance;

    /**
     * Return an instance of Database.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     *
     * @return \Minz\Database
     */
    public static function get()
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        if (!self::$instance) {
            $dsn = self::buildDsn($database_configuration, true);
            $username = $database_configuration['username'];
            $password = $database_configuration['password'];
            $options = $database_configuration['options'];
            self::$instance = new self($dsn, $username, $password, $options);
        }

        return self::$instance;
    }

    /**
     * Drop the entire database. Hell yeah!
     *
     * It's useful for tests. Only SQLite database is supported for now. Take
     * care of getting a new database object after calling this method.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if database is not SQLite
     *
     * @return boolean Return true if the database file was deleted, false otherwise
     */
    public static function drop()
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        $database_type = $database_configuration['type'];

        self::$instance = null;

        if ($database_type === 'sqlite') {
            $database_path = $database_configuration['path'];
            if ($database_path === ':memory:') {
                return true;
            } else {
                return @unlink($database_path);
            }
        } else {
            throw new Errors\DatabaseError(
                "The database type {$database_type} is not supported for dropping."
            );
        }
    }

    /**
     * Return a DSN string to initialize PDO
     *
     * @param array $database_configuration The array from the Configuration
     * @param boolean $with_dbname Indicates if dbname must be included in the DSN
     *                             (it has no effects with SQLite)
     *
     * @return string
     */
    private static function buildDsn($database_configuration, $with_dbname)
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
     * Initialize a PDO database.
     *
     * @see \PDO
     *
     * @param string $dsn
     * @param string $username (optional if sqlite)
     * @param string $password (optional if sqlite)
     * @param array $options (optional)
     *
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     */
    private function __construct($dsn, $username = null, $password = null, $options = [])
    {
        $database_type = strstr($dsn, ':', true);

        // Force some options values
        $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
        $options[\PDO::ATTR_EMULATE_PREPARES] = false;

        try {
            parent::__construct($dsn, $username, $password, $options);

            if ($database_type === 'sqlite') {
                $this->exec('PRAGMA foreign_keys = ON;');
            }
        } catch (\PDOException $e) {
            throw new Errors\DatabaseError(
                "An error occured during database initialization: {$e->getMessage()}."
            );
        }
    }
}
