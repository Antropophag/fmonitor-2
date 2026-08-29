.DEFAULT_GOAL := help

COMPOSE := docker compose

.PHONY: help up down logs ps reset _bitrix-secret

help:
	@echo "make up     Собрать и поднять пилот на http://127.0.0.1:8092/"
	@echo "make down   Остановить пилот, сохранив данные"
	@echo "make logs   Показать логи"
	@echo "make ps     Показать состояние контейнеров"
	@echo "make reset  Удалить локальные данные пилота"

_bitrix-secret:
	@mkdir -p .local
	@php rapid-pilot/export-legacy-bitrix-secret.php ../fmonitor/application/controllers/Integration.php .local/bitrix-workforce.json

up: _bitrix-secret
	@docker info >/dev/null 2>&1 || { echo "Docker daemon недоступен. Запустите Docker внутри WSL или включите WSL integration в Docker Desktop." >&2; exit 1; }
	$(COMPOSE) up --build --detach --wait
	@echo "FMonitor 2.0: http://127.0.0.1:8092/"

down: _bitrix-secret
	$(COMPOSE) down

logs: _bitrix-secret
	$(COMPOSE) logs --follow

ps: _bitrix-secret
	$(COMPOSE) ps

reset: _bitrix-secret
	$(COMPOSE) down --volumes
