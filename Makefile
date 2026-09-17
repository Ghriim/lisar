# The ONLY supported entry point for every command.
# Nothing is run from the host: every target execs into the right container, so bin/console,
# bin/phpunit and vendor/bin/* are never invoked directly.

DOCKER_COMPOSE = UID=$(shell id -u) GID=$(shell id -g) docker compose

# -T: make runs without a TTY, and `docker compose exec` fails if it tries to allocate one.
EXEC       = $(DOCKER_COMPOSE) exec -T -u app
APP        = $(EXEC) app
APP_TEST   = $(EXEC) app-test
CONSOLE    = $(APP) php bin/console
CONSOLE_TEST = $(APP_TEST) php bin/console

# Optional arguments, usable on test-unit / test-integration:
#   file=tests/Unit/Foo/BarTest.php   class=BarTest   debug=true   coverage=true
# The suites are allowed to be empty while the project has no code in them yet; drop this the
# day both of them have tests.
PHPUNIT_ARGS = --do-not-fail-on-empty-test-suite
ifdef file
    PHPUNIT_ARGS += $(file)
endif
ifdef class
    PHPUNIT_ARGS += --filter $(class)
endif
ifeq ($(debug),true)
    PHPUNIT_ARGS += --debug
endif
ifeq ($(coverage),true)
    PHPUNIT_ENV = -e XDEBUG_MODE=coverage
    PHPUNIT_ARGS += --coverage-text
endif

.DEFAULT_GOAL := help
.PHONY: help setup build start stop reset-db reset-test-db load-fixtures cs-fix stan test test-unit test-integration pre-commit db-connect shell

help: ## List the available targets
	@grep -E '^[a-z-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

setup: ## One-off local setup: git hooks
	git config core.hooksPath .git-hooks/
	@echo "Hooks path set to .git-hooks/"

build: ## Full rebuild, all containers up, migrations on the dev database
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) up --detach --wait
	$(APP) composer install
	$(MAKE) migrate

start: build reset-db load-fixtures ## build + reset-db + load-fixtures

stop: ## Stop the containers
	$(DOCKER_COMPOSE) down

migrate: ## Run the pending migrations on the dev database
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

reset-db: ## Drop + create + migrate the dev database
	$(CONSOLE) doctrine:database:drop --if-exists --force
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

reset-test-db: ## Drop + create + migrate the test database
	$(CONSOLE_TEST) doctrine:database:drop --if-exists --force
	$(CONSOLE_TEST) doctrine:database:create
	$(CONSOLE_TEST) doctrine:migrations:migrate --no-interaction --allow-no-migration

load-fixtures: ## Load the Doctrine fixtures into the dev database
	$(CONSOLE) doctrine:fixtures:load --no-interaction

cs-fix: ## PHP-CS-Fixer on src/ and tests/
	$(APP) vendor/bin/php-cs-fixer fix

stan: ## PHPStan level 8 on src/
	$(APP) vendor/bin/phpstan analyse --memory-limit=-1

test: reset-test-db test-unit test-integration ## Full suite: migrations, then unit, then integration

test-unit: ## Unit suite — file=, class=, debug=true, coverage=true
	$(EXEC) $(PHPUNIT_ENV) app-test vendor/bin/phpunit --testsuite=unit $(PHPUNIT_ARGS)

test-integration: ## Integration suite — file=, class=, debug=true, coverage=true
	$(EXEC) $(PHPUNIT_ENV) app-test vendor/bin/phpunit --testsuite=integration $(PHPUNIT_ARGS)

pre-commit: cs-fix stan test-unit ## Same sequence as the git hook

db-connect: ## Interactive MySQL shell on the dev database
	$(DOCKER_COMPOSE) exec mysql mysql -ulisar -plisar lisar

shell: ## Interactive shell in the app container
	$(DOCKER_COMPOSE) exec -u app app sh
