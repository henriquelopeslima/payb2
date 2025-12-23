include .env

.PHONY: up stop down container_php run_command install_dependencies

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
