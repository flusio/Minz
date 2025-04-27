<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * An attribute to run custom checks in Validable objects.
 *
 * This attribute must be applied on class methods. They must not take
 * parameters and should return nothing. They must use the `addError` method to
 * declares errors.
 *
 *     use Minz\Form;
 *     use Minz\Validable;
 *
 *     class Article extends Form
 *     {
 *         #[Form\Field(transform: 'trim')]
 *         public string $title = '';
 *
 *         #[Form\Field]
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
 * @see \Minz\Validable
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Check
{
}
