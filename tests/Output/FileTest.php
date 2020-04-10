<?php

namespace Minz\Output;

use PHPUnit\Framework\TestCase;
use Minz\Errors;

class FileTest extends TestCase
{
    public function testConstructor()
    {
        $output = new File(\Minz\Configuration::$data_path . '/empty.pdf');

        $this->assertSame('application/pdf', $output->contentType());
    }

    public function testConstructorFailsIfFileDoesntExist()
    {
        $this->expectException(Errors\OutputError::class);
        $this->expectExceptionMessage(
            'data/missing.pdf file cannot be found.'
        );

        new File(\Minz\Configuration::$data_path . '/missing.pdf');
    }

    public function testConstructorFailsIfExtensionIsNotSupported()
    {
        $this->expectException(Errors\OutputError::class);
        $this->expectExceptionMessage(
            'html is not a supported file extension.'
        );

        new File(\Minz\Configuration::$data_path . '/unsupported.html');
    }

    public function testRender()
    {
        $output = new File(\Minz\Configuration::$data_path . '/empty.pdf');

        $this->assertNotEmpty($output->render());
    }
}
