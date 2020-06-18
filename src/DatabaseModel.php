<?php

namespace Minz;

class DatabaseModel
{
    private const VALID_TABLE_NAME_REGEX = '/^\w[\w\d]*$/';
    private const VALID_COLUMN_NAME_REGEX = '/^\w[\w\d]*$/';

    /** @var \Minz\Database */
    protected $database;

    /** @var string */
    protected $table_name;

    /** @var string */
    protected $primary_key_name;

    /** @var string[] */
    protected $properties;

    /**
     * @throws \Minz\DatabaseModelError if the table name, or one of the
     *                                  declared properties is invalid
     * @throws \Minz\DatabaseModelError if the primary key name isn't declared
     *                                  in properties
     * @throws \Minz\Errors\DatabaseError if the database initialization fails
     *
     * @see \Minz\Database::_construct()
     */
    public function __construct($table_name, $primary_key_name, $properties)
    {
        $this->database = Database::get();

        $this->setTableName($table_name);
        $this->setProperties($properties);
        $this->setPrimaryKeyName($primary_key_name);
    }

    /**
     * Create an instance of the model in database
     *
     * @param mixed[] $values The list of properties with associated values
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \PDOException if an error occured in the SQL syntax
     *
     * @return integer|string|boolean Return the id as an integer if cast is
     *                                possible, as a string otherwise. Return
     *                                true if lastInsertId is not supported by
     *                                the PDO driver.
     */
    public function create($values)
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
            return $values[$this->primary_key_name];
        } else {
            return $this->lastInsertId();
        }
    }

    /**
     * Return a list of all items in database for the current model/table.
     *
     * @param string[] $selected_properties Allow to limit what properties to
     *                                      get (optional)
     *
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \PDOException if an error occured in the SQL syntax
     *
     * @return array
     */
    public function listAll($selected_properties = [])
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
     * @param integer|string $primary_key The value of the row's primary key to find
     *
     * @throws \PDOException if an error occured in the SQL syntax
     *
     * @return array|null Return the corresponding row data, null otherwise
     */
    public function find($primary_key)
    {
        $sql = "SELECT * FROM {$this->table_name} WHERE {$this->primary_key_name} = ?";

        $statement = $this->prepare($sql);
        $statement->execute([$primary_key]);
        $result = $statement->fetch();
        if ($result) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Return a row matching the given values.
     *
     * @param mixed[] $values The values which must match
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return array|null Return the corresponding row data, null otherwise
     */
    public function findBy($values)
    {
        $result = $this->listBy($values);
        if ($result) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Return a list of rows matching the given values.
     *
     * The $values array keys must match with valid properties. The $values
     * array values can either be a single value or an array (in which case the
     * where condition will be IN instead of =).
     *
     * @param array $values The values which must match
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \PDOException if an error occured in the SQL syntax
     *
     * @return array Return the corresponding row data, null otherwise
     */
    public function listBy($values)
    {
        if (!$values) {
            throw new Errors\DatabaseModelError(
                'It is expected values not to be empty.'
            );
        }

        $properties = [];
        $parameters = [];
        $where_statement_as_array = [];
        foreach ($values as $property => $parameter) {
            $properties[] = $property;

            if (is_array($parameter)) {
                $parameters = array_merge($parameters, $parameter);
                $question_marks = array_fill(0, count($parameter), '?');
                $in_statement = implode(',', $question_marks);
                $where_statement_as_array[] = "{$property} IN ({$in_statement})";
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
     * @param mixed[] $primary_keys
     *
     * @return boolean
     */
    public function exists($primary_keys)
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
     * Update a row
     *
     * @param mixed[] $values The list of properties with associated values
     * @param integer|string $primary_key The value of the row's primary key to change
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \PDOException if an error occured in the SQL syntax
     */
    public function update($primary_key, $values)
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
     * Delete a row.
     *
     * @param integer|string $primary_key The value of the row's primary key to delete
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    public function delete($pk_values)
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
     * @throws \Minz\DatabaseModelError if the query fails
     *
     * @return integer
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) FROM {$this->table_name};";

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Call query method on a database object.
     *
     * @param string $sql_statement
     *
     * @throws \PDOException if an error occured in the SQL syntax
     *
     * @return \PDOStatement
     */
    protected function query($sql_statement)
    {
        return $this->database->query($sql_statement);
    }

    /**
     * Call prepare method on a database object.
     *
     * @param string $sql_statement
     *
     * @throws \PDOException if an error occured in the SQL syntax
     *
     * @return \PDOStatement
     */
    protected function prepare($sql_statement)
    {
        return $this->database->prepare($sql_statement);
    }

    /**
     * Return the id of the last inserted row
     *
     * @see \PDO::lastInsertId()
     *
     * @return integer|string|boolean Return the id as an integer if cast is
     *                                possible, as a string otherwise. Return
     *                                true if lastInsertId is not supported by
     *                                the PDO driver.
     */
    protected function lastInsertId()
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
     * Return one (and only one) undeclared property.
     *
     * The properties must be declared in the `$properties` attribute. If
     * someone tries to set or use an undeclared property, an error must be
     * throwed.
     *
     * @param string[] $properties The properties to check
     *
     * @return string|null Return an undeclared property if any, null otherwise
     */
    protected function findUndeclaredProperty($properties)
    {
        $valid_properties = $this->properties;
        $undeclared_properties = array_diff($properties, $valid_properties);
        if ($undeclared_properties) {
            return current($undeclared_properties);
        } else {
            return null;
        }
    }

    /**
     * @throws \Minz\DatabaseModelError if the table name is invalid
     */
    private function setTableName($table_name)
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
     * @throws \Minz\DatabaseModelError if at least one of the properties is invalid
     */
    private function setProperties($properties)
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
     * @throws \Minz\DatabaseModelError if the primary key name isn't declared
     *                                  in properties
     */
    private function setPrimaryKeyName($primary_key_name)
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
