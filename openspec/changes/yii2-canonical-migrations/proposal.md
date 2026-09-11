## Why

После переноса jobs production migration остаётся отдельным самописным CLI entrypoint и не проходит через общую Yii2 console composition. Следующий ограниченный срез №76 должен дать операторам один Yii2-вход к уже утверждённому canonical migration application, не меняя schema contract и не повторяя применённые фазы при upgrade.

## What Changes

- Добавить production Yii2 console command `php bin/yii schema-migrate/run` для canonical migrations с сохранением существующего JSON/exit-code контракта, блокировки и redaction.
- Оставить один migration application/ledger owner: Yii command делегирует существующему `CanonicalMigrationApplication`, не переносит DDL в controller и не вводит параллельный Yii migration ledger.
- Перевести production Compose/Make migration invocation на общий Yii2 console entrypoint.
- Сохранить совместимость `bin/fmonitor2-migrate.php` как тонкого временного alias либо удалить его только после проверки всех production callers; оба входа не могут иметь независимую логику.
- Проверить fresh install, повторный запуск, upgrade существующей частично/полностью мигрированной БД, конкурентный запуск, ошибочную конфигурацию и database/storage failures без утечки секретов.
- Удалить production runtime dependency migration entrypoint от ручного autoload/bootstrap, сохранив исторические verifiers как oracle.

## Capabilities

### New Capabilities

- `operations/yii2-canonical-migrations`: единый Yii2 console seam для безопасного запуска существующего canonical migration catalogue и ledger.

### Modified Capabilities

Нет: schema и предметные migration requirements не меняются; новый delta описывает только новый публичный operational seam и совместимость.

## Impact

Затрагиваются Yii2 console configuration/controller, `bin/fmonitor2-migrate.php`, production Compose/Make callers, runtime packaging и focused migration verifiers. MariaDB schema, migration catalogue, данные, web/jobs runtime и imports не меняются. Не входят imports, workforce sync, runtime prepare/check/recovery, OTIZ, web cutover, deployment рабочего стенда и общий closure №76.
