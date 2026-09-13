# YII2-STAND-RESTORE-CONTROL-001 — проверяемое восстановление стенда

## Простыми словами

Оператор восстанавливает disposable stand только из bundle, который ранее создал
и подтвердил Yii2 stand-backup. До первого изменения система проверяет bundle и
пустой target; после возможного изменения неизвестный результат никогда не
выдаётся за успех. Live deployment и production cutover в этот срез не входят.

## 1. Actor, authority и public seam

Actor — deployment operator, authority — owner issue #76 от 2026-09-13. Source
oracle — merged PR #119 (`c5041e2134420f941d678c9977b147cb01f6e809`) и base
`11b8587372040ab045d1a69faeeb1432ff85e400`.

Единственный новый production seam:

`php bin/yii stand-restore/run --manifest=<absolute-target-manifest> --bundle-digest=<64-lowercase-hex> --operation-id=<uuid-v4> --interactive=0`.

CLI MUST вывести ровно один canonical JSON object и newline, пустой stderr.
Success: exit 0, `{"bundle_digest":"…","ok":true,"outcome":"RESTORE_VERIFIED"}`.
Malformed/duplicate/missing argv либо interactive mode дают exit 64
`CONFIGURATION_INVALID` до filesystem/driver calls. Controller только адаптирует
argv; state machine принадлежит `app/RuntimeRestore` и использует общую Yii2
composition. Новый standalone bootstrap запрещён.

## 2. Единый verified bundle contract

Restore принимает без преобразования version 1 target и bundle/manifest protocol
`YII2-STAND-BACKUP-CONSOLE-001`: exact canonical target digest, source, image,
database name/observed ID, три volume name/observed ID, inventory digest,
`verified.json`, operation UUID и ровно `database.sql`, `artifacts.tar`,
`sessions.json`, `manifest.json` в content-addressed direct child.

До destructive effect application MUST независимо проверить canonical bytes,
bundle digest, pointer, exact schemas, member allowlist, non-symlink regular
readable non-empty objects, stable inode/read и каждый size/SHA-256. Traversal,
extra/missing field/member, corruption, coherent re-hash с неверной identity,
source/image/inventory/observed-ID mismatch дают non-zero `BACKUP_INVALID`;
invalid target/digest admission даёт `TARGET_INVALID`. Эти исходы оставляют DB,
state roots, driver effects, restore ledger и confirmed pointer byte-identical.

## 3. Target admission

Recording restore разрешён только при `FMONITOR_STAND_RESTORE_TEST_MODE=1`, project
`test-*`, fixture driver под sibling temporary root и exact target inventory.
Production driver без отдельной live-authority возвращает
`PRODUCTION_DRIVER_UNAVAILABLE` до effects.

Target database и artifact/session roots MUST быть exact observed target и пусты.
Любая таблица/row/foreign member даёт exit 66 `TARGET_NOT_EMPTY`; symlink, device,
socket, unsafe path/owner либо неоднозначное observation дают exit 64
`TARGET_INVALID`. Ничего существующее не очищается и не усыновляется.

## 4. Roundtrip и readiness

Focused proof MUST вызвать настоящий `stand-backup/create`, затем изменить или
удалить disposable target state, затем настоящий `stand-restore/run`. Restore
driver получает проверенные payload bytes, а не fixture success response. Он
материализует database facts/history и exact AUTO_INCREMENT next values,
artifact bytes/modes и sessions. После effects application MUST независимо
сверить exact schema/table inventory, DB facts/AUTO_INCREMENT, persistent
inventory/digests и readiness. Только после этого публикуется confirmed pointer.

Post-restore inventory/readiness failure не может вернуть success и даёт
`OUTCOME_UNKNOWN`, потому что partial effect уже возможен.

## 5. Operation, replay и interruption

После полной preflight admission application создаёт exclusive restore lease и
append-only restore operation ledger. Argument digest связывает command, target
digest и bundle digest. Same UUID+arguments replay не вызывает driver; другой
digest даёт exit 65 `OPERATION_CONFLICT`. Contender при lease получает exit 75
`LEASE_HELD`.

Definite driver rejection до первого effect даёт `RESTORE_FAILED`, durable record
и byte-identical replay. Timeout/interrupt/exception после начала либо возможного
начала effect даёт exit 70 `OUTCOME_UNKNOWN`; lease и diagnostic evidence
сохраняются, confirmed pointer не публикуется, автоматический driver retry
запрещён. Crash после durable confirmed record допускает replay, который
перепроверяет bundle/readiness и восстанавливает только matching pointer.

Malformed/truncated/conflicting ledger или lease всегда дают `OUTCOME_UNKNOWN`
до нового effect. Ни один error/output не раскрывает target/evidence paths,
credentials, DSN, payload/native fragments или checkout path.

## 6. Legacy и Done

После GREEN executable inventory MUST перечислить production consumers старых
`RuntimeRecovery` и `bin/fmonitor2-runtime-recovery.php`. Пока старый format,
historical forward migration, jobs recovery или runbook production command не
замещены новым protocol, legacy остаётся и новая production dependency на него
запрещается architecture test. Удаление допустимо только при полном replacement
evidence; review/history сохраняются.

Done: intended RED → independent Gate 3 APPROVED → отдельный executor → focused
GREEN, включая весь PR #119 backup suite → exact-source Gate 5 APPROVED → один
Quality Graph CI `VERIFY_OK`. Live stand, operational rollback drill, production
cutover и чеклист #76 не меняются этим контрактом.
