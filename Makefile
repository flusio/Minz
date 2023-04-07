.DEFAULT_GOAL := help

ifdef NODOCKER
	PHP = php
else
	PHP = ./docker/bin/php
endif

ifdef PGSQL
	DB_DSN='pgsql:host=database;port=5432;dbname=minz_test'
	DB_USERNAME='postgres'
	DB_PASSWORD='postgres'
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

.PHONY: docker-build
docker-build: ## Rebuild the Docker images
	docker-compose -p minz -f docker/docker-compose.yml build

.PHONY: test
test: bin/phpunit ## Run the tests suite
	DB_DSN=$(DB_DSN) DB_USERNAME=$(DB_USERNAME) DB_PASSWORD=$(DB_PASSWORD) \
		$(PHP) ./bin/phpunit \
		$(COVERAGE) --whitelist ./src \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: bin/phpcs ## Run the linter on the PHP files
	$(PHP) ./bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: bin/phpcbf ## Fix the errors raised by the linter
	$(PHP) ./bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

bin/phpunit:
	mkdir -p bin/
	wget -O bin/phpunit https://phar.phpunit.de/phpunit-9.5.10.phar
	echo 'a34b9db21de3e75ba2e609e68a4da94633f4a99cad8413fd3731a2cd9aa08ca8 bin/phpunit' | sha256sum -c - || rm bin/phpunit

bin/phpcs:
	mkdir -p bin/
	wget -O bin/phpcs https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.6.1/phpcs.phar
	echo 'd0ce68aa469aff7e86935c6156a505c4d6dc90adcf2928d695d8331722ce706b bin/phpcs' | sha256sum -c - || rm bin/phpcs

bin/phpcbf:
	mkdir -p bin/
	wget -O bin/phpcbf https://github.com/squizlabs/PHP_CodeSniffer/releases/download/3.6.1/phpcbf.phar
	echo '4fd260dd0eb4beadd6c68ae12a23e9adb15e155dfa787c9e6ba7104d3fc01471 bin/phpcbf' | sha256sum -c - || rm bin/phpcbf
