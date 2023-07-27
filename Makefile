.DEFAULT_GOAL := help

ifdef NODOCKER
	PHP = php
	COMPOSER = composer
else
	PHP = ./docker/bin/php
	COMPOSER = ./docker/bin/composer
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
	docker compose -p minz -f docker/docker-compose.yml build

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install

.PHONY: test
test: ## Run the tests suite
	DB_DSN=$(DB_DSN) DB_USERNAME=$(DB_USERNAME) DB_PASSWORD=$(DB_PASSWORD) \
		$(PHP) ./vendor/bin/phpunit \
		$(COVERAGE) --whitelist ./src \
		--bootstrap ./tests/bootstrap.php \
		--testdox \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: ## Run the linters on the PHP files
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit 1G -c phpstan.neon
	$(PHP) ./vendor/bin/phpcs --standard=PSR12 ./src ./tests

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter
	$(PHP) ./vendor/bin/phpcbf --standard=PSR12 ./src ./tests

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
