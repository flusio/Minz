<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class DotenvTest extends TestCase
{
    public function testPop()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('FOO');

        $this->assertSame('bar', $variable);
    }

    public function testPopWithDefaultValue()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('SPAM', 'egg');

        $this->assertSame('egg', $variable);
    }

    public function testPopErasesTheValue()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $dotenv->pop('FOO');
        $variable = $dotenv->pop('FOO');

        $this->assertNull($variable);
    }

    public function testPopTrimsNamesAndValues()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('WITH');

        $this->assertSame('SPACES', $variable);
    }

    public function testPopWithVariableWithoutValue()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('TRUE_VALUE');

        $this->assertSame('', $variable);
    }

    public function testPopWithValueBetweenSingleQuotes()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('SINGLE_QUOTES');

        $this->assertSame('foo', $variable);
    }

    public function testPopWithValueBetweenDoubleQuotes()
    {
        $dotenv_path = Configuration::$app_path . '/dotenv';
        $dotenv = new Dotenv($dotenv_path);

        $variable = $dotenv->pop('DOUBLE_QUOTES');

        $this->assertSame('foo', $variable);
    }
}
