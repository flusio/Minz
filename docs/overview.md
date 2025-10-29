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
    - [`\Minz\Dotenv`](/src/Dotenv.php) is often used in configuration files, to load environment variables;
- **[`\Minz\Request`](/src/Request.php) abstracts HTTP or CLI requests;**
- **[`\Minz\Response`](/src/Response.php) abstracts HTTP or CLI responses,** it embeds a [`\Minz\Output`](/src/Output.php) (interface) which is in charge of rendering the content. Here is the list of available outputs:
    - **[`\Minz\Output\Template`](/src/Output/Template.php) renders content (e.g. HTML) using a template system:**
        - [`\Minz\Template\Twig`](/src/Template/Twig.php) is [a full-featured template system](https://twig.symfony.com/);
        - [`\Minz\Template\Simple`](/src/Template/Simple.php) is a simple template system based on PHP tags;
    - [`\Minz\Output\Json`](/src/Output/Json.php) renders Json;
    - [`\Minz\Output\Text`](/src/Output/Text.php) renders text;
    - [`\Minz\Output\File`](/src/Output/File.php) renders files;
- **[`\Minz\Router`](/src/Router.php) is in charge of the routing of your application;**
- **[`\Minz\Url`](/src/Url.php) renders the URLs of your application, using the information from the router;**
- [`\Minz\Engine`](/src/Engine.php) does the plumbing of your application, executing a controller action based on a given request and returning its response;

**To learn how to use these classes, [read the “getting started” guide.](/docs/getting_started.md)**

## Models and database

- **[`\Minz\Database`](/src/Database.php) abstracts the connection to the database,** it acts as a wrapper around the PHP [\PDO class](https://www.php.net/manual/book.pdo.php).
- **[`\Minz\Database\Recordable`](/src/Database/Recordable.php) provides useful methods to manipulate the models in the database.** It is usable together with the following classes:
    - [`\Minz\Database\Table`](/src/Database/Table.php) to define the database table of the model;
    - [`\Minz\Database\Column`](/src/Database/Column.php) to define the database columns of the model;
    - [`\Minz\Database\Resource`](/src/Database/Resource.php) which provides methods to load easily the model with a Request parameter;
    - [`\Minz\Database\Factory`](/src/Database/Factory.php) to easily create models with default values during the tests;
    - [`\Minz\Database\Helper`](/src/Database/Helper.php) which provides useful methods to the Recordable trait;
    - [`\Minz\Database\Lockable`](/src/Database/Lockable.php) provides some methods to (un)lock a model;
- **[`\Minz\Migration\Migrator`](/src/Migration/Migrator.php) manages the migrations of your data,** it is used with [`\Minz\Migration\Controller`](/src/Migration/Controller.php);
- [`\Minz\Translatable`](/src/Translatable.php) to mark a `Validable` message to be translated.

## Emails and mailers

- **[`\Minz\Mailer`](/src/Mailer.php) allows to send emails.** It is used alongside with:
    - [`\Minz\Mailer\Email`](/src/Mailer/Email.php) is a wrapper around [the PHPMailer class](https://github.com/PHPMailer/PHPMailer/);
    - [`\Minz\Mailer\Job`](/src/Mailer/Job.php) allows to send emails asynchronously.

## Forms and validations

- **[`\Minz\Form`](/src/Form.php) provides an easy way to handle forms.** It is used alongside with:
    - **[`\Minz\Form\Field`](/src/Form/Field.php) to declare fields in the forms;**
    - **[`\Minz\Form\Csrf`](/src/Form/Csrf.php) to protect against CSRF attacks in the forms;**
    - [`\Minz\Form\CsrfToken`](/src/Form/CsrfToken.php) generates cryptographic-secure tokens to be used in forms;
    - [`\Minz\Form\OnHandleRequest`](/src/Form/OnHandleRequest.php) is an attribute to declare form methods to execute when handling a request;
- **[`\Minz\Validable`](/src/Validable.php) a trait to validate objects' properties values** (often used in models and forms). It is used alongside with `PropertyCheck`s and `Check`s:
    - [`\Minz\Validable\PropertyCheck`](/src/Validable/PropertyCheck.php) the base class extended by the other check classes applying to properties;
    - [`\Minz\Validable\Comparison`](/src/Validable/Comparison.php) allows to compare the values;
    - [`\Minz\Validable\Email`](/src/Validable/Email.php) allows to check email addresses;
    - [`\Minz\Validable\Format`](/src/Validable/Format.php) allows to check the format of a string;
    - [`\Minz\Validable\Inclusion`](/src/Validable/Inclusion.php) allows to check that a value is included in a given set;
    - [`\Minz\Validable\Length`](/src/Validable/Length.php) allows to check the min and/or max length of a string;
    - [`\Minz\Validable\Presence`](/src/Validable/Presence.php) allows to check that a property is present (not null nor an empty string);
    - [`\Minz\Validable\Unique`](/src/Validable/Unique.php) allows to check that a value is unique in database;
    - [`\Minz\Validable\Url`](/src/Validable/Url.php) allows to check URL addresses;
    - [`\Minz\Validable\Check`](/src/Validable/Check.php) allows to declare custom checks.

## Errors

- **[`\Minz\Errors\Exception`](/src/Errors/Exception.php) the base exception for all the Minz exceptions.**
    - [`\Minz\Errors\LogicException`](/src/Errors/LogicException.php)
        - [`\Minz\Errors\ActionError`](/src/Errors/ActionError.php) used when the action of controller doesn't behave as expected.
        - [`\Minz\Errors\ConfigurationError`](/src/Errors/ConfigurationError.php) used when the configuration is invalid.
        - [`\Minz\Errors\ControllerError`](/src/Errors/ControllerError.php) used when the expected controller doesn't exist
        - [`\Minz\Errors\DatabaseModelError`](/src/Errors/DatabaseModelError.php) used when accessing a database model incorrectly.
        - [`\Minz\Errors\InvalidMailerError`](/src/Errors/InvalidMailerError.php) used when calling an invalid Mailer.
        - [`\Minz\Errors\MigrationError`](/src/Errors/MigrationError.php) used when migrations are not correctly declared.
        - [`\Minz\Errors\OutputError`](/src/Errors/OutputError.php) used when an output cannot be initialized.
        - [`\Minz\Errors\ResponseError`](/src/Errors/ResponseError.php) used when a Response cannot be initialized.
        - [`\Minz\Errors\RoutingError`](/src/Errors/RoutingError.php) used when a Router isn't correctly configured.
        - [`\Minz\Errors\UrlError`](/src/Errors/UrlError.php) used when getting an url incorrectly.
    - [`\Minz\Errors\RuntimeException`](/src/Errors/RuntimeException.php)
        - [`\Minz\Errors\DatabaseError`](/src/Errors/DatabaseError.php) used when an error happens when connecting to a database.
        - [`\Minz\Errors\MailerError`](/src/Errors/MailerError.php) used when an error happens when sending an email.
        - [`\Minz\Errors\RequestError`](/src/Errors/RequestError.php) used when the request is incorrectly initialized.
        - [`\Minz\Errors\RouteNotFoundError`](/src/Errors/RouteNotFoundError.php) used when a request calls an unknown route.

## Additional classes

- **[`\Minz\Job`](/src/Job.php) to manage asynchronous jobs,** it is used with [`\Minz\Job\Controller`](/src/Job/Controller.php);
- **[`\Minz\Log`](/src/Log.php) logs errors and information to [syslog](https://en.wikipedia.org/wiki/Syslog);**
- [`\Minz\Controller\Handler`](/src/Controller/Handler.php) is a base class for various controller handlers, extended by:
    - [`\Minz\Controller\BeforeAction`](/src/Controller/BeforeAction.php) declares an handler that must be called before the controller action;
    - [`\Minz\Controller\AfterAction`](/src/Controller/AfterAction.php) declares an handler that must be called after the controller action;
    - [`\Minz\Controller\ErrorHandler`](/src/Controller/ErrorHandler.php) allows to execute code on errors triggered in controllers;
- [`\Minz\Email`](/src/Email.php) is an utility class to sanitize and validate emails;
- [`\Minz\Flash`](/src/Flash.php) to pass messages from a page to another through redirections;
- [`\Minz\Random`](/src/Random.php) to generate random values;
- [`\Minz\Time`](/src/Time.php) abstracts the time;
- [`\Minz\File`](/src/File.php) abstracts uploaded files;

## Tests

Finally, Minz provides a bunch of helpers and assertions for [PHPUnit](https://phpunit.readthedocs.io/):

- **[`\Minz\Tests\ApplicationHelper`](/src/Tests/ApplicationHelper.php) allows you to execute a request against your application and get its response in a single line of code;**
- **[`\Minz\Tests\InitializerHelper`](/src/Tests/InitializerHelper.php) reinitializes the database, session and mailer before each tests;**
- **[`\Minz\Tests\ResponseAsserts`](/src/Tests/ResponseAsserts.php) provides a bunch of assertions to test the response of your application;**
- [`\Minz\Tests\CsrfHelper`](/src/Tests/CsrfHelper.php) helps to generate valid CSRF tokens during tests;
- [`\Minz\Tests\TimeHelper`](/src/Tests/TimeHelper.php) freezes the time, on the condition that you use the Time class;
- [`\Minz\Tests\Mailer`](/src/Tests/Mailer.php) catches emails sent by the mailer and allows you to test them with the assertions provided by
  [`\Minz\Tests\MailerAsserts`](/src/Tests/MailerAsserts.php) provides assertions to tests emails;
- [`\Minz\Tests\FilesHelper`](/src/Tests/FilesHelper.php) provides a method to copy files in a temporary directory in order to simulate an upload to your application.
