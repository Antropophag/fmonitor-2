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
	@php rapid-pilot/validate-production-import-env.php
	$(COMPOSE) exec \
		-e FMONITOR_SOURCE_HOST="$${FMONITOR_SOURCE_HOST:-host.docker.internal}" \
		-e FMONITOR_SOURCE_PORT="$${FMONITOR_SOURCE_PORT:-3306}" \
		-e FMONITOR_SOURCE_NAME="$${FMONITOR_SOURCE_NAME:-c1_fmonitor}" \
		-e FMONITOR_SOURCE_USER="$${FMONITOR_SOURCE_USER}" \
		-e FMONITOR_SOURCE_PASSWORD="$${FMONITOR_SOURCE_PASSWORD}" \
		-e FMONITOR_MIGRATION_CUTOFF="$${FMONITOR_MIGRATION_CUTOFF:-}" \
		pilot sh -c 'FMONITOR_PILOT_ACTIVE_MANIFEST="$$(find /home/fmonitor/.local/state/fmonitor2/pilot-demo -name active.json -print -quit)" FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 FMONITOR_DB_NAME=fmonitor2_demo FMONITOR_DB_USER=fmonitor2_demo FMONITOR_DB_PASSWORD=fmonitor2_demo_local php rapid-pilot/initialize-native-only.php --cutoff="$${FMONITOR_MIGRATION_CUTOFF:-$$(date +%F\ 23:59:59)}"'

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs --follow

ps:
	$(COMPOSE) ps

reset:
	$(COMPOSE) down --volumes
