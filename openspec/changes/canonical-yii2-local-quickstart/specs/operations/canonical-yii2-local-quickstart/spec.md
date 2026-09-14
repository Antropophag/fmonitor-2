## Purpose

Определяет безопасный и воспроизводимый пользовательский путь от чистого checkout и заполненного `.env` до готового локального Yii2-стенда через стандартные Make-команды.

## ADDED Requirements

### Requirement: Canonical clean local startup
Система SHALL предоставлять `make up` как единый публичный local orchestration seam, который из чистого checkout с валидным `.env` собирает актуальный Yii2 image, поднимает изолированную БД, безопасно создаёт runtime DB account, выполняет storage preparation и canonical migrations, идемпотентно создаёт первого owner-admin, запускает `php` и `web` и завершается успешно только после readiness.

#### Scenario: Первый запуск из чистого checkout
- **WHEN** пользователь копирует `.env.example` в `.env`, задаёт обязательные local secrets и email первого owner, затем выполняет `make up`
- **THEN** команда завершается с кодом 0, печатает URL, а `/health/live` и `/health/ready` актуального Yii2 runtime отвечают успешно

#### Scenario: Неполная конфигурация отклоняется до эффектов
- **WHEN** обязательное значение отсутствует или осталось явным placeholder
- **THEN** `make up` завершается ненулевым кодом с безопасной диагностикой и не публикует готовность стенда

### Requirement: Idempotent convergence
`make up` MUST быть безопасен для повторного запуска с тем же Compose project и конфигурацией: он SHALL сохранять существующие данные и identities, применять только недостающие preparation/migrations и принимать exact replay initial-owner provisioning без ручных SQL или удаления volumes.

#### Scenario: Повторный запуск существующего стенда
- **WHEN** пользователь повторно выполняет `make up` для уже подготовленного local project
- **THEN** стенд снова или по-прежнему ready, существующие пользователи, процессные данные, sessions и artifacts не удалены и не переинициализированы

#### Scenario: Несовместимое identity state
- **WHEN** initial-owner provisioning обнаруживает partial или отличающееся существующее identity state
- **THEN** команда fail-closed сообщает `IDENTITY_NOT_EMPTY` либо эквивалентную безопасную причину и не повышает существующего пользователя

### Requirement: Consistent lifecycle commands
`make down`, `make logs` и `make ps` SHALL управлять тем же `deploy/runtime/compose.yaml` и тем же project identity, что и `make up`; обычный local lifecycle MUST не зависеть от запуска `rapid-pilot`.

#### Scenario: Остановка сохраняет данные
- **WHEN** пользователь выполняет `make down`
- **THEN** сервисы canonical Yii2 project остановлены, а именованные database/state/secrets volumes сохранены

#### Scenario: Наблюдение выбранного проекта
- **WHEN** пользователь выполняет `make ps` или `make logs`
- **THEN** вывод относится к сервисам выбранного canonical Yii2 Compose project, а не к legacy pilot project

### Requirement: Explicit destructive reset
Удаление локальных данных SHALL выполняться только отдельной командой `make reset`, которая MUST явно ограничивать действие текущим canonical Compose project и не затрагивать соседние либо production resources.

#### Scenario: Явный reset выбранного local project
- **WHEN** пользователь осознанно выполняет `make reset` с валидной local configuration
- **THEN** Compose containers и named volumes только выбранного local project удаляются, а исходники, private environment других проектов и соседние Compose resources сохраняются

#### Scenario: Обычная остановка не эквивалентна reset
- **WHEN** пользователь выполняет `make down`
- **THEN** named volumes не удаляются

### Requirement: Local template and operational separation
`.env.example` SHALL документировать полный минимальный local Yii2 contract без production credentials и без обязательного Bitrix/jobs setup. Production runbook MUST сохраняться как отдельный низкоуровневый operational path; local quickstart SHALL не утверждать production deployment или CI approval.

#### Scenario: Local configuration without integrations
- **WHEN** пользователь заполняет только обязательные local runtime, database и initial-owner значения
- **THEN** `make up` поднимает основной web runtime без Bitrix token, production source access и jobs profile

#### Scenario: Production operations remain available
- **WHEN** оператору нужны exact image, DML grant verification, backup, update или jobs operations
- **THEN** существующий production runbook и низкоуровневые Compose/CLI команды остаются доступными и не заменяются Make quickstart
