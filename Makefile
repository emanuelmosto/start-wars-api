.PHONY: up down logs shell artisan migrate npm-install dev build bootstrap env composer-install

DC=docker compose
APP=laravel.test

# ---- OS detection ----
ifeq ($(OS),Windows_NT)
	SHELL := powershell.exe
	.SHELLFLAGS := -NoProfile -ExecutionPolicy Bypass -Command
	IS_WINDOWS := 1
else
	IS_WINDOWS := 0
endif

# ---- Helpers ----
env:
ifeq ($(IS_WINDOWS),1)
	if (!(Test-Path .env)) { Copy-Item .env.example .env }
else
	@if [ ! -f .env ]; then cp .env.example .env; fi
endif

composer-install:
ifeq ($(IS_WINDOWS),1)
	docker run --rm -v "$(CURDIR):/app" -w /app composer:2 composer install --no-interaction --prefer-dist
else
	docker run --rm -u "$$(id -u):$$(id -g)" -v "$$(pwd):/app" -w /app composer:2 composer install --no-interaction --prefer-dist
endif

bootstrap: composer-install env
	$(DC) up -d --build
	$(DC) exec $(APP) php artisan key:generate --force
	$(DC) exec $(APP) sh -lc "php artisan storage:link || true"
	@echo "Waiting for DB and running migrations..."
	@$(DC) exec $(APP) sh -lc 'for i in 1 2 3 4 5 6 7 8 9 10; do php artisan migrate --force && exit 0; echo Migration\ failed,\ retrying\ in\ 2s...; sleep 2; done; echo Migration\ failed\ after\ retries; exit 1'

up: bootstrap
	$(DC) exec $(APP) npm install
	$(DC) exec $(APP) npm run dev

down:
	$(DC) down

logs:
	$(DC) logs -f

shell:
	$(DC) exec $(APP) sh

artisan:
	$(DC) exec $(APP) php artisan $(cmd)

migrate:
	$(DC) exec $(APP) php artisan migrate

npm-install:
	$(DC) exec $(APP) npm install

dev:
	$(DC) exec $(APP) npm run dev

build:
	$(DC) exec $(APP) npm run build
