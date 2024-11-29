<?php

// This file is part of Minz.
// Copyright 2020-2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    use Tests\FilesHelper;

    public function testConstructFailsIfInvalidErrorKey(): void
    {
        $this->expectException(Errors\RuntimeException::class);
        $this->expectExceptionMessage('Invalid parameter: unknown error.');

        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => 42,
        ]);
    }

    public function testContent(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        /** @var string */
        $content = $file->content();

        $this->assertStringContainsString('FOO=bar', $content);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('errorsProvider')]
    public function testContentReturnsNothingIfInError(int $error): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $content = $file->content();

        $this->assertFalse($content);
    }

    public function testContentReturnsNothingIfIsUploadedFileReturnsFalse(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            // This is used during tests to change the behaviour of the method.
            // This cannot be used in development or production environments.
            'is_uploaded_file' => false
        ]);

        $content = $file->content();

        $this->assertFalse($content);
    }

    public function testMove(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
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

    #[\PHPUnit\Framework\Attributes\DataProvider('errorsProvider')]
    public function testMoveFailsIfInError(int $error): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
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

    public function testMoveFailsIfIsUploadedFileReturnsFalse(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
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

    public function testIsTooLargeWithNoErrors(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $file->isTooLarge();

        $this->assertFalse($result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tooLargeErrorsProvider')]
    public function testIsTooLargeWithTooLargeErrors(int $error): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $result = $file->isTooLarge();

        $this->assertTrue($result);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notTooLargeErrorsProvider')]
    public function testIsTooLargeWithNotTooLargeErrors(int $error): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $result = $file->isTooLarge();

        $this->assertFalse($result);
    }

    public function testIsType(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $file->isType([
            'text/plain',
            'text/html',
        ]);

        $this->assertTrue($result);
    }

    public function testIsTypeReturnsFalseIfWrongType(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $file->isType([
            'text/html',
            'image/jpeg',
        ]);

        $this->assertFalse($result);
    }

    public function testErrorIsNotSetIfOk(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertNull($file->error);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('errorsProvider')]
    public function testErrorIsSetIfNotOk(int $error): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ]);

        $this->assertSame($error, $file->error);
    }

    public function testErrorIsSetIfIsUploadedFileReturnsFalse(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $file = new File([
            'name' => '',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            // This is used during tests to change the behaviour of the method.
            // This cannot be used in development or production environments.
            'is_uploaded_file' => false,
        ]);

        $this->assertSame(-1, $file->error);
    }

    /**
     * @return array<array{int}>
     */
    public static function errorsProvider(): array
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

    /**
     * @return array<array{int}>
     */
    public static function tooLargeErrorsProvider(): array
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
        ];
    }

    /**
     * @return array<array{int}>
     */
    public static function notTooLargeErrorsProvider(): array
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
