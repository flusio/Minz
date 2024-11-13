<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * A class to mark a message as translatable with gettext.
 *
 * This class is only useful in PHP Attributes to allow gettext to know that a
 * message is translatable.
 *
 * For instance, in a model with a "Validable" email, you may want to translate
 * the message of the verification. This is not directly possible with the
 * `_()` function as the message is defined in a PHP Attribute. Instead, you
 * can use the Translatable class as following:
 *
 *     use Minz\Translatable;
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Email(
 *             message: new Translatable('Enter a valid email address.'),
 *         )]
 *         public string $email;
 *     }
 *
 * Then, configure xgettext to parse the `Translatable` keyword.
 *
 * The Translatable class only accepts a single form of translation (i.e. not
 * singular/plural forms, nor parameters).
 *
 * You can translate a Translatable message directly by casting the message to
 * string:
 *
 *     use Minz\Translatable;
 *
 *     $message = new Translatable('Enter a valid email address.');
 *     $translated = (string) $message;
 *
 * I would be very happy to delete this class if a better option appears to
 * translate the Validable messages!
 */
class Translatable
{
    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function __toString(): string
    {
        return _($this->message);
    }
}
