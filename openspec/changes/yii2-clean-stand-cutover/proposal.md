## Why

Owner decision от 2026-09-14 заменяет restore-based closure issue #76 на clean Yii2 stand deployment: legacy stand не содержит обязательных production records, поэтому перенос его БД, sessions, jobs/outbox и artifacts не является acceptance gate. Актуальный `origin/main` `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae` уже содержит основные Yii2 runtime capabilities; нужен один ограниченный verification/operational slice, который докажет их совместную работу на fresh disposable target до отдельно авторизуемого production cutover.

## What Changes

- Добавить executable clean-stand acceptance через canonical production Compose и публичные Yii2 web/console/jobs seams.
- Связать acceptance с exact source, immutable image digest и exact attest-нутым clean/disposable target без implicit discovery.
- Доказать fresh MariaDB creation, runtime preparation, canonical migrations и idempotent initial-user provisioning.
- Доказать startup/readiness web, PHP-FPM, worker и scheduler, а также normal jobs/outbox execution.
- Выполнить representative FKR, construction-control, checklist и OTIZ golden flows на новом пустом контуре.
- Доказать process/include closure normal production paths без `rapid-pilot` и `RuntimeRecovery`.
- Если acceptance текущего `main` уже GREEN, ограничить delivery verification/evidence и не создавать искусственный production delta.
- Не выполнять production cutover или удаление старого stand в рамках change; оба действия требуют последующей exact owner authorization.

## Capabilities

### New Capabilities

- `operations/yii2-clean-stand-cutover`: fresh provisioning и executable acceptance exact Yii2 stand candidate до отдельно авторизуемого production cutover.

### Modified Capabilities

Нет. Существующие runtime, migrations, authentication/access, jobs, web/image и optional backup/restore contracts не изменяются.

## Impact

- Actor: owner-authorized deployment operator.
- Source oracle: issue #76 owner decision 2026-09-14, `PRODUCT.md`, `CONTEXT.md`, canonical production Compose/runbook и landed capability code на `origin/main`.
- Target public seams: Docker Compose из `deploy/runtime/compose.yaml`, `php bin/yii schema-migrate/run`, existing initial-owner provisioning CLI, Yii2 HTTP routes, `php bin/yii jobs/*` и read-only target/image inspection.
- Release value: доказанный clean Yii2 candidate без зависимости cutover от legacy data restore или unresolved UNKNOWN reconciliation.
- Affected areas: executable deployment acceptance tests/harness, operator evidence/runbook, Quality Graph verification input и current delivery pointer. Production code меняется только если approved RED выявит реальный пробел.
- Non-goals: legacy data migration, restore rehearsal, rollback, UNKNOWN reconciliation, `RuntimeRecovery` retirement, unrelated cleanup, production cutover execution и удаление старого stand.
