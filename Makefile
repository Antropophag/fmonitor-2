.DEFAULT_GOAL := help

COMPOSE := docker compose
FMONITOR_LOCAL_ENV_FILE ?= .env
RUNTIME_COMPOSE := docker compose --env-file "$(FMONITOR_LOCAL_ENV_FILE)" -f deploy/runtime/compose.yaml
TEST_TOOL_IMAGE ?= fmonitor2-php-test:latest

# Local lifecycle configuration is accepted only from the checkout's .env.
COMPOSE_PROJECT_NAME :=
FMONITOR_RUNTIME_IMAGE :=
FMONITOR_HTTP_PORT :=
FMONITOR_DB_NAME :=
FMONITOR_DB_USER :=
FMONITOR_DB_PASSWORD :=
FMONITOR_MIGRATION_DB_USER :=
FMONITOR_MIGRATION_DB_PASSWORD :=
FMONITOR_PROCESS_TABLE_PREFIX :=
FMONITOR_LEGACY_TABLE_PREFIX :=
FMONITOR_SESSION_INSTANCE :=
FMONITOR_YII_COOKIE_VALIDATION_KEY :=
FMONITOR_YII_IDENTITY_KEY :=
FMONITOR_TRUSTED_REQUEST_HOST :=
FMONITOR_TRUSTED_REQUEST_SCHEME :=
FMONITOR_INITIAL_OWNER_EMAIL :=
FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD :=
-include $(FMONITOR_LOCAL_ENV_FILE)
export COMPOSE_PROJECT_NAME FMONITOR_RUNTIME_IMAGE FMONITOR_HTTP_PORT FMONITOR_DB_NAME FMONITOR_DB_USER FMONITOR_DB_PASSWORD
export FMONITOR_MIGRATION_DB_USER FMONITOR_MIGRATION_DB_PASSWORD FMONITOR_PROCESS_TABLE_PREFIX FMONITOR_LEGACY_TABLE_PREFIX
export FMONITOR_SESSION_INSTANCE FMONITOR_YII_COOKIE_VALIDATION_KEY FMONITOR_YII_IDENTITY_KEY FMONITOR_TRUSTED_REQUEST_HOST
export FMONITOR_TRUSTED_REQUEST_SCHEME FMONITOR_INITIAL_OWNER_EMAIL FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD

define validate_local_environment
	@test -f "$(FMONITOR_LOCAL_ENV_FILE)" || { echo "LOCAL_CONFIG_INVALID: создайте regular env file из .env.example" >&2; exit 64; }; \
	set -eu; \
	for value in "$(COMPOSE_PROJECT_NAME)" "$(FMONITOR_RUNTIME_IMAGE)" "$(FMONITOR_HTTP_PORT)" "$(FMONITOR_DB_NAME)" "$(FMONITOR_DB_USER)" "$(FMONITOR_DB_PASSWORD)" "$(FMONITOR_MIGRATION_DB_USER)" "$(FMONITOR_MIGRATION_DB_PASSWORD)" "$(FMONITOR_PROCESS_TABLE_PREFIX)" "$(FMONITOR_LEGACY_TABLE_PREFIX)" "$(FMONITOR_SESSION_INSTANCE)" "$(FMONITOR_YII_COOKIE_VALIDATION_KEY)" "$(FMONITOR_YII_IDENTITY_KEY)" "$(FMONITOR_TRUSTED_REQUEST_HOST)" "$(FMONITOR_TRUSTED_REQUEST_SCHEME)" "$(FMONITOR_INITIAL_OWNER_EMAIL)" "$(FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD)"; do \
		case "$$value" in ''|*replace_me*|replace_with_*) echo "LOCAL_CONFIG_INVALID: заполните все обязательные значения .env" >&2; exit 64;; esac; \
	done; \
	case "$(COMPOSE_PROJECT_NAME)" in fm2-local-?*) ;; *) echo "LOCAL_CONFIG_INVALID: COMPOSE_PROJECT_NAME должен иметь вид fm2-local-<name>" >&2; exit 64;; esac; \
	case "$(COMPOSE_PROJECT_NAME)" in *[!A-Za-z0-9_-]*) echo "LOCAL_CONFIG_INVALID: недопустимый COMPOSE_PROJECT_NAME" >&2; exit 64;; esac; \
	case "$(FMONITOR_HTTP_PORT)" in ''|*[!0-9]*) echo "LOCAL_CONFIG_INVALID: недопустимый FMONITOR_HTTP_PORT" >&2; exit 64;; esac; \
	[ "$(FMONITOR_HTTP_PORT)" -ge 1 ] 2>/dev/null && [ "$(FMONITOR_HTTP_PORT)" -le 65535 ] 2>/dev/null || { echo "LOCAL_CONFIG_INVALID: недопустимый FMONITOR_HTTP_PORT" >&2; exit 64; }; \
	[ "$$(printf %s "$(FMONITOR_YII_COOKIE_VALIDATION_KEY)" | wc -c | tr -d ' ')" -ge 32 ] && [ "$$(printf %s "$(FMONITOR_YII_IDENTITY_KEY)" | wc -c | tr -d ' ')" -ge 32 ] || { echo "LOCAL_CONFIG_INVALID: Yii keys должны быть не короче 32 байт" >&2; exit 64; }; \
	case "$(FMONITOR_TRUSTED_REQUEST_SCHEME)" in http|https) ;; *) echo "LOCAL_CONFIG_INVALID: trusted scheme должен быть http или https" >&2; exit 64;; esac; \
	case "$(FMONITOR_TRUSTED_REQUEST_HOST)" in *:*) ;; *) echo "LOCAL_CONFIG_INVALID: trusted host должен включать порт" >&2; exit 64;; esac; \
	case "$(FMONITOR_INITIAL_OWNER_EMAIL)" in *@*.*) ;; *) echo "LOCAL_CONFIG_INVALID: недопустимый initial owner email" >&2; exit 64;; esac
endef

.PHONY: help up down logs ps reset import-production \
	test-env-up test-env-down test-db-reset migrate unit-test db-test \
	characterization-test e2e-test architecture-check lint test verify fresh-test fresh-test-verify ci-setup test-tools setup doctor

help:
	@echo "make setup  Подготовить закреплённые зависимости (без изменения существующих)"
	@echo "make doctor Проверить инструменты и существующие зависимости"
	@echo "make up     Собрать и поднять локальный Yii2 runtime (настройки в .env)"
	@echo "make import-production  Загрузить не начатые объекты, пользователей и роли production"
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

up:
	$(validate_local_environment); \
	docker info >/dev/null 2>&1 || { echo "LOCAL_DOCKER_UNAVAILABLE" >&2; exit 69; }; \
	docker build --file deploy/runtime/Dockerfile --tag "$(FMONITOR_RUNTIME_IMAGE)" .; \
	$(RUNTIME_COMPOSE) config --quiet; \
	$(RUNTIME_COMPOSE) up --detach --wait db; \
	$(RUNTIME_COMPOSE) --profile deployment run --rm -e FMONITOR_MIGRATION_DB_USER -e FMONITOR_MIGRATION_DB_PASSWORD --entrypoint php prepare bin/yii local-runtime/provision-database --interactive=0; \
	$(RUNTIME_COMPOSE) --profile deployment run --rm prepare; \
	$(RUNTIME_COMPOSE) --profile deployment run --rm migrate; \
	$(RUNTIME_COMPOSE) --profile deployment run --rm --entrypoint php prepare bin/fmonitor2-runtime-check.php; \
	$(RUNTIME_COMPOSE) --profile deployment run --rm -e FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD --entrypoint php prepare bin/fmonitor2-provision-initial-admin.php --email "$(FMONITOR_INITIAL_OWNER_EMAIL)"; \
	$(RUNTIME_COMPOSE) up --detach --wait php web; \
	curl --fail --silent --show-error --header "Host: $(FMONITOR_TRUSTED_REQUEST_HOST)" "http://127.0.0.1:$(FMONITOR_HTTP_PORT)/health/live" >/dev/null; \
	curl --fail --silent --show-error --header "Host: $(FMONITOR_TRUSTED_REQUEST_HOST)" "http://127.0.0.1:$(FMONITOR_HTTP_PORT)/health/ready" >/dev/null; \
	echo "FMonitor Yii2: http://127.0.0.1:$(FMONITOR_HTTP_PORT)/"

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
	$(validate_local_environment); $(RUNTIME_COMPOSE) down

logs:
	$(validate_local_environment); $(RUNTIME_COMPOSE) logs --follow

ps:
	$(validate_local_environment); $(RUNTIME_COMPOSE) ps

reset:
	$(validate_local_environment); $(RUNTIME_COMPOSE) down --volumes --remove-orphans

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
