# Getting started: setup a project

So you want to use or learn Minz? In this chapter, I’ll show you how to setup
your first project. I prefer to warn you: there are a lot to do manually, but
in the end, Minz should be very helpful.

In this guide, I’ll assume that you use Linux, that you understand basic
command line instructions and that you’ve already setup your Web development
environment with PHP 8.2+. Are we good?

In this chapter, we’ll simply learn how to display (and test!) “Hello World!”
when opening the home page.

## A word about Minz

You might ask: “Why should I use a framework?” If you don’t have the answer, I
recommend you to NOT use Minz. **In general, I recommend to not complexify your
setup if you don’t need it.**

I designed Minz to be used for small to medium applications that need a minimal
architecture. Its value lies in its simplicity. You can have a look at [the
source code](/src), there are very few files and I think they are easy to read
and hack. **Please note I’ll often invite you to explore the source code. If
you don’t explore the code of Minz, you probably use it the wrong way.**

In the rest of this chapter, I’ll show you the basic operation of Minz:
**transforming the browser requests into Minz `Response` via a controller
action.** In the end, you’ll also learn how to test controller actions to
be sure they always do what you want.

The classes featured in this chapter are: `\Minz\Request`, `\Minz\Response`,
`\Minz\Router`, `\Minz\Configuration`.

## Setup Composer

It is expected that you use [Composer](https://getcomposer.org/).

Make sure to have a minimal `composer.json` file:

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },

    "require": {
        "php": "^8.2"
    }
}
```

## Serving the index file

First, create a new folder for this project with a basic home page:

```console
$ mkdir /var/www/hello/
$ cd /var/www/hello/
$ # from now on, I'll always assume that you current directory is /var/www/hello!
$ mkdir public/
$ echo '<?php echo "Hello World!";' > public/index.php
```

Make sure your webserver is configured to serve the `public/` folder as the
root folder. For instance, my NGINX server is configured as follow:

```nginx
server {
    listen       80;
    listen       [::]:80;
    server_name  localhost;
    root         /var/www/hello/public;

    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php$is_args$query_string;
    }

    location ~ index.php$ {
        fastcgi_pass unix:/run/php-fpm/www.sock;

        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
}
```

You can adapt to your own needs, but keep in mind the `location` sections are
important to get right. The idea is to try to serve existing files under the
`public/` folder, and if there’s no matching file, to serve the URL with the
`index.php` file.

Don’t forget to reload your webserver, e.g. with systemd and Nginx:

```console
$ sudo systemctl reload nginx
```

If your configuration is correct, you should see `Hello World!` welcoming you
when you open a Web browser at [localhost/test](http://localhost/test).
Common mistakes involve a bad webserver configuration, or wrong file
permissions (i.e. the `www-data` or `nginx` user not being able to access the
files).

Once you have this right, we’ll setup Minz.

## Installing Minz

First, create some more folders:

```console
$ mkdir configuration/
$ mkdir src/
$ mkdir tests/
```

Then, install Minz with [Composer](https://getcomposer.org):

```console
$ composer require flus/minz
```

Finally, you can load Minz in your `public/index.php` file:

```php
<?php

$app_path = realpath(__DIR__ . '/..');

require $app_path . '/vendor/autoload.php';

\Minz\Configuration::load('development', $app_path);

echo 'Hello World!';
```

If you open [localhost](http://localhost/), you should see an error (or a blank
page). It tells you Minz wasn’t able to load the configuration file. Let’s
create `configuration/environment_development.php`:

```php
<?php

return [
    'secret_key' => 'a secret key',

    'url_options' => [
        'host' => 'localhost',
    ],
];
```

This is the most basic configuration file that you can write. Now, you should
see the “Hello World!” again. The `Configuration` class allows you to configure
a lot of things like the database and mailer information, some paths, or even
your application configuration.

**To learn more, [explore the `\Minz\Configuration` class](/src/Configuration.php).**

## Introducing Requests

Minz provides a class to abstract the HTTP requests: `\Minz\Request`. It
contains the parameters (e.g. `GET` and `POST` parameters), as well as the
headers, cookies and server information. You have to initialize the request
only once in your index file, then it will be passed to the different Minz
components. **Abstracting the requests makes it a lot easier to write tests.**

For now, I’ll just show you how to use it to change the word “World” by a
parameter passed via a `GET` parameter. Add the following lines after the
environment initialization:

```php
<?php

// ...

$http_method = strtoupper($_SERVER['REQUEST_METHOD']); // e.g. 'GET' or 'POST'
$http_uri = $_SERVER['REQUEST_URI']; // e.g. '/test'
$http_parameters = $_GET;

$request = new \Minz\Request($http_method, $http_uri, $http_parameters);

// The second argument is the default value to return. If it wasn't given, the
// method would return `null` when the `name` parameter is missing.
$name = $request->parameters->getString('name', 'World');
echo "Hello {$name}!";
```

You can notice the difference by opening [localhost](http://localhost/) and
[localhost/?name=Charlie](http://localhost/?name=Charlie).

Now, you might think it’s tedious and prone to errors to initialize the Request
manually. That’s why there’s a method for that: `initFromGlobals()`.
Internally, it does essentially what we saw above.

```php
<?php

// ...

$request = \Minz\Request::initFromGlobals();

$name = $request->parameters->getString('name', 'World');
echo "Hello {$name}!";
```

**To learn more, [explore the `\Minz\Request` class](/src/Request.php).**

## Introducing Responses

Minz abstracts the HTTP responses the same way as the requests but with a
different class: `\Minz\Response`. To build a Response, you have to set a HTTP
status (e.g. 200, 404), some content to display and optional additional HTTP
headers.

This class is generally instantiated by a controller action and used by the
index file. For now, we’ll instantiate it in the index file as well. We’ll tell
it to return a `text` response:

```php
<?php

// ...

$request = \Minz\Request::initFromGlobals();

$name = $request->parameters->getString('name', 'World');

$response = \Minz\Response::text(200, "Hello {$name}!");

// we declare the HTTP headers to the browser
http_response_code($response->code());
foreach ($response->headers() as $header) {
    header($header);
}

// and we display the page content
echo $response->render();
```

If you open the website again, you’ll notice the text is rendered differently.
This is because we’re now returning text and not HTML (i.e. Minz generates a
`Content-Type: text/plain` header). In fact, the `text()` method is usually
used with command line actions. We’ll fix that in a future chapter.

As for Requests, it’s possible to shorten the code to send the Response to the
client:

```php
<?php

// ...

$request = \Minz\Request::initFromGlobals();

$name = $request->parameters->getString('name', 'World');

$response = \Minz\Response::text(200, "Hello {$name}!");

\Minz\Response::sendByHttp($response);
```

**To learn more, [explore the `\Minz\Response` class](/src/Response.php).**

## Introducing routing and controllers

We’ve seen the two most important classes of Minz: `Request` and `Response`.
These classes allow us to test Minz applications easily. I’ll explain you how
to test in the next section, but for now we’ll move the code that we want to
test in a dedicated controller action.

A controller is a class dedicated to a specific part of your application,
usually a ressource (e.g. users or posts). The controllers methods are called
“actions”. They are dedicated to a specific activity such as listing users,
creation or deletion. **An action always takes a `\Minz\Request` in parameter
and returns a `\Minz\Response`.** Finally, an action is associated to a couple
of a HTTP verb (e.g. `GET`, `POST`) and an URL (e.g. `/posts`); it's called the
“routing”.

**The previous paragraph contains a lot of important information. I recommend
you to take the time to understand each sentence.** If it’s still too abstract
for you, don’t worry: the rest of this section will show you how all these
things are working.

First, let’s create a controller with a single action. Create a new file named
`src/Home.php`.

```php
<?php

namespace App;

// The Home class is our controller
class Home
{
    // and show() is an action of this controller, it takes a $request
    public function show($request)
    {
        // the code of the action is simply what we wrote earlier in the index
        // page
        $name = $request->parameters->getString('name', 'World');
        $response = \Minz\Response::text(200, "Hello {$name}!");

        // and it returns a $response
        return $response;
    }
}
```

We just wrote our first controller action. The last thing to do is to associate
it to a couple of HTTP verb and URL: it’s time to introduce the routing
mechanism of Minz! Routing is handled by the `\Minz\Router` class. Where should
you declare the routes? It’s mainly up to you: you could do it in the index
file for instance. However, Minz comes with a convention to make it easier to
write tests: let’s write a new `src/Application.php` file.

```php
<?php

namespace App;

class Application
{
    public function run($request)
    {
        // This is where we’ll declare our routes
        $router = new \Minz\Router();

        // We create a new route to connect the URL `GET /` to our action.
        // The syntax is `controller#action`.
        $router->addRoute('GET', '/', 'Home#show');

        \Minz\Engine::init($router);

        return \Minz\Engine::run($request);
    }
}
```

The `Application` constructor should take no required parameter and should
declare a `run()` method taking a `$request` as unique parameter and returning
a `$response`. This is pure convention, you can choose to architecture your
application differently, but keep in mind it allows you to test your
controllers more easily.

We’re almost done: all we have to do is to use the `Application` class in our
index file:

```php

// ...

$application = new \App\Application();

$request = \Minz\Request::initFromGlobals();

$response = $application->run($request);

\Minz\Response::sendByHttp($response);
```

You can test that the application still work by opening again [localhost](http://localhost/)
and [localhost/?name=Charlie](http://localhost/?name=Charlie). More important,
if you open [localhost/test](http://localhost/test), you should get an error
telling you that “Path "GET /test" doesn’t match any route.” It’s because we
didn’t create the corresponding route in the router. The error is ugly for
now, but we’ll fix that later.

As a lot of different errors can occur during the runtime, it's advised to wrap
the code in a try / catch block:

```php

// ...

try {
    $application = new \App\Application();

    $request = \Minz\Request::initFromGlobals();

    $response = $application->run($request);
} catch (\Minz\Errors\RequestError $e) {
    $response = \Minz\Response::notFound('not_found.phtml', [
        'error' => $e,
    ]);
} catch (\Exception $e) {
    $response = \Minz\Response::internalServerError('internal_server_error.phtml', [
        'error' => $e,
    ]);
}

\Minz\Response::sendByHttp($response);
```

**To learn more, [explore the `\Minz\Router` class](/src/Router.php).**

## Testing controllers

We’re almost at the end of this chapter! In this last section, I want to teach
you how to test controllers actions. You could probably develop with Minz
without learning that, but I strongly believe that tests should not be
considered as secondary work; this is why I’ll introduce you to these in this
first chapter.

Tests will be written with [PHPUnit](https://phpunit.readthedocs.io). Let’s
install it first with Composer:

```console
$ composer require --dev phpunit/phpunit
```

Let’s write a `tests/bootstrap.php` file, it will be loaded by PHPUnit:

```php
<?php

$app_path = realpath(__DIR__ . '/..');

require $app_path . '/vendor/autoload.php';
\Minz\Configuration::load('test', $app_path);
```

Note how similar it is compared to the beginning of the index file. You might
have guessed we now need a new configuration file. We can simply copy the
existing one:

```console
$ cp configuration/environment_development.php configuration/environment_test.php
```

It’s now time to write our first tests in `tests/HomeTest.php`:

```php
<?php

namespace App;

class HomeTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowReturnsOkStatusCode()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponseCode($response, 200);
    }

    public function testShowReturnsContentTypeText()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function testShowRendersHelloWorld()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponseEquals($response, 'Hello World!');
    }

    public function testShowRendersGivenParameterName()
    {
        $name = 'Charlie';

        $response = $this->appRun('GET', '/', [
            'name' => $name,
        ]);

        $this->assertResponseEquals($response, "Hello {$name}!");
    }
}
```

To execute the tests and check that everything goes fine, run the following
command:

```console
$ ./vendor/bin/phpunit --bootstrap ./tests/bootstrap.php --testdox ./tests/
```

The tests are pretty standard for PHPUnit tests except this `appRun` method and
custom assertions. You may have noticed these two lines:

```php
use \Minz\Tests\ApplicationHelper;
use \Minz\Tests\ResponseAsserts;
```

This is where all the magic happens: they add methods to the `HomeTest` class
in order to facilitate our tests.

Remember when I told you that the `Application` class was just a convention? It
was to allow the `appRun` method provided by the `ApplicationHelper` class to
work. It does a bit of magic by loading the application from `\App\Application`,
creating a `\Minz\Request` from the parameters that you give to it, and
executing it against the `run` method of `Application`. Finally it returns the
`\Minz\Response` returned by the controller action. The beauty of this method
is that you can test almost all the stack of your application very easily
(including the routing).

**To learn more, [explore the `\Minz\Tests\ApplicationHelper` class](/src/Tests/ApplicationHelper.php).**

The `ResponseAsserts`, for its part, provides a bunch of assertions to test the
returned response. I personnaly use them **a lot**.

**To learn more, [explore the `\Minz\Tests\ResponseAsserts` class](/src/Tests/ResponseAsserts.php).**

A note about magic: I usually don’t like magic in the code too much ([“explicit
is better than implicit”](https://www.python.org/dev/peps/pep-0020/#the-zen-of-python)).
However, I sometimes like to add a pinch of magic in order to enhance the
experience of developers. I always do my best to make it optional so you are
free to adopt whatever philosophy you prefer. In this case, it would totally be
possible to instantiate the `Request` and `Application` manually. However, I
prefer my tests to contain the bare minimum information to make them easier
to be read.

## Conclusion

In this chapter, we’ve seen the most important components of Minz. I recommend
you to be sure you understood every bit of it before going any further.

**To summarise, Minz abstracts HTTP requests with the `\Minz\Request` class,
and HTTP responses with the `\Minz\Response` class. It transfers a request to a
controller action via the routing mechanism (i.e. `\Minz\Router`). The action
returns a response which is then outputed by the index file. Finally, Minz
provides a bunch of useful methods to help you to test your controllers
easily.**

Next steps to learn Minz:

- get [an overview of its classes](/docs/overview.md);
- read [its source code](/src) and get used to it;
- read the source code of [the applications](/README.md#minz-in-the-real-world)
  that I wrote with Minz;
- if you have any question, [open an issue on GitHub](https://github.com/flusio/Minz/issues).
