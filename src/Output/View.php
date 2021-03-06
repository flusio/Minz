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
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class View implements Output
{
    /** @var string[] */
    public const EXTENSION_TO_CONTENT_TYPE = [
        'html' => 'text/html',
        'json' => 'application/json',
        'phtml' => 'text/html',
        'txt' => 'text/plain',
        'xml' => 'text/xml',
    ];

    /** @var string */
    private $filepath;

    /** @var string */
    private $content_type;

    /** @var string */
    private $pointer;

    /** @var mixed[] */
    private $variables;

    /** @var string|null */
    private $layout_name;

    /** @var mixed[] */
    private $layout_variables;

    /** @var mixed[] */
    private static $default_variables = [];

    /**
     * Declare default variables so they can be used without passing them
     * explicitely when creating a View.
     *
     * @param mixed[] $variables
     */
    public static function declareDefaultVariables($variables)
    {
        self::$default_variables = $variables;
    }

    /**
     * @return mixed[] $variables
     */
    public static function defaultVariables()
    {
        return self::$default_variables;
    }

    /**
     * @param string $pointer
     * @param mixed[] $variables (optional)
     *
     * @throws \Minz\Errors\OutputError if the pointed file extension is not
     *                                  associated to a supported one
     * @throws \Minz\Errors\OutputError if the pointed file doesn't exist
     */
    public function __construct($pointer, $variables = [])
    {
        $this->setContentType($pointer);
        $this->setFilepath($pointer);
        $this->pointer = $pointer;
        $this->variables = $variables;
    }

    /**
     * @return string
     */
    public function pointer()
    {
        return $this->pointer;
    }

    /**
     * @return string
     */
    public function filepath()
    {
        return $this->filepath;
    }

    /**
     * @param string $pointer
     *
     * @throws \Minz\Errors\OutputError if the pointed file doesn't exist
     */
    public function setFilepath($pointer)
    {
        $app_path = Configuration::$app_path;
        $filepath = "{$app_path}/src/views/{$pointer}";
        if (!file_exists($filepath)) {
            $missing_file = "src/views/{$pointer}";
            throw new Errors\OutputError("{$missing_file} file cannot be found.");
        }

        $this->filepath = $filepath;
    }

    /**
     * @return string
     */
    public function contentType()
    {
        return $this->content_type;
    }

    /**
     * @param string $pointer
     *
     * @throws \Minz\Errors\OutputError if the pointed file extension is not
     *                                  associated to a supported one
     */
    public function setContentType($pointer)
    {
        $file_extension = pathinfo($pointer, PATHINFO_EXTENSION);
        if (!isset(self::EXTENSION_TO_CONTENT_TYPE[$file_extension])) {
            throw new Errors\OutputError(
                "{$file_extension} is not a supported view file extension."
            );
        }
        $this->content_type = self::EXTENSION_TO_CONTENT_TYPE[$file_extension];
    }

    /**
     * @return mixed[]
     */
    public function variables()
    {
        return $this->variables;
    }

    /**
     * Generate and return the content.
     *
     * Variables are passed and accessible in the view file.
     *
     * @return string The content generated by the view file
     */
    public function render()
    {
        $variables = array_merge(self::$default_variables, $this->variables);
        foreach ($variables as $var_name => $var_value) {
            if (is_string($var_value)) {
                $var_value = $this->protect($var_value);
            }
            $$var_name = $var_value;
        }

        include_once('ViewHelpers.php');

        ob_start();
        include $this->filepath;
        $output = ob_get_clean();

        if ($this->layout_name) {
            $layout_pointer = "_layouts/{$this->layout_name}";
            $this->layout_variables['content'] = $output;
            $view = new View($layout_pointer, $this->layout_variables);
            $output = $view->render();
        }

        return $output;
    }

    /**
     * Allow to set a layout to the view.
     *
     * It must be called from within the view file directly.
     *
     * @param string $layout_name The name of the file under src/views/_layouts/
     * @param mixed[] $layout_variables A list of variables to pass to the layout
     *
     * @throws \Minz\Errors\OutputError if the layout file doesn't exist
     * @throws \Minz\Errors\OutputError if the layout variables aren't an array
     */
    private function layout($layout_name, $layout_variables = [])
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
     * @param string $name The name of the file under src/views/
     * @param mixed[] $variables A list of variables to pass to the included view
     *
     * @throws \Minz\Errors\OutputError if the included pointer file doesn't exist
     */
    private function include($pointer, $variables)
    {
        $view = new View($pointer, $variables);
        return $view->render();
    }

    /**
     * Return the value of a variable without escaping its content.
     *
     * @param string $variable_name
     *
     * @throws \Minz\Errors\OutputError if the variable doesn't exist
     *
     * @return mixed
     */
    private function safe($variable_name)
    {
        $variables = array_merge(self::$default_variables, $this->variables);
        if (!isset($variables[$variable_name])) {
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
     *
     * @param string $variable
     *
     * @return string
     */
    private function protect($variable)
    {
        return htmlspecialchars($variable, ENT_COMPAT, 'UTF-8');
    }

    /**
     * Helper to find the path to a layout file
     *
     * @param string $layout_name
     *
     * @return string
     */
    private static function layoutFilepath($layout_name)
    {
        $app_path = Configuration::$app_path;
        return "{$app_path}/src/views/_layouts/{$layout_name}";
    }
}
