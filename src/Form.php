<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * A class to declare and manipulate forms.
 *
 * You can declare a new form type by extending this class.
 * The form fields are declared with the Form\Field attribute.
 *
 *     use Minz\Form;
 *
 *     class Article extends Form
 *     {
 *         #[Form\Field(trim: true)]
 *         public string $title = '';
 *
 *         #[Form\Field]
 *         public string $content = '';
 *     }
 *
 * Initialize the form in a controller and pass it to the view:
 *
 *     public function new(Request $request): Response
 *     {
 *         return Response::ok('articles/new.phtml', [
 *             'form' => new forms\Article(),
 *         ]);
 *     }
 *
 * You can change the default values of the form by passing default values:
 *
 *     new forms\Article([
 *         'title' => 'Default title',
 *     ]);
 *
 * In the view, access the form fields and display the potential errors:
 *
 *     <label for="title">
 *         Title
 *     </label>

 *     <?php if ($form->hasError('title')): ?>
 *         <p id="title-error">
 *             <?= $form->getError('title') ?>
 *         </p>
 *     <?php endif ?>

 *     <input
 *         type="text"
 *         id="title"
 *         name="title"
 *         value="<?= $form->title ?>"
 *         required
 *         <?php if ($form->hasError('title')): ?>
 *             aria-invalid="true"
 *             aria-errormessage="title-error"
 *         <?php endif ?>
 *     />
 *
 * Then, handle the request in the controller. You can bind the form to a model
 * with the Validable trait in order to validate it.
 *
 *     public function create(Request $request): Response
 *     {
 *         $article = new models\Article();
 *
 *         $form = new forms\Article(model: $article);
 *         $form->handleRequest($request);
 *
 *         if (!$form->validate()) {
 *             return Response::badRequest('articles/new.phtml', [
 *                 'form' => $form,
 *             ]);
 *         }
 *
 *         // You need to call getModel to refresh the model with the values
 *         // set from the request.
 *         $article = $form->getModel();
 *         $article->save();
 *
 *         return Response::redirect('articles');
 *     }
 *
 * You can handle CSRF validation with the Form\Csrf trait.
 *
 * You can run special checks that are validated on `$form->validate()` with
 * the Form\Check attribute.
 *
 * @template T of object
 *
 * @phpstan-type FieldConfiguration array{
 *     'type': string,
 *     'trim': bool,
 *     'format': string,
 *     'bind_model': bool|string,
 * }
 *
 * @phpstan-import-type ValidableError from Validable
 */
class Form
{
    /** @var array<string, string[]> */
    protected array $errors = [];

    /** @var ?T */
    protected ?object $model = null;

    /**
     * @param array<string, mixed> $default_values
     * @param ?T $model
     */
    public function __construct(array $default_values = [], ?object $model = null)
    {
        if ($model) {
            $this->model = clone $model;
        }

        $configuration = $this->configuration();
        foreach ($configuration as $field_name => $field_configuration) {
            if ($this->model && isset($this->model->$field_name)) {
                $this->set($field_name, $this->model->$field_name);
            } elseif (isset($default_values[$field_name])) {
                $this->set($field_name, $default_values[$field_name]);
            }
        }
    }

    /**
     * @return array<string, FieldConfiguration>
     */
    public function configuration(): array
    {
        $class_reflection = new \ReflectionClass(static::class);
        $properties = $class_reflection->getProperties();

        $configuration = [];

        foreach ($properties as $property) {
            $field_attributes = $property->getAttributes(
                Form\Field::class,
                \ReflectionAttribute::IS_INSTANCEOF
            );

            if (empty($field_attributes)) {
                continue;
            }

            $field_attribute = $field_attributes[0]->newInstance();

            $property_name = $property->getName();
            $property_type = $property->getType();

            if (!($property_type instanceof \ReflectionNamedType)) {
                $class_name = static::class;
                throw new \LogicException("{$class_name} must define the {$property_name} property type");
            }

            $field_type = $property_type->getName();
            if ($field_type === 'DateTime') {
                $field_type = 'DateTimeImmutable';
            }

            $field_declaration = [
                'type' => $field_type,
            ];

            if ($field_type === 'string') {
                $field_declaration['trim'] = $field_attribute->trim;
            } else {
                $field_declaration['trim'] = false;
            }

            if ($field_type === 'DateTimeImmutable') {
                $format = $field_attribute->format ?? Form\Field::DATETIME_FORMAT;
                $field_declaration['format'] = $format;
            } else {
                $field_declaration['format'] = '';
            }

            $field_declaration['bind_model'] = $field_attribute->bind_model;

            $configuration[$property_name] = $field_declaration;
        }

        return $configuration;
    }

    public function handleRequest(Request $request): void
    {
        $configuration = $this->configuration();
        foreach ($configuration as $field_name => $field_configuration) {
            if ($field_configuration['type'] === 'bool') {
                $value = $request->paramBoolean($field_name);
            } elseif ($field_configuration['type'] === 'int') {
                $value = $request->paramInteger($field_name);
            } elseif ($field_configuration['type'] === 'DateTimeImmutable') {
                $value = $request->paramDatetime($field_name, format: $field_configuration['format']);
            } elseif ($field_configuration['type'] === 'array') {
                $value = $request->paramArray($field_name);
            } else {
                $value = $request->param($field_name);
            }

            if ($value === null && !$request->hasParam($field_name)) {
                continue;
            }

            $this->set($field_name, $value);
        }
    }

    public function set(string $field_name, mixed $value): void
    {
        $configuration = $this->configuration();

        if (!isset($configuration[$field_name])) {
            throw new \LogicException("Form doesn't declare a {$field_name} field.");
        }

        $field_configuration = $configuration[$field_name];
        if ($field_configuration['trim'] && is_string($value)) {
            $value = trim($value);
        }

        $this->$field_name = $value;

        $bind_model = $field_configuration['bind_model'];
        if ($bind_model && $this->model) {
            if (is_string($bind_model)) {
                $this->model->$bind_model($value);
            } else {
                $this->model->$field_name = $value;
            }
        }
    }

    public function format(string $field_name): string
    {
        $configuration = $this->configuration();
        if (!isset($configuration[$field_name])) {
            throw new \LogicException("Form doesn't declare a {$field_name} field.");
        }

        if ($configuration[$field_name]['type'] !== 'DateTimeImmutable') {
            throw new \LogicException("{$field_name} must be of type DateTimeImmutable.");
        }

        if (!$this->$field_name) {
            return '';
        }

        return $this->$field_name->format($configuration[$field_name]['format']);
    }

    public function hasError(string $field_name): bool
    {
        return isset($this->errors[$field_name]);
    }

    public function getError(string $field_name): string
    {
        if ($this->hasError($field_name)) {
            return implode(' ', $this->errors[$field_name]);
        } else {
            return '';
        }
    }

    public function addError(string $field_name, string $error): void
    {
        $this->errors[$field_name][] = $error;
    }

    /**
     * @param string[] $errors
     */
    public function addErrors(string $field_name, array $errors): void
    {
        foreach ($errors as $error) {
            $this->addError($field_name, $error);
        }
    }

    public function validate(): bool
    {
        // Check the custom "check" methods.
        $class_reflection = new \ReflectionClass(static::class);
        $methods = $class_reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $check_attributes = $method->getAttributes(Form\Check::class);
            if (empty($check_attributes)) {
                continue;
            }

            $method->invoke($this);
        }

        // Check the (validable) model.
        if ($this->model && is_callable([$this->model, 'validate'])) {
            /** @var array<string, ValidableError[]> */
            $errors = call_user_func([$this->model, 'validate'], false);

            foreach ($errors as $field_name => $field_errors) {
                $field_errors = array_column($field_errors, 1);
                $this->addErrors($field_name, $field_errors);
            }
        }

        return empty($this->errors);
    }

    /**
     * @return T
     */
    public function getModel(): object
    {
        if (!$this->model) {
            throw new \RuntimeException('Model is not set');
        }

        return $this->model;
    }
}
