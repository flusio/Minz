<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Form;

/**
 * An attribute to allow to run custom checks in forms.
 * It is used in Form classes.
 *
 *     use App\models;
 *     use Minz\Form;
 *
 *     class Registration extends Form
 *     {
 *         #[Form\Field]
 *         public string $username = '';
 *
 *         #[Form\Check]
 *         public function checkUniqueUsername(): void
 *         {
 *             if (models\User::existsBy(['username' => $this->username])) {
 *                 $this->addError('username', 'The username must be unique.');
 *             }
 *         }
 *     }
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Check
{
}
