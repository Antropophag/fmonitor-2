## Why

Production case import всё ещё запускается отдельным PHP entrypoint с собственной environment/DB composition. После двух Gate 3 review исходный пакет из трёх операций разделён: текущий срез №76 переносит только case import через общий Yii2 console runtime, чтобы его полный eligibility/concurrency/reconciliation oracle можно было независимо проверить и поставить.

## What Changes

- Добавить production Yii2 command для выбранного case import.
- Оставить единственным владельцем изменений существующий `PilotCaseImporter`; Yii controller только валидирует transport inputs, собирает зависимости и делегирует.
- Сохранить JSON/exit-code contract, eligibility, idempotency, retry/concurrency, unknown-commit reconciliation и redaction.
- Перевести repository-owned callers на Yii2 command; `bin/fmonitor2-import-cases.php` оставить тонким совместимым alias либо удалить после доказательства отсутствия callers.
- Доказать общий Composer/Yii runtime и отсутствие production-загрузки `rapid-pilot`, demo или HTTP/session composition.

## Capabilities

### New Capabilities

- `operations/yii2-imports-workforce`: Yii2 console transport для существующего production case import; snapshot/workforce части capability отложены в отдельные changes.

### Modified Capabilities

Нет: eligibility и предметные import requirements не меняются.

## Impact

Затрагиваются Yii2 console controller/adapter, `bin/fmonitor2-import-cases.php`, доказанные callers, package/load contract и case-import tests. Source oracle: issue #76 и `PILOT-CASE-IMPORT-001`. Не входят snapshot import, workforce sync, schema, web, runtime recovery, stand deployment и общий cutover.
