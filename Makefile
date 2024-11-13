.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

DOCKER_COMPOSE = docker compose -f docker/docker-compose.yml

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

.PHONY: docker-build
docker-build: ## Rebuild the Docker images
	$(DOCKER_COMPOSE) build --pull

.PHONY: docker-pull
docker-pull: ## Pull the Docker images from the Docker Hub
	$(DOCKER_COMPOSE) pull --ignore-buildable

.PHONY: docker-clean
docker-clean: ## Clean the Docker stuff
	$(DOCKER_COMPOSE) down -v

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install

.PHONY: test
test: FILE ?= ./tests
ifdef FILTER
test: override FILTER := --filter=$(FILTER)
endif
test: COVERAGE ?= --coverage-html ./coverage
test: ## Run the tests suite (can take FILE, FILTER and COVERAGE arguments)
	DB_DSN=$(DB_DSN) DB_USERNAME=$(DB_USERNAME) DB_PASSWORD=$(DB_PASSWORD) \
		$(PHP) ./vendor/bin/phpunit \
		-c .phpunit.xml \
		$(COVERAGE) \
		$(FILTER) \
		$(FILE)

.PHONY: lint
lint: LINTER ?= all
lint: ## Run the linters on the PHP files (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all phpstan))
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit 1G -c .phpstan.neon
endif
ifeq ($(LINTER),$(filter $(LINTER), all rector))
	$(PHP) vendor/bin/rector process --dry-run --config .rector.php
endif
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) ./vendor/bin/phpcs --standard=PSR12 ./src ./tests
endif

.PHONY: lint-fix
lint-fix: ## Fix the errors raised by the linter (can take a LINTER argument)
ifeq ($(LINTER), $(filter $(LINTER), all rector))
	$(PHP) vendor/bin/rector process --config .rector.php
endif
ifeq ($(LINTER), $(filter $(LINTER), all phpcs))
	$(PHP) ./vendor/bin/phpcbf --standard=PSR12 ./src ./tests
endif

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
