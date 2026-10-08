DC      = docker compose
PHP     = $(DC) exec -T php
CONSOLE = $(PHP) php bin/console

export UID := $(shell id -u)
export GID := $(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help build up down test reset logs bash wait-db db-test

help: ## Lista os alvos disponíveis
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

build: ## Sobe tudo do zero: imagens, dependências, banco, migrations, fixtures e banco de teste
	$(DC) build
	$(DC) up -d
	$(PHP) composer install --no-interaction --prefer-dist
	$(MAKE) wait-db
	$(CONSOLE) doctrine:database:create --if-not-exists --no-interaction
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	$(MAKE) db-test
	@echo "Aplicação disponível em http://localhost:$${HTTP_PORT:-8080}"

up: ## Sobe os contêineres
	$(DC) up -d

down: ## Derruba os contêineres
	$(DC) down

test: ## Roda a suíte de testes (PHPUnit) no banco _test
	$(PHP) php bin/phpunit

reset: ## Recria o banco, roda migrations e fixtures
	$(MAKE) wait-db
	$(CONSOLE) doctrine:database:drop --force --if-exists --no-interaction
	$(CONSOLE) doctrine:database:create --no-interaction
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration
	$(CONSOLE) doctrine:fixtures:load --no-interaction
	$(MAKE) db-test

logs: ## Acompanha os logs
	$(DC) logs -f

bash: ## Abre um shell no contêiner PHP
	$(DC) exec php bash

wait-db:
	@echo "Aguardando o MySQL..."
	@until [ "$$($(DC) ps --format '{{.Health}}' mysql)" = "healthy" ]; do sleep 2; done
	@echo "MySQL pronto."

db-test: ## Prepara o banco de teste (sufixo _test)
	$(CONSOLE) --env=test doctrine:database:create --if-not-exists --no-interaction
	$(CONSOLE) --env=test doctrine:migrations:migrate --no-interaction --allow-no-migration
