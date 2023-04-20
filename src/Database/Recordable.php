<?php

namespace Minz\Database;

use Minz\Errors;

/**
 * A trait to manipulate a model in database.
 *
 * This trait provides methods that are usually provided by an ORM. Unlike ORMs
 * though, Recordable provides no methods to build easily complex SQL requests.
 * It is up to the developer to write its own methods executing its requests.
 *
 * Recordable uses the \Minz\Database class to execute the SQL requests.
 *
 * It is usually used with Recordable and Column.
 *
 *     use Minz\Database;
 *
 *     #[Database\Table(name: 'users')]
 *     class User
 *     {
 *         use Database\Recordable;
 *
 *         #[Database\Column]
 *         public int $id;
 *
 *         #[Database\Column]
 *         public \DateTimeImmutable $created_at;
 *
 *         #[Database\Column]
 *         public string $nickname;
 *     }
 *
 * Then, you can easily find or create models in database:
 *
 *     // To create or update
 *     $user = new User();
 *     $user->nickname = 'Alix';
 *     $user->created_at = \Minz\Time::now();
 *     $user->save();
 *
 *     // To find
 *     $user = User::find($some_id);
 *     $user = User::findBy(['nickname' => 'Alix']);
 *     $users = User::listAll();
 *
 *     // To count
 *     $count_users = User::count();
 *
 *     // To delete
 *     $user->remove();
 *
 * You can add your own methods to your model. For instance:
 *
 *     class User
 *     {
 *         // ...
 *
 *         public static function listCreatedAfter(\DateTimeImmutable $after): array
 *         {
 *             $table_name = self::tableName();
 *
 *             $sql = <<<SQL
 *                 SELECT * FROM {$table_name}
 *                 WHERE created_at >= :after
 *             SQL;
 *
 *             $database = \Minz\Database::get();
 *             $statement = $database->prepare($sql);
 *             $statement->execute([
 *                 ':after' => $after->format(Database\Column::DATETIME_FORMAT),
 *             ]);
 *
 *             return self::fromDatabaseRows($statement->fetchAll());
 *         }
 *     }
 *
 * @phpstan-type DatabaseCriteria array<literal-string, ModelValue|ModelValue[]>
 * @phpstan-type DatabaseValue mixed
 * @phpstan-type DatabaseValues array<literal-string, DatabaseValue>
 * @phpstan-type DatabaseValuesUnsafe array<string, DatabaseValue>
 * @phpstan-type ModelPrimaryKey string|int
 * @phpstan-type ModelType string|int|bool|\DateTimeImmutable
 * @phpstan-type ModelValue ModelType|ModelType[]|null
 * @phpstan-type ModelValues array<literal-string, ModelValue>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Recordable
{
    private bool $is_persisted = false;

    /**
     * Return a list of all the models from the database.
     *
     * @return self[]
     */
    public static function listAll(): array
    {
        return self::listBy([]);
    }

    /**
     * Return a list of the models from the database matching the given criteria.
     *
     * @param DatabaseCriteria $criteria
     *
     * @return self[]
     */
    public static function listBy(array $criteria): array
    {
        $table_name = self::tableName();
        list($where_statement, $parameters) = Helper::buildWhere($criteria);

        if ($where_statement) {
            $where_statement = "WHERE {$where_statement}";
        }

        $sql = <<<SQL
            SELECT * FROM {$table_name}
            {$where_statement}
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);
        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return a model by its primary key value.
     *
     * @param ModelPrimaryKey $pk_value
     */
    public static function find(mixed $pk_value): ?self
    {
        $pk_column = self::primaryKeyColumn();
        return self::findBy([$pk_column => $pk_value]);
    }

    /**
     * Return a model by criteria.
     *
     * If several models match the criteria, only the first is returned.
     *
     * @param DatabaseCriteria $criteria
     */
    public static function findBy(array $criteria): ?self
    {
        $table_name = self::tableName();
        list($where_statement, $parameters) = Helper::buildWhere($criteria);

        if ($where_statement) {
            $where_statement = "WHERE {$where_statement}";
        }

        $sql = <<<SQL
            SELECT * FROM {$table_name}
            {$where_statement}
            LIMIT 1
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Return the nth model if it exists.
     */
    public static function take(int $n = 0): ?self
    {
        $table_name = self::tableName();

        $sql = <<<SQL
            SELECT * FROM {$table_name}
            LIMIT 1
            OFFSET :offset
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':offset' => $n,
        ]);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Return whether a model exists in database or not, using a primary key value.
     *
     * @param ModelPrimaryKey $pk_value
     */
    public static function exists(mixed $pk_value): bool
    {
        $pk_column = self::primaryKeyColumn();
        return self::existsBy([$pk_column => $pk_value]);
    }

    /**
     * Return whether a model exists in database or not, using criteria.
     *
     * @param DatabaseCriteria $criteria
     */
    public static function existsBy(array $criteria): bool
    {
        $table_name = self::tableName();
        list($where_statement, $parameters) = Helper::buildWhere($criteria);

        if ($where_statement) {
            $where_statement = "WHERE {$where_statement}";
        }

        $sql = <<<SQL
            SELECT EXISTS (
                SELECT 1 FROM {$table_name}
                {$where_statement}
            )
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);
        return (bool)$statement->fetchColumn();
    }

    /**
     * Return the number of models in database.
     */
    public static function count(): int
    {
        return self::countBy([]);
    }

    /**
     * Return the number of models in database matching the given criteria.
     *
     * @param DatabaseCriteria $criteria
     */
    public static function countBy(array $criteria): int
    {
        $table_name = self::tableName();
        list($where_statement, $parameters) = Helper::buildWhere($criteria);

        if ($where_statement) {
            $where_statement = "WHERE {$where_statement}";
        }

        $sql = <<<SQL
            SELECT COUNT(*) FROM {$table_name}
            {$where_statement}
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);
        return intval($statement->fetchColumn());
    }

    /**
     * Create a model in database using the given values.
     *
     * It returns the value of the primary key.
     *
     * You'll probably prefer to use the save() method as it's easier to use.
     *
     * @param ModelValues $values
     *
     * @return ?ModelPrimaryKey
     */
    public static function create(array $values): mixed
    {
        $table_name = self::tableName();
        $parameters = [];
        $columns = [];
        $values_placeholders = [];

        $values = Helper::convertValuesToDatabase(static::class, $values);

        foreach ($values as $parameter => $value) {
            $parameters[":{$parameter}"] = $value;
            $columns[] = $parameter;
            $values_placeholders[] = ":{$parameter}";
        }

        $columns_statement = implode(", ", $columns);
        $values_statement = implode(", ", $values_placeholders);

        $sql = <<<SQL
            INSERT INTO {$table_name} ({$columns_statement})
            VALUES ({$values_statement})
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $result = $statement->execute($parameters);

        $pk_column = self::primaryKeyColumn();
        $inserted_pk_value = $values[$pk_column] ?? null;
        $database_pk_value = $database->lastInsertId();

        if (is_string($inserted_pk_value) || is_int($inserted_pk_value)) {
            return $inserted_pk_value;
        } elseif (filter_var($database_pk_value, FILTER_VALIDATE_INT)) {
            return intval($database_pk_value);
        } else {
            return $database_pk_value;
        }
    }

    /**
     * Update a model in database using the given values.
     *
     * It returns true if the update worked.
     *
     * You'll probably prefer to use the save() method as it's easier to use.
     *
     * @param ModelPrimaryKey $pk_value
     * @param ModelValues $values
     */
    public static function update(mixed $pk_value, array $values): bool
    {
        $table_name = self::tableName();
        $pk_column = self::primaryKeyColumn();
        $parameters = [];
        $set_exprs = [];

        $values = Helper::convertValuesToDatabase(static::class, $values);

        $parameters[":{$pk_column}"] = $pk_value;
        foreach ($values as $property => $value) {
            $set_exprs[] = "{$property} = :{$property}";
            $parameters[":{$property}"] = $value;
        }

        $set_statement = implode(', ', $set_exprs);

        $sql = <<<SQL
            UPDATE {$table_name}
            SET {$set_statement}
            WHERE {$pk_column} = :{$pk_column}
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }

    /**
     * Delete a model, using a primary key value.
     *
     * @param ModelPrimaryKey $pk_value
     */
    public static function delete(mixed $pk_value): bool
    {
        $pk_column = self::primaryKeyColumn();
        return self::deleteBy([
            $pk_column => $pk_value,
        ]);
    }

    /**
     * Delete a model, using criteria.
     *
     * @param DatabaseCriteria $criteria
     */
    public static function deleteBy(array $criteria): bool
    {
        $table_name = self::tableName();
        list($where_statement, $parameters) = Helper::buildWhere($criteria);

        // Don't accept empty where to avoid potential destructive operation on
        // bad formatted criteria.
        if (!$where_statement) {
            return true;
        }

        $sql = <<<SQL
            DELETE FROM {$table_name}
            WHERE {$where_statement}
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }

    /**
     * Save the current model in database.
     *
     * If the model is not persisted yet, it will be created. Otherwise, it
     * will be updated.
     *
     * If the model declares a created_at column of DateTimeImmutable type, it
     * will be set to current time at creation.
     *
     * If the model declares a updated_at column of DateTimeImmutable type, it
     * will be set to current time each time this method is called.
     */
    public function save(): void
    {
        $table_name = self::tableName();
        $pk_column = self::primaryKeyColumn();
        $columns = self::databaseColumns(include_computed: false);

        if (
            !$this->is_persisted &&
            isset($columns['created_at']) &&
            $columns['created_at']['type'] === 'DateTimeImmutable' &&
            !isset($this->created_at)
        ) {
            // PHPStan complains about created_at being potentially undefined.
            // Except that we know that it exists since it is declared as a
            // column. Also, I use `isset()` on purpose in the previous
            // condition because it will return true if created_at is null,
            // without complaining about the "undefined" case.
            // @phpstan-ignore-next-line
            $this->created_at = \Minz\Time::now();
        }

        if (
            isset($columns['updated_at']) &&
            $columns['updated_at']['type'] === 'DateTimeImmutable'
        ) {
            // @phpstan-ignore-next-line
            $this->updated_at = \Minz\Time::now();
        }

        $values = $this->toValues();

        if ($this->is_persisted) {
            $pk_value = $this->$pk_column;
            self::update($pk_value, $values);
        } else {
            $pk_value = self::create($values);

            $this->$pk_column = $pk_value;
            $this->is_persisted = true;
        }
    }

    /**
     * Remove the current model from the database.
     */
    public function remove(): bool
    {
        $pk_column = self::primaryKeyColumn();
        $pk_value = $this->$pk_column;
        $result = self::delete($pk_value);

        if ($result) {
            $this->is_persisted = false;
        }

        return $result;
    }

    /**
     * Return the table name of the model.
     */
    public static function tableName(): string
    {
        $reflection = new \ReflectionClass(self::class);
        $table_attributes = $reflection->getAttributes(Table::class);
        if (empty($table_attributes)) {
            throw new \Exception(self::class . ' must define a \Minz\Database\Table attribute');
        }
        $table = $table_attributes[0]->newInstance();
        return $table->name;
    }

    /**
     * Return the primary key column name of the model.
     *
     * @return literal-string
     */
    public static function primaryKeyColumn(): string
    {
        $reflection = new \ReflectionClass(self::class);
        $table_attributes = $reflection->getAttributes(Table::class);
        if (empty($table_attributes)) {
            throw new \Exception(self::class . ' must define a \Minz\Database\Table attribute');
        }
        $table = $table_attributes[0]->newInstance();
        return $table->primary_key;
    }

    /**
     * Return whether the current model is in database or not.
     *
     * $is_persisted is set to true when the model is loaded with one of the
     * methods fromDatabaseRow or fromDatabaseRows.
     */
    public function isPersisted(): bool
    {
        return $this->is_persisted;
    }

    /**
     * Export the current model values.
     *
     * This method doesn't return the "computed" columns.
     *
     * @return ModelValues
     */
    public function toValues(): array
    {
        $columns = self::databaseColumns(include_computed: false);
        $values = [];
        foreach ($columns as $property => $column) {
            $propertyReflection = new \ReflectionProperty(static::class, $property);
            if ($propertyReflection->isInitialized($this)) {
                $values[$property] = $this->$property;
            }
        }
        return $values;
    }

    /**
     * Export the current model values as database row values.
     *
     * This method doesn't return the "computed" columns.
     *
     * @return DatabaseValues
     */
    public function toDbValues(): array
    {
        return Helper::convertValuesToDatabase(
            static::class,
            $this->toValues(),
        );
    }

    /**
     * Convert a database row result in a model.
     *
     * @param DatabaseValuesUnsafe $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        $model_values = Helper::convertValuesFromDatabase(static::class, $row);

        $class_reflection = new \ReflectionClass(static::class);
        $model = $class_reflection->newInstanceWithoutConstructor();

        foreach ($model_values as $property => $value) {
            $model->$property = $value;
        }

        $model->is_persisted = true;

        return $model;
    }

    /**
     * Convert database rows results in a list of models.
     *
     * @param DatabaseValuesUnsafe[] $rows
     *
     * @return self[]
     */
    public static function fromDatabaseRows(array $rows): array
    {
        $models = [];

        foreach ($rows as $row) {
            $models[] = self::fromDatabaseRow($row);
        }

        return $models;
    }

    /**
     * Return the declarations of the columns of the model.
     *
     * @return array<literal-string, array{
     *     'type': string,
     *     'computed': bool,
     *     'format'?: string,
     * }>
     */
    public static function databaseColumns(bool $include_computed = true): array
    {
        $class_reflection = new \ReflectionClass(static::class);
        $properties = $class_reflection->getProperties();

        $columns = [];

        foreach ($properties as $property) {
            $column_attributes = $property->getAttributes(Column::class);
            if (empty($column_attributes)) {
                continue;
            }

            $column = $column_attributes[0]->newInstance();

            if (!$include_computed && $column->computed) {
                continue;
            }

            /** @var literal-string */
            $property_name = $property->getName();
            $property_type = $property->getType();

            if (!($property_type instanceof \ReflectionNamedType)) {
                $class_name = static::class;
                throw new Errors\DatabaseError("{$class_name} must define the {$property_name} property type");
            }

            $column_type = $property_type->getName();
            if (
                $column_type === 'DateTime' ||
                $column_type === 'DateTimeImmutable'
            ) {
                $column_type = 'DateTimeImmutable';
            } elseif ($column_type === 'array') {
                $column_type = 'json';
            }

            $column_declaration = [
                'type' => $column_type,
                'computed' => $column->computed,
            ];

            if ($column_type === 'DateTimeImmutable') {
                $format = $column->format ?? Column::DATETIME_FORMAT;
                $column_declaration['format'] = $format;
            }

            $columns[$property_name] = $column_declaration;
        }

        return $columns;
    }
}
