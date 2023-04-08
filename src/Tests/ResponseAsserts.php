<?php

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
 * @phpstan-import-type ViewPointer from \Minz\Output\View
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait ResponseAsserts
{
    /**
     * Assert that a Response code is matching the given one.
     *
     * A location can be given to test the redirections destinations.
     *
     * @param ResponseHttpCode $code
     */
    public function assertResponseCode(Response $response, int $code, ?string $location = null): void
    {
        $this->assertEquals($code, $response->code());

        if ($location !== null && ($code === 301 || $code === 302)) {
            $response_headers = $response->headers(true);
            $this->assertEquals($location, $response_headers['Location']);
        }
    }

    /**
     * Assert that a Response output equals to the given string
     */
    public function assertResponseEquals(Response $response, string $output): void
    {
        $this->assertEquals($output, $response->render());
    }

    /**
     * Assert that a Response output contains the given string
     */
    public function assertResponseContains(Response $response, string $output): void
    {
        $this->assertStringContainsString($output, $response->render());
    }

    /**
     * Assert that a Response output contains the given string (ignoring
     * differences in casing).
     */
    public function assertResponseContainsIgnoringCase(Response $response, string $output): void
    {
        $this->assertStringContainsStringIgnoringCase($output, $response->render());
    }

    /**
     * Assert that a Response output doesn’t contain the given string
     */
    public function assertResponseNotContains(Response $response, string $output): void
    {
        $this->assertStringNotContainsString($output, $response->render());
    }

    /**
     * Assert that a Response declares the given headers.
     *
     * @param ResponseHeaders $headers
     */
    public function assertResponseHeaders(Response $response, array $headers): void
    {
        $response_headers = $response->headers(true);
        foreach ($headers as $header => $value) {
            $this->assertArrayHasKey($header, $response_headers);
            $this->assertEquals($value, $response_headers[$header]);
        }
    }

    /**
     * Assert that a Response output is set with the given pointer.
     *
     * @param ViewPointer $expected_pointer
     */
    public function assertResponsePointer(Response $response, string $expected_pointer): void
    {
        $output = $response->output();
        $this->assertNotNull($output, 'Response has no output');
        $this->assertTrue(is_callable([$output, 'pointer']), 'Response has no pointer');
        $pointer = $output->pointer();
        $this->assertEquals($expected_pointer, $pointer);
    }
}
