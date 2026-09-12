# YII2-IMPORTS-WORKFORCE-001 — case import через Yii2 console

Status: `ACCEPTED_FOR_GATE_2`
Owner authorization: issue #76, continuation and scope split confirmed 2026-09-12
Actor: deployment import operator
Public seam: `php bin/yii case-import/run --object-id=<id> [...] --interactive=0`
OpenSpec: `openspec/changes/yii2-imports-workforce/`

## A1. Закрытый transport

Command SHALL принимать route первым, обязательный `--interactive=0` последним и `1..100` уникальных canonical positive int64 `--object-id`. Иные inputs SHALL дать exit `64`, `{"ok":false,"reason":"CONFIGURATION_INVALID"}` и не обращаться к DB. Terminal stdout — один JSON object/newline, stderr пуст.

Обязательны `FMONITOR_DB_HOST`, `FMONITOR_DB_PORT`, `FMONITOR_DB_NAME`, `FMONITOR_DB_USER`, `FMONITOR_DB_PASSWORD`, `FMONITOR_PROCESS_TABLE_PREFIX`, `FMONITOR_LEGACY_TABLE_PREFIX`; port `1..65535`, prefixes — действующая grammar. Пустой password/prefix разрешён. Database unavailable — exit `69`; schema unavailable — `78`; import failure — `70`; неизвестный commit — `75`; eligibility rejection — `2`; success — `0`, ровно по `PILOT-CASE-IMPORT-001`.

## A2. Полная behavioral parity

Yii seam SHALL делегировать одному `PilotCaseImporter` и сохранять selection order, eligibility/all-or-nothing, repeat, progressed-case preservation, malformed legacy handling, least privilege, rollback, concurrent winner/observer и unknown-commit reconciliation. Success объявляется только по доказанным durable facts; UNKNOWN не success.

## A3. Один owner и compatibility

Controller SHALL быть transport adapter; shared service является единственным местом mysqli/`PilotCaseImporter` composition. Yii DB/ActiveRecord, SQL/domain rules в controller и независимая composition в alias запрещены. `bin/fmonitor2-import-cases.php` SHALL быть тонким launcher к той же Yii composition и сохранять прежний syntax/outcomes/durable facts.

## A4. Package, admission и redaction

Production load closure SHALL включать Composer/Yii, controller, shared service и `PilotCaseImporter`, но не `rapid-pilot`, demo, web/session/jobs composition или независимый legacy autoloader. Command не вызывается из HTTP/startup. Перенос не расширяет operator admission. DB secrets/coordinates/prefix, legacy values, exceptions/traces и SQL не выводятся.

Gate 2 SHALL включать closed transport matrix, полный существующий DB oracle раздельно через Yii seam и alias, single-composition source witness и package/load trace. Обязательны независимые Gates 3/5 и один exact-source full CI; локальные `make test`/`make verify` не выполнять. Snapshot import, workforce sync, schema, runtime recovery, web/cutover и stand deployment не входят.
