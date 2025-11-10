<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
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
 *         #[Form\Field(transform: 'trim')]
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
 *
 *     <?php if ($form->isInvalid('title')): ?>
 *         <p id="title-error">
 *             <?= $form->error('title') ?>
 *         </p>
 *     <?php endif ?>
 *
 *     <input
 *         type="text"
 *         id="title"
 *         name="title"
 *         value="<?= $form->title ?>"
 *         required
 *         <?php if ($form->isInvalid('title')): ?>
 *             aria-invalid="true"
 *             aria-errormessage="title-error"
 *         <?php endif ?>
 *     />
 *
 * Then, handle the request in the controller. You can bind the form to a model
 * so it will automatically set its properties.
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
 *         // You need to call model() to refresh the model with the values
 *         // set from the request.
 *         $article = $form->model();
 *         $article->save();
 *
 *         return Response::redirect('articles');
 *     }
 *
 * As the Form class uses the `Validable` trait, you can call `$form->validate()`
 * to validate the fields. You also can run additionnal checks that are
 * validated on `$form->validate()` with the Validable\Check attribute (as you
 * would do on a model).
 *
 * Note that if a Validable model is bound to the form, it will call the
 * `validate()` method on it too and copy the errors to the form errors. This
 * avoids to duplicate validations.
 *
 *     use Minz\Form;
 *     use Minz\Validable;
 *
 *     class Article extends Form
 *     {
 *         // These checks could be declared in the Article model instead.
 *         #[Form\Field(transform: 'trim')]
 *         #[Validable\Presence(message: 'Enter a title.')]
 *         public string $title = '';
 *
 *         #[Form\Field]
 *         #[Validable\Presence(message: 'Enter a content.')]
 *         public string $content = '';
 *
 *         #[Validable\Check]
 *         public function checkOpenHour(): void
 *         {
 *             $now = \Minz\Time::now();
 *             $height_am = \Minz\Time::relative('8AM');
 *             $height_pm = \Minz\Time::relative('8PM');
 *
 *             if ($now < $height_am || $now > $height_pm) {
 *                 $this->addError('@base', 'checkOpenHour', 'You can only publish between 8AM and 8PM.');
 *             }
 *         }
 *     }
 *
 * Form constructor accepts an `$options` parameter. It allows to pass
 * additional context from the controller to your form.
 *
 *     public function new(Request $request): Response
 *     {
 *         $user = // load the logged-in user
 *
 *         $form = new forms\Article(options: [
 *             'user' => $user,
 *         ]);
 *
 *         return Response::ok('articles/new.phtml', [
 *             'form' => $form,
 *         ]);
 *     }
 *
 * The options can be used in the form with `$this->options` (of ParameterBag
 * class):
 *
 *     use Minz\Form;
 *
 *     class Article extends Form
 *     {
 *         // ...
 *
 *         #[Form\Field]
 *         public string $category = '';
 *
 *         public function availableCategories(): array
 *         {
 *             $user = $this->options->get('user');
 *             return Category::listByUser($user);
 *         }
 *     }
 *
 * Sometimes, you may need to access the request to perform additionnal checks.
 * You can access the request by using the Form\OnHandleRequest attribute on a
 * form method. The method will then be called at the end of `handleRequest()`.
 *
 *     use Minz\Form;
 *     use Minz\Request;
 *
 *     class MyForm extends Form
 *     {
 *         private string $content_type = '';
 *
 *         #[Form\OnHandleRequest]
 *         public function rememberContentType(Request $request): void
 *         {
 *             $this->content_type = $request->header('Content-Type', '');
 *         }
 *     }
 *
 * You can handle CSRF validation with the Form\Csrf trait.
 *
 * @see \Minz\Form\Csrf
 * @see \Minz\Form\Field
 * @see \Minz\Form\OnHandleRequest
 * @see \Minz\Validable
 *
 * @template T of object = \stdClass
 *
 * @phpstan-type FieldSchema array{
 *     'type': string,
 *     'transform': ?callable-string,
 *     'format': string,
 *     'bind': bool|string,
 * }
 *
 * @phpstan-import-type ValidableError from Validable
 * @phpstan-import-type Parameters from ParameterBag
 */
class Form
{
    use Validable;

    /** @var ?T */
    protected ?object $model = null;

    public readonly ParameterBag $options;

    /**
     * Initialize a form.
     *
     * You can override default values with the default_values parameter.
     *
     * You can bind a model to the form so its properties are synchronized with
     * the form fields (see Form\Field::$bind).
     *
     * If both default_values and model are passed, the latter has the priority.
     *
     * @param array<string, mixed> $default_values
     * @param ?T $model
     * @param Parameters $options
     */
    public function __construct(
        array $default_values = [],
        ?object $model = null,
        array $options = [],
    ) {
        if ($model) {
            $this->model = clone $model;
        }

        $this->options = new ParameterBag($options);

        $fields_schema = $this->fieldsSchema();
        foreach ($fields_schema as $field_name => $field_schema) {
            if ($this->model && isset($this->model->$field_name)) {
                $this->set($field_name, $this->model->$field_name);
            } elseif (isset($default_values[$field_name])) {
                $this->set($field_name, $default_values[$field_name]);
            }
        }
    }

    /**
     * Return the schema of the form fields.
     *
     * The method returns an array where keys corresponds to the name of the
     * Form/Field fields, and values are the corresponding schema.
     *
     * @throws Errors\LogicException
     *     Raised if a field doesn't declare its type.
     *
     * @return array<string, FieldSchema>
     */
    public function fieldsSchema(): array
    {
        $class_reflection = new \ReflectionClass(static::class);
        $properties = $class_reflection->getProperties();

        $fields_schema = [];

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
                throw new Errors\LogicException("{$class_name} must define the {$property_name} property type");
            }

            $field_type = $property_type->getName();
            if ($field_type === 'DateTime') {
                $field_type = 'DateTimeImmutable';
            }

            $format = '';
            if ($field_type === 'DateTimeImmutable') {
                $format = $field_attribute->format ?? Form\Field::DATETIME_FORMAT;
            }

            $fields_schema[$property_name] = [
                'type' => $field_type,
                'transform' => $field_attribute->transform,
                'bind' => $field_attribute->bind,
                'format' => $format,
            ];
        }

        return $fields_schema;
    }

    /**
     * Retrieve the fields values from a Request.
     *
     * The values are automatically casted to the correct types, based on the
     * fields schema.
     */
    public function handleRequest(Request $request): void
    {
        $fields_schema = $this->fieldsSchema();
        foreach ($fields_schema as $field_name => $field_schema) {
            if ($field_schema['type'] === 'bool') {
                $value = $request->parameters->getBoolean($field_name);
            } elseif ($field_schema['type'] === 'int') {
                $value = $request->parameters->getInteger($field_name);
            } elseif ($field_schema['type'] === 'DateTimeImmutable') {
                $value = $request->parameters->getDatetime($field_name, format: $field_schema['format']);
            } elseif ($field_schema['type'] === 'array') {
                $value = $request->parameters->getArray($field_name);
            } elseif ($field_schema['type'] === 'Minz\\File') {
                $value = $request->parameters->getFile($field_name);
            } else {
                $value = $request->parameters->getString($field_name);
            }

            if ($value === null && !$request->parameters->has($field_name)) {
                continue;
            }

            $this->set($field_name, $value);
        }

        $class_reflection = new \ReflectionClass($this);
        $methods = $class_reflection->getMethods();

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(Form\OnHandleRequest::class);

            if (empty($attributes)) {
                continue;
            }

            $method->invokeArgs($this, [$request]);
        }
    }

    /**
     * Set a field with the given value.
     *
     * If the field is declared with a `transform` callback, the value is
     * passed to it and the returned value is used.
     *
     * If the form has been bound to a model, its properties are synchronized
     * (see the field `bind` attribute).
     *
     * @throws Errors\LogicException
     *     Raised if $field_name doesn't correspond to an existing field.
     */
    public function set(string $field_name, mixed $value): void
    {
        $fields_schema = $this->fieldsSchema();

        if (!isset($fields_schema[$field_name])) {
            throw new Errors\LogicException("Form doesn't declare a {$field_name} field.");
        }

        $field_schema = $fields_schema[$field_name];

        if ($field_schema['transform']) {
            $transform_function = $field_schema['transform'];
            $value = call_user_func($transform_function, $value);
        }

        $this->$field_name = $value;

        $bind = $field_schema['bind'];
        if ($bind && $this->model) {
            if (is_string($bind)) {
                $this->model->$bind($value);
            } else {
                $this->model->$field_name = $value;
            }
        }
    }

    /**
     * Format a DateTimeImmutable field using the Field `format` option.
     *
     * @throws Errors\LogicException
     *     Raised if the field doesn't exist or if the field is not a
     *     DateTimeImmutable.
     */
    public function format(string $field_name): string
    {
        $fields_schema = $this->fieldsSchema();
        if (!isset($fields_schema[$field_name])) {
            throw new Errors\LogicException("Form doesn't declare a {$field_name} field.");
        }

        if ($fields_schema[$field_name]['type'] !== 'DateTimeImmutable') {
            throw new Errors\LogicException("{$field_name} must be of type DateTimeImmutable.");
        }

        if (!$this->$field_name) {
            return '';
        }

        return $this->$field_name->format($fields_schema[$field_name]['format']);
    }

    /**
     * Return the synchronized model.
     *
     * @throws Errors\LogicException
     *     Raised if the model is not set.
     *
     * @return T
     */
    public function model(): object
    {
        if (!$this->model) {
            throw new Errors\LogicException('Model is not set');
        }

        return $this->model;
    }

    /**
     * Check the model when validating the form and copy the errors to the form
     * errors.
     */
    #[Validable\Check]
    public function checkModel(): void
    {
        if (
            !$this->model ||
            !is_callable([$this->model, 'validate']) ||
            !is_callable([$this->model, 'errors'])
        ) {
            return;
        }

        $is_valid = $this->model->validate();

        if ($is_valid) {
            return;
        }

        $model_errors = $this->model->errors(false);

        foreach ($model_errors as $field_name => $field_errors) {
            foreach ($field_errors as $field_error) {
                $this->addError($field_name, $field_error[0], $field_error[1]);
            }
        }
    }
}
