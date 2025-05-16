<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz;

/**
 * @phpstan-type Parameters array<string, mixed>
 */
class ParameterBag
{
    /** @var array<string, mixed> */
    protected array $parameters = [];

    protected bool $case_sensitive = true;

    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        array $parameters = [],
        bool $case_sensitive = true,
    ) {
        $this->case_sensitive = $case_sensitive;

        foreach ($parameters as $name => $value) {
            $this->set($name, $value);
        }
    }

    public function set(string $name, mixed $value): void
    {
        if (!$this->case_sensitive) {
            $name = mb_strtolower($name);
        }

        $this->parameters[$name] = $value;
    }

    public function has(string $name): bool
    {
        if (!$this->case_sensitive) {
            $name = mb_strtolower($name);
        }

        return array_key_exists($name, $this->parameters);
    }

    public function get(string $name): mixed
    {
        if (!$this->case_sensitive) {
            $name = mb_strtolower($name);
        }

        return $this->parameters[$name] ?? null;
    }

    /**
     * Return a parameter value as a string.
     *
     * @template T of ?string
     *
     * @param T $default
     * @return string|T
     */
    public function getString(string $name, ?string $default = null): ?string
    {
        if (!$this->has($name)) {
            return $default;
        }

        $value = $this->get($name);

        if (
            !is_bool($value) &&
            !is_float($value) &&
            !is_integer($value) &&
            !is_string($value)
        ) {
            return $default;
        }

        return strval($value);
    }

    /**
     * Return a parameter value as a boolean.
     */
    public function getBoolean(string $name, bool $default = false): bool
    {
        if (!$this->has($name)) {
            return $default;
        }

        $value = $this->get($name);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Return a parameter value as an integer.
     *
     * @template T of ?int
     *
     * @param T $default
     * @return int|T
     */
    public function getInteger(string $name, ?int $default = null): ?int
    {
        if (!$this->has($name)) {
            return $default;
        }

        $value = $this->get($name);

        if (
            !is_float($value) &&
            !is_integer($value) &&
            !is_string($value)
        ) {
            return $default;
        }

        return intval($value);
    }

    /**
     * Return a parameter value as a DateTimeImmutable.
     *
     * @template T of ?\DateTimeImmutable
     *
     * @param T $default
     * @return \DateTimeImmutable|T
     */
    public function getDatetime(
        string $name,
        ?\DateTimeImmutable $default = null,
        string $format = 'Y-m-d\\TH:i'
    ): ?\DateTimeImmutable {
        if (!$this->has($name)) {
            return $default;
        }

        $value = $this->get($name);

        if ($value instanceof \DateTimeInterface) {
            $datetime = \DateTimeImmutable::createFromInterface($value);
        } elseif (is_string($value)) {
            $datetime = \DateTimeImmutable::createFromFormat($format, $value);
        } elseif (is_integer($value)) {
            $datetime = new \DateTimeImmutable('@' . $value);
        } else {
            $datetime = false;
        }

        if ($datetime === false) {
            return $default;
        }

        return $datetime;
    }

    /**
     * Return a parameter value as an array.
     *
     * If the parameter isn’t an array, it’s placed into one.
     *
     * The default value is merged with the parameter value.
     *
     * @param mixed[] $default
     *
     * @return mixed[]
     */
    public function getArray(string $name, array $default = []): array
    {
        if (!$this->has($name)) {
            return $default;
        }

        $value = $this->get($name);

        if (!is_array($value)) {
            $value = [$value];
        }

        return array_merge($default, $value);
    }

    /**
     * Return a parameter value as a Json array.
     *
     * If the value is equal to true, false or null, it returns the value in
     * an array.
     *
     * If the parameter cannot be parsed as Json, default value is returned.
     *
     * @template T of mixed[]|null
     *
     * @param T $default
     *
     * @return mixed[]|T
     */
    public function getJson(string $name, mixed $default = null): ?array
    {
        if (!$this->has($name)) {
            return $default;
        }

        $value = $this->get($name);

        if (!is_string($value)) {
            return $default;
        }

        $json_value = json_decode($value, true);

        if ($json_value === null && $value !== 'null') {
            return $default;
        }

        if (!is_array($json_value)) {
            $json_value = [$json_value];
        }

        return $json_value;
    }

    /**
     * Return a parameter value as a \Minz\File.
     *
     * The parameter must be an array containing at least a `tmp_name` and an
     * `error` keys, or a null value will be returned.
     *
     * @see https://www.php.net/manual/features.file-upload.post-method.php
     */
    public function getFile(string $name): ?\Minz\File
    {
        $value = $this->get($name);

        if (!is_array($value)) {
            return null;
        }

        $tmp_name = $value['tmp_name'] ?? '';
        $error = $value['error'] ?? -1;
        $name = $value['name'] ?? '';

        if (!is_string($tmp_name) || !is_int($error) || !is_string($name)) {
            return null;
        }

        $file_info = [
            'tmp_name' => $tmp_name,
            'error' => $error,
            'name' => $name,
        ];

        if (isset($value['is_uploaded_file']) && is_bool($value['is_uploaded_file'])) {
            $file_info['is_uploaded_file'] = $value['is_uploaded_file'];
        };

        try {
            return new File($file_info);
        } catch (Errors\RuntimeException $e) {
            return null;
        }
    }
}
