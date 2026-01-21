<?php

// This file is part of Minz.
// Copyright 2020-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Validable;

/**
 * Check that a string property is a valid URL.
 *
 *     use Minz\Validable;
 *
 *     class User
 *     {
 *         use Validable;
 *
 *         #[Validable\Url(message: 'Enter a valid website URL.')]
 *         public string $website;
 *     }
 *
 * The URL must contain at least a scheme (either http or https) and a host.
 *
 * Note that the "null" and empty values are considered as valid in order to
 * accept optional values.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Url extends PropertyCheck
{
    /** @var string[] */
    public array $schemes;

    /**
     * @param string[] $schemes
     */
    public function __construct(string $message, array $schemes = ['http', 'https'])
    {
        parent::__construct($message);
        $this->schemes = array_map('strtolower', $schemes);
    }

    public function assert(): bool
    {
        $value = $this->value();

        if ($value === null || $value === '') {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $url_components = parse_url($value);

        if (
            !$url_components ||
            !isset($url_components['scheme']) ||
            !isset($url_components['host'])
        ) {
            return false;
        }

        $url_scheme = strtolower($url_components['scheme']);
        return in_array($url_scheme, $this->schemes);
    }
}
