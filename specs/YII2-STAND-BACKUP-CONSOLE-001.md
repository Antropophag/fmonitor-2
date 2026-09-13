# YII2-STAND-BACKUP-CONSOLE-001 — проверяемый backup bundle стенда

## Простыми словами

Перед будущим переключением Yii2 FMonitor должен получить реальный набор backup
bytes, который можно проверить независимо от программы, создавшей dump. Этот срез
работает только в изолированном recording-контуре и не обращается к рабочему стенду.

## 1. Actor, seam и граница

Actor — deployment operator с authorization id
`owner-2026-09-13-issue-76-stand-backup`. Public seam:
`php bin/yii stand-backup/create|verify --manifest=<exact-target> --operation-id=<uuid> --interactive=0`.

Source oracle — issue #76, coordinated restore contract #36 и exact Yii2 target
PR #113 (`404aa6858b8fdd61ac0e8151f09a67000a387ead`). CLI MUST выводить ровно один
canonical JSON object и newline, пустой stderr; exit 0 разрешён только для
`BACKUP_VERIFIED`. PHP production port в этом срезе MUST возвращать safe
`PRODUCTION_DRIVER_UNAVAILABLE` без external effects. PHP recording port доступен
только при `FMONITOR_STAND_BACKUP_TEST_MODE=1`, manifest project `test-*` и fixture
под внешним temporary root.

## 2. Exact target admission

Version 1 manifest MUST иметь ровно: authorization id, immutable source и image
`name@sha256:<64 hex>`, absolute canonical compose path, non-default exact project,
exact database name/observed ID, exact unique database/artifact/session volume
names и observed IDs, external absolute evidence root и independently observed
inventory digest. Missing/extra keys, wrong types/version/auth, relative,
`/`, home/repository root, любой symlink component, unresolved `${...}`, mutable
digest, default/neighbor project, duplicate/unknown target, missing/non-unique ID,
inventory mismatch или changed ID MUST вернуть exit 64 и exact
`{"ok":false,"reason":"TARGET_INVALID"}\n` до driver call, staging, lease,
operation index или verified-pointer mutation.

Valid admission MUST вычислять target digest из canonical accepted manifest.
Повторный `verify` не вызывает driver и не изменяет files.

## 3. Concrete bundle и независимый verifier

Recording driver пишет в выданный staging directory ровно три regular files:
`database.sql`, `artifacts.tar`, `sessions.json`. Symlink, directory, device,
missing, empty или unreadable member запрещён. Owner MUST открыть каждый member
без follow-symlink, перечитать его до EOF, вычислить SHA-256 и byte size сам и
повторить проверку после driver completion. Driver-reported digest/size/success
не является доказательством.

Canonical accepted target и restore manifest MUST сериализоваться как UTF-8 JSON
с sorted keys, separators `,`/`:` и ровно одним trailing `\n`; SHA-256 включает
этот newline. Restore manifest MUST содержать schema version, target digest, source,
image, exact database/volume names и observed IDs, operation id и для каждого
allowlisted member `{size,sha256}`. Bundle digest — SHA-256 canonical UTF-8 JSON
restore manifest с sorted keys и separators `,`/`:`. Credentials, DSN, secret
values/paths, payload bytes, native output и checkout path запрещены в manifest.

При достаточной capacity и стабильных bytes owner MUST fsync payload/manifest,
атомарно rename staging в `bundles/<bundle-digest>` на том же filesystem и fsync
`bundles`. Existing destination MUST быть non-symlink direct child с точным digest
и byte-identical independently verified содержимым; иначе publish fail closed.
После durable bundle owner MUST append+fsync successful operation record, затем
атомарно опубликовать и fsync `verified.json` с bundle/target/operation digests и
только после этого снять lease с fsync evidence directory. Результат:
exit 0 и safe `BACKUP_VERIFIED` payload с bundle digest.

## 4. Failure, preservation и UNKNOWN

Insufficient capacity; wrong target; missing/empty/unreadable/mutated member;
unexpected member; size/digest mismatch при повторном чтении; cross-filesystem
publish; driver rejection/failure MUST дать non-zero `BACKUP_INVALID`. Timeout,
interrupt или невозможность определить, состоялся ли driver effect, MUST дать
non-zero `OUTCOME_UNKNOWN`. Ни один из них не создаёт/заменяет `verified.json`,
не изменяет опубликованные bundle bytes и не разрешает destructive lifecycle.

Owner удаляет только собственный незавершённый staging directory, если отсутствие
неизвестного writer подтверждено. При UNKNOWN staging сохраняется для последующего
операторского разбора. Ранее verified bundle и pointer MUST оставаться byte-identical
после каждой неуспешной попытки.

## 5. Replay, lease и concurrency

Каждый `create` требует UUID operation id. Operation record append-only связывает
operation id, target digest, argument digest, outcome и optional bundle digest.
`BACKUP_VERIFIED`, `BACKUP_INVALID` и `OUTCOME_UNKNOWN` MUST дописать ровно один
durable record после успешного exact admission; pre-admission `TARGET_INVALID`,
`OPERATION_CONFLICT` и `LEASE_HELD` record не дописывают. Повтор того
же id и arguments возвращает byte-identical прежний public result без driver call;
другие arguments возвращают exit 65 `OPERATION_CONFLICT`.
Definite failure protocol MUST при owned lease сначала очистить staging, затем
append+fsync `BACKUP_INVALID`, затем снять lease. Secondary failure до durable
record возвращает `OUTCOME_UNKNOWN`, сохраняет lease и не допускает повтор driver;
после durable record public outcome остаётся `BACKUP_INVALID`, а replay завершает
только owned lease cleanup без повторного effect.

На один target действует exclusive create lease. Contender получает exit 75
`LEASE_HELD`, не вызывает driver и не создаёт второй staging/bundle. Lease не
снимается автоматически как stale. Только владелец текущей операции снимает его
после definite success/failure; UNKNOWN сохраняет lease. Два процесса не могут
опубликовать разные verified pointers для одной операции.

Malformed, truncated, duplicate/conflicting, symlink, non-regular, unreadable или
неполный operation history MUST завершаться fail closed `OUTCOME_UNKNOWN` до
driver/lease/staging/pointer mutation. Если crash произошёл после durable success
record, replay MUST независимо проверить записанный bundle, восстановить missing
matching pointer без driver call и вернуть прежний success. Crash до success record
сохраняет lease/staging/bundle как UNKNOWN и не допускает автоматический повтор.
Каждый history record MUST иметь exact canonical outcome/result/exit schema:
public result и exit выводятся только из validated outcome/bundle facts, а не
доверяются произвольным persisted значениям. Invalid success digest, mismatch,
extra/secret result или неверный exit дают `OUTCOME_UNKNOWN`. Dangling symlink
считается corrupt history. Replay принимает только canonical pointer bytes; lease
может быть снят лишь при exact matching operation/target ownership, иначе остаётся
неизменным и replay возвращает `OUTCOME_UNKNOWN`.

## 6. Safe output и Done

Все success/rejection/failure/timeout/replay/concurrency outcomes MUST иметь один
JSON object, empty stderr и не содержать seeded credential, DSN, secret path,
payload/native fragments, manifest path, evidence root или checkout path.
Окружение, locale, cwd, key order/whitespace manifest не меняют canonical outcome.

`verify --bundle-digest` принимает только 64 lowercase hex как один direct
non-symlink child `bundles/`. Verifier MUST проверять exact restore/pointer schemas,
каждое target identity поле и operation UUID; coherent extra/wrong metadata,
traversal либо symlink component отклоняются до чтения за пределами bundle root.
Любой KeyboardInterrupt, subprocess или filesystem exception MUST преобразоваться
в safe definite/UNKNOWN outcome без traceback. Directory fsync order является
частью recording-port evidence.
Синтаксически неверный либо traversal digest MUST вернуть admission
`TARGET_INVALID` exit 64 до любого bundle filesystem access; корректный digest с
повреждённым либо symlink bundle возвращает `BACKUP_INVALID`.

Done: полный hermetic matrix RED → независимый Gate 3 `APPROVED` → отдельная
реализация → focused GREEN → независимый Gate 5 `APPROVED` → один exact-source CI
`VERIFY_OK`. Live backup, deployment, restore и общий №76 остаются отдельными.
