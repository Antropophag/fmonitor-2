# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 — выбор состава без шаблона

Версия 0.11, 2026-09-06. **DRAFT / GATE 1 TERMINAL ROUTE AMENDMENT**.

Controlling owner amendment: section17. Fresh launch без исторических данных;
legacy migration/mixed-writer compatibility clauses предыдущей версии не входят
в launch scope. Optional PDF не хранится. Existing schema/reader approvals
сохраняются в своих scope; этот amended selection contract требует полного Gate1.

## Простыми словами

Сотрудник ФКР или Руководитель ФКР выбирает монтажников и инженера и сохраняет
этот выбор до загрузки подписанного оригинала. Сохранение не требует и не
создаёт PDF: шаблон можно сформировать позже отдельным действием из того же
неизменяемого снимка. Выбор ещё не является действующим назначением, не
открывает работы и не меняет состав в справочнике и у стройконтроля.

Кандидат полностью задаёт как новый выбор (`new_order`), так и append-only
замену ещё не подписанного выбора (`replace_pending`). Эта продуктовая политика
утверждена владельцем 2026-09-05 (раздел 15). Технический Gate 1 всего batch
остаётся незакрытым; до его APPROVED ни одна ветвь не переходит к RED.

## 1. Actor, граница и Gate

Actors: сотрудник ФКР и Руководитель ФКР. Единственный public application seam:

```php
interface AssignmentOrderCompositionApplication
{
    public function selectAssignmentOrderComposition(
        SelectAssignmentOrderCompositionCommand $command,
    ): AssignmentOrderCompositionResult;
}
```

HTTP/CLI/экран вызывают этот метод. Direct SQL, fixture, renderer, legacy
prepare и private repository method не реализуют действие. Имя
`selectAssignmentOrderComposition` сохраняется.

### Public construction

В том же namespace `FMonitor2\AssignmentOrderComposition`:

```php
final readonly class SelectionDependencies
{
    public function __construct(
        public SelectionAuthorizer $authorizer,
        public SelectionDependencyReader $facts,
        public SelectionClock $clock,
        public SelectionTerminalRequestReader $requests,
        public SelectionUnitOfWork $transactions,
        public SelectionFreshTerminalReaderFactory $freshReaders,
        public SelectionAttemptAuditWriter $audits,
        public SelectionTerminalAttemptUnitOfWork $terminalAttempts,
    ) {}
}
final class AssignmentOrderCompositionFactory
{
    public static function create(SelectionDependencies $dependencies): AssignmentOrderCompositionApplication { /* normative composition */ }
}
```

Factory только собирает application, не вызывает ports и не выполняет I/O.
Все ports уже определены sections5/6/9; production и verification вызывают один
public command owner через этот factory. Native adapters/wiring проходят свои
проверки до интеграции; это construction contract, не approval runtime binding.

Документ — единый кандидат Gate 1. До независимого `APPROVED`, выполнения
release-compatibility условий раздела 14 и продемонстрированного RED код и тесты
этого среза не создаются.

## 2. Точные публичные типы и bounds

Namespace: `FMonitor2\AssignmentOrderComposition`.

```php
enum AssignmentOrderCompositionMode: string
{ case NEW_ORDER = 'new_order'; case REPLACE_PENDING = 'replace_pending'; }
enum AssignmentOrderCompositionStatus: string
{
    case SELECTED='selected'; case REPLAYED='replayed';
    case REJECTED='rejected'; case CONFLICT='conflict'; case FAILED='failed';
}
enum AssignmentOrderCompositionReason: string
{
    case INVALID_COMMAND='invalid_command';
    case AUTHORIZATION_DENIED='authorization_denied';
    case OBJECT_NOT_FOUND='object_not_found';
    case INSTALLER_REQUIRED='installer_required';
    case CONTROL_ENGINEER_REQUIRED='control_engineer_required';
    case INSTALLER_NOT_IN_CATALOG='installer_not_in_catalog';
    case INSTALLER_NOT_EMPLOYED='installer_not_employed';
    case CONTROL_ENGINEER_NOT_ELIGIBLE='control_engineer_not_eligible';
    case OBJECT_HAS_PTO_ACT='object_has_pto_act';
    case OBJECT_COMPLETED='object_completed';
    case NO_CHANGES='no_changes';
    case REQUEST_ID_CONFLICT='request_id_conflict';
    case STALE_SELECTION='stale_selection';
    case PENDING_SELECTION_EXISTS='pending_selection_exists';
    case SELECTION_NOT_FOUND='selection_not_found';
    case ORIGINAL_ALREADY_ACCEPTED='original_already_accepted';
    case DEPENDENCY_UNAVAILABLE='dependency_unavailable';
    case PERSISTENCE_FAILURE='persistence_failure';
    case PERSISTENCE_OUTCOME_UNKNOWN='persistence_outcome_unknown';
    case ALLOCATION_CAPACITY_EXHAUSTED='allocation_capacity_exhausted';
}
final readonly class SelectionRequestId { public function __construct(public string $value) {} }
final readonly class InstallationObjectId { public function __construct(public int $value) {} }
final readonly class UserId { public function __construct(public int $value) {} }
final readonly class InstallerTabId { public function __construct(public int $value) {} }
final readonly class InstallerTabIdList
{
    /** @param list<InstallerTabId> $ids */
    public function __construct(public array $ids) {}
}
final readonly class InstallerTabIdSet
{
    /** @param list<InstallerTabId> $ascendingUniqueIds */
    public function __construct(public array $ascendingUniqueIds) {}
}
final readonly class SelectionRevision { public function __construct(public int $value) {} }
final readonly class SelectAssignmentOrderCompositionCommand
{
    public function __construct(
        public SelectionRequestId $requestId,
        public AssignmentOrderCompositionMode $mode,
        public InstallationObjectId $installationObjectId,
        public UserId $actorUserId,
        public InstallerTabIdList $installerTabIds,
        public ?UserId $controlEngineerUserId,
        public SelectionRevision $expectedSelectionRevision,
    ) {}
}
```

Transport получает actor только из session. Application shape validation
принимает DTO как переданный и до любых reads проверяет: requestId — canonical
lowercase RFC4122 UUID, 36 ASCII bytes; every ID — PHP int
`1..9223372036854775807`; expected revision — `0..4294967295`; installer list —
0..500 IDs, duplicates are invalid, затем normalizes to strictly ascending set; mode — один из
двух enum values. Empty set и null engineer сохраняются для точных business
outcomes. Любое иное нарушение даёт `rejected/invalid_command` с исходным
requestId и без authority/dependency read или audit. Конструкторы DTO не
утверждают эту валидацию и не вводят отдельное transport outcome.

## 3. Typed result, serializer и reasons

```php
final readonly class SelectionSuccessPayload
{
    public function __construct(
        public int $caseId, public int $assignmentOrderId,
        public int $assignmentOrderVersion, public int $selectionRevision,
        public string $compositionIdentity, public string $compositionSha256,
        public string $selectionDate, public string $selectedAt,
    ) {}
}
interface AssignmentOrderCompositionResult
{
    public function status(): AssignmentOrderCompositionStatus;
    public function reasonCode(): ?AssignmentOrderCompositionReason;
    public function retryable(): bool;
    public function requestId(): SelectionRequestId;
    public function success(): ?SelectionSuccessPayload;
}
interface AssignmentOrderCompositionResultSerializer
{
    /** @return array{status:string,reasonCode:?string,retryable:bool,
     * requestId:string,caseId:?int,assignmentOrderId:?int,
     * assignmentOrderVersion:?int,selectionRevision:?int,
     * compositionIdentity:?string,compositionSha256:?string,
     * selectionDate:?string,selectedAt:?string} */
    public function serialize(AssignmentOrderCompositionResult $result): array;
}
```

`selected/replayed`: null reason, retryable false, non-null success.
`rejected/conflict`: retryable false, null success. `failed`: null success,
retryable true except nonretryable `allocation_capacity_exhausted`. Serializer emits every annotated key in
that order; enums use lowercase backing values. Закрытость concrete result
задаётся следующими factories; произвольная реализация интерфейса не является
допустимым result production owner.

Success IDs follow section 2; version `1..65535`, revision `1..4294967295`;
identity exact ASCII `composition-<orderId>-v<version>` (max160 bytes); hash 64
lowercase hex; date `YYYY-MM-DD`; selectedAt UTC RFC3339 seconds.

| Status | Exact allowed reasons |
| --- | --- |
| selected/replayed | null |
| rejected | invalid_command, authorization_denied, object_not_found, installer_required, control_engineer_required, installer_not_in_catalog, installer_not_employed, control_engineer_not_eligible, object_has_pto_act, object_completed, no_changes |
| conflict | request_id_conflict, stale_selection, pending_selection_exists, selection_not_found, original_already_accepted |
| failed | dependency_unavailable, persistence_failure, persistence_outcome_unknown, allocation_capacity_exhausted |

### 3.1 Concrete closed result construction

`SelectionResult` — единственная concrete `final readonly` реализация
`AssignmentOrderCompositionResult`. Constructor private; public factories:

```php
final readonly class SelectionResult implements AssignmentOrderCompositionResult
{
    private function __construct() {}
    public static function selected(SelectionRequestId $requestId, SelectionSuccessPayload $payload): self { /* normative factory */ }
    public static function replayed(SelectionRequestId $requestId, SelectionSuccessPayload $payload): self { /* normative factory */ }
    public static function rejected(SelectionRequestId $requestId, AssignmentOrderCompositionReason $reason): self { /* normative factory */ }
    public static function conflict(SelectionRequestId $requestId, AssignmentOrderCompositionReason $reason): self { /* normative factory */ }
    public static function failed(SelectionRequestId $requestId, AssignmentOrderCompositionReason $reason): self { /* normative factory */ }
    public function status(): AssignmentOrderCompositionStatus { /* normative accessor */ }
    public function reasonCode(): ?AssignmentOrderCompositionReason { /* normative accessor */ }
    public function retryable(): bool { /* normative accessor */ }
    public function requestId(): SelectionRequestId { /* normative accessor */ }
    public function success(): ?SelectionSuccessPayload { /* normative accessor */ }
}
```

Это syntax-valid declarations, не готовая implementation. Каждая factory
выбирает exact status по своему имени; reason допускается только из строки
таблицы раздела 3. Retryable выводится из status/reason и не принимается от
caller. Success payload допустим только с bounds/formats выше. Нарушение
factory contract вызывает `InvalidArgumentException('Invalid selection result.')`,
без I/O и без преобразования в business outcome. Исходный requestId возвращается
даже для `invalid_command`: result factory не повторяет UUID validation.
`SelectionSuccessPayload` — пассивный typed carrier; проверку всех его полей
выполняют selected/replayed factories. Serializer — concrete
`SelectionResultSerializer`, public `__construct()` без dependencies,
`serialize(AssignmentOrderCompositionResult): array`; foreign implementation
интерфейса отклоняется тем же fixed `InvalidArgumentException`. Он не читает
clock/DB, не меняет результат и не пропускает null keys.

## 4. Exact canonical bytes

Canonical intent is compact UTF-8 JSON, no spaces/final LF, unescaped
slashes/Unicode, keys exactly:
`actorUserId,controlEngineerUserId,expectedSelectionRevision,installationObjectId,installerTabIds,mode`.
Installers are unique numeric-ascending. Request ID, case, clock, allocations,
snapshots, authority and original/template facts are excluded. Fingerprint is
lowercase SHA-256 of those bytes.

```json
{"actorUserId":18,"controlEngineerUserId":73,"expectedSelectionRevision":0,"installationObjectId":4512,"installerTabIds":[7001],"mode":"new_order"}
```

Fingerprint: `62ee3be1c62ff977fc0e508a4743d85b8f5bc83386b3b8b0c26f542d321aff6c`.

Composition uses the same encoding and exact key order
`caseId,compositionIdentity,engineerUserId,installers,orderId`.

```json
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":73,"installers":[7001],"orderId":81}
```

Hash: `5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a`.

## 5. Authority

Capability is `assignment_order.composition.select`; authorize before every
confidential request lookup, including replay and unknown-commit recovery.
Autonomous local-role mode requires active pilot user, active assigned builtin
role and exact local permission, seeded only to codes `fkr_operator`,`manager`.
`manager` keeps its code and display becomes “Руководитель ФКР”. Do not grant by
display name, custom role or administrator wildcard. Alternative legacy mode
requires active legacy user/role and exact additive capability row; it does not
also require local grant.

```php
enum SelectionCapability:string { case SELECT='assignment_order.composition.select'; }
enum SelectionAuthorizationStatus:string
{ case ALLOWED='allowed'; case DENIED='denied'; case UNAVAILABLE='unavailable'; }
final readonly class SelectionAuthorization
{ public function __construct(public SelectionAuthorizationStatus $status) {} }
interface SelectionAuthorizer
{ public function authorize(UserId $actor, SelectionCapability $capability): SelectionAuthorization; }
```

Missing/inactive user/role/grant is denied; query/schema error unavailable.
Denial → authorization_denied and never reads stored result; unavailable →
retryable dependency_unavailable.

## 6. Typed lookup payloads

Every lookup is a closed object with exactly `found(payload)`, `notFound()` or
`unavailable()` and null payload for the last two. Unavailable is never absence.

```php
enum SelectionLookupStatus:string
{ case FOUND='found'; case NOT_FOUND='not_found'; case UNAVAILABLE='unavailable'; }
```

Следующие типы — concrete `final readonly` lookup classes с private
constructor и public readonly `status: SelectionLookupStatus`, `payload: ?T`:

| Class | T |
| --- | --- |
| SelectionCaseLookup | SelectionCasePayload |
| SelectionInstallerBatchLookup | InstallerBatchPayload |
| SelectionEngineerLookup | EngineerSnapshot |
| SelectionInstantLookup | SelectionInstant |
| SelectionTerminalRequestLookup | SelectionTerminalRequestRecord |

Для каждой строки public factories exact:
`found(T $payload): self`, `notFound(): self`, `unavailable(): self`.
`found` задаёт FOUND/non-null, остальные — соответствующий status/null.
Public `(status, ?payload)` constructor отсутствует, поэтому сочетание
FOUND/null невозможно. Factory не читает dependencies; payload type checking —
PHP type system. Значения snapshot проверяет application owner по правилам ниже,
поэтому malformed dependency payload не становится construction/setup exception.
Adapter query/schema/transport failure возвращает `unavailable()`, отсутствие
сущности — `notFound()`; catch-all absence запрещён.

```php
final readonly class SelectionCasePayload
{ public function __construct(public int $caseId,public int $objectId,
  public bool $completed,public ?string $ptoActDate) {} }
final readonly class InstallerSnapshot
{ public function __construct(public int $tabId,public string $fio,
  public string $position,public string $employmentStatus,
  public string $employedFrom,public ?string $employedTo,
  public string $workforceSource,public string $workforceSourceUpdatedAt) {} }
final readonly class InstallerBatchPayload
{ /** @param list<InstallerSnapshot> $snapshots @param list<int> $missingIds */
  public function __construct(public array $snapshots,public array $missingIds) {} }
final readonly class EngineerSnapshot
{ public function __construct(public int $userId,public string $fio,public string $position) {} }
final readonly class SelectionInstant
{ public function __construct(public string $utcRfc3339Seconds) {} }
interface SelectionDependencyReader
{
    public function findCaseByObject(InstallationObjectId $id): SelectionCaseLookup;
    public function findInstallers(InstallerTabIdSet $ids,SelectionInstant $at): SelectionInstallerBatchLookup;
    public function findEngineer(UserId $id,SelectionInstant $at): SelectionEngineerLookup;
}
interface SelectionClock { public function now(): SelectionInstantLookup; }
```

Snapshot constructors являются пассивными carriers; они не утверждают
business eligibility и не выбрасывают validation exception. Application owner
проверяет каждый полученный payload до использования. Case/object/user/tab IDs
имеют bounds раздела 2; case objectId должен совпасть с requested ID; PTO — null
либо реальная calendar date. Snapshot text — trimmed nonempty valid UTF-8:
FIO/position ≤300 Unicode code points, source ≤80; sourceUpdatedAt — RFC3339
instant ≤40 bytes; clock — exact UTC `YYYY-MM-DDTHH:MM:SSZ`. Malformed field,
extra/duplicate/wrong ID, неправильный порядок или broken date period дают
`failed/dependency_unavailable`, а не exception или отсутствие сущности.

Installer batch — полное разбиение requested IDs на numeric-ascending unique
snapshots и numeric-ascending unique missingIds, без пересечения; empty arrays
имеют list shape. Batch-level NOT_FOUND не используется для missing workers:
такой ответ трактуется как malformed dependency/unavailable. Clock NOT_FOUND
аналогично unavailable. Case NOT_FOUND — object_not_found; engineer NOT_FOUND —
control_engineer_not_eligible. Engineer snapshot уже обозначает active user/role
с exact `construction_control_engineer`; inactive/absent reader возвращает
notFound, infrastructure failure — unavailable.

Installer employmentStatus допускает `employed` и `dismissed`. EmployedFrom —
valid calendar date; employedTo — null либо valid date ≥ employedFrom.
При structurally valid batch отсутствующий ID даёт installer_not_in_catalog;
`dismissed`, from > selectionDate или non-null to < selectionDate дают
installer_not_employed. Неизвестный status и отсутствующий required period —
dependency_unavailable. Поэтому отрицательная eligibility остаётся отдельным
наблюдаемым business result, а не невозможным FOUND payload. Eligibility использует
selectionDate; ни document date, ни effective assignment date не создаются.

```php
final readonly class SelectionIdentitySummary
{ public function __construct(public int $assignmentOrderId,public int $orderVersion,
  public int $selectionRevision,public string $compositionIdentity,
  public string $compositionSha256,public bool $hasAcceptedOriginal) {} }
enum SelectionLegacyPhysicalStatus: string
{ case PREPARED='prepared'; case REGISTERED='registered'; }
final readonly class LegacyIdentitySummary
{ public function __construct(public int $assignmentOrderId,public int $orderVersion,
  public SelectionLegacyPhysicalStatus $physicalStatus,public bool $hasAcceptedOriginal) {} }
final readonly class EffectiveOrderIdentity
{ public function __construct(public int $assignmentOrderId,public int $orderVersion) {} }
final readonly class SelectionStateSnapshot
{ public function __construct(public ?SelectionIdentitySummary $latestSelection,
  public ?SelectionIdentitySummary $latestPendingSelection,
  public ?SelectionIdentitySummary $latestAcceptedSelection,
  public ?LegacyIdentitySummary $latestRegistryLegacyIdentity,
  public ?EffectiveOrderIdentity $effectiveOrder) {} }
```

Latest selection is greatest unique selection revision; pending is latest only
without original root for its exact ID; accepted is greatest selection revision
with such root. Registry legacy identity is present only when the greatest
registry version is legacy-owned. Effective order comes from the existing
effective owner, never from MAX(selection). Original-root inspection failure or
malformed legacy state makes state unavailable.

### 6.1 Legacy status normalization

State adapter в одном read snapshot проверяет registry/source identity и original
root до construction `LegacyIdentitySummary`. `hasAcceptedOriginal` означает
ровно один validated accepted original root для exact case/order; ошибка query,
duplicate/malformed root или ownership mismatch делает state unavailable.
Raw physical `status` принимает только literal `prepared` и `registered`.
Null, unknown text и unsupported source combination не превращаются в бизнес-отказ.

| Valid registry legacy source | Physical status | Accepted original | Cross-source outcome |
| --- | --- | --- | --- |
| present, unique matching physical source | prepared | false | pending_selection_exists |
| present, unique matching physical source | prepared | true | legacy predecessor allows new_order at N+1 |
| present, unique matching physical source | registered | false | preserved registered predecessor allows new_order at N+1 |
| present, unique matching physical source | registered | true | legacy predecessor allows new_order at N+1 |
| orphan/dual/mismatch/unknown status or invalid root | any | any | dependency_unavailable; no allocation |

Эта таблица нормализует только legacy compatibility и не делает registration
целевым основанием открытия. `replace_pending` никогда не усыновляет legacy row;
после cross-source refusal/eligibility действуют section10 ledger rules.

`SelectionTransactionSession::selectionState()` возвращает
`SelectionStateLookup`, такой же closed lookup с `T=SelectionStateSnapshot`.
Пустая история — FOUND snapshot с null summaries; NOT_FOUND — malformed
state/unavailable, не отсутствие case. До allocation проверяются все summary
bounds/identity/hash, связь latest/pending/accepted revisions и current registry
source. Невозможное сочетание или lookup unavailable → rollback и
failed/dependency_unavailable. `lockedCase()` аналогично возвращает
`SelectionCaseLookup`; исчезновение уже resolved case или malformed payload
внутри транзакции — dependency_unavailable. Эти read failures не являются
persistence_error и не добавляют terminal request/audit.

## 7. Registry and dateless ledger schema

Tables use InnoDB/canonical utf8mb4; UUID/hash/discriminator fields use ASCII
binary collation. The approved deployment table prefix remains up to 25 bytes;
new member base name `fm2_assignment_order_selection_members` is 38 ASCII
bytes and therefore 63 bytes with that prefix. Every migration-generated
constraint/index name must also fit MariaDB's 64-byte identifier limit with
the full prefix. Exact generated
names are pinned and fingerprinted by the subsequent migration contract. The
implementation chooses its literal migration number at the actual frontier
after its migration gate; this spec does not reserve version13.

| `fm2_assignment_order_identities` | Exact definition |
| --- | --- |
| assignment_order_id | BIGINT UNSIGNED PK AUTO_INCREMENT; allocator/read validation 1..PHP_INT_MAX |
| installation_case_id | BIGINT UNSIGNED NOT NULL; FK cases RESTRICT/RESTRICT |
| order_version | SMALLINT UNSIGNED NOT NULL; CHECK 1..65535 |
| source_kind | VARCHAR(24) ASCII BIN NOT NULL; CHECK legacy_order/selection |
| allocated_at_utc | DATETIME(6) NOT NULL |

Unique `(case,version)` and `(id,case)`; index `(case,source_kind,version)`.

| `fm2_assignment_order_selections` | Exact definition |
| --- | --- |
| assignment_order_id | BIGINT UNSIGNED PK; paired FK with case to registry |
| installation_case_id | BIGINT UNSIGNED NOT NULL |
| order_version | SMALLINT UNSIGNED NOT NULL, CHECK 1..65535 |
| selection_revision | INT UNSIGNED NOT NULL, CHECK ≥1 |
| mode | VARCHAR(24) ASCII BIN NOT NULL; CHECK new_order/replace_pending |
| previous_selection_order_id | BIGINT UNSIGNED NULL; FK selections |
| replaces_selection_order_id | BIGINT UNSIGNED NULL; FK selections |
| composition_identity | VARCHAR(160) ASCII BIN NOT NULL UNIQUE |
| composition_sha256 | CHAR(64) ASCII BIN NOT NULL lowercase-hex CHECK |
| control_engineer_user_id | BIGINT UNSIGNED NOT NULL, CHECK ≤PHP_INT_MAX |
| control_engineer_fio_snapshot | VARCHAR(300) NOT NULL, trimmed nonempty CHECK |
| control_engineer_position_snapshot | VARCHAR(300) NOT NULL, trimmed nonempty CHECK |
| selection_date | DATE NOT NULL |
| selected_at_utc | DATETIME(6) NOT NULL |
| selected_by_user_id | BIGINT UNSIGNED NOT NULL, CHECK ≤PHP_INT_MAX |

Unique `(case,version)` and `(case,selection_revision)`; indexes previous and
replaces IDs. CHECK requires replaces null for new_order and non-null for
replace_pending.

| `fm2_assignment_order_selection_members` | Exact definition |
| --- | --- |
| assignment_order_id | BIGINT UNSIGNED NOT NULL FK selection RESTRICT/RESTRICT |
| installer_tab_id | BIGINT UNSIGNED NOT NULL CHECK 1..PHP_INT_MAX |
| fio_snapshot, position_snapshot | VARCHAR(300) NOT NULL trimmed nonempty |
| employment_status_snapshot | VARCHAR(40) ASCII BIN NOT NULL CHECK employed |
| employed_from_snapshot | DATE NOT NULL |
| employed_to_snapshot | DATE NULL CHECK null or ≥from |
| workforce_source_snapshot | VARCHAR(80) NOT NULL trimmed nonempty |
| workforce_source_updated_at_snapshot | VARCHAR(40) ASCII BIN NOT NULL |

PK `(orderId,installerId)`. Selection has no order_date, valid_from/to,
change_action, registration/opening status or effective flag. Selection date is
Moscow date of selectedAt, only eligibility/audit context.

## 8. Exact request/event/audit storage

| `fm2_assignment_order_selection_requests` | Exact definition |
| --- | --- |
| request_id | CHAR(36) ASCII BIN PK, canonical UUID CHECK |
| operation_fingerprint | CHAR(64) ASCII BIN NOT NULL lowercase-hex CHECK |
| actor_user_id | BIGINT UNSIGNED NOT NULL |
| control_engineer_user_id | BIGINT UNSIGNED NULL |
| expected_selection_revision | INT UNSIGNED NOT NULL |
| installation_object_id | BIGINT UNSIGNED NOT NULL |
| installer_tab_ids_json | TEXT ASCII BIN NOT NULL; canonical array, 2..10001 bytes |
| mode | VARCHAR(24) ASCII BIN NOT NULL CHECK new_order/replace_pending |
| status | VARCHAR(16) ASCII BIN NOT NULL CHECK selected/rejected/conflict |
| reason_code | VARCHAR(64) ASCII BIN NULL; status/reason CHECK section3 |
| retryable | TINYINT(1) NOT NULL CHECK 0 |
| case_id | BIGINT UNSIGNED NULL |
| assignment_order_id | BIGINT UNSIGNED NULL FK selections |
| assignment_order_version | SMALLINT UNSIGNED NULL |
| selection_revision | INT UNSIGNED NULL |
| composition_identity | VARCHAR(160) ASCII BIN NULL |
| composition_sha256 | CHAR(64) ASCII BIN NULL |
| selection_date | DATE NULL |
| selected_at_utc | DATETIME(6) NULL |
| terminal_at_utc | DATETIME(6) NOT NULL |

Selected requires every success column non-null/equal to selection and null
reason; rejection/conflict requires every success column null and allowed
reason. Technical failure is never cached. Stored tuple and digest both must
match replay.

| `fm2_assignment_order_selection_events` | Exact definition |
| --- | --- |
| event_id | BIGINT UNSIGNED PK AUTO_INCREMENT; pre-commit/read validation 1..PHP_INT_MAX |
| event_type | VARCHAR(64) ASCII BIN CHECK assignment_order_composition_selected |
| request_id | CHAR(36) ASCII BIN NOT NULL UNIQUE FK requests |
| installation_case_id | BIGINT UNSIGNED NOT NULL |
| assignment_order_id | BIGINT UNSIGNED NOT NULL UNIQUE FK selections |
| assignment_order_version | SMALLINT UNSIGNED NOT NULL |
| selection_revision | INT UNSIGNED NOT NULL |
| previous_selection_order_id | BIGINT UNSIGNED NULL |
| replaces_selection_order_id | BIGINT UNSIGNED NULL |
| composition_sha256 | CHAR(64) ASCII BIN NOT NULL |
| occurred_at_utc | DATETIME(6) NOT NULL |
| actor_user_id | BIGINT UNSIGNED NOT NULL |

Event values equal selection/request and contain no member/file/template/date.

| `fm2_assignment_order_selection_audits` | Exact definition |
| --- | --- |
| audit_id | BIGINT UNSIGNED PK AUTO_INCREMENT; pre-commit/read validation 1..PHP_INT_MAX |
| request_id | CHAR(36) ASCII BIN NOT NULL, nonunique index |
| actor_user_id | BIGINT UNSIGNED NOT NULL |
| installation_object_id | BIGINT UNSIGNED NOT NULL |
| mode | VARCHAR(24) ASCII BIN NOT NULL CHECK new_order/replace_pending |
| status | VARCHAR(16) ASCII BIN NOT NULL CHECK selected/rejected/conflict |
| reason_code | VARCHAR(64) ASCII BIN NULL; status/reason CHECK |
| attempted_at_utc | DATETIME(6) NOT NULL |

These eight are the only safe fields. Success commits registry/selection/member/
request/event/audit atomically. Fresh business rejection/conflict commits request
and one audit. Invalid shape creates none. Each denial appends an independent
rejected/authorization_denied audit without terminal lookup/mutation. Matching
authorized replay is silent. Different tuple adds one conflict audit and leaves
the terminal row. Technical failure/exhaustion creates no business facts.
Confirmed audit rollback → persistence_failure; unknown audit commit →
persistence_outcome_unknown. No safe-log implementation is invented.

## 9. Exact transaction ports/payloads

```php
final readonly class SelectionNormalizedIntent
{ public function __construct(public int $actorUserId,public ?int $engineerUserId,
  public int $expectedRevision,public int $objectId,public InstallerTabIdSet $installers,
  public AssignmentOrderCompositionMode $mode,public string $canonicalJson,
  public string $fingerprint) {} }
final readonly class SelectionTerminalRequestRecord
{ public function __construct(public SelectionRequestId $requestId,
  public SelectionNormalizedIntent $intent,
  public AssignmentOrderCompositionResult $terminalResult) {} }
interface SelectionTerminalRequestReader
{ public function findTerminalRequest(SelectionRequestId $id):SelectionTerminalRequestLookup; }
interface SelectionUnitOfWork
{ public function executeForCase(int $caseId,SelectionTransactionalWork $work):SelectionUnitOfWorkResult; }
interface SelectionTransactionalWork
{ public function run(SelectionTransactionSession $transaction):SelectionTransactionDecision; }
interface SelectionTransactionSession
{
 public function lockedCase():SelectionCaseLookup;
 public function findTerminalRequest(SelectionRequestId $id):SelectionTerminalRequestLookup;
 public function selectionState():SelectionStateLookup;
 public function allocateIdentity(SelectionSourceKind $kind,SelectionInstant $at):SelectionIdentityAllocationResult;
 public function stageAccepted(SelectionAcceptedPersistence $payload):SelectionStageResult;
 public function stageTerminalAttempt(SelectionTerminalAttemptPersistence $payload):SelectionStageResult;
}
interface SelectionFreshTerminalReaderFactory
{ public function open():SelectionCloseableTerminalRequestReader; }
interface SelectionCloseableTerminalRequestReader extends SelectionTerminalRequestReader
{ public function close():void; }
interface SelectionAttemptAuditWriter
{ public function append(SelectionSafeAttemptAudit $audit):SelectionAuditWriteResult; }
interface SelectionTerminalAttemptUnitOfWork
{ public function execute(SelectionTerminalAttemptPersistence $payload):SelectionUnitOfWorkResult; }
```

`SelectionTerminalAttemptUnitOfWork` обслуживает только authorized case-NOT_FOUND
ветку после acquisition attempt instant. Она атомарно сохраняет terminal request
с `rejected/object_not_found` и один matching audit без case lock, allocation,
selection/member/event facts. CaseId0 или fictional case не создаются. Payload
использует прежние normalized intent/result/audit types. Wrong result/reason/
request/intent/audit echoes отклоняются до mutation как persistence failure.
Successful commit → committed(exact result); already observed terminal до mutation
→ observedTerminal(record); exact request-key race → requestRace(); confirmed
rollback → rolledBack(PERSISTENCE_FAILURE либо доказанный ALLOCATION_CAPACITY_EXHAUSTED);
unconfirmed commit/rollback → outcomeUnknown(). Эти ветви используют существующие
section9.1/10 replay/recovery mappings без blind retry или нового clock.
Другие SQL faults не являются request race. Writer не принимает произвольные
case-independent business outcomes и не изменяет existing terminal/audit.

The referenced payloads have these exact constructors (normative type table):

| Type | Constructor fields, in order |
| --- | --- |
| `SelectionIdentityAllocation` | `int assignmentOrderId, int caseId, int orderVersion, SelectionSourceKind sourceKind, SelectionInstant allocatedAt` |
| `SelectionAcceptedPersistence` | `SelectionIdentityAllocation allocation, int selectionRevision, AssignmentOrderCompositionMode mode, ?int previousSelectionOrderId, ?int replacesSelectionOrderId, EngineerSnapshot engineer, list<InstallerSnapshot> installers, string selectionDate, SelectionInstant selectedAt, UserId actor, SelectionNormalizedIntent intent, AssignmentOrderCompositionResult selectedResult, SelectionSelectedEvent event, SelectionSafeAttemptAudit audit` |
| `SelectionTerminalAttemptPersistence` | `SelectionRequestId requestId, SelectionNormalizedIntent intent, AssignmentOrderCompositionResult terminalResult, SelectionSafeAttemptAudit audit` |
| `SelectionSelectedEvent` | `SelectionRequestId requestId, int caseId, int orderId, int orderVersion, int selectionRevision, ?int previousSelectionOrderId, ?int replacesSelectionOrderId, string compositionSha256, SelectionInstant occurredAt, UserId actor` |
| `SelectionSafeAttemptAudit` | `SelectionRequestId requestId, UserId actor, InstallationObjectId objectId, AssignmentOrderCompositionMode mode, AssignmentOrderCompositionStatus status, ?AssignmentOrderCompositionReason reason, SelectionInstant attemptedAt` |

`SelectionSourceKind` is a backed enum `LEGACY_ORDER='legacy_order'` and
`SELECTION='selection'`; this command allocates only `SELECTION`. Installer list
in accepted persistence is nonempty, unique and numeric-ascending.
`SelectionSelectedEvent` и `SelectionSafeAttemptAudit` — pre-insert payloads;
DB-generated eventId/auditId отсутствуют в constructors. ID назначает storage
через соответствующий AUTO_INCREMENT; положительный int ≤PHP_INT_MAX проверяется
до признания stage успешным. Generated IDs не входят в external command result.

`SelectionStageStatus` — enum STAGED='staged', REQUEST_RACE='request_race',
PERSISTENCE_ERROR='persistence_error', CAPACITY_EXHAUSTED='capacity_exhausted'. `SelectionStageResult` — final readonly,
private constructor, public readonly status и nullable eventId/auditId; factories:
`accepted(int eventId,int auditId): self` → STAGED с обоими IDs;
`terminal(int auditId): self` → STAGED с null eventId и auditId;
`requestRace(): self`, `persistenceError(): self`, `capacityExhausted(): self`
→ соответствующий status, оба ID null. При accepted stage eventId и auditId обязательны; terminal stage
никогда не возвращает eventId. Это receipt staging, не committed success;
rollback может оставить AUTO_INCREMENT gap, но не acknowledged факт.

`SelectionAuditWriteStatus` — enum COMMITTED='committed', ROLLED_BACK='rolled_back',
OUTCOME_UNKNOWN='outcome_unknown', CAPACITY_EXHAUSTED='capacity_exhausted'. `SelectionAuditWriteResult` — final readonly,
private constructor, public readonly status и nullable auditId; factories
`committed(int auditId): self`, `rolledBack(): self`, `outcomeUnknown(): self`,
`capacityExhausted(): self`. Только committed несёт valid ID. Receipt factory с ID вне bounds выбрасывает
`InvalidArgumentException('Invalid selection receipt.')` без I/O; production
stage adapter сначала валидирует DB values и возвращает persistenceError
без самостоятельного rollback/commit; транзакцией владеет только UoW.
Отдельный independent audit writer проверяет ID перед собственным commit:
confirmed rollback → rolledBack, uncertain commit → outcomeUnknown.
Public snapshot observer использует `SelectionStoredEvent(int eventId,
SelectionSelectedEvent payload)` и `SelectionStoredAudit(int auditId,
SelectionSafeAttemptAudit payload)`; caller не придумывает generated IDs.
Оба envelopes passive, ID проверяется observer при чтении из storage.
`SelectionRollbackCause` — closed enum:
`DEPENDENCY_UNAVAILABLE='dependency_unavailable'`,
`PERSISTENCE_FAILURE='persistence_failure'`,
`ALLOCATION_CAPACITY_EXHAUSTED='allocation_capacity_exhausted'`.
`SelectionTransactionDecision` — final readonly с private constructor и public
factories `commit(SelectionResult $result)`, `rollback(SelectionRollbackCause
$cause)`, `requestRace()`, `observedTerminal(SelectionTerminalRequestRecord $record)`.
`SelectionUnitOfWorkResult` — final readonly с private constructor и public
factories `committed(SelectionResult $result)`,
`observedTerminal(SelectionTerminalRequestRecord $record)`, `requestRace()`,
`rolledBack(SelectionRollbackCause $cause)`, `outcomeUnknown()`.
Оба closed types имеют `kind(): string` с literal factory name, `result():
?SelectionResult`, `rollbackCause(): ?SelectionRollbackCause`,
`terminalRecord(): ?SelectionTerminalRequestRecord`. Только соответствующая
ветвь несёт указанный payload; остальные getters null. `commit/committed`
принимают только selected/rejected/conflict, не replayed/failed.
Недопустимый result вызывает `InvalidArgumentException('Invalid selection decision.')`
без I/O. Причина не передаётся mutable closure capture или exception side channel.

UoW locks exact case row, rechecks request/state, allocates at most once after
acceptance, stages exactly one terminal outcome and commits once. Session exposes
no SQL/connection/commit/rollback. Callback выбирает decision, UoW выполняет
commit или rollback. Public final outcome возникает только после UoW result.
`commit(result)` требует exact один успешный stage соответствующего result и
равный stored terminal result; missing/multiple stage, wrong receipt shape или
mismatch → rollback(PERSISTENCE_FAILURE). `observedTerminal` запрещён после
allocation/staging, завершается release/rollback read-only transaction и не
считается write commit. Unexpected adapter/callback exception → rollback с
PERSISTENCE_FAILURE; не является бизнес-отказом.

### 9.1 Exhaustive staging/transaction outcome mapping

| Observation | Callback decision / UoW action | UoW result after confirmed action | External result |
| --- | --- | --- | --- |
| accepted STAGED receipt, exact staged selected result | commit(selected result) | committed(same result) | selected |
| terminal STAGED receipt, exact rejected/conflict result | commit(terminal result) | committed(same result) | exact rejected/conflict |
| stage PERSISTENCE_ERROR, ambiguous query failure, malformed generated-ID representation or wrong receipt shape | rollback(PERSISTENCE_FAILURE) | rolledBack(PERSISTENCE_FAILURE) | failed/persistence_failure |
| allocator/stage CAPACITY_EXHAUSTED with lossless proven registry/event/audit counter overflow | rollback(ALLOCATION_CAPACITY_EXHAUSTED) | rolledBack(ALLOCATION_CAPACITY_EXHAUSTED) | failed/allocation_capacity_exhausted, retryable false |
| stage REQUEST_RACE | callback requestRace(); UoW rolls back | requestRace() | fresh authorized terminal lookup, as section10 |
| typed state/case/dependency unavailable or malformed while locked | rollback(DEPENDENCY_UNAVAILABLE) | rolledBack(DEPENDENCY_UNAVAILABLE) | failed/dependency_unavailable |
| proven allocation/revision/version bound exceeded before writes | rollback(ALLOCATION_CAPACITY_EXHAUSTED) | rolledBack(ALLOCATION_CAPACITY_EXHAUSTED) | failed/allocation_capacity_exhausted, retryable false |
| matching already terminal record before allocation/staging | observedTerminal(record) | observedTerminal(same record) | section10 replay mapping |
| commit acknowledgement unknown or requested rollback cannot be confirmed | no second mutation | outcomeUnknown() | section10 fresh authorized lookup recovery |

REQUEST_RACE receipt возникает только при exact terminal-request unique
collision. Callback передаёт его отдельным typed requestRace decision; UoW
допускает такой decision только после matching stage receipt. Иначе выполняется
rollback(PERSISTENCE_FAILURE). Иной UNIQUE/FK/CHECK fault — PERSISTENCE_ERROR.
После confirmed rollback requestRace остаётся отдельным UoW outcome.
Для read-only observedTerminal требуется подтверждённое освобождение transaction;
если cleanup не подтверждён, UoW возвращает outcomeUnknown, не silent success.
No blind mutation retry. Никакой stage owner не выполняет rollback сам.

Independent denial/changed-request audit не использует case UoW. Его writer
владеет только своей transaction и pre-commit generated-ID validation:
committed(valid auditId) → исходный denial/request-conflict result;
rolledBack() → failed/persistence_failure;
capacityExhausted() → failed/allocation_capacity_exhausted, retryable false;
outcomeUnknown() → failed/persistence_outcome_unknown. Malformed receipt или
thrown writer error, после которого commit нельзя исключить, → outcome_unknown,
без повторной записи audit. Technical failure/exhaustion не кешируются.

### 9.2 Lossless allocator and generated-counter closure

`SelectionIdentityAllocationStatus` — enum ALLOCATED='allocated',
CAPACITY_EXHAUSTED='capacity_exhausted', PERSISTENCE_ERROR='persistence_error'.
`SelectionIdentityAllocationResult` — final readonly с private constructor и
public readonly status, nullable `SelectionIdentityAllocation $allocation`.
Factories: `allocated(SelectionIdentityAllocation): self`,
`capacityExhausted(): self`, `persistenceError(): self`. Только ALLOCATED имеет
payload. Allocated factory проверяет positive bounded IDs/case/version,
sourceKind=SELECTION и valid instant; иначе fixed
`InvalidArgumentException('Invalid selection allocation.')`. Allocation port
не может вернуть PHP-overflowed ID или внешний mutable status вместо этого типа.

Единственный registry allocator владеет чтением current frontier и фактическим
ID reservation. Перед int conversion он проверяет canonical unsigned decimal
server value: digits only, no leading zeros кроме0, comparison по length и
лексикографическому порядку. Ни float, ни overflowed cast недопустимы. Точно
доказанный next/generated registry ID >9223372036854775807 → capacityExhausted;
malformed/zero/negative/ambiguous value, wrong case/source/version, query failure
без доказанного overflow → persistenceError. Callback переносит соответствующую
typed rollback cause в UoW; allocation result не является commit acknowledgement.

Event и audit AUTO_INCREMENT counters независимы от registry allocator.
Storage проверяет их actual generated decimal IDs до constructing int receipt
и до commit. Доказанный positive decimal >PHP_INT_MAX → stage capacityExhausted;
ошибочная lexical representation/zero/negative/protocol mismatch → persistenceError.
Даже если identity/request уже staged, UoW откатывает все business facts; возможен
только технический AUTO_INCREMENT gap. Independent audit writer при таком же
proven audit-counter overflow возвращает capacityExhausted только после
confirmed rollback; uncertain rollback/commit → outcomeUnknown. SQL error text
сам по себе не является доказательством capacity exhaustion.

Если до обращения к allocator current case version/revision уже на пределе,
application выбирает capacity rollback до reservation. Если exhausted значение
получено после native allocation, no acknowledged out-of-range identity возникает:
public nonretryable capacity result допустим только после confirmed rollback.
Shape-invalid input IDs остаются invalid_command до этих проверок.

Fixed boundary examples: registry next9223372036854775808 → capacity without
allocation; event native generated9223372036854775808 → capacity after rollback
всех staged selected facts; independent denial audit generated9223372036854775808
→ capacity after its confirmed rollback; native result `not-a-number` → persistence
failure after confirmed rollback; любой из этих rollback без acknowledgement
→ outcomeUnknown и стандартная recovery без mutation retry. Last valid generated
ID9223372036854775807 разрешён, следующий invocation exhausted. Event/audit IDs
не переиспользуются и не перенумеровываются ради обхода capacity.

Typed verification observer exposes registry ownership, selection, request,
event and audit snapshots via explicit test configuration; no production fault
hook or direct-SQL acceptance seam.

## 10. Exact precedence and replay

1. Shape/canonicalization; invalid shape performs no dependency/audit.
2. Authorize. Unavailable → dependency_unavailable без clock/lookup/audit.
   Denied → acquire attempt instant по правилу ниже, затем independent denial
   audit без confidential lookup; its confirmed result следует sections5/8/9.
3. Outer terminal lookup: matching selected→replayed exact success; matching
   rejected/conflict→stored outcome, оба без clock read и нового audit.
   Tuple/digest mismatch → acquire attempt instant и independent request-conflict
   audit, без success disclosure; unavailable→dependency_unavailable без clock.
   Proven no terminal record → acquire attempt instant один раз перед step4.
4. Resolve object/case. Absent→object_not_found через
   SelectionTerminalAttemptUnitOfWork; unavailable→dependency.
   Completed precedes PTO.
5. Empty installers→installer_required; null engineer→control_engineer_required.
6. Use the already acquired attempt instant; derive Moscow selection date.
   Второй clock read запрещён.
7. Installer batch: first numeric missing then first numeric unemployed; engineer
   absent/ineligible next. Infrastructure errors remain dependency failures.
8. In transaction recheck request, then locked state. Expected revision compares
   only latest ledger revision (zero if none), but zero does not mean no pending
   order: apply exact cross-source rules below.
9. If greatest registry source is a legacy prepared/unregistered order with no
   accepted original root, return pending_selection_exists without mutation.
   Do not adopt/replace it. If legacy root inspection is malformed/unavailable,
   return dependency_unavailable. A matching registered legacy predecessor, or
   a legacy order with accepted original, permits new_order at registry version
   N+1 and ledger selection revision1. Registered is only a compatibility
   predecessor predicate, not target applicability approval.
10. For `new_order`, latest ledger selection pending→pending_selection_exists;
    latest accepted permits a new prospective order, including the same
    composition. For `replace_pending`, no latest ledger selection→
    selection_not_found; a latest selection with accepted original→
    original_already_accepted; exact latest pending is the only replace target.
    A replacement with the same normalized engineer/installer composition→
    rejected/no_changes. Otherwise it creates a new immutable identity/version/
    revision, with both previous and replaces IDs pointing to that latest pending
    selection. It never converts a legacy prepared row. Ledger revision
    increments independently of legacy version.
11. Prove revision≤4294967295, version≤65535, global ID≤PHP_INT_MAX using
    lossless decimal arithmetic. Proven overflow→nonretryable capacity exhausted;
    ambiguous failure→persistence failure. Allocate, compose, stage, commit once.
12. Unknown commit: reauthorize, fresh independent same-request lookup only.
    matching selected→replayed; stored rejection/conflict→stored; mismatch→request
    conflict; proven absent→persistence_failure; unavailable→outcome_unknown.

### 10.1 One invocation-owned attempt instant

`SelectionClock::now()` вызывается ровно один раз для invocation, которому
нужно создать любой audit/terminal fact или fresh selection. Значение lazily
получается в указанных ветвях steps2/3 и сохраняется в invocation-owned immutable
context; storage/audit writer не читает другой clock. Shape rejection,
authorization unavailable, terminal lookup unavailable и полный matching
replay не вызывают clock вообще. Unavailable/NOT_FOUND/malformed instant →
failed/dependency_unavailable до любых audit/terminal writes, включая denial и
changed-request conflict; первоначальная бизнес-причина не возвращается как
успешно audited terminal outcome.

Все attemptedAt, terminal_at_utc, selectedAt, occurredAt и allocatedAt данной
invocation равны этому UTC instant; selectionDate — его Moscow calendar date.
Повторная authorization в unknown recovery не читает clock заново. Если recovery
требует denial/conflict audit, используется тот же сохранённый instant; новый
внешний invocation получает своё собственное время. Matching recovered terminal
возвращает stored timestamps, а не attempt instant recovery.

Request-race resolution использует те же authorization/read-only lookup outcomes,
что unknown recovery: matching selected → replayed, matching terminal rejection/
conflict → exact stored result, mismatch → request_id_conflict с independent
audit, proven absence → persistence_failure, unavailable → outcome_unknown.
После rollback/unknown никакого нового allocation или blind write retry нет.

No minted request ID, fallback source, blind retry or second allocation.

## 11. Backfill, global ownership and readiness

Stop all order writers. Preflight exact legacy AUTO_INCREMENT, max ID, count and
hash `(id,case,version)`; reject bad range/duplicate/orphan/schema/prepared_at.
Backfill exact IDs/case/version as legacy_order; allocatedAt is parsed preparedAt,
not migration time, and emits no event. Registry frontier is decimal max of
captured AUTO_INCREMENT, legacy max+1, registry max+1, preserving gaps and
bounded by PHP_INT_MAX. Receipt freezes frontier/max/count/hash only for IDs
`<=legacy_max_id`; dynamic checks enforce current bidirectional ownership.

Post-cutover legacy writer uses same case lock/registry allocation and explicit
physical ID. It may proceed only after absent registry history or matching
registered legacy predecessor. Latest selection fails `SELECTION_OWNED_CASE`;
inconsistent history fails `IDENTITY_HISTORY_UNAVAILABLE` (compatibility-owner
outcomes, not this command reasons). No skip/synthetic predecessor/reuse.

Readiness requires exact schema fingerprints, receipt/frontier, no orphan/dual/
mismatch, and registry-aware selection writer, original reader and every legacy
writer. No runtime DDL/lazy repair/precedence/implicit old allocator/mixed writer
window. Rollback only to registry-aware build; never delete history/lower frontier.

## 12. Original reader handoff

Original source remains `AssignmentOrderCompositionReader::find(caseId,orderId)`
and `AssignmentOrderCompositionSnapshot` in
`app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php`.
Add one consistent-snapshot registry dispatch. ID owned by other case→not_found
without probe. No registry/no source→not_found; orphan→unavailable.
`legacy_order` requires matching physical order, no selection, and unchanged
legacy temporal/member reader. `selection` requires matching dateless header,
no physical order, canonical members/hash, numeric order and no temporal filter.

Missing registered source, dual/source mismatch, malformed snapshot/hash,
duplicate ownership/query failure→unavailable; never precedence/fallback. Original
root/request gets no invented FK/physical row. This changes no original authority
or revision rules. Optional renderer later reads this same immutable source and
returns PDF without storing template files/versions; only generation date and
append-only audit are persisted.

## 13. Acceptance matrix — approved synthetic only

All fixtures are isolated approved synthetic data; actions use public selector,
results/public projections/typed observer. Expected literals come from spec.
Renderer/template storage is absent, not mocked as a dependency.

| Given/action | Exact observable outcome |
| --- | --- |
| no history; object4512→case4512, actor18, installer7001, engineer73, clock `2026-09-05T09:00:00Z`, allocator81; new_order rev0 | selected order81/version1/revision1, exact section4 identity/hash/date; one registry/selection/member/request/event/audit; no physical order/original/effective/opening/artifact |
| repeat same request | replayed identical success; counts unchanged |
| same request changed normalized field | conflict/request_id_conflict; one safe conflict audit; no disclosure/mutation |
| revoke then same accepted request twice; restore | two denial results/audits before lookup; accepted facts unchanged; then silent replay; final request/event/audit counts 1/1/3 |
| two requests expected rev0 | one selected; loser stale; no mixed snapshot |
| pending ledger selection | new_order/current rev→pending_selection_exists |
| pending ledger selection; replace_pending/current rev changed composition | selected new immutable ID/version/revision; previous+replaces identify old pending; old snapshot remains |
| pending ledger selection; replace_pending same composition | rejected/no_changes; request+audit only |
| no ledger selection / latest ledger accepted; replace_pending | selection_not_found / original_already_accepted; no identity/event |
| legacy prepared, no accepted original, ledger rev0 | pending_selection_exists; no allocation/adoption |
| legacy registered or accepted-original version N, no ledger | new_order rev0→version N+1, selection revision1 |
| malformed/unavailable legacy original-root inspection | failed/dependency_unavailable |
| applicable legacy A plus pending B | directory assigned/free and inspection case/member bytes unchanged and still A |
| completed plus PTO | rejected/object_completed; terminal request+audit only |
| empty installers/null engineer | corresponding required reason; request+audit only |
| known rollback | failed/persistence_failure; no committed facts |
| unknown then matching/absent/unavailable | replayed / persistence_failure / outcome_unknown; no retry mutation |
| any capacity boundary exceeded | failed/allocation_capacity_exhausted retryable false; no business facts/wrap |
| original reader selected ID | found exact canonical selection composition, no date/effective filter |
| registry/source mismatch or dual source | unavailable, never precedence-selected |

Preservation fixture: applicable A/version1 installer7001 engineer73; pending
B/version2 installer7002 engineer74. Directory assignment list+summary and
inspection scope/attribution remain 7001/73. Selection revision/audit may change;
actualStart, opening, originals, effective intervals/checklist attribution may not.

## 14. Release-compatibility dependency and Gate status

This full two-mode candidate is **not READY for Gate 1 or RED** until a separate
compatibility contract is approved at exact hashes and provides
registry/backfill/receipt migration, every legacy writer conversion, additive
original reader, readiness/rollback gates, and optional-render public path that
consumes selection without old prepare. Migration version is chosen only there.

Seamless legacy prepare after selection is outside this slice and remains a
release blocker if required; this release fails it closed. Effective projection
replacement belongs to `apply-assignment-order-original-to-composition`; until
then directory/inspection ignore selection exactly as section13.

After compatibility and independent Gate1 approval: demonstrated RED →
independent Gate3 → minimal GREEN → regressions/architecture → independent Gate5.

## 15. Approved pending-replacement policy

Owner approval: `docs/operations/owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`,
ответ «утверждаю разрешаю». REPLACE_PENDING до принятия original разрешён как
новая immutable selection/version с видимой прежней историей; accepted-original
composition этой командой не меняется. Новый owner approval не требуется.

FKR actor заменяет только exact latest ledger selection без accepted original;
прежняя selection и audit формирования остаются immutable и видимыми. New selection
становится current. Original для replaced identity не принимается как current
pending choice. После accepted original изменение состава — только отдельный
forward-only order lifecycle. Legacy prepared row не конвертируется.

## 16. v0.8 technical correction disposition

v0.4 independent review:
`docs/operations/selection-v04-independent-readiness-2026-09-05.md`.
Actual writers/readers:
`docs/operations/selection-writer-reader-cutover-inventory-2026-09-05.md`.
Этот revision исправляет result/lookup closure, ownership validation,
pre-insert generated IDs/typed receipts и legacy-status normalization. Он не
выдаёт самому себе approval и не снимает P0 prerequisites: exact migration/
backfill/receipt/writer cutover, original-reader amendment и same-identity
optional-render contract. Они остаются отдельными technical Gate1 obligations.
v0.5 bounded rereview `selection-v05-typed-contract-review-2026-09-05.md`
закрыл construction findings, но потребовал точный clock placement и перенос
rollback cause. v0.6 добавляет invocation-owned lazy instant, typed rollback
cause и полный stage→decision→UoW→public-result mapping. Он требует fresh
independent review; RED и production implementation не начаты.

### v0.7 AUTO_INCREMENT constructibility correction

MariaDB не разрешает AUTO_INCREMENT column в CHECK. Registry/event/audit IDs
поэтому сохраняют BIGINT UNSIGNED AUTO_INCREMENT без такого CHECK; все bounds
остаются обязательны в allocator/storage pre-commit validation, receipt factories
и read integrity. Malformed generated-ID representation вызывает rollback/persistence_failure;
lossless proven positive counter overflow — rollback/allocation_capacity_exhausted,
как sections9.1/9.2. Invalid persisted ID — unavailable, не success. Не меняются
ID range, allocation authority, FK types, replay или owner policy.
Источник: [MariaDB constraints](https://mariadb.com/docs/server/reference/sql-statements/data-definition/constraint);
local MariaDB11.4.7 data-free DDL probe дал errno1901 для прежнего shape.
Это schema constructibility correction, не Gate2 RED application behavior.
Registry engine planning: `canonicalize-assignment-order-identity-registry` и
`ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001`; его draft не является Gate1 approval.

### v0.8 Generated counter result correction

Independent v0.7 review `selection-v07-generated-id-review-2026-09-05.md` потребовал
различить proven capacity от malformed storage receipt. Sections9.1/9.2 теперь
закрывают registry/event/audit mappings и allocator result type. Typed v0.6
control-flow сохраняется; schema CHECK не возвращён. Full technical Gate1 всё
ещё требует P0 release dependencies и свежего независимого approval.

## 17. Fresh launch и PDF без хранения — controlling owner amendment

Authority: `docs/operations/fresh-launch-owner-scope-2026-09-06.md` и
`docs/operations/selection-template-no-storage-owner-approval-2026-09-06.md`.
Исторических данных/PDF нет. Требования previous sections о переносе истории,
registry-aware old prepare writer, mixed N−1/N compatibility и сохранении старых
template artifacts не являются prerequisites текущего запуска.

Fresh startup использует единственного нового selection writer. Старые
prepare/registration/direct signed-original входы исключаются из нового portal
flow; их совместимая переделка не требуется. Режимы new_order/replace_pending,
immutable новые selection/original facts и их audit остаются обязательными.

Optional render использует ту же identity, формирует PDF с сегодняшней датой
Europe/Moscow и выдаёт в ответе без сохранения bytes, файлов или версий.
Повторный запрос формирует PDF заново. Сохраняются дата последнего успешного
формирования для original-date prefill и append-only audit actor/time/identity.
Новая artifact-storage family/migration исключена. Signed originals/corrections
хранятся неизменно; template generation не задаёт окончательную document date.

Selection Gate1 проверяет exact standalone selection command/new-writer contract
для fresh contour на approved registry/schema. Он не требует заранее реализовать
PDF/HTTP/opening или backward-compatible writers. Original locked-validation,
on-demand PDF/date/audit, fresh bootstrap и HTTP/application/opening проходят
свои gates до связывания и полного запуска; изолированные approvals не заменяют их.
Existing approved registry/schema/registered-reader components переиспользуются;
их synthetic historical cases не превращаются в новые launch obligations.
Full exact-SHA VERIFY_OK, CI, deployment/restart и golden path остаются обязательными.
