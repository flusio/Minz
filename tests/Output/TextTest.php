<?php

namespace Minz\Output;

use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function testConstructor(): void
    {
        $output = new Text('Hello World!');

        $this->assertSame('text/plain', $output->contentType());
    }

    public function testRender(): void
    {
        $output = new Text('Hello World!');

        $this->assertSame('Hello World!', $output->render());
    }
}
