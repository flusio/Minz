<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Template;

/**
 * @phpstan-type TemplateName non-empty-string
 * @phpstan-type TemplateContext array<string, mixed>
 */
interface TemplateInterface
{
    /**
     * @param TemplateName $name
     * @param TemplateContext $context
     */
    public function __construct(string $name, array $context);

    public function render(): string;
}
