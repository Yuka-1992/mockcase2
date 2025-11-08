.PHONY: build rebuild up down start stop restart ps logs exec php-sh refresh prune clean init setup fresh seed

COMPOSE ?= docker compose
DC := $(COMPOSE) -f docker-compose.yml
SERVICE ?= php
LOGS ?= -f --tail=100

build:
	$(DC) build

rebuild:
	$(DC) build --no-cache --pull

up:
	$(DC) up -d

buildup:
	$(DC) up -d --build

down:
	$(DC) down

start:
	$(DC) start

stop:
	$(DC) stop

restart:
	$(DC) restart

ps:
	$(DC) ps

logs:
	$(DC) logs $(LOGS) $(SERVICE)

exec:
	$(DC) exec $(SERVICE) sh

php-sh:
	$(DC) exec php sh

refresh:
	$(DC) down -v --remove-orphans
	$(DC) build --no-cache --pull
	$(DC) up -d

prune:
	docker system prune -af
	docker volume prune -f

clean: down
	$(DC) rm -sf

# マイグレーションのコマンドとして使用

fresh:
	$(DC) exec php php artisan migrate:fresh --force || true

seed:
	$(DC) exec php php artisan migrate --seed --force || true

# 初期設定で使用

setup:
	make buildup
	$(DC) exec php sh -lc '[ -f composer.json ] || composer create-project laravel/laravel:^10 .'
	$(DC) exec php sh -lc 'chmod -R 777 storage bootstrap/cache 2>/dev/null || true'

init:
	make buildup
	$(DC) exec php composer install
	$(DC) exec php sh -lc 'cp -n .env.example .env'
	$(DC) exec php sh -lc 'chmod -R 777 storage bootstrap/cache 2>/dev/null || true'
	$(DC) exec php php artisan key:generate || true
	@make fresh
	@make seed
