<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

/**
 * Allow to declare the database table of a class.
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
 *     }
 *
 * An optional `primary_key` parameter can be passed to declare the primary key
 * of the table (default is `id`). It must be a single column name (multi-columns
 * primary keys are not supported).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Table
{
    /** @var literal-string $name */
    public string $name;

    /** @var literal-string $primary_key */
    public string $primary_key;

    /**
     * @param literal-string $name
     * @param literal-string $primary_key
     */
    public function __construct(string $name, string $primary_key = 'id')
    {
        $this->name = $name;
        $this->primary_key = $primary_key;
    }
}
