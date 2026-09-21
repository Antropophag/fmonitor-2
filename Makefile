.DEFAULT_GOAL := help

COMPOSE := docker compose
LOCAL_ENV_RUN := bash tools/delivery/local-runtime-env --
LOCAL_ENV_VALIDATE := bash tools/delivery/local-runtime-env --validate
RUNTIME_COMPOSE := $(LOCAL_ENV_RUN) docker compose --env-file '@env-file' -f deploy/runtime/compose.yaml
TEST_TOOL_IMAGE ?= fmonitor2-php-test:latest

.PHONY: help up up-with-data down logs ps reset import-production import-legacy sync-workforce register-test \
	test-env-up test-env-down test-db-reset migrate unit-test db-test \
	characterization-test e2e-test architecture-check lint test verify fresh-test fresh-test-verify ci-setup test-tools setup doctor

help:
	@echo "make setup  Подготовить закреплённые зависимости (без изменения существующих)"
	@echo "make doctor Проверить инструменты и существующие зависимости"
	@echo "make up     Собрать и поднять локальный Yii2 runtime (настройки в .env)"
	@echo "make import-production  Совместимый alias для make import-legacy"
	@echo "make down   Остановить Yii2 runtime, сохранив данные"
	@echo "make logs   Показать логи"
	@echo "make ps     Показать состояние контейнеров"
	@echo "make reset  Явно удалить данные выбранного local Yii2 project"
	@echo "make test-env-up/down  Поднять/остановить disposable test MariaDB"
	@echo "make test-db-reset    Пересоздать чистую test DB"
	@echo "make migrate          Применить canonical production migrations к test DB"
	@echo "make unit-test/db-test/characterization-test/e2e-test"
	@echo "make architecture-check  Проверить machine-checkable boundaries"
	@echo "make test CATEGORY=unit|integration|e2e|governance  Выбранная категория"
	@echo "make test             Полная clean-checkout проверка"
	@echo "make fresh-test         Полная проверка с обязательным test-env teardown"
	@echo "make register-test FILE=... CATEGORY=... RUNTIME=... SUITE=..."

register-test:
	@python3 tools/verification/inventory.py register --file "$(FILE)" --category "$(CATEGORY)" --runtime "$(RUNTIME)" --suite "$(SUITE)"

up:
	@$(LOCAL_ENV_VALIDATE)
	@if grep -q '^FMONITOR_BITRIX_WEBHOOK_URL=' .env || grep -q '^FMONITOR_BITRIX_DEPARTMENT_IDS_JSON=' .env; then \
		tools/delivery/local-integration-config stage bitrix .env .local/bitrix-workforce.json; \
	else \
		tools/delivery/local-integration-config bitrix .local/bitrix-workforce.json; \
	fi
	@$(LOCAL_ENV_RUN) docker info >/dev/null 2>&1 || { echo "LOCAL_DOCKER_UNAVAILABLE" >&2; exit 69; }
	$(LOCAL_ENV_RUN) docker build --file deploy/runtime/Dockerfile --tag '@env:FMONITOR_RUNTIME_IMAGE' .
	$(RUNTIME_COMPOSE) config --quiet
	$(RUNTIME_COMPOSE) up --detach --wait db
	$(RUNTIME_COMPOSE) --profile deployment run --rm -e FMONITOR_MIGRATION_DB_USER -e FMONITOR_MIGRATION_DB_PASSWORD --entrypoint php prepare bin/yii local-runtime/provision-database --interactive=0
	$(RUNTIME_COMPOSE) --profile deployment run --rm prepare
	$(RUNTIME_COMPOSE) --profile deployment run --rm migrate
	$(RUNTIME_COMPOSE) --profile deployment run --rm --entrypoint php prepare bin/fmonitor2-runtime-check.php
	$(RUNTIME_COMPOSE) --profile deployment run --rm -e FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD --entrypoint php prepare bin/fmonitor2-provision-initial-admin.php --resume-existing-local --email '@env:FMONITOR_INITIAL_OWNER_EMAIL'
	$(RUNTIME_COMPOSE) up --detach --wait php web jobs-worker jobs-scheduler
	@$(RUNTIME_COMPOSE) exec -T jobs-worker php bin/yii jobs/process-health --interactive=0 >/dev/null
	@$(LOCAL_ENV_RUN) curl --fail --silent --show-error --header '@trusted-host-header' '@local-url/health/live' >/dev/null
	@$(LOCAL_ENV_RUN) curl --fail --silent --show-error --header '@trusted-host-header' '@local-url/health/ready' >/dev/null
	@$(LOCAL_ENV_RUN) printf 'FMonitor Yii2: %s/\n' '@local-url'

import-legacy:
	@tools/delivery/local-integration-config stage legacy .env .local/legacy-source.env
	@tools/delivery/local-integration-config legacy .local/legacy-source.env
	@$(RUNTIME_COMPOSE) --profile deployment run --rm --no-deps --volume "$$(pwd)/.local/legacy-source.env:/run/fmonitor-input/config:ro" local-integration legacy /run/fmonitor-input/config -- php bin/yii legacy-import/run --interactive=0

sync-workforce:
	@tools/delivery/local-integration-config stage bitrix .env .local/bitrix-workforce.json
	@tools/delivery/local-integration-config bitrix .local/bitrix-workforce.json
	@$(RUNTIME_COMPOSE) --profile deployment run --rm --no-deps --volume "$$(pwd)/.local/bitrix-workforce.json:/run/fmonitor-input/config:ro" '@optional-volume:FMONITOR_BITRIX_CA_FILE_HOST:/run/fmonitor-input/bitrix-ca.pem:ro' '@optional-environment:FMONITOR_BITRIX_CA_FILE_HOST:FMONITOR_BITRIX_CA_INPUT=/run/fmonitor-input/bitrix-ca.pem' local-integration bitrix /run/fmonitor-input/config -- php bin/yii workforce-sync/run --interactive=0

up-with-data:
	@tools/delivery/local-integration-config stage legacy .env .local/legacy-source.env
	@tools/delivery/local-integration-config stage bitrix .env .local/bitrix-workforce.json
	@$(MAKE) --no-print-directory up
	@$(MAKE) --no-print-directory import-legacy
	@$(MAKE) --no-print-directory sync-workforce
	@echo "FMonitor Yii2 with production data: ready"

import-production: import-legacy

down:
	$(RUNTIME_COMPOSE) down

logs:
	$(RUNTIME_COMPOSE) logs --follow

ps:
	$(RUNTIME_COMPOSE) ps

reset: ; $(RUNTIME_COMPOSE) down --volumes --remove-orphans

test-env-up:
	docker compose -f compose.test.yaml up --detach --wait test-db

test-env-down: ; docker compose -f compose.test.yaml down --volumes --remove-orphans

test-db-reset: test-env-up
	@FMONITOR_TEST_DB_PORT="$${FMONITOR_TEST_DB_PORT:-23306}" php tools/verification/reset-test-db.php

migrate:
	@FMONITOR_DB_HOST="$${FMONITOR_TEST_DB_HOST:-127.0.0.1}" \
	FMONITOR_DB_PORT="$${FMONITOR_TEST_DB_PORT:-23306}" \
	FMONITOR_DB_NAME="$${FMONITOR_TEST_DB_NAME:-fmonitor2_test}" \
	FMONITOR_DB_USER="$${FMONITOR_TEST_DB_USER:-fmonitor2_test}" \
	FMONITOR_DB_PASSWORD="$${FMONITOR_TEST_DB_PASSWORD:-fmonitor2_test_local}" \
	FMONITOR_PROCESS_TABLE_PREFIX= php bin/yii schema-migrate/run --interactive=0

unit-test:
	@bash tools/verification/run.sh unit

db-test: test-env-up
	@bash tools/verification/run.sh db

characterization-test:
	@bash tools/verification/run.sh characterization

e2e-test: test-env-up
	@bash tools/verification/run.sh e2e

architecture-check:
	@php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
	@tools/architecture/check

lint:
	@bash tools/verification/run.sh lint

verify: test

ifneq ($(strip $(CATEGORY)),)
test:
	@bash tools/verification/run.sh category "$(CATEGORY)" $(if $(strip $(SHARD)),--shard "$(SHARD)")
else
test:
	@set +e; failures=""; failed_count=0; setup_failed=0; setup_cause=""; \
	record_failure() { \
		failed_stage="$$1"; \
		failed_count=$$((failed_count + 1)); \
		failures="$${failures}$${failures:+,}$${failed_stage}"; \
	}; \
	run_stage() { \
		stage_name="$$1"; shift; \
		stage_started=$$(date +%s); \
		"$$@"; stage_status=$$?; \
		printf 'VERIFY_STAGE_TIMING stage=%s seconds=%s exit=%s\n' "$$stage_name" "$$(( $$(date +%s) - stage_started ))" "$$stage_status"; \
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

endif

fresh-test-verify: fresh-test

fresh-test:
	@set +e; \
	$(MAKE) --no-print-directory $(foreach file,$(MAKEFILE_LIST),-f '$(file)') test CATEGORY=; \
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

setup:
	@bash tools/delivery/setup.sh

doctor:
	@bash tools/delivery/setup.sh --check

ci-setup: setup

.PHONY: quality-graph-validate
quality-graph-validate:
	python3 tools/delivery/check-current-quality-graph.py
	.venv/bin/python tools/delivery/render-current-quality-graph.py --check

test-tools:
	docker build --label "org.opencontainers.image.revision=$$(git rev-parse HEAD)" \
		-t "$(TEST_TOOL_IMAGE)" -f tools/verification/Dockerfile.test tools/verification
