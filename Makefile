.DEFAULT_GOAL := help

COMPOSE := docker compose

.PHONY: help up up-bitrix down logs ps reset import-production _bitrix-secret

help:
	@echo "make up     Собрать и поднять пилот на http://127.0.0.1:8092/"
	@echo "make up-bitrix  Поднять пилот и часовую синхронизацию Bitrix (нужен ../fmonitor)"
	@echo "make import-production  Загрузить не начатые объекты, пользователей и роли production"
	@echo "make down   Остановить пилот, сохранив данные"
	@echo "make logs   Показать логи"
	@echo "make ps     Показать состояние контейнеров"
	@echo "make reset  Удалить локальные данные пилота"

_bitrix-secret:
	@mkdir -p .local
	@php rapid-pilot/export-legacy-bitrix-secret.php ../fmonitor/application/controllers/Integration.php .local/bitrix-workforce.json

up:
	@docker info >/dev/null 2>&1 || { echo "Docker daemon недоступен. Запустите Docker внутри WSL или включите WSL integration в Docker Desktop." >&2; exit 1; }
	$(COMPOSE) up --build --detach --wait
	@echo "FMonitor 2.0: http://127.0.0.1:8092/"

up-bitrix: _bitrix-secret
	@docker info >/dev/null 2>&1 || { echo "Docker daemon недоступен. Запустите Docker внутри WSL или включите WSL integration в Docker Desktop." >&2; exit 1; }
	$(COMPOSE) --profile bitrix up --build --detach --wait
	@echo "FMonitor 2.0 с Bitrix sync: http://127.0.0.1:8092/"

import-production:
	@test -f .env || { echo ".env не найден. Выполните: cp .env.example .env" >&2; exit 2; }
	$(COMPOSE) run --rm --no-deps --env-from-file .env --entrypoint sh \
		-e FMONITOR_DB_HOST=mariadb \
		-e FMONITOR_DB_PORT=3306 \
		-e FMONITOR_DB_NAME=fmonitor2_demo \
		-e FMONITOR_DB_USER=fmonitor2_demo \
		-e FMONITOR_DB_PASSWORD=fmonitor2_demo_local \
		pilot -c 'socat TCP4-LISTEN:23306,bind=127.0.0.1,fork,reuseaddr TCP4:mariadb:3306 & FMONITOR_PILOT_ACTIVE_MANIFEST="$$(find /home/fmonitor/.local/state/fmonitor2/pilot-demo -name active.json -print -quit)" php rapid-pilot/initialize-native-only.php --cutoff="$${FMONITOR_MIGRATION_CUTOFF:-$$(date +%F\ 23:59:59)}"'

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs --follow

ps:
	$(COMPOSE) ps

reset:
	$(COMPOSE) down --volumes
