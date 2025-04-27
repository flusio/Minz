<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * The Validable trait allows to validate an object.
 *
 * It provides a `validate()` method to the classes that use it. To work, the
 * class must also apply some `Validable\PropertyCheck` attributes on its
 * properties, or `Validable\Check` on methods.
 *
 * This trait is often used on models and on forms (the `\Minz\Form` class
 * uses it by default).
 *
 * For instance, a User class could check the validity of its nickname property:
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Presence(message: 'Choose a nickname.')]
 *         #[Validable\Length(max: 42, message: 'Choose a nickname of less than {max} characters.')]
 *         #[Validable\Format(pattern: '/^[\w]*$/', message: 'Choose a nickname that only contains letters.')]
 *         public string $nickname;
 *     }
 *
 * Then, call the `validate()` method to check the validity. It will populate
 * the `$this->errors` property.
 *
 *     $user = new User();
 *     $user->nickname = 'invalid nickname';
 *
 *     // Return true if there is no error
 *     $is_valid = $user->validate();
 *
 *     // Tell if the user is invalid (e.g. has at least one error)
 *     $is_user_invalid = $user->isInvalid();
 *
 *     // Tell if the nickname is invalid
 *     $is_nickname_invalid = $user->isInvalid('nickname');
 *
 *     // Get the full list of errors with their error codes
 *     $errors = $user->errors(format: false);
 *
 *     // Get a formatted error message if any
 *     $nickname_error = $user->error('nickname');
 *
 * The `Validable\PropertyCheck` attributes are applied on properties and will
 * check their values. You cannot directly apply the PropertyCheck attribute,
 * you must use one of its children classes. You can also declare your own
 * property checks by extending this class.
 *
 * The `Validable\Check` attribute works a bit differently as it can be applied
 * on class methods. These methods must be public, must not take parameters and
 * should return nothing. They must use the `addError` method to declares
 * errors.
 *
 *     use App\models;
 *     use Minz\Form;
 *     use Minz\Validable;
 *
 *     class LoginForm extends Form
 *     {
 *         // You don't have to call `use Validable` as the Form class already
 *         // does that.
 *
 *         #[Form\Field]
 *         #[Validable\Presence(message: 'Enter a nickname.')]
 *         public string $username = '';
 *
 *         #[Form\Field]
 *         #[Validable\Presence(message: 'Enter a password.')]
 *         public string $password = '';
 *
 *         #[Validable\Check]
 *         public function checkCredentials(): void
 *         {
 *             $user = models\User::findBy(['username' => $this->username]);
 *
 *             if (!$user || !$user->verifyPassword($this->password)) {
 *                 $this->addError('@base', 'invalid_credentials', 'Credentials are invalid.');
 *             }
 *         }
 *     }
 *
 * @see \Minz\Validable\PropertyCheck
 * @see \Minz\Validable\Check
 * @see \Minz\Form
 *
 * @phpstan-type ValidableError array{string, string}
 */
trait Validable
{
    /** @var array<string, ValidableError[]> */
    protected array $errors = [];

    /**
     * Run all the checks and return true if the object is valid.
     */
    public function validate(): bool
    {
        // Create a ReflectionClass to find all the checkable properties and
        // methods.
        $class_reflection = new \ReflectionClass(static::class);
        $properties = $class_reflection->getProperties();
        $methods = $class_reflection->getMethods();

        // First, we iterate on all the properties
        foreach ($properties as $property) {
            $property_check_attributes = $property->getAttributes(
                Validable\PropertyCheck::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );

            if (empty($property_check_attributes)) {
                // No PropertyCheck? The property is not validable
                continue;
            }

            $field = $property->getName();

            // Then, we iterate on all the PropertyCheck attributes
            foreach ($property_check_attributes as $reflection_attribute) {
                $attribute = $reflection_attribute->newInstance();

                // The PropertyCheck needs some information to work
                $attribute->instance = $this;
                $attribute->property = $property;

                if (!$attribute->assert()) {
                    // If the assert fails, we register the error.
                    $error_code = $attribute->code();
                    $error_message = $attribute->message();
                    $this->addError($field, $error_code, $error_message);
                }
            }
        }

        // Then, we iterate on the methods declared with the Check attribute
        // and we call them.
        foreach ($methods as $method) {
            $check_attributes = $method->getAttributes(Validable\Check::class);
            if (empty($check_attributes)) {
                continue;
            }

            $method->invoke($this);
        }

        // Finally, return wether the object is valid or not.
        return !$this->isInvalid();
    }

    /**
     * Return whether the object (default) or the given field is invalid.
     *
     * The object is invalid if any error is declared. A field is invalid if
     * the key exists in the errors list.
     */
    public function isInvalid(string $field = '@any'): bool
    {
        if ($field === '@any') {
            return !empty($this->errors);
        } else {
            return isset($this->errors[$field]);
        }
    }

    /**
     * Return the full list of errors.
     *
     * The array keys are the field names (or the special `@base` string
     * meaning that the errors concern the object in its globality).
     *
     * The values can either be:
     *
     * - if format is true: a string containing the list of errors concerning
     *   the field.
     * - if format is false: an array of errors, where each error is an array
     *   with two elements (the error code and the error message).
     *
     * @return array<string, ($format is true ? string : ValidableError[])>
     */
    public function errors(bool $format = true): array
    {
        if (!$format) {
            return $this->errors;
        }

        $formatted_errors = [];

        foreach ($this->errors as $field => $errors) {
            $formatted_errors[$field] = $this->error($field);
        }

        return $formatted_errors;
    }

    /**
     * Return a formatted error concerning a field.
     *
     * An empty string is returned if the field is valid.
     *
     * If several errors concern the field, they are concatenated with a space
     * between them.
     */
    public function error(string $field): string
    {
        if (!$this->isInvalid($field)) {
            return '';
        }

        $field_errors = $this->errors[$field] ?? [];
        $messages = array_column($field_errors, 1);
        return implode(' ', $messages);
    }

    /**
     * Declare an error for a field.
     *
     * The error code is usually derived from the PropertyCheck class name, or
     * the Check method name.
     */
    public function addError(string $field, string $error_code, string $error_message): void
    {
        $this->errors[$field][] = [$error_code, $error_message];
    }
}
