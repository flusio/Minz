# Develop Minz

## Setup Docker

The development environment is managed with Docker by default.

First, make sure to install [Docker Engine](https://docs.docker.com/engine/install/).
The `docker` command must be executable by your normal user.

## Install the project

Clone the repository:

```console
$ git clone git@github.com:flusio/Minz.git
```

Install the dependencies:

```console
$ make install
```

A note about the `make` commands: they might feel magic, but they are not!
They are just shortcuts for common commands.
If you want to know what they do, you can open the [Makefile](/Makefile) and locates the command that you are interested in.
They are hopefully easily readable by newcomers.

## Working in the Docker containers

As the environment runs in Docker, you cannot run the `php` (or the others) directly.
There are few scripts to allow to execute commands in the Docker containers easily:

```console
$ ./docker/bin/php
$ ./docker/bin/composer
```

## Running the tests

You can execute the tests with:

```console
$ make test
```

Execute the tests of a specific file with the `FILE=` parameter:

```console
$ make test FILE=tests/path/to/file.php
```

Filter tests with the `FILTER=` parameter (it takes a function name, or a part of it):

```console
$ make test FILE=tests/path/to/file.php FILTER=testSomePattern
```

## Code coverage

The previous command generates code coverage under the folder `coverage/`.
To disable code coverage, run the command:

```console
$ make test COVERAGE=
```

## Running the linters

Execute the linters with:

```console
$ make lint
```

You can run a specific linter with:

```console
$ make lint LINTER=phpstan
$ make lint LINTER=rector
$ make lint LINTER=phpcs
```

## Updating the development environment

Pull the changes with Git:

```console
$ git pull
```

If dependencies have been added or updated, install them:

```console
$ make install
```

Sometimes, you may also have to pull or rebuild the Docker images:

```console
$ make docker-pull
$ make docker-build
```

If you encounter any problem with the Docker containers, you can clean them:

```console
$ make docker-clean
```
