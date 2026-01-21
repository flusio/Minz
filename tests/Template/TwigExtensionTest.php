<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Template;

use PHPUnit\Framework\TestCase;

class TwigExtensionTest extends TestCase
{
    public function testUrl(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        \Minz\Engine::init($router);

        $url = TwigExtension::url('rabbits#list', ['foo' => 'bar', 'spam' => 'egg']);

        $this->assertSame('/rabbits?foo=bar&spam=egg', $url);
    }

    public function testUrlFull(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('GET', '/rabbits', 'rabbits#list');
        \Minz\Engine::init($router);

        $url = TwigExtension::urlFull('rabbits#list', ['foo' => 'bar', 'spam' => 'egg']);

        $this->assertSame('http://localhost/rabbits?foo=bar&spam=egg', $url);
    }

    public function testUrlStatic(): void
    {
        $url = TwigExtension::urlStatic('file.txt');

        $this->assertStringStartsWith('/file.txt?', $url);
    }

    public function testUrlFullStatic(): void
    {
        $url = TwigExtension::urlFullStatic('file.txt');

        $this->assertStringStartsWith('http://localhost/file.txt?', $url);
    }

    public function testIsEnvironment(): void
    {
        $result = TwigExtension::isEnvironment('production');
        $this->assertFalse($result);
        $result = TwigExtension::isEnvironment('test');
        $this->assertTrue($result);
    }

    public function testTranslateDate(): void
    {
        ini_set('intl.default_locale', 'en-GB');
        $date = new \DateTime('2022-09-06');

        $translated_date = TwigExtension::translateDate($date);

        ini_set('intl.default_locale', '');

        $this->assertSame('Tuesday 6 September', $translated_date);
    }

    public function testTranslateDateWithFormat(): void
    {
        ini_set('intl.default_locale', 'en-GB');
        $date = new \DateTime('2022-09-06');

        $translated_date = TwigExtension::translateDate($date, 'dd/MM/Y');

        ini_set('intl.default_locale', '');

        $this->assertSame('06/09/2022', $translated_date);
    }

    public function testTranslateDateWithSpecifiedLocale(): void
    {
        ini_set('intl.default_locale', 'en-GB');
        $date = new \DateTime('2022-09-06');

        $translated_date = TwigExtension::translateDate($date, 'EEEE d MMMM', 'fr-FR');

        ini_set('intl.default_locale', '');

        $this->assertSame('mardi 6 septembre', $translated_date);
    }

    public function testTranslate(): void
    {
        $string = 'Hello!';

        $translated_string = TwigExtension::translate($string);

        $this->assertSame('Hello!', $translated_string);
    }

    public function testTranslateWithVariable(): void
    {
        $string = 'Hello %s!';

        $translated_string = TwigExtension::translate($string, ['World']);

        $this->assertSame('Hello World!', $translated_string);
    }

    public function testTranslateSingular(): void
    {
        $translated_string = TwigExtension::translate('%d rabbit', '%d rabbits', 1, [1]);

        $this->assertSame('1 rabbit', $translated_string);
    }

    public function testTranslatePlural(): void
    {
        $translated_string = TwigExtension::translate('%d rabbit', '%d rabbits', 2, [2]);

        $this->assertSame('2 rabbits', $translated_string);
    }
}
