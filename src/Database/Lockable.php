<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Database;

/**
 * Make a model lockable in database.
 *
 * This trait can be applied to a model (i.e. already using the Recordable
 * trait) to add lockable methods.
 * The Lockable trait expects the model to have a locked_at column of type
 * DateTimeImmutable.
 *
 *     use Minz\Database;
 *
 *     #[Database\Table(name: 'users')]
 *     class User
 *     {
 *         use Database\Recordable;
 *         use Database\Lockable;
 *
 *         #[Database\Column]
 *         public int $id;
 *
 *         #[Database\Column]
 *         public ?\DateTimeImmutable $locked_at = null;
 *     }
 *
 * You can use it then like the following:
 *
 *     $user = User::take();
 *
 *     $user->lock();
 *     assert($user->isLocked());
 *
 *     $user->unlock();
 *     assert(!$user->isLocked());
 *
 * By default, the lock is valid for 1 hour. You can change it by passing a
 * datetime to lock and isLocked:
 *
 *     $lock_timeout = \Minz\Time::ago(2, 'hours');
 *     $user->lock($lock_timeout);
 *     assert($user->isLocked($lock_timeout));
 */
trait Lockable
{
    /**
     * Lock the model during a certain time (default is 1 hour) and return
     * whether the operation was successful or not.
     */
    public function lock(?\DateTimeImmutable $lock_timeout = null): bool
    {
        $now = \Minz\Time::now();
        if (!$lock_timeout) {
            $lock_timeout = \Minz\Time::ago(1, 'hour');
        }

        $table_name = self::tableName();
        $pk_column = self::primaryKeyColumn();
        $pk_value = $this->$pk_column;

        $sql = <<<SQL
            UPDATE {$table_name}
            SET locked_at = :locked_at
            WHERE {$pk_column} = :pk_value
            AND (locked_at IS NULL OR locked_at <= :lock_timeout)
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':locked_at' => $now->format(Column::DATETIME_FORMAT),
            ':lock_timeout' => $lock_timeout->format(Column::DATETIME_FORMAT),
            ':pk_value' => $pk_value,
        ]);

        $result = $statement->rowCount() === 1;

        if ($result) {
            // The value changed in the database, we want it to be reflected in
            // the current model.
            $this->locked_at = $now;
        }

        return $result;
    }

    /**
     * Unlock the model and return whether the operation was successful or not.
     *
     * If the model wasn't locked, the method returns true.
     */
    public function unlock(): bool
    {
        $table_name = self::tableName();
        $pk_column = self::primaryKeyColumn();
        $pk_value = $this->$pk_column;

        $sql = <<<SQL
            UPDATE {$table_name}
            SET locked_at = null
            WHERE {$pk_column} = :pk_value
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':pk_value' => $pk_value,
        ]);

        $result = $statement->rowCount() === 1;

        if ($result) {
            // The value changed in the database, we want it to be reflected in
            // the current model.
            $this->locked_at = null;
        }

        return $result;
    }

    /**
     * Return whether the model is locked or not. A timeout can be precised
     * (default is 1 hour).
     */
    public function isLocked(?\DateTimeImmutable $lock_timeout = null): bool
    {
        if ($this->locked_at === null) {
            return false;
        }

        if (!$lock_timeout) {
            $lock_timeout = \Minz\Time::ago(1, 'hour');
        }

        return $this->locked_at > $lock_timeout;
    }
}
