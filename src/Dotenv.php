<?php

namespace Minz;

/**
 * The Dotenv class allows to load variables from a file, generally named `.env`
 * It is usually used to load the production configuration.
 *
 * It doesn't load the variables to any `getenv()`, `$_ENV` or `$_SERVER`.
 *
 * Also, the only method to get the values is `pop`, which deletes the variable
 * from the Dotenv object. This is done to avoid to duplicate critical secrets
 * in too many variables.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Dotenv
{
    /** @var array */
    private $variables = [];

    /**
     * @param string $dotenv_path
     *
     * @throws \Minz\Errors\DotenvError if the path doesn't exist or cannot be read
     */
    public function __construct($dotenv_path)
    {
        $dotenv_content = @file_get_contents($dotenv_path);
        if ($dotenv_content === false) {
            throw new Errors\DotenvError("{$dotenv_path} cannot be read.");
        }

        $dotenv_lines = preg_split("/\r\n|\n|\r/", $dotenv_content);
        foreach ($dotenv_lines as $line) {
            if (!trim($line)) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list(
                    $name,
                    $value
                ) = array_map('trim', explode('=', $line, 2));
            } else {
                $name = $line;
                $value = '';
            }

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
     * @param string $name
     * @param string|null $default The default value to return if variable doesn't
     *                             exist (default is null)
     *
     * @return string
     */
    public function pop($name, $default = null)
    {
        if (isset($this->variables[$name])) {
            $value = $this->variables[$name];
            unset($this->variables[$name]);
            return $value;
        } else {
            return $default;
        }
    }
}
