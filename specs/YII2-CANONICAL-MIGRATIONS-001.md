# YII2-CANONICAL-MIGRATIONS-001 — canonical schema через Yii2 console

Status: `ACCEPTED_FOR_GATE_2`
Owner authorization: issue #76, autonomous decomposition decision 2026-09-09 and continuation 2026-09-11
Actor: production deployment operator
Public seam: `php bin/yii schema-migrate/run` и временный compatibility alias `php bin/fmonitor2-migrate.php`
OpenSpec: `openspec/changes/yii2-canonical-migrations/`

## Простыми словами

Оператор применяет ту же проверенную canonical schema через общий Yii2 console runtime. Новый вход не создаёт второй migration ledger, не меняет таблицы или данные и не запускается из web/jobs runtime. Imports, восстановление runtime и переключение рабочего стенда остаются следующими срезами №76.

## A1. Закрытый CLI transport

Production command SHALL принимать ровно route `schema-migrate/run` без positional arguments. Единственная разрешённая command option — стандартная Yii `--interactive=0` ровно после route; она обязательна для repository-owned production callers. Иные options, `--interactive`, `--interactive=1`, дубли и перестановки SHALL отклоняться. При любом terminal результате команда SHALL вывести ровно один JSON object и newline в stdout. Успех SHALL завершаться exit `0`; invalid route/options/environment — exit `64` и `{"ok":false,"reason":"CONFIGURATION_INVALID"}`; database unavailable, включая connect/charset failure, — exit `69` и `{"ok":false,"reason":"DATABASE_UNAVAILABLE"}`; неизвестный `Throwable` — exit `70` и `{"ok":false,"reason":"SOFTWARE_ERROR"}`.

До соединения обязательны строковые `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT`, `FMONITOR_DB_NAME`, `FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, `FMONITOR_PROCESS_TABLE_PREFIX`. Host/name/user SHALL быть непустыми; port SHALL быть canonical decimal `1..65535`; prefix SHALL соответствовать `^[A-Za-z0-9_]{0,25}$`. Пустой пароль разрешён. Rejection до application invocation не создаёт schema/data facts.

Пароль, DB coordinates, prefix, environment/file content, exception message/class/trace и SQL SHALL отсутствовать в stdout/stderr. Yii bootstrap warning/usage SHALL не предшествовать и не следовать terminal JSON.

## A2. Единственный migration owner

Yii controller SHALL быть transport/composition adapter к существующему `InstallationProcess\\CanonicalMigrationApplication`, `ProductionPilotMigrationCatalogue` и `MariaDbMigrationLock`. Он SHALL владеть ровно одним mysqli connection lifecycle на запуск и SHALL не использовать `yii\\db\\Connection`, ActiveRecord, `yii migrate`, Yii table `migration` или собственный DDL/ledger. Все schema phases, starting/terminal version, lock, restart и exact readiness semantics наследуются из `PRODUCTION-MIGRATION-RUNNER-001` без изменения.

## A3. Fresh, repeat и upgrade

На пустой совместимой MariaDB успешная команда SHALL применить непрерывный текущий production catalogue, вернуть существующий successful JSON с starting/terminal version и завершиться только после exact readiness всех owned tables. Повтор на этом состоянии SHALL быть idempotent: ни schema, ни rows, ни завершённые ledger phases не меняются.

На точном завершённом predecessor состоянии команда SHALL применять только отсутствующий suffix каталога и SHALL сохранять все прежние rows и append-only history. Совместимое restartable partial состояние SHALL продолжаться с первой незавершённой фазы. Несовместимая partial/schema/ledger форма SHALL fail closed согласно существующему owner, без repair, destructive rebuild, удаления или переписывания данных.

## A4. Replay, concurrency и неопределённый исход

Два независимых process invocation для одной database/prefix SHALL сериализоваться существующей database lock boundary. Только владелец lock выполняет migration phases; конкурент получает существующий bounded lock outcome. Accepted ledger phase не записывается дважды. После process interruption следующий запуск SHALL определять продолжение только по durable canonical schema/ledger, не по output предыдущего процесса; UNKNOWN outcome не считается success.

## A5. Compatibility alias и production callers

`bin/fmonitor2-migrate.php`, пока он нужен repository callers/verifiers, SHALL быть тонким launcher alias к тому же Yii command. Он SHALL не содержать environment validation, connection construction, catalogue assembly, DDL или exception mapping и SHALL возвращать тот же stdout/stderr/exit/schema result для одинакового входа.

`make migrate` и `deploy/runtime/compose.yaml` migration service SHALL вызывать canonical Yii route через реальный Composer/Yii runtime. Достижимый startup/load set command SHALL не включать `rapid-pilot`, `app/demo`, active manifest, web/session/jobs composition или Yii migration ledger. Runtime image SHALL сохранять mysqli extension и общую locked Composer installation. Web, worker и scheduler SHALL не запускать migrations.

## A6. Verification и границы

Gate 2 SHALL проверить isolated subprocess transport, invalid route/options/environment matrix, redaction, alias equivalence, single-owner source/load constraints и production callers. Existing `production_migration_runner_001`, runtime lock/parallel runner и canonical schema suites SHALL независимо проверить fresh/repeat/upgrade/rows/history/restart/incompatible/concurrency outcomes на новом Yii seam либо через доказанно тождественный shared adapter.

Обязательны focused Yii migration tests, package/deployment contract, applicable schema/runtime checks, architecture check, независимые Gates 3/5 и один exact-source full GitHub CI. Локальный полный `make test`/`make verify` запрещён owner decision 2026-09-11. Не входят production imports, workforce sync, runtime prepare/check/recovery, новая schema, web/OTIZ/checklist routes, upgrade/rollback rehearsal всего приложения, deployment stand и закрытие общего #76.
