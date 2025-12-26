include .env

.PHONY: up stop down container_php run_command install_dependencies migrations fixtures lint lint-dry-run copy_dist_files setup

DRY_RUN ?= 1

up:
	docker compose up -d

stop:
	docker compose stop

down:
	docker compose --profile '*' down --volumes --remove-orphans

container_php:
	docker compose exec php sh

run_command:
	docker compose exec -T php sh -c "$(CMD)"

install_dependencies:
	make run_command CMD="composer install --optimize-autoloader"

migrations:
	make run_command CMD="bin/console doctrine:migrations:migrate --no-interaction"

fixtures:
	make run_command CMD="bin/console doctrine:fixture:load --no-interaction"

test:
	make run_command CMD="bin/phpunit"

lint:
	make run_command CMD="php vendor/bin/php-cs-fixer fix --diff"

lint-dry-run:
	make run_command CMD="php vendor/bin/php-cs-fixer fix --diff --dry-run"

copy_dist_files:
	cp .php-cs-fixer.dist.php .php-cs-fixer.php
	cp phpunit.dist.xml phpunit.xml

setup: up install_dependencies copy_dist_files
