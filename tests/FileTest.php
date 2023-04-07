<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    use Tests\FilesHelper;

    public function testConstructFailsIfNoErrorKey()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parameter: missing "error" key.');

        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        new File([
            'tmp_name' => $tmp_filepath,
        ]);
    }

    public function testConstructFailsIfInvalidErrorKey()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parameter: unknown error.');

        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        new File([
            'tmp_name' => $tmp_filepath,
            'error' => 42,
        ]);
    }

    public function testConstructFailsIfNoTmpNameKey()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parameter: missing "tmp_name" key.');

        new File([
            'error' => UPLOAD_ERR_OK,
        ]);
    }

    public function testSourceNameIsSet()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => 'name.jpg',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertSame('name.jpg', $file->source_name);
    }

    public function testSourceNameDefaultsToEmptyString()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertSame('', $file->source_name);
    }

    public function testContent()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $content = $file->content();

        $this->assertStringContainsString('FOO=bar', $content);
    }

    /**
     * @dataProvider errorsProvider
     */
    public function testContentReturnsNothingIfInError($error)
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $content = $file->content();

        $this->assertFalse($content);
    }

    public function testContentReturnsNothingIfIsUploadedFileReturnsFalse()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            // This is used during tests to change the behaviour of the method.
            // This cannot be used in development or production environments.
            'is_uploaded_file' => false
        ]);

        $content = $file->content();

        $this->assertFalse($content);
    }

    public function testMove()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);
        $tmp_destination = Configuration::$tmp_path . '/' . bin2hex(random_bytes(10));

        $result = $file->move($tmp_destination);

        $this->assertTrue($result);
        $this->assertTrue(file_exists($tmp_destination));
        $this->assertFalse(file_exists($tmp_filepath));
        $this->assertSame($tmp_destination, $file->filepath);
    }

    /**
     * @dataProvider errorsProvider
     */
    public function testMoveFailsIfInError($error)
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);
        $tmp_destination = Configuration::$tmp_path . '/' . bin2hex(random_bytes(10));

        $result = $file->move($tmp_destination);

        $this->assertFalse($result);
        $this->assertFalse(file_exists($tmp_destination));
        $this->assertTrue(file_exists($tmp_filepath));
        $this->assertSame($tmp_filepath, $file->filepath);
    }

    public function testMoveFailsIfIsUploadedFileReturnsFalse()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            // This is used during tests to change the behaviour of the method.
            // This cannot be used in development or production environments.
            'is_uploaded_file' => false
        ]);
        $tmp_destination = Configuration::$tmp_path . '/' . bin2hex(random_bytes(10));

        $result = $file->move($tmp_destination);

        $this->assertFalse($result);
        $this->assertFalse(file_exists($tmp_destination));
        $this->assertTrue(file_exists($tmp_filepath));
        $this->assertSame($tmp_filepath, $file->filepath);
    }

    public function testIsTooLargeWithNoErrors()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $file->isTooLarge();

        $this->assertFalse($result);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testIsTooLargeWithTooLargeErrors($error)
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $result = $file->isTooLarge();

        $this->assertTrue($result);
    }

    /**
     * @dataProvider notTooLargeErrorsProvider
     */
    public function testIsTooLargeWithNotTooLargeErrors($error)
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $result = $file->isTooLarge();

        $this->assertFalse($result);
    }

    public function testIsType()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $file->isType([
            'text/plain',
            'text/html',
        ]);

        $this->assertTrue($result);
    }

    public function testIsTypeReturnsFalseIfWrongType()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $file->isType([
            'text/html',
            'image/jpeg',
        ]);

        $this->assertFalse($result);
    }

    public function testErrorIsNotSetIfOk()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertNull($file->error);
    }

    /**
     * @dataProvider errorsProvider
     */
    public function testErrorIsSetIfNotOk($error)
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $this->assertSame($error, $file->error);
    }

    public function testErrorIsSetIfIsUploadedFileReturnsFalse()
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            // This is used during tests to change the behaviour of the method.
            // This cannot be used in development or production environments.
            'is_uploaded_file' => false,
        ]);

        $this->assertSame(-1, $file->error);
    }

    public function errorsProvider()
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_FILE],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }

    public function tooLargeErrorsProvider()
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
        ];
    }

    public function notTooLargeErrorsProvider()
    {
        return [
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_FILE],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }
}
