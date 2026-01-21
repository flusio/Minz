<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use Minz\Errors;

/**
 * Make sure the context is correctly initialized before executing a test.
 * It (re)initializes the database, the session and the test mailer.
 *
 * @phpstan-ignore-next-line trait.unused
 */
trait InitializerHelper
{
    #[\PHPUnit\Framework\Attributes\Before]
    public function beginDatabaseTransaction(): void
    {
        $database = \Minz\Database::get();
        $database->beginTransaction();
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function rollbackDatabaseTransaction(): void
    {
        $database = \Minz\Database::get();
        $database->rollBack();
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetSession(): void
    {
        session_unset();
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTestMailer(): void
    {
        Mailer::clear();
    }
}
