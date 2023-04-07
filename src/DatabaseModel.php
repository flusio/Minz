<?php

namespace Minz;

/**
 * Allow to manipulate models in database.
 *
 * @phpstan-import-type ModelValues from Model
 *
 * @phpstan-import-type ModelId from Model
 *
 * @phpstan-type Criteria array<string, mixed>
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class DatabaseModel
{
    private const VALID_TABLE_NAME_REGEX = '/^\w[\w\d]*$/';
    private const VALID_COLUMN_NAME_REGEX = '/^\w[\w\d]*$/';

    protected Database $database;

    protected string $table_name;

    protected string $primary_key_name;

    /** @var string[] */
    protected array $properties;

    /**
     * @param string[] $properties
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If the table name, or one of the declared properties is invalid
     * @throws \Minz\Errors\DatabaseModelError
     *     If the primary key name isn't declared in properties
     * @throws \Minz\Errors\DatabaseError
     *     If the database initialization fails
     *
     * @see \Minz\Database::_construct()
     */
    public function __construct(string $table_name, string $primary_key_name, array $properties)
    {
        $this->database = Database::get();

        $this->setTableName($table_name);
        $this->setProperties($properties);
        $this->setPrimaryKeyName($primary_key_name);
    }

    /**
     * Create an instance of the model in database
     *
     * @param ModelValues $values
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If values is empty
     * @throws \Minz\Errors\DatabaseModelError
     *     If at least one property isn't declared by the model
     * @throws \PDOException
     *     If an error occured in the SQL syntax
     *
     * @return ModelId|boolean
     *     Return the id as an integer if cast is possible, as a string
     *     otherwise. Return true if lastInsertId is not supported by the PDO
     *     driver.
     */
    public function create($values): mixed
    {
        if (!$values) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$class}::create method expect values to be passed."
            );
        }

        $properties = array_keys($values);
        $undeclared_property = $this->findUndeclaredProperty($properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        $values_as_question_marks = array_fill(0, count($values), '?');
        $values_placeholder = implode(", ", $values_as_question_marks);
        $columns_placeholder = implode(", ", $properties);

        $sql = "INSERT INTO {$this->table_name} ({$columns_placeholder}) VALUES ({$values_placeholder})";

        $statement = $this->prepare($sql);
        $result = $statement->execute(array_values($values));

        if (isset($values[$this->primary_key_name])) {
            /** @var ModelId $value */
            $value = $values[$this->primary_key_name];
            return $value;
        } else {
            return $this->lastInsertId();
        }
    }

    /**
     * Return a list of all items in database for the current model/table.
     *
     * @param string[] $selected_properties
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If at least one property isn't declared by the model
     * @throws \PDOException
     *     If an error occured in the SQL syntax
     *
     * @return ModelValues[]
     */
    public function listAll(array $selected_properties = []): array
    {
        $undeclared_property = $this->findUndeclaredProperty($selected_properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        if ($selected_properties) {
            $select_sql = implode(', ', $selected_properties);
        } else {
            $select_sql = '*';
        }

        $sql = "SELECT {$select_sql} FROM {$this->table_name}";

        $statement = $this->query($sql);
        return $statement->fetchAll();
    }

    /**
     * Return a row of the current model/table.
     *
     * @param ModelId $primary_key
     *
     * @throws \PDOException
     *     If an error occured in the SQL syntax
     *
     * @return ?ModelValues
     */
    public function find(mixed $primary_key): ?array
    {
        $sql = "SELECT * FROM {$this->table_name} WHERE {$this->primary_key_name} = ?";

        $statement = $this->prepare($sql);
        $statement->execute([$primary_key]);
        /** @var ?ModelValues $result */
        $result = $statement->fetch();
        if ($result) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Return a row matching the given criteria.
     *
     * @param Criteria $criteria
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If criteria is empty
     * @throws \Minz\Errors\DatabaseModelError
     *     If at least one property isn't declared by the model
     * @throws \Minz\Errors\DatabaseModelError
     *     If an error occured in the SQL syntax
     *
     * @return ?ModelValues
     */
    public function findBy(array $criteria): ?array
    {
        $result = $this->listBy($criteria);
        if ($result) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Return a list of rows matching the given criteria.
     *
     * The $criteria array keys must match with valid properties. The $criteria
     * array values can either be a single value or an array (in which case the
     * where condition will be IN instead of =).
     *
     * @param Criteria $criteria
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If criteria is empty
     * @throws \Minz\Errors\DatabaseModelError
     *     If at least one property isn't declared by the model
     * @throws \PDOException
     *     If an error occured in the SQL syntax
     *
     * @return ModelValues[]
     */
    public function listBy(array $criteria): array
    {
        if (!$criteria) {
            throw new Errors\DatabaseModelError(
                'It is expected criteria not to be empty.'
            );
        }

        $properties = [];
        $parameters = [];
        $where_statement_as_array = [];
        foreach ($criteria as $property => $parameter) {
            $properties[] = $property;

            if (is_array($parameter)) {
                $parameters = array_merge($parameters, $parameter);
                $question_marks = array_fill(0, count($parameter), '?');
                $in_statement = implode(',', $question_marks);
                $where_statement_as_array[] = "{$property} IN ({$in_statement})";
            } elseif ($parameter === null) {
                $where_statement_as_array[] = "{$property} IS NULL";
            } else {
                $parameters[] = $parameter;
                $where_statement_as_array[] = "{$property} = ?";
            }
        }

        $undeclared_property = $this->findUndeclaredProperty($properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        $where_statement = implode(' AND ', $where_statement_as_array);
        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_statement}";
        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Return whether all the given values exist in database.
     *
     * @param ModelId|ModelId[] $primary_keys
     */
    public function exists(mixed $primary_keys): bool
    {
        if (!is_array($primary_keys)) {
            $primary_keys = [$primary_keys];
        }

        $matching_rows = $this->listBy([
            $this->primary_key_name => $primary_keys
        ]);
        return count($matching_rows) === count($primary_keys);
    }

    /**
     * Update a row.
     *
     * @param ModelId $primary_key
     * @param ModelValues $values
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If values are empty
     * @throws \Minz\Errors\DatabaseModelError
     *     If at least one property isn't declared by the model
     * @throws \PDOException
     *     If an error occured in the SQL syntax
     */
    public function update(mixed $primary_key, array $values): bool
    {
        if (!$values) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$class}::update method expect values to be passed."
            );
        }

        $properties = array_keys($values);
        $undeclared_property = $this->findUndeclaredProperty($properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        $set_statement_as_array = [];
        foreach ($properties as $property) {
            $set_statement_as_array[] = "{$property} = ?";
        }
        $set_statement = implode(', ', $set_statement_as_array);
        $where_statement = "{$this->primary_key_name} = ?";

        $sql = "UPDATE {$this->table_name} SET {$set_statement} WHERE {$where_statement}";

        $statement = $this->prepare($sql);
        $parameters = array_values($values);
        $parameters[] = $primary_key;
        return $statement->execute($parameters);
    }

    /**
     * Delete all rows.
     *
     * It returns the number of deleted rows.
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    public function deleteAll(): int
    {
        return $this->exec("DELETE FROM {$this->table_name}");
    }

    /**
     * Delete one or several row by primary key value.
     *
     * @param ModelId|ModelId[] $pk_values
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    public function delete(mixed $pk_values): bool
    {
        if (is_array($pk_values)) {
            $question_marks = array_fill(0, count($pk_values), '?');
            $where_statement = implode(',', $question_marks);
            $where_statement = "{$this->primary_key_name} IN ({$where_statement})";
        } else {
            $where_statement = "{$this->primary_key_name} = ?";
            $pk_values = [$pk_values];
        }

        $sql = "DELETE FROM {$this->table_name} WHERE {$where_statement}";
        $statement = $this->prepare($sql);
        return $statement->execute($pk_values);
    }

    /**
     * Return the number of model instances saved in database
     *
     * @throws \Minz\Errors\DatabaseModelError if the query fails
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table_name};";

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of rows matching the given criteria.
     *
     * The $criteria array keys must be existing properties. The $criteria array
     * values can either be a single value or an array (in which case the where
     * condition will be IN instead of =).
     *
     * @param Criteria $criteria
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     if criteria is empty
     * @throws \Minz\Errors\DatabaseModelError
     *     if at least one property isn't declared by the model
     * @throws \Minz\Errors\DatabaseModelError
     *     if the query fails
     * @throws \PDOException
     *     if an error occured in the SQL syntax
     */
    public function countBy(array $criteria): int
    {
        if (!$criteria) {
            throw new Errors\DatabaseModelError(
                'It is expected criteria not to be empty.'
            );
        }

        $properties = [];
        $parameters = [];
        $where_statement_as_array = [];
        foreach ($criteria as $property => $parameter) {
            $properties[] = $property;

            if (is_array($parameter)) {
                $parameters = array_merge($parameters, $parameter);
                $question_marks = array_fill(0, count($parameter), '?');
                $in_statement = implode(',', $question_marks);
                $where_statement_as_array[] = "{$property} IN ({$in_statement})";
            } elseif ($parameter === null) {
                $where_statement_as_array[] = "{$property} IS NULL";
            } else {
                $parameters[] = $parameter;
                $where_statement_as_array[] = "{$property} = ?";
            }
        }

        $undeclared_property = $this->findUndeclaredProperty($properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        $where_statement = implode(' AND ', $where_statement_as_array);
        $sql = <<<SQL
            SELECT COUNT(*) FROM {$this->table_name}
            WHERE {$where_statement}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return intval($statement->fetchColumn());
    }

    /**
     * Call query method on a database object.
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    protected function query(string $sql_statement): \PDOStatement
    {
        return $this->database->query($sql_statement);
    }

    /**
     * Call exec method on a database object.
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    protected function exec(string $sql_statement): int
    {
        return $this->database->exec($sql_statement);
    }

    /**
     * Call prepare method on a database object.
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    protected function prepare(string $sql_statement): \PDOStatement
    {
        return $this->database->prepare($sql_statement);
    }

    /**
     * Return the id of the last inserted row
     *
     * @see \PDO::lastInsertId()
     *
     * @return ModelId|true
     *     Return the id as an integer if cast is possible, as a string
     *     otherwise. Return true if lastInsertId is not supported by the PDO
     *     driver.
     */
    protected function lastInsertId(): mixed
    {
        try {
            $id = $this->database->lastInsertId();
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                return intval($id);
            } else {
                return $id;
            }
        } catch (\PDOException $e) {
            return true;
        }
    }

    /**
     * @see \PDO::beginTransaction https://www.php.net/manual/pdo.begintransaction.php
     *
     * @throws \PDOException if there is already an active transaction.
     */
    protected function beginTransaction(): bool
    {
        return $this->database->beginTransaction();
    }

    /**
     * @see \PDO::commit https://www.php.net/manual/pdo.commit.php
     *
     * @throws \PDOException if there is no active transaction.
     */
    protected function commit(): bool
    {
        return $this->database->commit();
    }

    /**
     * @see \PDO::rollBack https://www.php.net/manual/pdo.rollback.php
     *
     * @throws \PDOException if there is no active transaction.
     */
    protected function rollBack(): bool
    {
        return $this->database->rollBack();
    }

    /**
     * Return one (and only one) undeclared property.
     *
     * The properties must be declared in the `$properties` attribute. If
     * someone tries to set or use an undeclared property, an error must be
     * throwed.
     *
     * @param string[] $properties
     */
    protected function findUndeclaredProperty(array $properties): ?string
    {
        $valid_properties = $this->properties;
        $undeclared_properties = array_diff($properties, $valid_properties);
        if ($undeclared_properties) {
            return current($undeclared_properties);
        } else {
            return null;
        }
    }

    public function tableName(): string
    {
        return $this->table_name;
    }

    public function primaryKeyName(): string
    {
        return $this->primary_key_name;
    }

    /**
     * @return string[]
     */
    public function properties(): array
    {
        return $this->properties;
    }

    /**
     * @throws \Minz\Errors\DatabaseModelError
     *     If the table name is invalid
     */
    private function setTableName(string $table_name): void
    {
        if (!preg_match(self::VALID_TABLE_NAME_REGEX, $table_name)) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$table_name} is not a valid table name in the {$class} model."
            );
        }
        $this->table_name = $table_name;
    }

    /**
     * @param string[] $properties
     *
     * @throws \Minz\Errors\DatabaseModelError
     *     If at least one of the properties is invalid
     */
    private function setProperties(array $properties): void
    {
        foreach ($properties as $property) {
            if (!preg_match(self::VALID_COLUMN_NAME_REGEX, $property)) {
                $class = get_called_class();
                throw new Errors\DatabaseModelError(
                    "{$property} is not a valid column name in the {$class} model."
                );
            }
        }
        $this->properties = $properties;
    }

    /**
     * @throws \Minz\Errors\DatabaseModelError
     *     If the primary key name isn't declared in properties
     */
    private function setPrimaryKeyName(string $primary_key_name): void
    {
        if (!in_array($primary_key_name, $this->properties)) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "Primary key {$primary_key_name} must be in properties in the {$class} model."
            );
        }
        $this->primary_key_name = $primary_key_name;
    }
}
