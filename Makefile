.DEFAULT_GOAL := help

COMPOSE := docker compose

.PHONY: help up up-bitrix down logs ps reset import-production _bitrix-secret \
	test-env-up test-env-down test-db-reset migrate unit-test db-test \
	characterization-test e2e-test architecture-check lint delivery-evidence-check verify fresh-test-verify

help:
	@echo "make up     Собрать и поднять пилот на http://127.0.0.1:8092/"
	@echo "make up-bitrix  Поднять пилот и часовую синхронизацию Bitrix (нужен ../fmonitor)"
	@echo "make import-production  Загрузить не начатые объекты, пользователей и роли production"
	@echo "make down   Остановить пилот, сохранив данные"
	@echo "make logs   Показать логи"
	@echo "make ps     Показать состояние контейнеров"
	@echo "make reset  Удалить локальные данные пилота"
	@echo "make test-env-up/down  Поднять/остановить disposable test MariaDB"
	@echo "make test-db-reset    Пересоздать чистую test DB"
	@echo "make migrate          Применить canonical production migrations к test DB"
	@echo "make unit-test/db-test/characterization-test/e2e-test"
	@echo "make architecture-check  Проверить machine-checkable boundaries"
	@echo "make delivery-evidence-check  Проверить SSD/TDD provenance receipts"
	@echo "make verify           Полная clean-checkout проверка"
	@echo "make fresh-test-verify  Полная проверка с обязательным test-env teardown"

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
		-e FMONITOR_PILOT_OWNER_EMAIL="$${FMONITOR_PILOT_OWNER_EMAIL:-ts.grishin@shlz.ru}" \
		pilot -c 'socat TCP4-LISTEN:23306,bind=127.0.0.1,fork,reuseaddr TCP4:mariadb:3306 & FMONITOR_PILOT_ACTIVE_MANIFEST="$$(find /home/fmonitor/.local/state/fmonitor2/pilot-demo -name active.json -print -quit)" php rapid-pilot/initialize-native-only.php --cutoff="$${FMONITOR_MIGRATION_CUTOFF:-$$(date +%F\ 23:59:59)}"'

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs --follow

ps:
	$(COMPOSE) ps

reset:
	$(COMPOSE) down --volumes

test-env-up:
	docker compose -f compose.test.yaml up --detach --wait test-db

test-env-down:
	docker compose -f compose.test.yaml down --volumes --remove-orphans

test-db-reset: test-env-up
	@FMONITOR_TEST_DB_PORT="$${FMONITOR_TEST_DB_PORT:-23306}" php tools/verification/reset-test-db.php

migrate:
	@FMONITOR_DB_HOST="$${FMONITOR_TEST_DB_HOST:-127.0.0.1}" \
	FMONITOR_DB_PORT="$${FMONITOR_TEST_DB_PORT:-23306}" \
	FMONITOR_DB_NAME="$${FMONITOR_TEST_DB_NAME:-fmonitor2_test}" \
	FMONITOR_DB_USER="$${FMONITOR_TEST_DB_USER:-fmonitor2_test}" \
	FMONITOR_DB_PASSWORD="$${FMONITOR_TEST_DB_PASSWORD:-fmonitor2_test_local}" \
	FMONITOR_PROCESS_TABLE_PREFIX= php bin/fmonitor2-migrate.php

unit-test:
	@bash tools/verification/run.sh unit

db-test: test-env-up
	@bash tools/verification/run.sh db

characterization-test:
	@bash tools/verification/run.sh characterization

e2e-test: test-env-up
	@bash tools/verification/run.sh e2e

architecture-check:
	@tools/architecture/check

delivery-evidence-check:
	@php tools/delivery/check-evidence.php

lint:
	@bash tools/verification/run.sh lint

verify:
	@set +e; failures=""; failed_count=0; setup_failed=0; setup_cause=""; \
	record_failure() { \
		failed_stage="$$1"; \
		failed_count=$$((failed_count + 1)); \
		failures="$${failures}$${failures:+,}$${failed_stage}"; \
	}; \
	run_stage() { \
		stage_name="$$1"; shift; \
		"$$@"; stage_status=$$?; \
		if [ $$stage_status -eq 0 ]; then \
			printf 'VERIFY_STAGE %s PASS\n' "$$stage_name"; \
		else \
			printf 'VERIFY_STAGE %s FAIL\n' "$$stage_name"; \
			record_failure "$$stage_name"; \
		fi; \
		return $$stage_status; \
	}; \
	run_setup_stage() { \
		stage_name="$$1"; shift; \
		run_stage "$$stage_name" "$$@"; stage_status=$$?; \
		if [ $$stage_status -ne 0 ]; then \
			printf 'SETUP_FAILURE stage=%s\n' "$$stage_name" >&2; \
			setup_failed=1; \
			setup_cause="$$stage_name"; \
		fi; \
		return $$stage_status; \
	}; \
	skip_setup_blocked_stage() { \
		stage_name="$$1"; \
		setup_blocker="$$2"; \
		printf 'SETUP_FAILURE stage=%s cause=%s outcome=SKIP\n' "$$stage_name" "$$setup_blocker" >&2; \
		printf 'VERIFY_STAGE %s FAIL\n' "$$stage_name"; \
		record_failure "$$stage_name"; \
	}; \
	run_setup_stage test-db-reset $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') test-db-reset; \
	if [ $$setup_failed -eq 0 ]; then \
		run_setup_stage migrate $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') migrate; \
	else \
		skip_setup_blocked_stage migrate "$$setup_cause"; \
	fi; \
	run_stage architecture-check $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') architecture-check; \
	run_stage lint $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') lint; \
	run_stage unit-test $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') unit-test; \
	if [ $$setup_failed -eq 0 ]; then \
		run_stage db-test $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') db-test; \
	else \
		skip_setup_blocked_stage db-test "$$setup_cause"; \
	fi; \
	run_stage characterization-test $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') characterization-test; \
	if [ $$setup_failed -eq 0 ]; then \
		run_stage e2e-test $(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') e2e-test; \
	else \
		skip_setup_blocked_stage e2e-test "$$setup_cause"; \
	fi; \
	run_stage diff-check git diff --check; \
	if [ $$failed_count -ne 0 ]; then \
		printf 'FULL_VERIFICATION_FAILURE count=%s stages=%s\n' "$$failed_count" "$$failures"; \
		exit 1; \
	fi; \
	printf 'VERIFY_OK\n'

fresh-test-verify:
	@set +e; \
	$(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') verify; \
	verify_status=$$?; \
	$(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') test-env-down; \
	teardown_status=$$?; \
	if [ $$teardown_status -ne 0 ]; then \
		printf 'SETUP_FAILURE stage=test-env-down\n' >&2; \
	fi; \
	if [ $$verify_status -eq 0 ] && [ $$teardown_status -eq 0 ]; then \
		printf 'FRESH_TEST_VERIFY_OK\n'; \
		exit 0; \
	fi; \
	printf 'FRESH_TEST_VERIFY_FAILURE verify_status=%s teardown_status=%s\n' "$$verify_status" "$$teardown_status"; \
	if [ $$verify_status -ne 0 ]; then exit $$verify_status; fi; \
	exit $$teardown_status
