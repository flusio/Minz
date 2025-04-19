<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a property is unique in database.
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Unique(message: 'Email {value} is already taken.')]
 *         public string $email;
 *     }
 *
 * This check can be used only on Recordable models.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Unique extends Check
{
    public function assert(): bool
    {
        $value = $this->getValue();
        $property_name = $this->property->getName();
        $instance = $this->instance;
        $instance_class = get_class($instance);
        $used_traits = class_uses($instance_class);

        if (
            $used_traits === false ||
            !in_array(\Minz\Database\Recordable::class, $used_traits) ||
            !method_exists($instance, 'isPersisted')
        ) {
            throw new \LogicException('Unique can only be used on a Recordable class.');
        }

        $table_name = $instance_class::tableName();
        $pk_column = $instance_class::primaryKeyColumn();

        $parameters = [];

        $where_statement = "WHERE {$property_name} = ?";
        $parameters[] = $value;

        if ($instance->isPersisted()) {
            $where_statement .= " AND {$pk_column} != ?";
            $parameters[] = $instance->$pk_column;
        }

        $sql = <<<SQL
            SELECT NOT EXISTS (
                SELECT 1 FROM {$table_name}
                {$where_statement}
            )
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return (bool)$statement->fetchColumn();
    }
}
