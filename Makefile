.DEFAULT_GOAL := help

ifdef PGSQL
	DB_DSN='pgsql:host=localhost;port=5432;dbname=minz_test'
	DB_USERNAME='postgres'
	DB_PASSWORD='password'
else
	DB_DSN='sqlite::memory:'
	DB_USERNAME=none
	DB_PASSWORD=none
endif

ifndef COVERAGE
	COVERAGE = --coverage-html ./coverage
endif

ifdef FILTER
	PHPUNIT_FILTER = --filter=$(FILTER)
else
	PHPUNIT_FILTER =
endif

ifdef FILE
	PHPUNIT_FILE = $(FILE)
else
	PHPUNIT_FILE = ./tests
endif

.PHONY: postgres
postgres: ## Start a Postgres database in a Docker container
	docker run --name minz-postgres --rm -p 5432:5432 -e POSTGRES_DB=minz_test -e POSTGRES_PASSWORD=password postgres:12-alpine

.PHONY: test
test: bin/phpunit ## Run the tests suite
	DB_DSN=$(DB_DSN) DB_USERNAME=$(DB_USERNAME) DB_PASSWORD=$(DB_PASSWORD) \
		php ./bin/phpunit \
		$(COVERAGE) --whitelist ./src \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: bin/phpcs ## Run the linter on the PHP files
	php ./bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: bin/phpcbf ## Fix the errors raised by the linter
	php ./bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

bin/phpunit:
	mkdir -p bin/
	wget -O bin/phpunit https://phar.phpunit.de/phpunit-8.5.2.phar
	echo '984e15fbf116a19ab98b6a642ccfc139a1a88172ffef995a9a27d00c556238f1 bin/phpunit' | sha256sum -c - || rm bin/phpunit

bin/phpcs:
	mkdir -p bin/
	wget -O bin/phpcs https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.5.3/phpcs.phar
	echo 'b44e0ad96138e2697a97959fefb9c6f1491f4a22d5daf08aabed12e9a2869678 bin/phpcs' | sha256sum -c - || rm bin/phpcs

bin/phpcbf:
	mkdir -p bin/
	wget -O bin/phpcbf https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.5.3/phpcbf.phar
	echo 'db20ec9cfd434deba03f6f20c818732d477696589d5aea3df697986b6e723ad7 bin/phpcbf' | sha256sum -c - || rm bin/phpcbf
