<?php

namespace Minz\Output;

use PHPUnit\Framework\TestCase;

class ViewHelpersTest extends TestCase
{
    public function testProtect(): void
    {
        $string = '<strong>foo</strong>';

        $protected_string = ViewHelpers::protect($string);

        $this->assertSame('&lt;strong&gt;foo&lt;/strong&gt;', $protected_string);
    }

    public function testProtectReturnsEmptyStringIfNull(): void
    {
        $string = null;

        $protected_string = ViewHelpers::protect($string);

        $this->assertSame('', $protected_string);
    }

    public function testUrl(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        \Minz\Url::setRouter($router);

        $url = ViewHelpers::url('rabbits#list', ['foo' => 'bar', 'spam' => 'egg']);

        $this->assertSame('/rabbits?foo=bar&amp;spam=egg', $url);
    }

    public function testUrlFull(): void
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        \Minz\Url::setRouter($router);

        $url = ViewHelpers::urlFull('rabbits#list', ['foo' => 'bar', 'spam' => 'egg']);

        $this->assertSame('http://localhost/rabbits?foo=bar&amp;spam=egg', $url);
    }

    public function testUrlStatic(): void
    {
        $url = ViewHelpers::urlStatic('file.txt');

        $this->assertStringStartsWith('/static/file.txt?', $url);
    }

    public function testUrlFullStatic(): void
    {
        $url = ViewHelpers::urlFullStatic('file.txt');

        $this->assertStringStartsWith('http://localhost/static/file.txt?', $url);
    }

    public function testUrlPublic(): void
    {
        $url = ViewHelpers::urlPublic('file.txt');

        $this->assertSame('/file.txt', $url);
    }

    public function testUrlFullPublic(): void
    {
        $url = ViewHelpers::urlFullPublic('file.txt');

        $this->assertSame('http://localhost/file.txt', $url);
    }

    public function testFormatDate(): void
    {
        ini_set('intl.default_locale', 'en-GB');
        $date = new \DateTime('2022-09-06');

        $formatted_date = ViewHelpers::formatDate($date);

        ini_set('intl.default_locale', '');

        $this->assertSame('Tuesday 6 September', $formatted_date);
    }

    public function testFormatDateWithFormat(): void
    {
        ini_set('intl.default_locale', 'en-GB');
        $date = new \DateTime('2022-09-06');

        $formatted_date = ViewHelpers::formatDate($date, 'dd/MM/Y');

        ini_set('intl.default_locale', '');

        $this->assertSame('06/09/2022', $formatted_date);
    }

    public function testFormatDateWithSpecifiedLocale(): void
    {
        ini_set('intl.default_locale', 'en-GB');
        $date = new \DateTime('2022-09-06');

        $formatted_date = ViewHelpers::formatDate($date, 'EEEE d MMMM', 'fr-FR');

        ini_set('intl.default_locale', '');

        $this->assertSame('mardi 6 septembre', $formatted_date);
    }

    public function testFormatGettext(): void
    {
        $string = 'Hello %s!';

        $formatted_string = ViewHelpers::formatGettext($string, 'World');

        $this->assertSame('Hello World!', $formatted_string);
    }

    public function testFormatNgettextSingular(): void
    {
        $formatted_string = ViewHelpers::formatNgettext('%d rabbit', '%d rabbits', 1, 1);

        $this->assertSame('1 rabbit', $formatted_string);
    }

    public function testFormatNgettextPlural(): void
    {
        $formatted_string = ViewHelpers::formatNgettext('%d rabbit', '%d rabbits', 2, 2);

        $this->assertSame('2 rabbits', $formatted_string);
    }
}
