<?php

namespace Minz\Tests;

/**
 * Provide some assert methods to help to test the response.
 *
 * @see \Minz\Response
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait ResponseAsserts
{
    /**
     * Assert that a Response is matching the given conditions.
     *
     * @param \Minz\Response $response
     * @param integer $code The HTTP code that the response must match with
     * @param string $output The rendered output that the response must match
     *                       with (optional), if code is 301 or 302, it will
     *                       check the Location header instead
     */
    public function assertResponse($response, $code, $output = null)
    {
        $response_output = $response->render();
        $this->assertSame($code, $response->code(), 'Output is: ' . $response_output);

        if ($output !== null && ($code === 301 || $code === 302)) {
            $response_headers = $response->headers(true);
            $this->assertSame($output, $response_headers['Location']);
        } elseif ($output !== null) {
            $this->assertStringContainsString($output, $response_output);
        }
    }

    /**
     * Assert that a Response declares the given headers.
     *
     * @param \Minz\Response $response
     * @param string[] $headers
     */
    public function assertHeaders($response, $headers)
    {
        // I would use assertArraySubset, but it's deprecated in PHPUnit 8
        // and will be removed in PHPUnit 9.
        $response_headers = $response->headers(true);
        foreach ($headers as $header => $value) {
            $this->assertArrayHasKey($header, $response_headers);
            $this->assertSame($value, $response_headers[$header]);
        }
    }

    /**
     * Assert that a Response output is set with the given pointer.
     *
     * @param \Minz\Response $response
     * @param string $expected_pointer
     */
    public function assertPointer($response, $expected_pointer)
    {
        $pointer = $response->output()->pointer();
        $this->assertSame($expected_pointer, $pointer);
    }
}
