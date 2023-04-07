<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testSetCode(): void
    {
        $response = new Response(200);

        $response->setCode(404);

        $this->assertSame(404, $response->code());
    }

    public function testSetCodeFailsIfCodeIsInvalid(): void
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        $response = new Response(200);

        // @phpstan-ignore-next-line
        $response->setCode(666);
    }

    public function testSetHeader(): void
    {
        $response = new Response(200);

        $response->setHeader('Content-Type', 'application/xml');

        $headers = $response->headers(true);
        $this->assertSame('application/xml', $headers['Content-Type']);
    }

    public function testSetContentSecurityPolicy(): void
    {
        $response = new Response(200);

        $response->setContentSecurityPolicy('script-src', "'self' 'unsafe-eval'");

        $headers = $response->headers(true);
        /** @var array<string, string> $csp */
        $csp = $headers['Content-Security-Policy'];
        $this->assertArrayHasKey('script-src', $csp);
        $this->assertSame("'self' 'unsafe-eval'", $csp['script-src']);
    }

    public function testConstructor(): void
    {
        $view = new Output\View('rabbits/items.phtml');
        $response = new Response(200, $view);

        $this->assertSame(200, $response->code());
        $this->assertSame([
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => [
                'default-src' => "'self'",
            ]
        ], $response->headers(true));
    }

    public function testConstructorAdaptsTheContentTypeFromView(): void
    {
        $view = new Output\View('rabbits/items.txt');
        $response = new Response(200, $view);

        $headers = $response->headers(true);
        $this->assertSame('text/plain', $headers['Content-Type']);
    }

    public function testConstructorAcceptsNoViews(): void
    {
        $response = new Response(200, null);

        $this->assertSame(200, $response->code());
        $headers = $response->headers(true);
        $this->assertSame('text/plain', $headers['Content-Type']);
    }

    public function testConstructorFailsIfInvalidCode(): void
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        // @phpstan-ignore-next-line
        $response = new Response(666);
    }

    public function testHeaders(): void
    {
        $response = new Response(200);
        $response->setHeader('Content-Type', 'image/png');

        /** @var string[] $headers */
        $headers = $response->headers();

        $content_type_header = current(array_filter($headers, function ($header) {
            return strpos($header, 'Content-Type') === 0;
        }));
        $this->assertSame('Content-Type: image/png', $content_type_header);
    }

    public function testHeadersWithComplexStructure(): void
    {
        $response = new Response(200);
        $response->setHeader('Content-Security-Policy', [
            'default-src' => "'self'",
            'style-src' => "'self' 'unsafe-inline'",
        ]);

        /** @var string[] $headers */
        $headers = $response->headers();

        $csp_header = current(array_filter($headers, function ($header) {
            return strpos($header, 'Content-Security-Policy') === 0;
        }));
        $this->assertSame(
            "Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'",
            $csp_header
        );
    }

    public function testSetCookie(): void
    {
        $response = new Response(200);

        $response->setCookie('foo', 'bar');

        $cookie = $response->cookies()['foo'];
        $this->assertSame('foo', $cookie['name']);
        $this->assertSame('bar', $cookie['value']);
        $this->assertSame([
            'expires' => 0,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict',
        ], $cookie['options']);
    }

    public function testSetCookieWithExpires(): void
    {
        $response = new Response(200);
        $expires = Time::fromNow(1, 'month')->getTimestamp();

        $response->setCookie('foo', 'bar', [
            'expires' => $expires,
        ]);

        $cookie = $response->cookies()['foo'];
        $this->assertSame($expires, $cookie['options']['expires']);
    }

    public function testSetCookieWithProductionConfiguration(): void
    {
        $old_url_options = Configuration::$url_options;
        Configuration::$url_options['host'] = 'mydomain.com';
        Configuration::$url_options['protocol'] = 'https';
        $response = new Response(200);

        $response->setCookie('foo', 'bar');

        Configuration::$url_options = $old_url_options;
        $cookie = $response->cookies()['foo'];
        $this->assertSame('foo', $cookie['name']);
        $this->assertSame('bar', $cookie['value']);
        $this->assertSame([
            'expires' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict',
            'domain' => 'mydomain.com',
        ], $cookie['options']);
    }

    public function testRemoveCookie(): void
    {
        $response = new Response(200);

        $response->removeCookie('foo');

        $cookie = $response->cookies()['foo'];
        $this->assertSame('foo', $cookie['name']);
        $this->assertSame('', $cookie['value']);
        $expires = $cookie['options']['expires'];
        $this->assertTrue($expires < Time::now()->getTimestamp());
    }

    public function testOk(): void
    {
        $response = Response::ok();

        $this->assertSame(200, $response->code());
    }

    public function testCreated(): void
    {
        $response = Response::created();

        $this->assertSame(201, $response->code());
    }

    public function testAccepted(): void
    {
        $response = Response::accepted();

        $this->assertSame(202, $response->code());
    }

    public function testNoContent(): void
    {
        $response = Response::noContent();

        $this->assertSame(204, $response->code());
    }

    public function testMovedPermanently(): void
    {
        $response = Response::movedPermanently('https://example.com');

        $this->assertSame(301, $response->code());
        $headers = $response->headers(true);
        $this->assertSame('https://example.com', $headers['Location']);
    }

    public function testFound(): void
    {
        $response = Response::found('https://example.com');

        $this->assertSame(302, $response->code());
        $headers = $response->headers(true);
        $this->assertSame('https://example.com', $headers['Location']);
    }

    public function testRedirect(): void
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#items');
        Url::setRouter($router);

        $response = Response::redirect('rabbits#items');

        $this->assertSame(302, $response->code());
        $headers = $response->headers(true);
        $this->assertSame('/rabbits', $headers['Location']);
    }

    public function testBadRequest(): void
    {
        $response = Response::badRequest();

        $this->assertSame(400, $response->code());
    }

    public function testUnauthorized(): void
    {
        $response = Response::unauthorized();

        $this->assertSame(401, $response->code());
    }

    public function testNotFound(): void
    {
        $response = Response::notFound();

        $this->assertSame(404, $response->code());
    }

    public function testInternalServerError(): void
    {
        $response = Response::internalServerError();

        $this->assertSame(500, $response->code());
    }

    public function testText(): void
    {
        $response = Response::text(200, 'Foo bar');

        $this->assertSame(200, $response->code());
        $this->assertSame('Foo bar', $response->render());
    }

    public function testJson(): void
    {
        $response = Response::json(200, [
            'foo' => 'bar',
        ]);

        $this->assertSame(200, $response->code());
        $this->assertSame('{"foo":"bar"}', $response->render());
        $headers = $response->headers(true);
        $this->assertSame('application/json', $headers['Content-Type']);
    }

    public function testRender(): void
    {
        $rabbits = [
            'Bugs',
            'Clémentine',
            'Jean-Jean',
        ];
        $response = Response::ok('rabbits/items.phtml', [
            'rabbits' => $rabbits,
        ]);

        $output = $response->render();

        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testRenderWithEmptyViewPointer(): void
    {
        $response = Response::ok(null);

        $output = $response->render();

        $this->assertSame('', $output);
    }
}
