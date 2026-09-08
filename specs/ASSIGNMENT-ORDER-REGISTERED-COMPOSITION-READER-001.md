# ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001

Версия0.1, 2026-09-06. DRAFT / independent Gate1 required.

## Простыми словами

Загрузка оригинала сможет получить состав как старого распоряжения, так и нового
выбора без PDF. Reader ничего не сохраняет. Он проверяет принадлежность записи
и не угадывает источник при повреждённых или противоречивых данных.

## 1. Scope и authority

Actor — original application после собственной authorization. Owner —
AssignmentOrderOriginal. Authority: COMPOSITION-SELECT-001 v0.8 section12,
approved original legacy temporal/member semantics и schema Gate5ced7b77.
Registry/selection tables установлены controlled fixture/deployment заранее;
reader не выполняет migration, global readiness scan, repair или audit.
Пользовательские полномочия и original revision/authority rules не меняются.

Standalone unwired adapter: ProductionAssignmentOrderOriginalFactory и locked
original persistence composition check остаются прежними до separately gated
combined wiring. HTTP/render/selection writer/effective applicability/opening и
canonical migrations не входят в срез. Legacy physical-only reader не изменяется.

## 2. Exact public seam

Namespace `FMonitor2\AssignmentOrderOriginal`. Существующие types сохраняются:
`AssignmentOrderCompositionReader::find(int $caseId,int $orderId): AssignmentOrderCompositionSnapshot`.
Snapshot constructor fields: status, installationCaseId, assignmentOrderId,
identity, sha256, installerIds, controlEngineerUserId. Status FOUND/NOT_FOUND/
UNAVAILABLE — existing enum, installerIds — numeric ascending positive PHP ints.

```php
final class AssignmentOrderRegisteredCompositionReaderFactory
{
    public static function create(\mysqli $connection, string $tablePrefix = ''): AssignmentOrderCompositionReader { /* normative */ }
}
enum AssignmentOrderRegisteredCompositionReadPhase: string
{
    case REGISTRY_READ='registry_read';
    case SOURCE_READ='source_read';
    case MEMBERS_READ='members_read';
    case BEFORE_RELEASE='before_release';
}
interface AssignmentOrderRegisteredCompositionReadObserver
{
    public function observe(AssignmentOrderRegisteredCompositionReadPhase $phase): void;
}
final class AssignmentOrderRegisteredCompositionReaderVerificationFactory
{
    public static function create(\mysqli $connection, string $tablePrefix,
        AssignmentOrderRegisteredCompositionReadObserver $observer): AssignmentOrderCompositionReader { /* normative */ }
}
```

Factories выполняют только prefix validation, без SQL/connection ownership.
Prefix0..25 `[A-Za-z0-9_]*`, PHP64; invalid →
InvalidArgumentException('Invalid registered composition reader configuration.').
Production связывает inert observer; verification — тот же reader, без env selector.

find всегда возвращает snapshot, не выбрасывает SQL/observer/cleanup exceptions.
Invalid IDs(<1), closed/unselected/non-utf8mb4 connection, active caller transaction,
query/observer/cleanup failure → UNAVAILABLE. Invalid IDs проверяются до SQL.
NOT_FOUND/UNAVAILABLE имеют exact requested caseId/orderId, null identity/hash/
engineer, empty installers; native message/SQL не раскрываются.

## 3. Read snapshot и dispatch

Один owned read-only consistent RR transaction на find; session isolation и charset
не изменяются. Разрешено existing one-shot SET TRANSACTION ISOLATION LEVEL
REPEATABLE READ до begin. Active caller transaction не commit/rollback-ится.
Connection остаётся caller-owned. Owned rollback и release ресурсов проверяются;
failure не возвращает ранее вычисленный FOUND. Никаких locks/DDL/DML/retry.

1. Прочитать registry по exact orderId. После native result — REGISTRY_READ.
   Duplicate identity или invalid positive bounded id/case → UNAVAILABLE.
   Valid other-case ownership → NOT_FOUND сразу, без source/member probes и без
   проверки чужих source/version/time facts. Это сохраняет nondisclosure.
2. Registry отсутствует: presence-only probes обеих source tables по orderId;
   обе отсутствуют → NOT_FOUND, любая существует → UNAVAILABLE orphan.
   Недоступная registry/source table — UNAVAILABLE, не evidence of absence.
3. Same-case registry: version1..65535, source_kind exact legacy_order/selection,
   allocated_at_utc valid SQL DATETIME(6). Прочитать выбранный source header и
   presence-only противоположного source по orderId; затем SOURCE_READ.
   Selected source отсутствует/duplicate, opposite присутствует либо id/case/version
   не совпадают с registry → UNAVAILABLE. Нет precedence/fallback.
4. Прочитать members выбранного источника по orderId, numeric installer order;
   затем MEMBERS_READ. Проверить source-specific contract ниже, вернуть FOUND.
5. BEFORE_RELEASE вызывается once перед rollback каждого реально acquired read
   transaction, включая error/NF paths. Throw не отменяет обязательный rollback.
   При registry-absent probes также SOURCE_READ once; MEMBERS_READ отсутствует.

Observer вызывается только после соответствующего actual result, не на failed SQL.
REGISTRY_READ barrier допускает concurrent synthetic изменение source; весь
текущий find видит свой исходный snapshot, следующий find — новое состояние.
Readiness/receipt/all-writer ownership проверяет deployment, а не каждый find.
Reader читает только запрошенную identity/source/members; O(member rows).

## 4. Source contracts

### legacy_order

Physical header id/case/version/engineer/order_date и members obey existing
MariaDbOriginalSqlComposition semantics: positive bounded IDs, real dates,
unique installer IDs; actions assign/retain/release; valid_from<=order_date;
valid_to null либо>=valid_from; release требует valid_to<=order_date.
В результат входят assign/retain с valid_to null либо>=order_date.
Пустой effective состав, invalid temporal/member/header values → UNAVAILABLE
в новом registered adapter. Existing physical-only reader остаётся без изменений.
Registry allocatedAt не заменяет order_date и не используется как temporal cutoff.
Canonical identity/hash — существующие composition-<id>-v<version> и codec ниже.

### selection

Exact dateless header id/case/version соответствует registry, selected_at_utc
равен allocated_at_utc, selection_revision1..4294967295. Header mode/predecessor
и snapshot fields валидны по SELECTION-SCHEMA-001 section5 в пределах этой записи:
new_order→replaces null; replace_pending→revision>1 и replaces=previous!=null;
revision1→previous null, revision>1→positive previous. Chain/request/event/audit
global proof остаётся schema/writer readiness, не выполняется per lookup.
selectedAt UTC seconds, selection_date его Moscow date; engineer positive.
Members1..500, strictly numeric-ascending unique positive IDs, employed; snapshot
texts UTF8 trimmed/nonempty300/80, real periods cover selection_date; source instant
valid known-offset RFC3339<=40bytes. Текущая дата/HR/availability не перечитывается;
selection members не получают legacy temporal filter/change_action.
Header engineer snapshot texts obey same300 bound. composition_identity exact;
composition_sha256 обязана совпасть с независимым recomputation.

Для обоих источников compact UTF8 JSON без finalLF, unescaped Unicode/slashes,
key order `{caseId,compositionIdentity,engineerUserId,installers,orderId}` с numeric IDs.
Hash lowercase SHA256. Snapshot не содержит template/original/effective dates.

## 5. Fixed examples и минимальная verification

Normative selection fixture `assignment-order-selection-example-v1.json`:
case4512/order81/engineer73/installers[7001] → FOUND, composition-81-v1,
hash5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a;
order82/replace_pending/installers[7002] → FOUND, composition-82-v2,
hash1e6e0030b9f3cca92c36a741f87331f652490391ae773386673dc32f838d8bda.
Это pending compositions для original lookup; reader не делает их effective.

Legacy independent fixture: order81/version1/case4512/engineer73/date2026-09-02,
7001 assign from2026-09-01/toNULL,7002 release from2026-09-01/to2026-09-02,
7003 retain from2026-08-01/to2026-09-01 → FOUND [7001], identity/hash как81 выше.
Другой case4513 для registered81 → NOT_FOUND даже без SELECT на source/member
tables. Absent registry+absent sources для99 → NOT_FOUND; orphan physical99,
orphan selection99, registered missing source, dual81 → UNAVAILABLE.

Public tests: оба modes; legacy temporal preservation и malformed-to-unavailable;
case/version/discriminator/hash/member drift; numeric ordering; missing table/query
denial; foreign-case nondisclosure с inaccessible sources; invalid prefix beforeSQL;
invalid IDs/active caller/closed connection; each observer throw/owned release;
barrier concurrent source update сохраняет first snapshot, next find видит drift.
До/после сравнить all registry/receipt/selection/physical/original fixtures и counters;
no domain audit/clock/storage I/O. Task-owned synthetic databases/users cleanup
attempt-all, existing external decoy control reuse. Никаких primary evidence.

Gates1→RED→independent Gate3→minimal unwired GREEN→relevant original composition
regressions/architecture-check→independent Gate5. Original production factory,
locked writer and canonical runner не переключаются; full VERIFY_OK остаётся later.
