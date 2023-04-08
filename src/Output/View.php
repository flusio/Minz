<?php

namespace Minz\Output;

use Minz\Configuration;
use Minz\Errors;

/**
 * A View represents the (string) content to deliver to users.
 *
 * It is represented by a file under src/views. The view file is called
 * "pointer".
 *
 * @phpstan-type ViewVariables array<string, mixed>
 *
 * @phpstan-type ViewPointer non-empty-string
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class View implements Output
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
    private static $default_variables = [];

    /**
     * Declare default variables so they can be used without passing them
     * explicitely when creating a View.
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
            $ext_length = strlen($ext);
            $ends_with_extension = substr($pointer, -$ext_length, $ext_length) === $ext;
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

        include_once('view_helpers.php');

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

        if (!is_array($layout_variables)) {
            throw new Errors\OutputError(
                "Layout variables parameter must be an array."
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
