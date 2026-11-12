<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

use AppTest\forms;
use AppTest\models;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    use Tests\FilesHelper;

    public function testConstructWithDefaultValues(): void
    {
        $form = new forms\Rabbit([
            'name' => 'Bugs',
        ]);

        $this->assertSame('Bugs', $form->name);
    }

    public function testConstructWithBindedModel(): void
    {
        $rabbit = new models\Rabbit();
        $rabbit->name = 'Bugs';
        $form = new forms\Rabbit([
            'name' => 'Clémentine',
        ], model: $rabbit);

        $this->assertSame('Bugs', $form->name);
    }

    public function testConstructWithOptions(): void
    {
        $form = new forms\Rabbit(options: [
            'foo' => 'bar',
        ]);

        $this->assertSame('bar', $form->options->get('foo'));
    }

    public function testHandleRequest(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
        ]);
        $form = new forms\Rabbit();

        $form->handleRequest($request);

        $this->assertSame('Bugs', $form->name);
        $this->assertSame(1, $form->friend_id);
    }

    public function testHandleRequestWithBindedModel(): void
    {
        $rabbit = new models\Rabbit();
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
        ]);
        $form = new forms\Rabbit(model: $rabbit);

        $form->handleRequest($request);

        $rabbit = $form->model();
        $this->assertSame('Bugs', $rabbit->name);
        $this->assertSame(1, $rabbit->friend_id);
    }

    public function testHandleRequestWithMissingBoolParam(): void
    {
        $request = new \Minz\Request('GET', '/', []);
        $form = new forms\FormWithBool();

        $form->handleRequest($request);

        $this->assertFalse($form->param_bool);
    }

    public function testHandleRequestWithFile(): void
    {
        $file_filepath = Configuration::$app_path . '/dotenv';
        $tmp_filepath = $this->tmpCopyFile($file_filepath);
        $request = new \Minz\Request('GET', '/', [
            'file' => [
                'name' => '',
                'tmp_name' => $tmp_filepath,
                'error' => UPLOAD_ERR_OK,
            ],
        ]);
        $form = new forms\FormWithFile();

        $form->handleRequest($request);

        $file_content = $form->file->content();
        $this->assertNotFalse($file_content);
        $this->assertStringContainsString('FOO=bar', $file_content);
    }

    public function testHandleRequestWithNullString(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'name' => null,
        ]);
        $form = new forms\Rabbit([
            'name' => 'Bugs',
        ]);

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertSame('Bugs', $form->name);
        $this->assertSame('The value is invalid.', $form->error('name'));
        $errors = $form->errors(format: false);
        $this->assertSame('type_invalid', $errors['name'][0][0]);
    }

    public function testHandleRequestWithNullStringAndBindedModel(): void
    {
        $rabbit = new models\Rabbit();
        $rabbit->name = 'Bugs';
        $request = new \Minz\Request('GET', '/', [
            'name' => null,
        ]);
        $form = new forms\Rabbit(model: $rabbit);

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertSame('Bugs', $form->model()->name);
    }

    public function testHandleRequestWithNullNullableStringAndTransform(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'param_trimmed_string' => null,
            'param_defaulted_string' => null,
        ]);
        $form = new forms\FormWithNullableString();

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertTrue($is_valid);
        $this->assertNull($form->param_trimmed_string);
        $this->assertSame('bar', $form->param_defaulted_string);
    }

    public function testHandleRequestWithNullNullableString(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'param_string' => null,
        ]);
        $form = new forms\FormWithNullableString();

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertTrue($is_valid);
        $this->assertNull($form->param_string);
    }

    public function testHandleRequestWithInvalidNullableString(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'param_string' => [],
        ]);
        $form = new forms\FormWithNullableString();

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertSame('foo', $form->param_string);
        $this->assertSame('Must be a string.', $form->error('param_string'));
    }

    public function testHandleRequestWithNullDatetime(): void
    {
        $datetime = new \DateTimeImmutable('2024-03-07 12:00');
        $request = new \Minz\Request('GET', '/', [
            'default_field_datetime' => null,
        ]);
        $form = new forms\FormWithDatetime([
            'default_field_datetime' => $datetime,
        ]);

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertSame($datetime, $form->default_field_datetime);
        $this->assertSame('The value is invalid.', $form->error('default_field_datetime'));
    }

    public function testHandleRequestWithEmptyNullableDatetime(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'nullable_field_datetime' => '',
        ]);
        $form = new forms\FormWithDatetime([
            'nullable_field_datetime' => new \DateTimeImmutable('2024-03-07 12:00'),
        ]);

        $form->handleRequest($request);
        $is_valid = $form->validate();

        $this->assertTrue($is_valid);
        $this->assertNull($form->nullable_field_datetime);
    }

    public function testFormatWithDatetime(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'default_field_datetime' => '2024-03-07T12:00',
            'custom_field_datetime' => '2024-03-07',
        ]);
        $form = new forms\FormWithDatetime();
        $form->handleRequest($request);

        $formatted_default_datetime = $form->format('default_field_datetime');
        $this->assertSame('2024-03-07T12:00', $formatted_default_datetime);
        $formatted_custom_datetime = $form->format('custom_field_datetime');
        $this->assertSame('2024-03-07', $formatted_custom_datetime);
    }

    public function testValidate(): void
    {
        $form = new forms\Rabbit();
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
            'csrf_token' => $form->csrfToken(),
        ], [
            'Origin' => \Minz\Url::baseUrl(),
        ]);
        $form->handleRequest($request);

        $is_valid = $form->validate();

        $this->assertTrue($is_valid);
        $this->assertEquals([], $form->errors(format: false));
    }

    public function testValidateWithCsrfRefererInsteadOfOrigin(): void
    {
        $form = new forms\Rabbit();
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
            'csrf_token' => $form->csrfToken(),
        ], [
            'Referer' => \Minz\Url::baseUrl() . '/foo?bar=baz',
        ]);
        $form->handleRequest($request);

        $is_valid = $form->validate();

        $this->assertTrue($is_valid);
        $this->assertEquals([], $form->errors(format: false));
    }

    public function testValidateWithValidableModel(): void
    {
        $validable_model = new models\ValidableModel();
        $request = new \Minz\Request('GET', '/', [
            'nickname' => '',
            'email' => 'not an email',
        ]);
        $form = new forms\ValidableModel(model: $validable_model);
        $form->handleRequest($request);

        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'nickname' => [
                ['presence', 'Choose a nickname.'],
            ],
            'email' => [
                ['email', 'Choose a valid email.'],
            ],
        ], $form->errors(format: false));
    }

    public function testValidateWithInvalidCsrfToken(): void
    {
        $form = new forms\Rabbit();
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
            'csrf_token' => 'not the token',
        ], [
            'Origin' => \Minz\Url::baseUrl(),
        ]);
        $form->handleRequest($request);

        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            '@base' => [
                ['csrf', 'The security token is invalid. Please try to submit the form again.'],
            ],
        ], $form->errors(format: false));
    }

    public function testValidateWithInvalidCsrfOrigin(): void
    {
        $form = new forms\Rabbit();
        $request = new \Minz\Request('GET', '/', [
            'name' => ' Bugs ',
            'friend_id' => 1,
            'csrf_token' => $form->csrfToken(),
        ], [
            'Origin' => 'https://not-the-origin.example.org',
        ]);
        $form->handleRequest($request);

        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            '@base' => [
                ['csrf', 'The security token is invalid. Please try to submit the form again.'],
            ],
        ], $form->errors(format: false));
    }

    public function testValidateWithCustomCheck(): void
    {
        $request = new \Minz\Request('GET', '/', [
            'name' => 'Clémentine',
        ]);
        $form = new forms\FormWithCheck();
        $form->handleRequest($request);

        $is_valid = $form->validate();

        $this->assertFalse($is_valid);
        $this->assertEquals([
            'name' => [
                ['checkName', 'Name must be equal to "Bugs"'],
            ],
        ], $form->errors(format: false));
    }
}
