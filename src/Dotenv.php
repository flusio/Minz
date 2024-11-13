<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Dotenv class loads variables from an environment file, generally named `.env`.
 *
 * An environment file contains the configuration of an application in the form
 * of key=value. For instance:
 *
 * ```env
 * DB_HOST=localhost
 * DB_PORT=5432
 * DB_NAME=myapp_db
 * ```
 *
 * It can be read easily with Dotenv:
 *
 * ```php
 * $dotenv = new \Minz\Dotenv('/path/to/.env');
 * $db_host = $dotenv->pop('DB_HOST');
 * $db_port = intval($dotenv->pop('DB_PORT', '5432'));
 * $db_name = $dotenv->pop('DB_NAME', 'myapp_production');
 * ```
 *
 * Dotenv is generally used in the configuration files. If a `.env` file exists
 * at the root of the application, `\Minz\Configuration` will automatically
 * load it and give access to a `$dotenv` variable to these files.
 *
 * The only method available to get values from a Dotenv object is `pop()`. It
 * returns a value and deletes the variable from the object. This avoids to
 * duplicate critical secrets in too many places.
 *
 * Dotenv doesn’t modify the global environment variables (i.e. `putenv()` is
 * never called). Variables defined in the global environment have the priority
 * over the values defined in the `.env` file. In particular, it allows to
 * override the configuration at the runtime.
 *
 * The syntax supported by Dotenv is deliberately simple:
 *
 * ```env
 * # Comments start with a `#`
 * BASIC_SYNTAX=foo
 * SINGLE_QUOTES_SYNTAX='foo'
 * DOUBLE_QUOTES_SYNTAX="foo"
 * WITH_SPACES_SYNTAX = foo
 * EMPTY_VALUE_SYNTAX
 * ```
 *
 * The last example sets an empty string to the variable `EMPTY_VALUE_SYNTAX`.
 * The other variables will all have the string `foo` as value.
 *
 * For security reasons, you should never commit your `.env` file in a version
 * control tool. If you use Git, add it to your `.gitignore`! Provide a
 * `env.sample` file instead, containing an example of supported variables. In
 * production, make sure to restrict permissions on the file:
 *
 * ```console
 * $ chown www-data:www-data /path/to/.env
 * $ chmod 400 /path/to/.env
 * ```
 *
 * @see \Minz\Configuration
 */
class Dotenv
{
    /** @var array<string, string> The list of the variables loaded from the env file */
    private array $variables = [];

    /**
     * Load an env file.
     *
     * If the file cannot be read, Dotenv will return immediately and log a
     * warning. Note: an exception handled by the Configuration class might be
     * better here. It’s not a critical issue though.
     */
    public function __construct(string $dotenv_path)
    {
        $dotenv_content = @file_get_contents($dotenv_path);
        if ($dotenv_content === false) {
            Log::warning("{$dotenv_path} dotenv file cannot be read.");
            return;
        }

        // Each line is parsed one by one
        $dotenv_lines = preg_split("/\r\n|\n|\r/", $dotenv_content);
        if ($dotenv_lines === false) {
            Log::warning("{$dotenv_path} dotenv file cannot be read.");
            return;
        }

        foreach ($dotenv_lines as $line) {
            $line = trim($line);
            if (!$line) {
                continue;
            }

            // Extract the variable name and value from the line
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
            } else {
                $name = $line;
                $value = '';
            }

            if (!$name || $name[0] === '#') {
                // Ignore variables with no name and the comments
                continue;
            }

            // Extract the value from a single or double quoted string
            $value_length = strlen($value);
            if ($value_length > 0) {
                $single_quoted = $value[0] === "'" && $value[$value_length - 1] === "'";
                $double_quoted = $value[0] === '"' && $value[$value_length - 1] === '"';

                if ($single_quoted || $double_quoted) {
                    $value = substr($value, 1, $value_length - 2);
                }
            }

            $this->variables[$name] = $value;
        }
    }

    /**
     * Return the value of the given variable and drop it.
     *
     * Note that variables defined in the global environment have the priority
     * over the values defined in the env file.
     */
    public function pop(string $name, ?string $default = null): ?string
    {
        $value_from_env = getenv($name);
        if (isset($this->variables[$name])) {
            $value_from_file = $this->variables[$name];
            unset($this->variables[$name]);
        } else {
            $value_from_file = false;
        }

        if ($value_from_env !== false) {
            return $value_from_env;
        } elseif ($value_from_file !== false) {
            return $value_from_file;
        } else {
            return $default;
        }
    }
}
