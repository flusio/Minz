<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use Minz\Configuration;
use Minz\Errors;

/**
 * An output class to return content to users based on a template file.
 *
 * It is represented by a file under src/views. The view file is called
 * "pointer".
 *
 * ```php
 * $view_output = new Output\View('pointer/to/view.phtml', [
 *     'foo' => 'bar',
 * ]);
 * $response = new Response(200, $view_output);
 * ```
 *
 * You should not have to initialize this output manually as it can be
 * shortened in:
 *
 * ```php
 * $response = Response::ok('pointer/to/view.phtml', [
 *     'foo' => 'bar',
 * ]);
 * ```
 *
 * The view file contains most of the time some templated HTML content:
 *
 * ```php
 * <p>
 *     Hello {$foo}!
 * </p>
 * ```
 *
 * The template has access to different methods defined in this file (see below
 * for their full documentation):
 *
 * ```php
 * $this->layout('path/to/layout.phtml', ['foo' => $foo]);
 * $this->include('path/to/partial.phtml', ['foo', => $foo]);
 * $this->safe('foo');
 * $this->protect($foo);
 * ```
 *
 * The template also has access to some helper functions defined in the file view_helpers.php.
 *
 * @see /src/Output/view_helpers.php
 *
 * @phpstan-type ViewVariables array<string, mixed>
 *
 * @phpstan-type ViewPointer non-empty-string
 */
class View implements \Minz\Output
{
    /** @var array<string, string> */
    public static array $extensions_to_content_types = [
        '.html' => 'text/html',
        '.json' => 'application/json',
        '.phtml' => 'text/html',
        '.txt' => 'text/plain',
        '.xml' => 'text/xml',
    ];

    private string $filepath;

    private string $content_type;

    /** @var ViewPointer */
    private string $pointer;

    private ?string $layout_name = null;

    /** @var ViewVariables */
    private array $variables;

    /** @var ViewVariables */
    private array $layout_variables;

    /** @var ViewVariables */
    private static array $default_variables = [];

    /**
     * Declare default variables so they can be used without passing them
     * explicitely when creating a View.
     *
     * This is usually called in the Application class, before running the
     * Engine.
     *
     * @param ViewVariables $variables
     */
    public static function declareDefaultVariables(array $variables): void
    {
        self::$default_variables = $variables;
    }

    /**
     * @return ViewVariables
     */
    public static function defaultVariables(): array
    {
        return self::$default_variables;
    }

    /**
     * @param ViewPointer $pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError if the pointed file doesn't exist
     */
    public function __construct(string $pointer, array $variables = [])
    {
        $this->setContentType($pointer);
        $this->setFilepath($pointer);
        $this->pointer = $pointer;
        $this->variables = $variables;
    }

    /**
     * @return ViewPointer
     */
    public function pointer(): string
    {
        return $this->pointer;
    }

    public function filepath(): string
    {
        return $this->filepath;
    }

    /**
     * @throws \Minz\Errors\OutputError if the pointed file doesn't exist
     */
    public function setFilepath(string $pointer): void
    {
        $app_path = Configuration::$app_path;
        $filepath = "{$app_path}/src/views/{$pointer}";
        if (!file_exists($filepath)) {
            $missing_file = "src/views/{$pointer}";
            throw new Errors\OutputError("{$missing_file} file cannot be found.");
        }

        $this->filepath = $filepath;
    }

    public function contentType(): string
    {
        return $this->content_type;
    }

    /**
     * @param ViewPointer $pointer
     */
    public function setContentType(string $pointer): void
    {
        $this->content_type = 'text/html';

        foreach (self::$extensions_to_content_types as $ext => $content_type) {
            $ends_with_extension = str_ends_with($pointer, $ext);
            if ($ends_with_extension) {
                $this->content_type = $content_type;
            }
        }
    }

    /**
     * @return ViewVariables
     */
    public function variables(): array
    {
        return $this->variables;
    }

    /**
     * Generate and return the content.
     *
     * Variables are passed and accessible in the view file.
     */
    public function render(): string
    {
        $variables = array_merge(self::$default_variables, $this->variables);
        foreach ($variables as $var_name => $var_value) {
            if (is_string($var_value)) {
                $var_value = $this->protect($var_value);
            }
            $$var_name = $var_value;
        }

        ob_start();
        include $this->filepath;
        $output = ob_get_clean();

        if ($this->layout_name) {
            $layout_pointer = "_layouts/{$this->layout_name}";
            $this->layout_variables['content'] = $output;
            $view = new View($layout_pointer, $this->layout_variables);
            $output = $view->render();
        }

        if ($output) {
            return $output;
        } else {
            return '';
        }
    }

    /**
     * Allow to set a layout to the view.
     *
     * It must be called from within the view file directly.
     *
     * The layout should render the calling view by displaying the special
     * `$content` variable.
     *
     * @param ViewVariables $layout_variables
     *
     * @throws \Minz\Errors\OutputError if the layout file doesn't exist
     * @throws \Minz\Errors\OutputError if the layout variables aren't an array
     */
    protected function layout(string $layout_name, array $layout_variables = []): void
    {
        $layout_filepath = self::layoutFilepath($layout_name);
        if (!file_exists($layout_filepath)) {
            throw new Errors\OutputError(
                "{$layout_name} layout file does not exist."
            );
        }

        $this->layout_name = $layout_name;
        $this->layout_variables = $layout_variables;
    }

    /**
     * Render a subview inside current view
     *
     * It must be called from within the view file directly.
     *
     * @param ViewPointer $pointer
     * @param ViewVariables $variables
     *
     * @throws \Minz\Errors\OutputError if the included pointer file doesn't exist
     */
    protected function include(string $pointer, array $variables = []): string
    {
        $view = new View($pointer, $variables);
        return $view->render();
    }

    /**
     * Return the value of a variable without escaping its content.
     *
     * @throws \Minz\Errors\OutputError if the variable doesn't exist
     */
    protected function safe(string $variable_name): mixed
    {
        $variables = array_merge(self::$default_variables, $this->variables);
        if (!array_key_exists($variable_name, $variables)) {
            throw new Errors\OutputError("{$variable_name} variable does not exist.");
        }

        return $variables[$variable_name];
    }

    /**
     * Return a variable by escaping its value.
     *
     * This is normally done for the string variables, but you might want to
     * output an object attribute, which is not protected.
     *
     * @see https://www.php.net/manual/function.htmlspecialchars.php
     */
    protected function protect(string $variable): string
    {
        return htmlspecialchars($variable, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Helper to find the path to a layout file
     */
    private static function layoutFilepath(string $layout_name): string
    {
        $app_path = Configuration::$app_path;
        return "{$app_path}/src/views/_layouts/{$layout_name}";
    }
}
