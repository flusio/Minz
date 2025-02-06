<?php

// This file is part of Minz.
// Copyright 2020-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Minz\Tests;

use Minz\Response;

/**
 * Provide some assert methods to help to test the response.
 *
 * @see \Minz\Response
 *
 * @phpstan-import-type ResponseHttpCode from Response
 *
 * @phpstan-import-type ResponseHeaders from Response
 *
 * @phpstan-import-type ResponseReturnable from Response
 *
 * @phpstan-import-type ViewPointer from \Minz\Output\View
 */
trait ResponseAsserts
{
    /**
     * Assert that a Response code is matching the given one.
     *
     * A location can be given to test the redirections destinations.
     *
     * @param ResponseReturnable $response
     * @param ResponseHttpCode $code
     */
    public function assertResponseCode(mixed $response, int $code, ?string $location = null): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $this->assertEquals($code, $response->code());

        if ($location !== null && ($code === 301 || $code === 302)) {
            $response_headers = $response->headers(true);
            $this->assertEquals($location, $response_headers['Location']);
        }
    }

    /**
     * Assert that a Response output equals to the given string
     *
     * @param ResponseReturnable $response
     */
    public function assertResponseEquals(mixed $response, string $output): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $this->assertEquals($output, $response->render());
    }

    /**
     * Assert that a Response output contains the given string
     *
     * @param ResponseReturnable $response
     */
    public function assertResponseContains(mixed $response, string $output): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $this->assertStringContainsString($output, $response->render());
    }

    /**
     * Assert that a Response output contains the given string (ignoring
     * differences in casing).
     *
     * @param ResponseReturnable $response
     */
    public function assertResponseContainsIgnoringCase(mixed $response, string $output): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $this->assertStringContainsStringIgnoringCase($output, $response->render());
    }

    /**
     * Assert that a Response output doesn’t contain the given string
     *
     * @param ResponseReturnable $response
     */
    public function assertResponseNotContains(mixed $response, string $output): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $this->assertStringNotContainsString($output, $response->render());
    }

    /**
     * Assert that a Response declares the given headers.
     *
     * @param ResponseReturnable $response
     * @param ResponseHeaders $headers
     */
    public function assertResponseHeaders(mixed $response, array $headers): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $response_headers = $response->headers(true);
        foreach ($headers as $header => $value) {
            $this->assertArrayHasKey($header, $response_headers);
            $this->assertEquals($value, $response_headers[$header]);
        }
    }

    /**
     * Assert that a Response output is set with the given pointer.
     *
     * @param ResponseReturnable $response
     * @param ViewPointer $expected_pointer
     */
    public function assertResponsePointer(mixed $response, string $expected_pointer): void
    {
        if ($response instanceof \Generator) {
            $response = $response->current();
        }

        $output = $response->output();
        $this->assertNotNull($output, 'Response has no output');
        $this->assertTrue(is_callable([$output, 'pointer']), 'Response has no pointer');
        $pointer = $output->pointer();
        $this->assertEquals($expected_pointer, $pointer);
    }
}
