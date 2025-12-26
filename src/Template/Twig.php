<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Template;

/**
 * A templating system based on Twig.
 *
 * @see https://twig.symfony.com/
 *
 * The templates must be placed under `src/views/` and ends with the `.twig`
 * extension (e.g. `src/views/home.html.twig`).
 *
 * Twig can be used to generate all kind of files (HTML, text, JS, etc.)
 *
 * Several Twig functions and filters are declared in the TwigExtension class.
 *
 * @see \Minz\Template\TwigExtension
 *
 * @phpstan-import-type TemplateName from TemplateInterface
 * @phpstan-import-type TemplateContext from TemplateInterface
 */
class Twig implements TemplateInterface
{
    private static ?\Twig\Environment $twig_instance = null;

    /**
     * @param TemplateName $name
     * @param TemplateContext $context
     */
    public function __construct(
        private string $name,
        private array $context = [],
    ) {
    }

    /**
     * Generate and return the content.
     */
    public function render(): string
    {
        return self::twig()->render($this->name, $this->context);
    }

    /**
     * Declare a standard Twig extension.
     */
    public static function addExtension(\Twig\Extension\ExtensionInterface $extension): void
    {
        $twig = self::twig();

        if ($extension instanceof \Twig\Extension\AttributeExtension) {
            $extension_class = $extension->getClass();
        } else {
            $extension_class = $extension::class;
        }

        if (!$twig->hasExtension($extension_class)) {
            self::twig()->addExtension($extension);
        }
    }

    /**
     * Declare a Twig extension where filters, functions and tests are declared
     * with Twig attributes.
     *
     * @see https://twig.symfony.com/doc/3.x/advanced.html#using-php-attributes-to-define-extensions
     *
     * @param class-string $extension_class
     */
    public static function addAttributeExtension(string $extension_class): void
    {
        $attribute_extension = new \Twig\Extension\AttributeExtension($extension_class);
        self::addExtension($attribute_extension);
    }

    /**
     * Return a Twig Environment as a singleton.
     */
    private static function twig(): \Twig\Environment
    {
        if (!self::$twig_instance) {
            $app_path = \Minz\Configuration::$app_path;
            $views_path = "{$app_path}/src/views";
            $loader = new \Twig\Loader\FilesystemLoader($views_path);

            self::$twig_instance = new \Twig\Environment($loader, [
                'cache' => \Minz\Configuration::$tmp_path . '/twig',
                'strict_variables' => true,
            ]);

            $extension = new \Twig\Extension\AttributeExtension(TwigExtension::class);
            self::$twig_instance->addExtension($extension);
        }

        return self::$twig_instance;
    }

    /**
     * Declare default context so the variables can be used without passing
     * them explicitely when creating a template.
     *
     * This is usually called in the Application class, or in a
     * Controller\BeforeAction handler.
     *
     * @param TemplateContext $context
     */
    public static function addGlobals($context): void
    {
        $twig = self::twig();

        foreach ($context as $name => $value) {
            $twig->addGlobal($name, $value);
        }
    }

    /**
     * Reset the Twig environment.
     */
    public static function reset(): void
    {
        self::$twig_instance = null;
    }
}
