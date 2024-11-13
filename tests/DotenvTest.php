<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class DotenvTest extends TestCase
{
    public function testPop(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('FOO');

        $this->assertSame('bar', $variable);
    }

    public function testPopWithDefaultValue(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('SPAM', 'egg');

        $this->assertSame('egg', $variable);
    }

    public function testPopErasesTheValue(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $dotenv->pop('FOO');
        $variable = $dotenv->pop('FOO');

        $this->assertNull($variable);
    }

    public function testPopWithEnvAlreadySet(): void
    {
        putenv('FOO=spam');
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('FOO');

        $this->assertSame('spam', $variable);
        putenv('FOO');
    }

    public function testPopErasesTheValueWithEnvAlreadySet(): void
    {
        putenv('FOO=spam');
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $dotenv->pop('FOO');
        putenv('FOO');
        $variable = $dotenv->pop('FOO');

        $this->assertNull($variable);
    }

    public function testPopWithEnvSetButNotInDotenv(): void
    {
        putenv('NOT_DEFINED=spam');
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('NOT_DEFINED');

        $this->assertSame('spam', $variable);
    }

    public function testPopTrimsNamesAndValues(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('WITH');

        $this->assertSame('SPACES', $variable);
    }

    public function testPopWithVariableWithoutValue(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('TRUE_VALUE');

        $this->assertSame('', $variable);
    }

    public function testPopWithValueBetweenSingleQuotes(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('SINGLE_QUOTES');

        $this->assertSame('foo', $variable);
    }

    public function testPopWithValueBetweenDoubleQuotes(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('DOUBLE_QUOTES');

        $this->assertSame('foo', $variable);
    }

    public function testPopIgnoresComments(): void
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('#COMMENTED');
        $this->assertNull($variable);

        $variable = $dotenv->pop('COMMENTED');
        $this->assertNull($variable);
    }
}
