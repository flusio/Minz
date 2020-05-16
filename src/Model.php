<?php

namespace Minz;

/**
 * A Model allows to declare a model with its properties.
 *
 * A property is data destined to be stored in the database. It has at least a
 * type and can be required and validated.
 *
 * The model can be exported to that database with the `toValues` method, and
 * imported with `fromValues`. Model class should be inherited and good
 * practices imply to create a constructor that declares the properties and
 * loads values. For example:
 *
 *     public function __construct($values)
 *     {
 *         parent::__construct(self::PROPERTIES);
 *         $this->fromValues($values);
 *     }
 *
 * If you want a constuctor accepting specific properties, you can declare a
 * static method:
 *
 *     public static function new($name)
 *     {
 *         return new MyModel([
 *             'name' => strip($name),
 *             'status' => 'new,
 *         ]);
 *     }
 *
 * This allows to load easily a model from database which is more common than
 * initializing a new model.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Model
{
    public const VALID_PROPERTY_TYPES = ['string', 'integer', 'datetime', 'boolean'];

    public const DEFAULT_PROPERTY_DECLARATION = [
        'type' => null,
        'required' => false,
        'validator' => null,
    ];

    // This is almost ISO 8601 format, only the middle T is replaced by a space
    // to match with the output format of pgsql.
    // @see https://www.postgresql.org/docs/current/datatype-datetime.html#DATATYPE-DATETIME-OUTPUT
    public const DATETIME_FORMAT = 'Y-m-d H:i:sP';

    public const ERROR_REQUIRED = 'required';
    public const ERROR_VALUE_TYPE_INVALID = 'value_type_invalid';
    public const ERROR_VALUE_INVALID = 'value_invalid';
    public const ERROR_PROPERTY_UNDECLARED = 'property_undeclared';
    public const ERROR_PROPERTY_TYPE_INVALID = 'property_type_invalid';
    public const ERROR_PROPERTY_VALIDATOR_INVALID = 'property_validator_invalid';

    /** @var array */
    protected $property_declarations;

    /**
     * Declare properties of the model.
     *
     * A declaration is an array where keys are property names, and the values
     * their declarations. A declaration can be a simple string defining a type
     * (string, integer, datetime or boolean), or an array with a required
     * `type` key and optional `required` and `validator` keys. For example:
     *
     *     [
     *         'id' => 'integer',
     *
     *         'name' => [
     *             'type' => 'string',
     *             'required' => true,
     *         ],
     *
     *         'status' => [
     *             'type' => 'string',
     *             'required' => true,
     *             'validator' => function ($status) {
     *                 return in_array($status, ['new', 'finished']);
     *             },
     *         ],
     *     ]
     *
     * The resulting model is initialized with its properties declared and set
     * to `null`. It must be loaded then with the `fromValues` method.
     *
     * A validator must return true if the value is correct, or false
     * otherwise. It also can return a string to detail the reason of the
     * error.
     *
     * @param array $property_declarations
     *
     * @throws \Minz\Errors\ModelPropertyError if type is invalid
     * @throws \Minz\Errors\ModelPropertyError if validator is declared but cannot
     *                                         be called
     */
    public function __construct($property_declarations = [])
    {
        $validated_property_declarations = [];

        foreach ($property_declarations as $property => $declaration) {
            if (!is_array($declaration)) {
                $declaration = ['type' => $declaration];
            }

            $declaration = array_merge(
                self::DEFAULT_PROPERTY_DECLARATION,
                $declaration
            );

            if (!in_array($declaration['type'], self::VALID_PROPERTY_TYPES)) {
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_PROPERTY_TYPE_INVALID,
                    "`{$declaration['type']}` is not a valid property type."
                );
            }

            if (
                $declaration['validator'] !== null &&
                !is_callable($declaration['validator'])
            ) {
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_PROPERTY_VALIDATOR_INVALID,
                    "`{$declaration['validator']}` validator cannot be called."
                );
            }

            $validated_property_declarations[$property] = $declaration;
            $this->$property = null;
        }

        $this->property_declarations = $validated_property_declarations;
    }

    /**
     * @return array
     */
    public function propertyDeclarations()
    {
        return $this->property_declarations;
    }

    /**
     * Return the list of declared properties values.
     *
     * Note that datetime are converted to (almost) iso 8601, see DATETIME_FORMAT.
     *
     * @return array
     */
    public function toValues()
    {
        $values = [];
        foreach ($this->property_declarations as $property => $declaration) {
            if ($declaration['type'] === 'datetime' && $this->$property) {
                $values[$property] = $this->$property->format(self::DATETIME_FORMAT);
            } else {
                $values[$property] = $this->$property;
            }
        }
        return $values;
    }

    /**
     * Load the properties values to the model.
     *
     * The array can contain strings, the values are automatically casted to
     * the correct type, based on the properties declarations.
     *
     * @param array $values
     *
     * @throws \Minz\Errors\ModelPropertyError if property is not declared
     */
    public function fromValues($values)
    {
        foreach ($values as $property => $value) {
            if (!isset($this->property_declarations[$property])) {
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_PROPERTY_UNDECLARED,
                    "`{$property}` property has not been declared."
                );
            }

            if ($value !== null) {
                $declaration = $this->property_declarations[$property];
                $type = $declaration['type'];

                if ($type === 'integer' && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    $value = intval($value);
                } elseif ($type === 'datetime' && is_string($value)) {
                    $date = date_create_from_format(self::DATETIME_FORMAT, $value);
                    if ($date !== false) {
                        $value = $date;
                    }
                } elseif (
                    $type === 'boolean' &&
                    filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null
                ) {
                    $value = $value === 'true';
                }
            }

            $this->$property = $value;
        }
    }

    /**
     * Load a specific property value to the model.
     *
     * Note that values are NOT casted so you have to make sure to use the
     * correct type.
     *
     * @param array $values
     *
     * @throws \Minz\Errors\ModelPropertyError if property is not declared
     * @throws \Minz\Errors\ModelPropertyError if required property is null
     * @throws \Minz\Errors\ModelPropertyError if validator returns false or a
     *                                         custom message
     */
    public function setProperty($property, $value)
    {
        if (!isset($this->property_declarations[$property])) {
            throw new Errors\ModelPropertyError(
                $property,
                self::ERROR_PROPERTY_UNDECLARED,
                "`{$property}` property has not been declared."
            );
        }

        $declaration = $this->property_declarations[$property];

        if (
            $declaration['required'] && (
                !isset($value) ||
                ($declaration['type'] === 'string' && empty($value))
            )
        ) {
            throw new Errors\ModelPropertyError(
                $property,
                self::ERROR_REQUIRED,
                "Required `{$property}` property is missing."
            );
        }

        if ($value !== null && $declaration['validator']) {
            $validator_result = $declaration['validator']($value);

            if ($validator_result !== true) {
                $custom_message = '';
                if ($validator_result !== false) {
                    $custom_message = ': ' . $validator_result;
                }
                $error_message = "`{$property}` property is invalid ({$value}){$custom_message}.";
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_VALUE_INVALID,
                    $error_message
                );
            }
        }

        $this->$property = $value;
    }

    /**
     * Return the errors of the model by checking its values against the declarations.
     *
     * The array is empty if there are no errors.
     *
     * Otherwise, the array is indexed by the properties names and the values
     * are arrays with a `code` (the \Minz\Model::ERROR_* constants) and a
     * `description`.
     *
     * @return array
     */
    public function validate()
    {
        $errors = [];
        foreach ($this->property_declarations as $property => $declaration) {
            $type = $declaration['type'];
            $value = $this->$property;

            $is_empty = !isset($value) || ($type === 'string' && empty($value));
            if ($declaration['required'] && $is_empty) {
                $errors[$property] = [
                    'code' => self::ERROR_REQUIRED,
                    'description' => "Required `{$property}` property is missing.",
                ];
                continue;
            }

            if ($value !== null) {
                if ($type === 'integer' && !is_int($value)) {
                    $errors[$property] = [
                        'code' => self::ERROR_VALUE_TYPE_INVALID,
                        'description' => "`{$property}` property must be an integer.",
                    ];
                    continue;
                }

                if ($type === 'datetime' && !($value instanceof \DateTime)) {
                    $errors[$property] = [
                        'code' => self::ERROR_VALUE_TYPE_INVALID,
                        'description' => "`{$property}` property must be a \DateTime.",
                    ];
                    continue;
                }

                if ($type === 'boolean' && !is_bool($value)) {
                    $errors[$property] = [
                        'code' => self::ERROR_VALUE_TYPE_INVALID,
                        'description' => "`{$property}` property must be a boolean.",
                    ];
                    continue;
                }

                if ($declaration['validator']) {
                    $validator_result = $declaration['validator']($value);

                    if ($validator_result !== true) {
                        if ($validator_result === false) {
                            $error_message = "`{$property}` property is invalid ({$value}).";
                        } else {
                            $error_message = $validator_result;
                        }

                        $errors[$property] = [
                            'code' => self::ERROR_VALUE_INVALID,
                            'description' => $error_message,
                        ];
                        continue;
                    }
                }
            }
        }

        return $errors;
    }
}
