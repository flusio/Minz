<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Template;

/**
 * A simple templating system based on PHP tags (i.e. <?= and <?php).
 *
 * The templates must be placed under `src/views/`.
 *
 * The template files contain most of the time some templated HTML content (but
 * can be used to generate other kinds of content):
 *
 * ```php
 * <p>
 *     Hello <?= $foo ?>!
 * </p>
 * ```
 *
 * The string variables are automatically escaped so it should be safe to
 * display them directly. Be careful if you want to display an object property
 * though:
 *
 * ```php
 * <p>
 *     Hello <?= $this->protect($user->username) ?>!
 * </p>
 * ```
 *
 * The templates have access to different methods defined in this file (see
 * below for their full documentation):
 *
 * ```php
 * $this->layout('path/to/layout.phtml', ['foo' => $foo]);
 * $this->include('path/to/partial.phtml', ['foo', => $foo]);
 * $this->safe('foo');
 * $this->protect($foo);
 * ```
 *
 * The templates also have access to some helper functions defined in the file
 * simple_template_helpers.php.
 *
 * @phpstan-import-type TemplateName from TemplateInterface
 * @phpstan-import-type TemplateContext from TemplateInterface
 */
class Simple implements TemplateInterface
{
    private string $filepath;

    /** @var TemplateName */
    private string $name;

    /** @var ?TemplateName */
    private ?string $layout_name = null;

    /** @var TemplateContext */
    private array $context;

    /** @var TemplateContext */
    private array $layout_context;

    /** @var TemplateContext */
    private static array $default_context = [];

    /**
     * @param TemplateName $name
     * @param TemplateContext $context
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the template file doesn't exist
     */
    public function __construct(string $name, array $context = [])
    {
        $this->setFilepath($name);
        $this->name = $name;
        $this->context = $context;
    }

    /**
     * @return TemplateName
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return TemplateContext
     */
    public function context(): array
    {
        return $this->context;
    }

    public function filepath(): string
    {
        return $this->filepath;
    }

    /**
     * @param TemplateName $name
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the file doesn't exist.
     */
    public function setFilepath(string $name): void
    {
        $app_path = \Minz\Configuration::$app_path;
        $filepath = "{$app_path}/src/views/{$name}";
        if (!file_exists($filepath)) {
            $missing_file = "src/views/{$name}";
            throw new \Minz\Errors\OutputError("{$missing_file} file cannot be found.");
        }

        $this->filepath = $filepath;
    }

    /**
     * Generate and return the content.
     *
     * Context is passed to the template file by setting local variables.
     */
    public function render(): string
    {
        $context = array_merge(self::$default_context, $this->context);
        foreach ($context as $var_name => $var_value) {
            if (is_string($var_value)) {
                $var_value = $this->protect($var_value);
            }
            $$var_name = $var_value;
        }

        ob_start();
        include $this->filepath;
        $content = ob_get_clean();

        if ($this->layout_name) {
            $layout_name = "_layouts/{$this->layout_name}";
            $this->layout_context['content'] = $content;
            $template = new self($layout_name, $this->layout_context);
            $content = $template->render();
        }

        if ($content) {
            return $content;
        } else {
            return '';
        }
    }

    /**
     * Allow to set a layout to the template.
     *
     * It must be called from within the template file directly.
     *
     * The layout should render the calling template by displaying the special
     * `$content` variable.
     *
     * @param TemplateName $layout_name
     * @param TemplateContext $layout_context
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the layout file doesn't exist.
     */
    protected function layout(string $layout_name, array $layout_context = []): void
    {
        $app_path = \Minz\Configuration::$app_path;
        $layout_filepath = "{$app_path}/src/views/_layouts/{$layout_name}";

        if (!file_exists($layout_filepath)) {
            throw new \Minz\Errors\OutputError(
                "{$layout_name} layout file does not exist."
            );
        }

        $this->layout_name = $layout_name;
        $this->layout_context = $layout_context;
    }

    /**
     * Render a subtemplate inside the current template.
     *
     * It must be called from within the template file directly.
     *
     * @param TemplateName $name
     * @param TemplateContext $context
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the included name file doesn't exist.
     */
    protected function include(string $name, array $context = []): string
    {
        $template = new self($name, $context);
        return $template->render();
    }

    /**
     * Return the value of a variable without escaping its content.
     *
     * @throws \Minz\Errors\OutputError
     *     Raised if the variable doesn't exist.
     */
    protected function safe(string $variable_name): mixed
    {
        $context = array_merge(self::$default_context, $this->context);

        if (!array_key_exists($variable_name, $context)) {
            throw new \Minz\Errors\OutputError("{$variable_name} variable does not exist.");
        }

        return $context[$variable_name];
    }

    /**
     * Return a variable by escaping its value.
     *
     * This is normally done for the string variables, but you might want to
     * output object properties, which are not protected.
     *
     * @see https://www.php.net/manual/function.htmlspecialchars.php
     */
    protected function protect(string $variable): string
    {
        return htmlspecialchars($variable, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Declare default context so the variables can be used without passing
     * them explicitely when creating a template.
     *
     * This is usually called in the Application class, or in a
     * Controller\BeforeAction handler.
     *
     * @param TemplateContext $context
     */
    public static function addGlobals(array $context): void
    {
        foreach ($context as $var_name => $var_value) {
            self::$default_context[$var_name] = $var_value;
        }
    }

    /**
     * @return TemplateContext
     */
    public static function defaultContext(): array
    {
        return self::$default_context;
    }

    /**
     * Reset the default context
     */
    public static function reset(): void
    {
        self::$default_context = [];
    }
}
