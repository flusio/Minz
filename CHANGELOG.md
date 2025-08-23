# Changelog of Minz

## 2025-08-23 - 2.0.2

### Improvements

- Allow to pass parameters via the appRun URI ([6d48add](https://github.com/flusio/Minz/commit/6d48add))

### Technical

- Update the dependencies ([dd3955c](https://github.com/flusio/Minz/commit/dd3955c), [f698143](https://github.com/flusio/Minz/commit/f698143), [96ea093](https://github.com/flusio/Minz/commit/96ea093))

## 2025-05-29 - 2.0.1

### Improvements

- Set default Origin in `ApplicationHelper::appRun` ([5e8f60c](https://github.com/flusio/Minz/commit/5e8f60c))
- Provide a CsrfHelper to generate CSRF tokens during tests ([31f5489](https://github.com/flusio/Minz/commit/31f5489))
- Change the visibility of Csrf methods to public ([31bad4a](https://github.com/flusio/Minz/commit/31bad4a))

### Bug fixes

- Allow to declare cookies and server information in `ApplicationHelper::appRun` ([3f7345a](https://github.com/flusio/Minz/commit/3f7345a))

## 2025-05-28 - 2.0.0

### Breaking changes

This is a big release.
Here are listed all the changes in a comprehensive way.
Take your time to upgrade!

The `Request` class has been refactored:

- Parameters must be retrieved with the `$request->parameters->get*()` methods.
- Headers must be retrieved with the `$request->headers->get*()` methods.
- Cookies must be retrieved with the `$request->cookied->get*()` methods.
- The server information must be retrieved with the `$request->server->get*()` methods.
- The headers are now set using the `getallheaders()` PHP function in `Request::initFromGlobals()`. For instance, `HTTP_CONTENT_TYPE` must now be fetched using the `Content-Type` key.

The `Router` class has been refactored:

- routes all have a name. If it's not specified, the name defaults to the action value.
- incidentally, the `uriByPointer()` has been removed since it's now useless.
- the `routes()` method returns the list of Routes with all the information (name, method, pattern and action)
- `match()` now returns a Route and no longer returns an `_action_pointer` parameter.
- incidentally, `Request` no longer has an `_action_pointer` parameter. It has been replaced by the `route()` method which returns more information.

The `Form` and `Validable` classes have been extensively redesigned so they work better together.
`Form` now uses the `Validable` trait so the `validate` method is provided by the latter.
However, this broke how the `Validable` trait worked previously.

Changes of the `Validable` trait:

- `validate()` now returns a boolean telling if the object is valid or not;
- an `$errors` property is added and can be queried with the new `isInvalid()`, `errors()`, `error()` and `addError()` methods (meaning that if you were using one of them in a `Validable` model, things will break);
- the `$errors` codes changed to remove the `\Minz\Validable\` namespace and to change the case to snake\_case.

Changes of the `Check` attributes:

- the old `Validable\Check` trait has been renamed to `Validable\PropertyCheck`
- the new `Validable\Check` corresponds to the old `Form\Check` trait
- the PropertyCheck methods changed: `getValue()` to `value()`, `getMessage()` to `message()` and a new `code()` method appears

Changes of the `Form` class:

- the methods changed: `hasError()` to `isInvalid()`, `getError()` to `error()` and `getModel()` to `model()`
- the `@global` error namespace has been changed to `@base`.

Changes of the `Field` attribute:

- a `transform` argument has been added to the constructor, taking a callable string
- the `trim` argument has been removed, you must change it by `transform: 'trim'`
- the `bind_model` argument has been renamed to `bind`

The CSRF protections have also largely step up:

- the `Csrf` class have been rewritten and moved to `Form\CsrfToken`, it is discouraged to use it alone
- the `Form\Csrf` trait now checks for the `Origin` (or `Referer`) of the request
- the `Form\Csrf` trait provides more methods: `rememberCsrfOrigin`, `csrfToken`, `csrfSessionId` and `csrfTokenName`
- the `Form\Csrf` trait error namespace is now `@base` instead of `@global`, and code is `csrf` instead of the full class name

The View templating system has also been reworked in order to allow to use Twig templates.
There are several consequences to this:

- The `Output\View` class has been renamed to `Output\Template` and the code related to the templating system has been extracted into `Template\Simple`
- The `ViewHelpers` class has been renamed to `Template\SimpleTemplateHelpers`
- The `view_helpers.php` file has been moved to `Template\simple_template_helpers.php`
- The `ResponseAsserts::assertResponsePointer` assertion has been renamed to `assertResponseTemplateName`
- The parameters refering to `pointer` and `variables` have been renamed to `name` and `context`
- The Engine options `not_found_view_pointer` and `internal_server_error_view_pointer` have been renamed to `not_found_template` and `internal_server_error_template`
- The `ViewPointer` and `ViewVariables` PHPStan types have been moved to `Template\TemplateInterface` and renamed to `ViewName` and `ViewContext`
- If the template name ends with `.twig`, the output will use Twig templating system to render the view.

### New

- Add Twig as a new template system ([ce1f441](https://github.com/flusio/Minz/commit/ce1f441))
- Add `BeforeAction` and `AfterAction` controller handlers ([541aeef](https://github.com/flusio/Minz/commit/541aeef))
- Allow `OPTIONS` as Request method ([31b4fac](https://github.com/flusio/Minz/commit/31b4fac))

### Bug fixes

- Fix the default `Form\Field` datetime format ([24532aa](https://github.com/flusio/Minz/commit/24532aa))

### Improvements

- Redesign Request parameters, headers and cookies ([78e43fb](https://github.com/flusio/Minz/commit/78e43fb))
- Redesign the Form and the Validable classes ([d076330](https://github.com/flusio/Minz/commit/d076330), [7634289](https://github.com/flusio/Minz/commit/7634289), [1b093ff](https://github.com/flusio/Minz/commit/1b093ff), [27f78cf](https://github.com/flusio/Minz/commit/27f78cf), [306711d](https://github.com/flusio/Minz/commit/306711d))
- Redesign the CSRF protections ([4925599](https://github.com/flusio/Minz/commit/4925599))
- Redesign the Router class ([d0cc0b6](https://github.com/flusio/Minz/commit/d0cc0b6), [4e03104](https://github.com/flusio/Minz/commit/4e03104))
- Initialize Request headers with `getallheaders()` ([99aefb8](https://github.com/flusio/Minz/commit/99aefb8))

### Technical

- Update the dependencies ([3922acb](https://github.com/flusio/Minz/commit/3922acb), [e9e04a1](https://github.com/flusio/Minz/commit/e9e04a1))

## 2025-04-30 - 1.1.0

### New

- Provide a Request `selfUri` property ([72bb47e](https://github.com/flusio/Minz/commit/72bb47e), [26e19f8](https://github.com/flusio/Minz/commit/26e19f8))
- Add a check to validate unique values in database ([fb2a1e1](https://github.com/flusio/Minz/commit/fb2a1e1))

### Improvements

- Pass the error to the controller error handlers ([088d867](https://github.com/flusio/Minz/commit/088d867))
- Improve the return type of Validable::validate method ([4ad279d](https://github.com/flusio/Minz/commit/4ad279d))

### Technical

- Update the Composer dependencies ([f860fea](https://github.com/flusio/Minz/commit/f860fea), [841855b](https://github.com/flusio/Minz/commit/841855b), [8f7b667](https://github.com/flusio/Minz/commit/8f7b667))

### Documentation

- Fix the examples of the Mailer class ([14cfa84](https://github.com/flusio/Minz/commit/14cfa84))

### Developers

- Remove the "How to test manually" section in PR template ([64ab430](https://github.com/flusio/Minz/commit/64ab430))

## 2025-02-07 - 1.0.1

### Bug fixes

- Fix setup of the database when it already exists but the permission to create database is denied ([579c3ec](https://github.com/flusio/Minz/commit/579c3ec))

## 2025-02-06 - 1.0.0

First release, happy coding!
