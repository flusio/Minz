# An overview of Minz classes

It might be intimidating to dig into the source code of a framework. This
chapter is here to guide you. Hopefully, there’s not too many components in
Minz so we’ll soon have covered it all.

Note that I only make quick presentations here. You’ll have to read the code
and the comments to learn how to use these classes.

**I have highlighted the classes that I believe are the most important of
Minz.** You’ll often have to interact with them.

## Core classes

- **[`\Minz\Configuration`](/src/Configuration.php) loads and stores your
  configuration;**
    - [`\Minz\Dotenv`](/src/Dotenv.php) is often used in configuration files,
      to load environment variables;
    - [`\Minz\Environment`](/src/Environment.php) is initialized at the same
      time as configuration to setup logs, error reporting and session;
- **[`\Minz\Request`](/src/Request.php) abstracts HTTP or CLI requests;**
- **[`\Minz\Response`](/src/Response.php) abstracts HTTP or CLI responses,** it
  embeds a [`\Minz\Output\Output`](/src/Output/Output.php) (interface) which is
  in charge of rendering the content. Here is the list of available outputs:
    - **[`\Minz\Output\View`](/src/Output/View.php) is a simple template system to render your content,**
      often used for (but not limited to) HTML;
    - [`\Minz\Output\Text`](/src/Output/Text.php) renders text;
    - [`\Minz\Output\File`](/src/Output/File.php) renders files;
- **[`\Minz\Router`](/src/Router.php) is in charge of the routing of your
  application;**
- **[`\Minz\Url`](/src/Url.php) renders the URLs of your application, using the
  information from the router;**
- [`\Minz\Engine`](/src/Engine.php) does the plumbing of your application,
  executing a controller action based on a given request and returning its
  response;

**To learn how to use these classes, [read the “getting started” guide.](/docs/getting_started.md)**

## Models and database

- **[`\Minz\Model`](/src/Model.php) represents resources of your application,** it
  helps you to declare their properties and their constraints;
- **[`\Minz\DatabaseModel`](/src/DatabaseModel.php) represents a resource in
  database** and provides some helpful methods to access the database;
- [`\Minz\Database`](/src/Database.php) abstracts the connection to the
  database, it acts as a wrapper around the PHP [\PDO class](https://www.php.net/manual/book.pdo.php);
- [`\Minz\Migrator`](/src/Migrator.php) manages migrations of your data (not
  only from the database!)

## Additional classes

- **[`\Minz\CSRF`](/src/CSRF.php) protects you from [Cross-Site Request
  Forgery attacks](https://en.wikipedia.org/wiki/Cross-site_request_forgery);**
- **[`\Minz\Log`](/src/Log.php) logs errors and information to [syslog](https://en.wikipedia.org/wiki/Syslog);**
- [`\Minz\Time`](/src/Time.php) abstracts the time;
- [`\Minz\Mailer`](/src/Mailer.php) is a wrapper around [PHPMailer](/lib/PHPMailer)
  to send emails;
- [`\Minz\File`](/src/File.php) abstracts uploaded files;

The three last classes are very useful during tests!

## Tests

Finally, Minz provides a bunch of helpers and assertions for [PHPUnit](https://phpunit.readthedocs.io/):

- **[`\Minz\Tests\ApplicationHelper`](/src/Tests/ApplicationHelper.php) allows
  you to execute a request against your application and get its response in a
  single line of code;**
- **[`\Minz\Tests\InitializerHelper`](/src/Tests/InitializerHelper.php)
  reinitializes the database, session and mailer before each tests;**
- **[`\Minz\Tests\ResponseAsserts`](/src/Tests/ResponseAsserts.php) provides a
  bunch of assertions to test the response of your application;**
- [`\Minz\Tests\DatabaseFactory`](/src/Tests/DatabaseFactory.php) creates fake
  data during tests, via the [`\Minz\Tests\FactoriesHelper`](/src/Tests/FactoriesHelper.php)
  helper;
- [`\Minz\Tests\TimeHelper`](/src/Tests/TimeHelper.php) freezes the time, on
  the condition that you use the Time class;
- [`\Minz\Tests\Mailer`](/src/Tests/Mailer.php) catches emails sent by the
  mailer and allows you to test them with the assertions provided by
  [`\Minz\Tests\MailerAsserts`](/src/Tests/MailerAsserts.php);
- [`\Minz\Tests\FilesHelper`](/src/Tests/FilesHelper.php) provides a method to
  copy files in a temporary directory in order to simulate an upload to your
  application.
