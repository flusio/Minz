<?php

namespace Minz;

/**
 * A Model allows to declare a model with its properties.
 *
 * A property is generally data destined to be stored in the database. It has
 * at least a type and can be required and validated.
 *
 * The model can be exported to that database with the `toValues` method, and
 * imported with `fromValues` (or via the constructor). Model class should be
 * inherited.
 *
 * Since a model is most often loaded from the database, good practice is to
 * create a model instance and load the data via the constructor:
 *
 *     $db_model = $dao->find($id);
 *     $my_model = new MyModel($db_model);
 *
 * If you want a constuctor accepting specific properties, you can declare a
 * static method:
 *
 *     public static function init($name)
 *     {
 *         return new MyModel([
 *             'name' => strip($name),
 *             'status' => 'new,
 *         ]);
 *     }
 *
 * A Model should declare a PROPERTIES public const. A declaration is an array
 * where keys are property names, and the values their declarations. A
 * declaration can be a simple string defining a type (string, integer,
 * datetime or boolean), or an array with a required `type` key and optional
 * `required`, `validator` and `format` keys. For example:
 *
 *     [
 *         'id' => 'integer',
 *
 *         'created_at' => [
 *             'type' => 'datetime',
 *             'format' => 'U',
 *         ]
 *
 *         'name' => [
 *             'type' => 'string',
 *             'required' => true,
 *         ],
 *
 *         'status' => [
 *             'type' => 'string',
 *             'required' => true,
 *             'validator' => '\MyApp\models\MyModel::validateStatus',
 *         ],
 *     ]
 *
 * The format option can only be used on datetime properties and handles the
 * format in database. It's set to Model::DATETIME_FORMAT by default.
 *
 * A validator must return true if the value is correct, or false otherwise. It
 * also can return a string to detail the reason of the error.
 *
 * A model can be validated then:
 *
 *     $errors = $my_model->validate();
 *
 * Finally, a property can be computed from the database (e.g. to count related
 * items for instance), in which case we might want the `fromValues()` method
 * to load the property in the model, but `toValues()` must NOT export it
 * because this would fail (i.e. the column doesn't exist). Such a property can
 * be declared as the following:
 *
 *     'count_comments' => [
 *         'type' => 'integer',
 *         'computed' => true,
 *     ]
 *
 * @phpstan-type PropertyDeclaration array{
 *     'type': value-of<Model::VALID_PROPERTY_TYPES>,
 *     'required': bool,
 *     'validator': ?callable,
 *     'computed': bool,
 *     'format'?: string,
 * }
 *
 * @phpstan-type PropertiesDeclarations array<string, PropertyDeclaration>
 *
 * @phpstan-type ModelValues array<string, mixed>
 *
 * @phpstan-type ModelId integer|string
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Model
{
    public const VALID_PROPERTY_TYPES = ['string', 'integer', 'datetime', 'boolean'];

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

    /**
     * Cache the property declarations of models
     *
     * @var array<string, PropertiesDeclarations>
     */
    protected static array $property_declarations = [];

    /**
     * Return the complete property declarations for a model.
     *
     * @return PropertiesDeclarations
     */
    public static function propertyDeclarations(string $model_class_name): array
    {
        if (isset(self::$property_declarations[$model_class_name])) {
            return self::$property_declarations[$model_class_name];
        } else {
            return [];
        }
    }

    /**
     * Declare properties of the given model.
     *
     * The $properties var can be incomplete (a string only, or without the
     * required or validator options). This method checks the properties are
     * valid and cache the complete declarations.
     *
     * @param array<string, mixed> $properties
     *
     * @throws \Minz\Errors\ModelPropertyError
     *     If a type is invalid
     * @throws \Minz\Errors\ModelPropertyError
     *     If validator is declared but cannot be called
     **/
    public static function declareProperties(string $model_class_name, array $properties): void
    {
        self::$property_declarations[$model_class_name] = [];
        foreach ($properties as $property => $declaration) {
            if (!is_array($declaration)) {
                $declaration = ['type' => $declaration];
            }

            if (!in_array($declaration['type'], self::VALID_PROPERTY_TYPES)) {
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_PROPERTY_TYPE_INVALID,
                    "`{$declaration['type']}` is not a valid property type."
                );
            }

            if (
                isset($declaration['validator']) &&
                !is_callable($declaration['validator'])
            ) {
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_PROPERTY_VALIDATOR_INVALID,
                    "`{$declaration['validator']}` validator cannot be called."
                );
            }

            if (
                $declaration['type'] === 'datetime' &&
                !isset($declaration['format'])
            ) {
                $declaration['format'] = self::DATETIME_FORMAT;
            }

            $clean_declaration = [
                'type' => $declaration['type'],
                'required' => $declaration['required'] ?? false,
                'validator' => $declaration['validator'] ?? null,
                'computed' => $declaration['computed'] ?? false,
            ];

            if (isset($declaration['format'])) {
                $clean_declaration['format'] = $declaration['format'];
            }

            self::$property_declarations[$model_class_name][$property] = $clean_declaration;
        }
    }

    /**
     * Initialize a Model and set the values.
     *
     * @param ModelValues $values
     */
    public function __construct($values = [])
    {
        $model_class_name = get_called_class();
        $already_declared = isset(self::$property_declarations[$model_class_name]);
        if (!$already_declared && defined('static::PROPERTIES')) {
            self::declareProperties($model_class_name, static::PROPERTIES);
        }

        foreach (self::propertyDeclarations(get_called_class()) as $property => $declaration) {
            $this->$property = null;
        }

        if ($values) {
            $this->fromValues($values);
        }
    }

    /**
     * Return the list of declared properties values.
     *
     * Note that datetime are converted to (almost) iso 8601 by default, see
     * DATETIME_FORMAT. It can be changed by providing a `format` option.
     *
     * @return ModelValues
     */
    public function toValues(): array
    {
        $values = [];
        foreach (self::propertyDeclarations(get_called_class()) as $property => $declaration) {
            if ($declaration['computed']) {
                continue;
            }

            if ($declaration['type'] === 'datetime' && $this->$property && isset($declaration['format'])) {
                $values[$property] = $this->$property->format($declaration['format']);
            } elseif ($declaration['type'] === 'boolean' && $this->$property !== null) {
                $values[$property] = (int)$this->$property;
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
     * @param ModelValues $values
     *
     * @throws \Minz\Errors\ModelPropertyError if property is not declared
     */
    public function fromValues(array $values): void
    {
        $property_declarations = self::propertyDeclarations(get_called_class());
        foreach ($values as $property => $value) {
            if (!isset($property_declarations[$property])) {
                throw new Errors\ModelPropertyError(
                    $property,
                    self::ERROR_PROPERTY_UNDECLARED,
                    "`{$property}` property has not been declared."
                );
            }

            if ($value !== null) {
                $declaration = $property_declarations[$property];
                $type = $declaration['type'];

                if ($type === 'integer' && filter_var($value, FILTER_VALIDATE_INT) !== false) {
                    $value = filter_var($value, FILTER_VALIDATE_INT);
                } elseif ($type === 'datetime' && is_string($value) && isset($declaration['format'])) {
                    $date = date_create_from_format($declaration['format'], $value);
                    if ($date !== false) {
                        $value = $date;
                    }
                } elseif (
                    $type === 'boolean' &&
                    filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null
                ) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
            }

            $this->$property = $value;
        }
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
     * @return array<string, array{
     *     'code': self::ERROR_*,
     *     'description': string,
     * }>
     */
    public function validate(): array
    {
        $errors = [];
        foreach (self::propertyDeclarations(get_called_class()) as $property => $declaration) {
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

            if (!$is_empty) {
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
