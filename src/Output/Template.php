<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use Minz\Configuration;
use Minz\Errors;

/**
 * An output class to return content to users based on a template file.
 *
 * It is represented by a file under src/views.
 *
 * ```php
 * $template_output = new Output\Template('pointer/to/template.phtml', [
 *     'foo' => 'bar',
 * ]);
 * $response = new Response(200, $template_output);
 * ```
 *
 * You should not have to initialize this output manually as it can be
 * shortened in:
 *
 * ```php
 * $response = Response::ok('pointer/to/template.phtml', [
 *     'foo' => 'bar',
 * ]);
 * ```
 *
 * The template can either be based on a simple system, or on Twig if the
 * template name ends with `.twig`:
 *
 * ```php
 * $response = Response::ok('pointer/to/template.html.twig', [
 *     'foo' => 'bar',
 * ]);
 * ```
 *
 * @see \Minz\Template\Simple
 * @see \Minz\Template\Twig
 *
 * @phpstan-import-type TemplateName from \Minz\Template\TemplateInterface
 * @phpstan-import-type TemplateContext from \Minz\Template\TemplateInterface
 */
class Template implements \Minz\Output
{
    /** @var array<string, string> */
    public static array $extensions_to_content_types = [
        '.html' => 'text/html',
        '.json' => 'application/json',
        '.phtml' => 'text/html',
        '.txt' => 'text/plain',
        '.xml' => 'text/xml',
    ];

    private string $content_type;

    private \Minz\Template\TemplateInterface $template;

    /**
     * @param TemplateName $name
     * @param TemplateContext $context
     *
     * @throws \Minz\Errors\OutputError if the pointed file doesn't exist
     */
    public function __construct(
        private string $name,
        private array $context = [],
    ) {
        $extension = pathinfo($this->name, PATHINFO_EXTENSION);

        if ($extension === 'twig') {
            $this->template = new \Minz\Template\Twig($this->name, $this->context);
            $sanitized_filename = substr($this->name, 0, -strlen('.twig'));
        } else {
            $this->template = new \Minz\Template\Simple($this->name, $this->context);
            $sanitized_filename = $this->name;
        }

        $this->content_type = 'text/html';

        foreach (self::$extensions_to_content_types as $ext => $content_type) {
            $ends_with_extension = str_ends_with($sanitized_filename, $ext);
            if ($ends_with_extension) {
                $this->content_type = $content_type;
            }
        }
    }

    /**
     * @return TemplateName
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return TemplateContext
     */
    public function context(): array
    {
        return $this->context;
    }

    public function contentType(): string
    {
        return $this->content_type;
    }

    public function template(): \Minz\Template\TemplateInterface
    {
        return $this->template;
    }

    public function render(): string
    {
        return $this->template->render();
    }
}
