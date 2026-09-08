# ASSIGNMENT-ORDER-ORIGINAL-MAINTENANCE-001

Версия0.2. DRAFT / independent Gate1 required.

## Простыми словами

Сервисное обслуживание удаляет только действительно бесхозные временные файлы.
Оно не мешает текущей загрузке и не удаляет оригинал, на который ссылается БД.
Операция имеет один публичный интерфейс, ограниченный размер порции и проверяемый
результат. Пользовательские права и HTTP в этом исправлении не меняются.

## 1. Scope и точный API

Наследуются ORIGINAL-UPLOAD v73 sections16 и storage declarations, DATA-INTEGRITY,
COMMAND-LIFECYCLE, SAFE-LOG-OWNER/ISOLATION, ATTEMPT-AUDITv0.4. Canonical13 и
schema v3 остаются неизменными. Прежние approvals/evidence не переписываются.

Все уже объявленные maintenance interfaces/DTO/factories реализуются с exact
names, types, readonly/final, parameter names/order/defaults из parent:
MaintenanceApplication/Authorizer/Commit/ResultLookup/Repository/Dependencies,
ProductionMaintenanceFactory, обычная VerificationFactory и отдельная final
RealMaintenanceVerificationFactory, OrphanCandidate/Kind/Page/DigestLock,
PrivateStorageFactory. Нового application mutator или runtime selector нет.

Service принимает только MaintenanceDependencies и реализует публичный
reconcileAssignmentOrderOriginalPrivateOrphans. SQL — отдельный MariaDb-prefixed
adapter; FileStorage владеет candidate/lock/delete. Screen/import/cron не пишут
факты. Реальный verification factory связывает те же adapters с injected clock и
faults. Production связывает SystemClock/no faults и trusted configured principal.
Hardcoded test-maintenance-01 запрещён как production policy; это только пример.

PrivateOrphanFixture получает обещанную interface declaration и отдельную
concrete реализацию без изменения его create behavior в этом пакете. Его прочие
непокрытые replay/path/primitive contract gaps остаются отдельным launch blocker.

## 2. Admission, время и terminal audit

Shape: UUID как Original; principal `[A-Za-z0-9._:-]{1,160}`; cutoff canonical UTC
second; batch1..1000; cursor exact parent codec, включая quote/space opaque IDs.
Invalid shape возвращает REJECTED/INVALID_COMMAND, counters0/cursor null,
authorization/lookup/clock/storage/DB audit0. Это уточняет прежнее противоречие
«invalid cursor до clock» и общего «каждый REJECTED terminal»: invalid-shape,
как у original upload, не создаёт terminal/audit.

Valid shape: exact authorization → authorized terminal lookup → clock/cutoff →
page → items → result+audit. Authorization UNAVAILABLE/Throwable возвращает
FAILED/PERSISTENCE_FAILURE, дальнейших calls0. DENIED не делает terminal lookup
или storage; получает один lazy instant и пытается сохранить terminal denial
через maintenance repository. Clock unavailable или не-COMMITTED даёт FAILED,
не раскрывая старый результат. System-principal maintenance не подменяет
пользовательский original-denial audit writer и не расширяет его cardinality.

Authorized stored hit копируется один раз и возвращается REPLAYED с прежними
reason/retryable/counts/cursor, без clock/storage/new audit. Stored result допустим
только COMPLETED/REJECTED/PARTIAL; malformed status/result tuple/getter/error =
FAILED/PERSISTENCE_FAILURE. При replay retryable сохраняется от PARTIAL; это
уточняет parent «REPLAYED false», противоречащее replay прежнего PARTIAL.

При miss clock вызывается один раз. Future cutoff (строго новее now-3600s) —
REJECTED/INVALID_COMMAND и terminal audit с этим instant, storage0. Epoch допустим.
Unavailable/malformed clock — FAILED с counters0, audit0. Committed audit time
всегда captured instant, не второй вызов clock.

## 3. Page и item protocol

Page status OK требует list уникальных OrphanCandidate, length<=batch, strict
binary tuple(at,id) order, at<=cutoff и >cursor. Candidate immutable: kind exact;
identity1..160 printable ASCII excluding slash/backslash (space/quote допустимы);
canonical UTC; byteSize0..20971520 для abandoned stage,1..20971520 для finalized;
stage sha=null, finalized sha lowercase64hex. Invalid/getter/Throwable/page
non-OK возвращает FAILED/PERSISTENCE_FAILURE с counters0/cursor null,
retryable=true, terminal/audit0: unavailable persisted candidate inventory
не выдаётся за обработанную порцию.

Если nextCursor не null, он должен decode-иться в последнюю candidate tuple;
empty page всегда nextCursor=null. Это page protocol, не утверждение существования
ещё одной строки. Полный page/getters snapshot валидируется до первого lock.

Для каждой candidate: acquireDigestLock(id) ровно один раз. Возвращённый lock
снимается ровно один раз в finally, даже при non-OK/identity mismatch/getter
failure, если object был получен. OK + exact ID разрешает следующий шаг;
LOCKED увеличивает retained и lock-count; FAILED/other/malformed увеличивает failed.

Для finalized под lock выполняется hasCommittedContent(id): только FOUND и
well-formed boolean допускаются; true → retained, false → deleteLocked.
UNAVAILABLE/NOT_FOUND/malformed/Throwable → failed, delete0. Для stage reference0.
Delete OK (включая уже отсутствующий файл) → deleted; любой другой status → failed.
Storage instance сохраняет last successful page snapshot/cutoff. Lock для delete
связывается с соответствующей listed candidate; без такого snapshot delete FAILED.
Deletion revalidates current kind/time/identity under the same lock; newer/changed
metadata не удаляется и возвращает FAILED. No reference/blob/domain repair.

Release Throwable диагностируется once и не меняет уже выбранные per-item counts
или не вызывает второй release/delete. Concrete lock permanently unusable после
первого release attempt; native unlock/close attempted once, no public handle.

scanned=число обработанных page candidates=deleted+retained+failed. Любой failed
→ PARTIAL/STORAGE_FAILURE; иначе любой lock-count → PARTIAL/LOCKED; иначе
COMPLETED/null. PARTIAL retryable=true, COMPLETED/REJECTED=false. Cursor — snapshot
page cursor. Result/audit после всех release attempts, даже при logger failure.

## 4. Native storage и активная загрузка

Public private-storage factory использует прежний root policy, real system clock,
observer/faults. Existing FileStorage constructor остаётся source-compatible;
optional storage observer добавляется только последним argument.

FileStorage list/lock/delete работают с прежним immutable metadata и теми же
physical paths/locks, что upload. No new filename from raw caller path.
Stage с открытым writer не является abandoned: beginStage удерживает stage lock
до close/abort, получая lock до публикации metadata. Maintenance с более поздним
clock видит LOCKED даже для stage, созданного более часа назад. Constructor/
metadata/open failure освобождает всё полученное once; обычный upload transcript
observer events остаётся прежним.

Content lock совпадает с upload lease digest domain. Delete допускает только
lock, созданный этим storage instance, OK/active/exact identity; чужой/released
lock даёт FAILED без filesystem mutation. Metadata snapshot проверяется до
доступа к candidate path. Обычные unreferenced orphan cleanup paths не изменяют
revision/request/event/audit original facts.

Storage emits actual DIGEST_LOCK_ACQUIRED только после успешного lock,
DELETE_BEGIN перед real delete attempt и DELETE_DONE только после подтверждённого
удаления/absence. Callback Throwable на acquired/begin даёт failed operation и
cleanup; DELETE_DONE failure не отменяет уже выполненное удаление и не повторяет
его. Остальные upload events и их ownership остаются прежними. Fault injector
точки DIGEST_LOCK/ORPHAN_REFERENCE_LOOKUP/ORPHAN_DELETE используются один раз
перед corresponding primitive; production binds none.

## 5. Repository и native composition

Maintenance repository использует прежние две maintenance tables и общий physical
mapper. findTerminalRequest выполняет один owned read-only consistent snapshot,
проверяет request echo, все scalar/status/count/cursor values и matching audit;
malformed/missing backing не становится miss. Active borrowed transaction или
SQL/release failure = UNAVAILABLE, caller transaction не завершается.

Commit DTO проверяется до SQL/observer/escaping. FAILED/REPLAYED не сохраняются;
COMPLETED/REJECTED/PARTIAL обязаны иметь exact reason/retry/count/cursor closure,
UUID/principal/UTC grammar. Counters0..1000 и сумма exact. Active caller transaction
→ ROLLED_BACK с zero writes/control. Native INSERT result+audit в одной short
transaction, strict native true acknowledgement. Collision request key →
CONFLICT после confirmed rollback; другие confirmed rollback → ROLLED_BACK;
native commit/rollback uncertainty → OUTCOME_UNKNOWN. Connection borrowed.

Application возвращает selected terminal только после COMMITTED. Любой другой
commit outcome → FAILED/PERSISTENCE_FAILURE с уже наблюдёнными item counts/cursor,
без повторных delete/commit или confidential denial lookup. FAILED не обещает
отсутствия файловых эффектов; UNKNOWN также не обещает отсутствия terminal row.
Retry использует тот же request ID, authorized hit replay-ит подтверждённый result.
Это уточняет физически невозможное parent обещание «audit failure всегда no row».

## 6. Диагностика, factory и критерий завершения

Best-effort safe log retains parent correlation envelope. Exact events:
- ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_LOCK_RELEASE_FAILED, sole phase=digest_release;
- ASSIGNMENT_ORDER_ORIGINAL_MAINTENANCE_PERSISTENCE_FAILED, sole phase=authorization,
  lookup, clock, candidate_page либо commit (один соответствующий failure на invocation).
No payload/path/identity/correction reason/SQL/exception details in safeFields.
Request-aware binding/record failure изолированы прежним guard, без retries.

Factory authorization validation идёт до resource I/O с parent exact exception;
потом safe-log acquisition, root/prefix validation, real ports. Production не
получает caller-selected clock/fault/observer. Safe-log/root errors redacted через
existing ProductionConfigurationUnavailable; failed acquisitions закрываются.

Минимальная проверка: public dependency/control flow+closed DTO/result/page/lock;
configured non-test principal; invalid shape no ports, replay no clock; two orphan
pagination and deletion after vanished cursor; referenced/locked/failure counts;
throwing observers/loggers/releases; real stage-held/content-held exclusion;
real native result+audit/replay/rollback; invalid DTO no SQL; production/real
verification factories и exact declaration parity указанного API. Existing real
maintenance/lease tests и source-free fixtures reuse; изменяемые старые expectations
только через exact unapplied patch+independent Gate3. Gates1→RED→Gate3→GREEN→
affected regressions/architecture→independent Gate5 обязательны.

## 7. Native repository construction для Gate2

Exact concrete public adapter: `AssignmentOrderOriginalMariaDbMaintenanceRepository` implements MaintenanceRepository. Constructor `(\mysqli $connection, string $tablePrefix = '', ?AssignmentOrderOriginalPersistenceObserver $observer = null)` passive, не делает I/O. Это public persistence port, не второй application mutator. Invalid DTO/prefix → ROLLED_BACK до state SQL/observer; active caller transaction → ROLLED_BACK после одного state SELECT, без observer/control/writes. Reader active/error → UNAVAILABLE без caller transaction control. Verification использует прежние BEFORE_READ_RELEASE/BEFORE_WRITE_BEGIN/BEFORE_NATIVE_COMMIT/AFTER_NATIVE_COMMIT/BEFORE_WRITE_ROLLBACK phases; production передаёт null. Read result копируется до release, read-release failure → UNAVAILABLE. Это уточняет только конструирование уже заявленного adapter для независимых real/zero-SQL tests.
