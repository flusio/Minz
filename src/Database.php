<?php

namespace Minz;

/**
 * Handle the database requests.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Database
{
    /** @var \Minz\Database|null */
    private static $instance;

    /** @var \PDO|null */
    private $pdo_connection;

    private $connect_to_db;

    /**
     * Initialize a database. Note it is private, you must use `\Minz\Database::get`
     * to get an instance.
     *
     * @param boolean $connect_to_db
     *     Indicates if the connection must be done on the database. Else, the
     *     dbname will not be included in the DSN. It is useful if you need to
     *     drop or create a database. It has no effects with SQLite.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     */
    private function __construct($connect_to_db = true)
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
    public function start()
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
    public function close()
    {
        // When unassigning a PDO object, it closes the connection to the
        // database.
        $this->pdo_connection = null;
    }

    /**
     * @see \PDO::beginTransaction https://www.php.net/manual/pdo.begintransaction.php
     */
    public function beginTransaction()
    {
        return $this->pdoCall('beginTransaction');
    }

    /**
     * @see \PDO::commit https://www.php.net/manual/pdo.commit.php
     */
    public function commit()
    {
        return $this->pdoCall('commit');
    }

    /**
     * @see \PDO::errorCode https://www.php.net/manual/pdo.errorcode.php
     */
    public function errorCode()
    {
        return $this->pdoCall('errorCode');
    }

    /**
     * @see \PDO::errorInfo https://www.php.net/manual/pdo.errorinfo.php
     */
    public function errorInfo()
    {
        return $this->pdoCall('errorInfo');
    }

    /**
     * @see \PDO::exec() https://www.php.net/manual/pdo.exec.php
     */
    public function exec($sql_statement)
    {
        return $this->pdoCall('exec', $sql_statement);
    }

    /**
     * @see \PDO::getAttribute() https://www.php.net/manual/pdo.getattribute.php
     */
    public function getAttribute($attribute)
    {
        return $this->pdoCall('getAttribute', $attribute);
    }

    /**
     * @see \PDO::inTransaction() https://www.php.net/manual/pdo.intransaction.php
     */
    public function inTransaction()
    {
        return $this->pdoCall('inTransaction');
    }

    /**
     * @see \PDO::lastInsertId() https://www.php.net/manual/pdo.lastinsertid.php
     */
    public function lastInsertId()
    {
        return $this->pdoCall('lastInsertId');
    }

    /**
     * @see \PDO::prepare() https://www.php.net/manual/pdo.prepare.php
     */
    public function prepare($sql_statement)
    {
        return $this->pdoCall('prepare', $sql_statement);
    }

    /**
     * @see \PDO::query() https://www.php.net/manual/pdo.query.php
     */
    public function query($sql_statement)
    {
        return $this->pdoCall('query', $sql_statement);
    }

    /**
     * @see \PDO::quote https://www.php.net/manual/pdo.quote.php
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return $this->pdoCall('quote', $string, $parameter_type);
    }

    /**
     * @see \PDO::rollBack https://www.php.net/manual/pdo.rollback.php
     */
    public function rollBack()
    {
        return $this->pdoCall('rollBack');
    }

    /**
     * @see \PDO::setAttribute() https://www.php.net/manual/pdo.setattribute.php
     */
    public function setAttribute($attribute, $value)
    {
        return $this->pdoCall('setAttribute', $attribute, $value);
    }

    /**
     * Transfer method calls to the PDO connection. It starts the connection if
     * it has been stopped.
     *
     * @param string $name Method name to transfer
     * @param mixed $arguments,... Arguments to pass to the method
     *
     * @return mixed
     */
    public function pdoCall($name, ...$arguments)
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
     *
     * @return \Minz\Database
     */
    public static function get()
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
     *
     * @return boolean Return true if the database was dropped, false otherwise
     */
    public static function drop()
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
        if ($database_type === 'sqlite') {
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
     *
     * @return boolean Return true if the database was created, false otherwise
     */
    public static function create()
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        $database_type = $database_configuration['type'];
        if ($database_type === 'sqlite') {
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
    public static function reset()
    {
        self::drop();
        self::create();
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
     * Reset the Database instance.
     *
     * You must probably DON'T want to use this method: it's only useful to
     * test the `get()` method.
     *
     * It tries to stop the PDO connection as well, but it seems it doesn't
     * work because I unassign the instance just after. Damn you PDO!
     */
    public static function resetInstance()
    {
        if (self::$instance) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
