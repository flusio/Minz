# Changelog of Minz

## unreleased

### Breaking changes

The `Form` and `Validable` classes have been extensively redesigned so they work better together.
`Form` now uses the `Validable` trait so the `validate` method is provided by the latter.
However, this broke how the `Validable` trait worked previously:

- `validate()` now returns a boolean telling if the object is valid or not;
- an `$errors` property is added and can be queried with the new `isInvalid()`, `errors()`, `error()` and `addError()` methods (meaning that if you were using on of them in a `Validable` model, things will break);
- the `$errors` codes changed to remove the `\Minz\Validable\` namespace and to change the case to snake\_case.

Also, the `Validable\Check` trait has been renamed to `Validable\PropertyCheck`, and the `Form\Check` trait has been renamed to `Validable\Check`.
The `Validable\PropertyCheck` class declares a new `getCode` method.

The following Form methods must be replaced by their `Validable` equivalent:

- `hasError()` to `isInvalid()`
- `getError()` to `error()`

The `@global` error namespace has been changed to `@base`.

Be careful when upgrading.

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
