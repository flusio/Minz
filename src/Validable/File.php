<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

use Minz\Errors;

/**
 * Check that a File property is valid.
 *
 * By default, only the upload error status is checked. Additionnaly, the max
 * size and the extension/mimetype can be checked.
 *
 * This check can be used only on \Minz\File attributes.
 *
 * The max_size is either determined by the following parameters:
 *
 * - the PHP INI `post_max_size` directive;
 * - the PHP INI `upload_max_filesize` directive;
 * - the optional `max_size` parameter of this check: can be an integer (octet)
 *   or it can be expressed with a string ending with one of the following
 *   suffixes: K, M, or G. The value is then parsed as for the other directives.
 *
 *     use Minz\Validable;
 *
 *     class UserForm
 *     {
 *         use Validable;
 *
 *         #[Validable\File(
 *             max_size: '1M',
 *             max_size_message: 'The file size cannot exceed {max_size}.'
 *         )
 *         public \Minz\File $avatar;
 *     }
 *
 * The extension and mimetype can also be checked by passing the `types`
 * parameter. This array takes extensions (e.g. `png`) as keys, and arrays
 * of mimetypes (e.g. `['image/png']) as values. The file extension must match
 * with at least one of the key, and its mimetype must match one of the
 * corresponding values.
 *
 *     use Minz\Validable;
 *
 *     class UserForm
 *     {
 *         use Validable;
 *
 *         #[Validable\File(
 *             types: [
 *                 'png' => ['image/png'],
 *                 'jpg' => ['image/jpeg'],
 *                 'jpeg' => ['image/jpeg'],
 *                 'webp' => ['image/webp'],
 *             ]
 *             types_message: 'The file type must be one of the following: {types}.'
 *         )
 *         public \Minz\File $avatar;
 *     }
 *
 * This check accepts three different error messages, used depending on the
 * error:
 *
 * - types_message: used when the extension/mimetype is not valid (available
 *   placeholders: {code}, {types})
 * - max_size_message: used when the filesize is bigger than the authorized
 *   size (available placeholders: {code}, {max_size})
 * - message: the default error message (available placeholder: {code})
 *
 * Note that the "null" value is considered as valid in order to accept
 * optional values.
 *
 * @see https://www.php.net/manual/en/filesystem.constants.php#constant.upload-err-cant-write
 * @see https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class File extends PropertyCheck
{
    /** @var ?array<string, string[]> */
    public ?array $types;

    public int|string|null $max_size;

    public string $types_message;

    public string $max_size_message;

    private ?string $error_type = null;

    /**
     * @param ?array<string, string[]> $types
     */
    public function __construct(
        string $message,
        string $types_message = '',
        string $max_size_message = '',
        ?array $types = null,
        int|string|null $max_size = null,
    ) {
        parent::__construct($message);
        $this->types_message = $types_message;
        $this->max_size_message = $max_size_message;
        $this->types = $types;
        $this->max_size = $max_size;
    }

    public function assert(): bool
    {
        $file = $this->value();

        if ($file === null) {
            return true;
        }

        $size = $file->size();
        $max_size = $this->getMaxSize();

        if (
            $file->isTooLarge() ||
            ($max_size > 0 && $size >= $max_size)
        ) {
            $this->error_type = 'max_size';
            return false;
        }

        if ($file->error) {
            $this->error_type = 'default';
            return false;
        }

        if ($this->types) {
            $expected_extensions = array_keys($this->types);

            if (!$file->isExtension($expected_extensions)) {
                $this->error_type = 'type';
                return false;
            }

            $extension = $file->extension();
            $expected_mimetypes = $this->types[$extension] ?? [];

            if (!$file->isType($expected_mimetypes)) {
                $this->error_type = 'type';
                return false;
            }
        }

        return true;
    }

    public function value(): ?\Minz\File
    {
        $value = parent::value();

        if ($value !== null && !($value instanceof \Minz\File)) {
            throw new Errors\LogicException('Validable\File can only be used on Minz\Form attributes.');
        }

        return $value;
    }

    public function message(): string
    {
        $file = $this->value();

        $code = $file ? $file->error : -1;

        if ($this->error_type === 'max_size' && $this->max_size_message) {
            $max_size = $this->getMaxSize();
            $max_size_formatted = $this->formatSize($max_size);

            return $this->formatMessage(
                $this->max_size_message,
                ['{code}', '{max_size}'],
                [$code, $max_size_formatted],
            );
        } elseif ($this->error_type === 'type' && $this->types && $this->types_message) {
            $expected_types = array_keys($this->types);
            $expected_types = array_map('strtoupper', $expected_types);
            $expected_types = implode(', ', $expected_types);

            return $this->formatMessage(
                $this->types_message,
                ['{code}', '{types}'],
                [$code, $expected_types],
            );
        } else {
            return $this->formatMessage(
                $this->message,
                ['{code}'],
                [$code],
            );
        }
    }

    public function code(): string
    {
        $base_code = parent::code();
        if ($this->error_type === 'max_size') {
            return "{$base_code}.max_size";
        } elseif ($this->error_type === 'type') {
            return "{$base_code}.type";
        } else {
            return $base_code;
        }
    }

    /**
     * Return the max size (in octets) allowed for the file.
     *
     * It returns the smallest of the INI post_max_size, INI upload_max_size
     * and max_size values.
     */
    private function getMaxSize(): int
    {
        $given_max_size = $this->max_size ?? 0;
        $given_max_size = $this->parseSize($given_max_size);
        $ini_post_max_size = ini_get('post_max_size');
        $ini_post_max_size = $ini_post_max_size ? $this->parseSize($ini_post_max_size) : 0;
        $ini_upload_max_size = ini_get('upload_max_filesize');
        $ini_upload_max_size = $ini_upload_max_size ? $this->parseSize($ini_upload_max_size) : 0;

        return min($given_max_size, $ini_post_max_size, $ini_upload_max_size);
    }

    /**
     * Parse and return a file size.
     *
     * The size can be expressed as an integer, in which case it is returned as
     * it is. It also can be expressed as a string ending with one of the
     * following suffixes: K (Kilobytes), M (Megabytes), or G (Gigabytes).
     * Note that the value is parsed as a PHP INI shorthand byte value.
     *
     * @see https://www.php.net/manual/en/faq.using.php#faq.using.shorthandbytes
     */
    private function parseSize(string|int $size): int
    {
        if (!$size) {
            return 0;
        }

        if (is_int($size)) {
            return $size;
        }

        $int_size = (int) $size;
        $suffix = strtoupper(substr($size, -1));

        if ($suffix === 'G') {
            return $int_size * 1024 * 1024 * 1024;
        } elseif ($suffix === 'M') {
            return $int_size * 1024 * 1024;
        } elseif ($suffix === 'K') {
            return $int_size * 1024;
        } else {
            return $int_size;
        }
    }

    /**
     * Convert a size in octets into a string ending by B, KB, MB or GB.
     *
     * The suffix is chosen by considering the first value greater than 1.
     */
    private function formatSize(int $size): string
    {
        $size_gb = round($size / (1024 * 1024 * 1024), 2);
        $size_mb = round($size / (1024 * 1024), 2);
        $size_kb = round($size / 1024, 2);

        if ($size_gb >= 1) {
            return "{$size_gb}GB";
        } elseif ($size_mb >= 1) {
            return "{$size_mb}MB";
        } elseif ($size_kb >= 1) {
            return "{$size_kb}KB";
        } else {
            return "{$size}B";
        }
    }
}
