<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Form;

/**
 * An attribute to declare form fields.
 *
 *     use Minz\Form;
 *
 *     class Article extends Form
 *     {
 *         #[Form\Field]
 *         public string $title = '';
 *     }
 *
 * You can declare as many fields as you want, including different types
 * (string, boolean, integer, datetime, arrays of strings).
 *
 * You can initialize the Field with `trim: true` to trim the value coming
 * from the form data.
 *
 *     class Article extends Form
 *     {
 *         #[Form\Field(trim: true)]
 *         public string $title = '';
 *     }
 *
 * Datetimes are parsed from the Request parameters with the format `Y-m-dTH:i`.
 * You can change the format (e.g. if the input only allows to select a date
 * and not a time).
 *
 *     class Article extends Form
 *     {
 *         // ...
 *
 *         #[Form\Field(format: 'Y-m-d')]
 *         public ?\DateTimeImmutable $published_at = null;
 *     }
 *
 * Fields are automatically bind to model attributes if the form is bind to a
 * model. It means that if the model has the same attribute name as the field,
 * it will be set when running `$form->handleRequest`. You can disable this
 * behaviour by passing `bind_model: false`.
 *
 *     class Article extends Form
 *     {
 *         #[Form\Field(bind_model: false)]
 *         public string $title = '';
 *     }
 *
 * You can also pass a method name that will be called on the model instead of
 * setting the value directly:
 *
 *     class Article extends Form
 *     {
 *         #[Form\Field(bind_model: 'setTitle')]
 *         public string $title = '';
 *     }
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Field
{
    public const DATETIME_FORMAT = 'Y-m-dTH:i';

    public function __construct(
        public bool $trim = false,
        public ?string $format = null,
        public bool|string $bind_model = true
    ) {
    }
}
