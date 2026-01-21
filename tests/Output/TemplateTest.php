<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Output;

use PHPUnit\Framework\TestCase;
use Minz\Errors;

class TemplateTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTwig(): void
    {
        \Minz\Template\Simple::reset();
        \Minz\Template\Twig::reset();
    }

    public function testConstructorWithPhtml(): void
    {
        $output = new Template('rabbits/items.phtml');

        $template = $output->template();
        $this->assertInstanceOf(\Minz\Template\Simple::class, $template);
        $this->assertStringEndsWith('src/views/rabbits/items.phtml', $template->filepath());
    }

    public function testConstructorFailsIfViewFileDoesntExist(): void
    {
        $this->expectException(Errors\OutputError::class);
        $this->expectExceptionMessage(
            'src/views/rabbits/missing.phtml file cannot be found.'
        );

        new Template('rabbits/missing.phtml');
    }

    public function testRenderWithSimpleTemplate(): void
    {
        $rabbits = [
            'Bugs',
            'Clémentine',
            'Jean-Jean',
        ];
        $output = new Template('rabbits/items.phtml', ['rabbits' => $rabbits]);

        $output = $output->render();

        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testRenderWithTwig(): void
    {
        $rabbits = [
            'Bugs',
            'Clémentine',
            'Jean-Jean',
        ];
        $output = new Template('rabbits/items.html.twig', ['rabbits' => $rabbits]);

        $output = $output->render();

        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testDeclareGlobalsWithSimpleTemplate(): void
    {
        \Minz\Template\Simple::addGlobals([
            'title' => 'Hello Rabbits!',
        ]);

        $output = new Template('default_variable.phtml');
        $result = $output->render();
        $this->assertStringContainsString("<h1>Hello Rabbits!</h1>\n", $result);
    }

    public function testDeclareGlobalsWithTwig(): void
    {
        \Minz\Template\Twig::addGlobals([
            'title' => 'Hello Rabbits!',
        ]);

        $output = new Template('default_variable.html.twig');
        $result = $output->render();
        $this->assertStringContainsString("<h1>Hello Rabbits!</h1>\n", $result);
    }

    public function testContentTypeWithPhtml(): void
    {
        $output = new Template('rabbits/items.phtml');

        $this->assertSame('text/html', $output->contentType());
    }

    public function testContentTypeWithTwig(): void
    {
        $output = new Template('rabbits/items.html.twig');

        $this->assertSame('text/html', $output->contentType());
    }

    public function testContentTypeWithUnsupportedExtension(): void
    {
        $output = new Template('rabbits/items.nope');

        $this->assertSame('text/html', $output->contentType());
    }

    public function testContentTypeWithOverwrittenExtToContentType(): void
    {
        $old_extensions_to_content_types = Template::$extensions_to_content_types;
        Template::$extensions_to_content_types['nope'] = 'text/plain';

        $output = new Template('rabbits/items.nope');

        Template::$extensions_to_content_types = $old_extensions_to_content_types;

        $this->assertSame('text/plain', $output->contentType());
    }
}
