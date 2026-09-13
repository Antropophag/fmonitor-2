## Purpose

Определяет проверяемую границу production image после перехода FMonitor на единый Yii2 runtime без поставки временного rapid-pilot слоя.

## ADDED Requirements

### Requirement: Production image не содержит rapid-pilot runtime

Production runtime image MUST собираться без каталога `rapid-pilot`, его PHP/shell
entrypoints, demo router и build-time verifier. Исторический oracle MAY оставаться
в repository и отдельном pilot demo image.

#### Scenario: Проверка файловой системы image

- **WHEN** оператор собирает production runtime image из зафиксированного source
- **THEN** внутри image отсутствует `/workspace/fmonitor-2/rapid-pilot`
- **AND** присутствуют Yii2 web/console entrypoints, configuration, application code, assets и locked vendor

#### Scenario: Проверка build recipe

- **WHEN** verification читает canonical template и generated Dockerfile
- **THEN** ни один production recipe не копирует и не исполняет `rapid-pilot`

### Requirement: Yii2 runtime contracts сохраняются после очистки image

Production image MUST сохранять non-root identity, Yii2 HTTP front controller и
console commands с прежними status/exit/output contracts. Очистка MUST NOT менять
schema, persistent facts, sessions, documents, jobs или application owners.

#### Scenario: Закрытая console ошибка

- **WHEN** packaged `php bin/yii schema-migrate/run --interactive=0` запущен без обязательной DB configuration
- **THEN** команда завершается кодом 64 с exact safe JSON и пустым stderr

#### Scenario: Web live endpoint

- **WHEN** packaged `public/runtime.php` обслуживает `GET /health/live`
- **THEN** запрос проходит через Yii2 и возвращает принятый safe live response без rapid-pilot include

### Requirement: Очистка не расширяет поставку

Production image MUST исключать repository-only tests, reviews, specs, docs, tools,
git metadata, local evidence и explicit secret-like inventory: `.env*`, `auth.json`,
`*.dump`, `*.sql.gz`, `*.bak`, `*.key`, `*.pem`, `*.p12`, `*.pfx`, `*.pk8`,
`*.crt`, `*.cer`, `*.der`, `*.p7b`, `*.p7c`, `*.msg`. Удаление rapid-pilot
MUST NOT приводить к копированию этих источников как замене отсутствующего runtime.

#### Scenario: Проверка allowlist артефакта

- **WHEN** verification инспектирует fresh-built image
- **THEN** repository-only roots и secret-like files отсутствуют, а процесс работает как uid 10001
