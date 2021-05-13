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
     * Assert that a Response is matching the given conditions (deprecated).
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
     * Assert that a Response code is matching the given one.
     *
     * @param \Minz\Response $response
     * @param integer $code The HTTP code that the response must match with
     * @param string $location If code is 301 or 302, the location must match this variable
     */
    public function assertResponseCode($response, $code, $location = null)
    {
        $this->assertEquals($code, $response->code());

        if ($location !== null && ($code === 301 || $code === 302)) {
            $response_headers = $response->headers(true);
            $this->assertEquals($location, $response_headers['Location']);
        }
    }

    /**
     * Assert that a Response output equals to the given string
     *
     * @param \Minz\Response $response
     * @param string $string The string the output must equal
     */
    public function assertResponseEquals($response, $string)
    {
        $output = $response->render();
        $this->assertEquals($string, $output);
    }

    /**
     * Assert that a Response output contains the given string
     *
     * @param \Minz\Response $response
     * @param string $string The string the output must contain
     */
    public function assertResponseContains($response, $string)
    {
        $output = $response->render();
        $this->assertStringContainsString($string, $output);
    }

    /**
     * Assert that a Response output doesn’t contain the given string
     *
     * @param \Minz\Response $response
     * @param string $string The string the output must not contain
     */
    public function assertResponseNotContains($response, $string)
    {
        $output = $response->render();
        $this->assertStringNotContainsString($string, $output);
    }

    /**
     * Assert that a Response declares the given headers.
     *
     * @param \Minz\Response $response
     * @param string[] $headers
     */
    public function assertResponseHeaders($response, $headers)
    {
        // I would use assertArraySubset, but it's deprecated in PHPUnit 8
        // and will be removed in PHPUnit 9.
        $response_headers = $response->headers(true);
        foreach ($headers as $header => $value) {
            $this->assertArrayHasKey($header, $response_headers);
            $this->assertEquals($value, $response_headers[$header]);
        }
    }

    /**
     * Alias for assertResponseHeaders (deprecated).
     */
    public function assertHeaders($response, $headers)
    {
        $this->assertResponseHeaders($response, $headers);
    }

    /**
     * Assert that a Response output is set with the given pointer.
     *
     * @param \Minz\Response $response
     * @param string $expected_pointer
     */
    public function assertResponsePointer($response, $expected_pointer)
    {
        $pointer = $response->output()->pointer();
        $this->assertEquals($expected_pointer, $pointer);
    }

    /**
     * Alias for assertResponsePointer (deprecated).
     */
    public function assertPointer($response, $expected_pointer)
    {
        $this->assertResponsePointer($response, $expected_pointer);
    }
}
