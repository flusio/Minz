<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

use Minz\Errors;

/**
 * A class to facilitate the use of the Recordable trait.
 *
 * @phpstan-import-type ModelValues from Recordable
 * @phpstan-import-type DatabaseCriteria from Recordable
 * @phpstan-import-type DatabaseValues from Recordable
 * @phpstan-import-type DatabaseValuesUnsafe from Recordable
 */
class Helper
{
    /**
     * Transform a list of criteria into a SQL where statement and a list of
     * parameters.
     *
     * @param DatabaseCriteria $criteria
     *
     * @return array{
     *     literal-string,
     *     array<literal-string, mixed>,
     * }
     */
    public static function buildWhere(array $criteria): array
    {
        $where_exprs = [];
        $parameters = [];

        foreach ($criteria as $property => $condition) {
            if (is_array($condition)) {
                $in_parameters = [];

                foreach ($condition as $value) {
                    $count_parameters = count($parameters);
                    /** @var literal-string */
                    $parameter_key = "param{$count_parameters}";
                    $parameters[$parameter_key] = $value;
                    $in_parameters[] = ":{$parameter_key}";
                }

                $in_statement = implode(',', $in_parameters);
                $where_exprs[] = "{$property} IN ({$in_statement})";
            } elseif ($condition === null) {
                $where_exprs[] = "{$property} IS NULL";
            } else {
                $count_parameters = count($parameters);
                /** @var literal-string */
                $parameter_key = "param{$count_parameters}";

                if (is_bool($condition)) {
                    $condition = (int)$condition;
                }

                $parameters[$parameter_key] = $condition;

                $where_exprs[] = "{$property} = :{$parameter_key}";
            }
        }

        $where_statement = implode(' AND ', $where_exprs);
        return [$where_statement, $parameters];
    }

    /**
     * Transform a list of "model" values in a list of "database" values.
     *
     * @param class-string $class_name
     * @param ModelValues $values
     *
     * @throws Errors\DatabaseModelError
     *     If the values include invalid parameters (undefined or computed
     *     column, or bad format).
     *
     * @return DatabaseValues
     */
    public static function convertValuesToDatabase(string $class_name, array $values): array
    {
        $columns = $class_name::databaseColumns(include_computed: false);
        $converted_values = [];

        foreach ($values as $property => $value) {
            if (!isset($columns[$property])) {
                throw new Errors\DatabaseModelError("{$class_name} doesn't define a {$property} property");
            }

            $column = $columns[$property];

            if (
                $column['type'] === 'DateTimeImmutable' &&
                isset($column['format']) &&
                $value instanceof \DateTimeInterface
            ) {
                $converted_values[$property] = $value->format($column['format']);
            } elseif (
                $column['type'] === 'int' &&
                is_int($value)
            ) {
                $converted_values[$property] = $value;
            } elseif (
                $column['type'] === 'bool' &&
                is_bool($value)
            ) {
                $converted_values[$property] = (int)$value;
            } elseif (
                $column['type'] === 'json' &&
                is_array($value)
            ) {
                $json = json_encode($value);

                if ($json === false) {
                    throw new Errors\DatabaseModelError("Cannot encode {$property} JSON value");
                }

                $converted_values[$property] = $json;
            } elseif (
                $column['type'] === 'string' &&
                is_string($value)
            ) {
                $converted_values[$property] = $value;
            } elseif ($value === null) {
                $converted_values[$property] = null;
            } else {
                throw new Errors\DatabaseModelError("Cannot convert {$property} value");
            }
        }

        return $converted_values;
    }

    /**
     * Transform a list of "database" values in a list of "model" values.
     *
     * @param class-string $class_name
     * @param DatabaseValuesUnsafe $values
     *
     * @throws Errors\DatabaseModelError
     *     If values include a parameter that is not defined as a column.
     *
     * @return ModelValues
     */
    public static function convertValuesFromDatabase(string $class_name, array $values): array
    {
        $columns = $class_name::databaseColumns();
        $converted_values = [];

        foreach ($values as $property => $value) {
            if (!isset($columns[$property])) {
                throw new Errors\DatabaseModelError("{$class_name} doesn't define a {$property} property");
            }

            /**
             * We can consider that the property is a literal-string since it
             * is defined as a model column, i.e. a class property has this
             * name.
             *
             * @var literal-string
             */
            $property = $property;
            $column = $columns[$property];

            if (
                $column['type'] === 'DateTimeImmutable' &&
                isset($column['format']) &&
                (is_string($value) || is_int($value))
            ) {
                $date = \DateTimeImmutable::createFromFormat(
                    $column['format'],
                    (string) $value
                );
                $converted_values[$property] = $date;
            } elseif ($column['type'] === 'int' && $value !== null) {
                $integer = filter_var($value, FILTER_VALIDATE_INT);
                $converted_values[$property] = $integer;
            } elseif ($column['type'] === 'bool' && $value !== null) {
                $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                $converted_values[$property] = $boolean;
            } elseif ($column['type'] === 'json' && is_string($value)) {
                $json_value = json_decode($value, true);

                if (!is_array($json_value)) {
                    throw new Errors\DatabaseError("Cannot decode {$property} JSON value");
                }

                $converted_values[$property] = $json_value;
            } elseif ($column['type'] === 'string' && is_string($value)) {
                $converted_values[$property] = $value;
            } elseif ($value === null) {
                $converted_values[$property] = null;
            } else {
                throw new Errors\DatabaseError("Cannot convert {$property} value");
            }
        }

        return $converted_values;
    }
}
