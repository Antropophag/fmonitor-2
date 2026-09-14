## Why

После завершения чистого Yii2 runtime операторский Compose умеет поднять стенд, но обычный локальный пользователь всё ещё попадает через стандартный `make up` в legacy `rapid-pilot` и вынужден вручную повторять production runbook. Issue #128 закрывает последний метр local DX: чистый checkout и один заполненный `.env` должны приводиться к готовому Yii2-стенду стандартными Make-командами.

## What Changes

- **BREAKING**: `make up`, `make down`, `make logs` и `make ps` переключаются с legacy Compose на `deploy/runtime/compose.yaml` и актуальный Yii2 runtime.
- `make up` становится идемпотентным orchestration seam: build, запуск MariaDB, безопасный bootstrap DML account, `prepare`, migrations, initial-owner provisioning, запуск `php`/`web` и readiness.
- `.env.example` становится полным, безопасным шаблоном local Yii2 runtime без обязательной Bitrix/production конфигурации.
- `make reset` остаётся единственной явной разрушительной local-командой и удаляет только ресурсы выбранного Compose project.
- Production runbook и низкоуровневые Compose/CLI seams сохраняются без упрощения эксплуатационных гарантий.
- Legacy rapid-pilot сохраняется как исторический oracle/adapter, но перестаёт быть зависимостью canonical local startup.

## Capabilities

### New Capabilities

- `operations/canonical-yii2-local-quickstart`: пользовательский контракт чистого локального запуска и управления актуальным Yii2 runtime через Make.

### Modified Capabilities

- Нет.

## Impact

- Owner issue: #128.
- Behavior slice: `YII2-LOCAL-QUICKSTART-001`.
- Actor: локальный разработчик или владелец тестового стенда.
- Source oracle: принятый clean-runtime lifecycle из PR #127 и `docs/operations/production-runtime-runbook.md`.
- Target public seam: `make up/down/logs/ps/reset` и `.env.example`.
- Release value: воспроизводимый `git clone → .env → make up` без ручного изображения CI/CD pipeline.
- Non-goals: production deployment/cutover, импорт production-данных, автоматическое включение jobs/Bitrix, удаление legacy исходников или существующих volumes, изменение доменной логики.
