<?php

namespace Minz;

/**
 * The Validable trait allows to validate an object.
 *
 * It provides a `validate()` method to the class that uses it. To work, the
 * class must also apply some `Validable\Check` attributes on its properties.
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
 * Then, call the validate method to find the potential errors:
 *
 *     $user = new User();
 *     $user->nickname = 'invalid nickname';
 *     $errors = $user->validate();
 *
 * The errors array is indexed by the concerned properties. Multiple errors can
 * concern one property. Each error is represented by a code (i.e. the check
 * class name) and the message given in parameter.
 *
 * You can pass `true` to `validate()` so the errors messages are concatenated
 * in a single string.
 *
 * If there are no errors, validate returns an empty array.
 *
 * @phpstan-type ValidableError array{string, string}
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Validable
{
    /**
     * @return array<string, ValidableError[]|string>
     */
    public function validate(bool $format = false): array
    {
        // Create a ReflectionClass to find all the checkable properties.
        $classReflection = new \ReflectionClass(self::class);
        $properties = $classReflection->getProperties();

        $errors = [];

        // First, we iterate on all the properties
        foreach ($properties as $property) {
            // We retrieve the "Check" attributes
            $check_attributes = $property->getAttributes(
                Validable\Check::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );

            if (empty($check_attributes)) {
                // No Check? The property is not validable
                continue;
            }

            $property_errors = [];

            // Then, we iterate on all the check attributes
            foreach ($check_attributes as $reflection_attribute) {
                $attribute = $reflection_attribute->newInstance();

                // The Check needs some information to work
                $attribute->instance = $this;
                $attribute->property = $property;

                if (!$attribute->assert()) {
                    // If the assert fails, we register the error
                    $error_code = get_class($attribute);
                    $error_message = $attribute->getMessage();
                    $property_errors[] = [$error_code, $error_message];
                }
            }

            if ($property_errors) {
                // There are errors, so we add them to the global array of
                // errors per property.
                $property_name = $property->getName();
                if ($format) {
                    $messages = array_column($property_errors, 1);
                    $errors[$property_name] = implode(' ', $messages);
                } else {
                    $errors[$property_name] = $property_errors;
                }
            }
        }

        return $errors;
    }
}
