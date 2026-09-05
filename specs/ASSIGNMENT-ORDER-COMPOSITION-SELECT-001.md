# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 — выбор состава без шаблона

Версия 0.4, 2026-09-05. **DRAFT / GATE 1 NOT APPROVED**.

## Простыми словами

Сотрудник ФКР или Руководитель ФКР выбирает монтажников и инженера и сохраняет
этот выбор до загрузки подписанного оригинала. Сохранение не требует и не
создаёт PDF: шаблон можно сформировать позже отдельным действием из того же
неизменяемого снимка. Выбор ещё не является действующим назначением, не
открывает работы и не меняет состав в справочнике и у стройконтроля.

Кандидат полностью задаёт как новый выбор (`new_order`), так и append-only
замену ещё не подписанного выбора (`replace_pending`). Весь batch остаётся
`OWNER_APPROVAL_REQUIRED`: отсутствие ответа владельца не считается согласием
и до решения ни одна из ветвей не переходит к RED.

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
retryable true except nonretryable `allocation_capacity_exhausted`. Impossible
combinations are not constructible. Serializer emits every annotated key in
that order; enums use lowercase backing values.

Success IDs follow section 2; version `1..65535`, revision `1..4294967295`;
identity exact ASCII `composition-<orderId>-v<version>` (max160 bytes); hash 64
lowercase hex; date `YYYY-MM-DD`; selectedAt UTC RFC3339 seconds.

| Status | Exact allowed reasons |
| --- | --- |
| selected/replayed | null |
| rejected | invalid_command, authorization_denied, object_not_found, installer_required, control_engineer_required, installer_not_in_catalog, installer_not_employed, control_engineer_not_eligible, object_has_pto_act, object_completed, no_changes |
| conflict | request_id_conflict, stale_selection, pending_selection_exists, selection_not_found, original_already_accepted |
| failed | dependency_unavailable, persistence_failure, persistence_outcome_unknown, allocation_capacity_exhausted |

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

The following names are concrete readonly classes, each constructed with
`(SelectionLookupStatus $status, ?T $payload)`: `SelectionCaseLookup` has
`T=SelectionCasePayload`; `SelectionInstallerBatchLookup` has
`T=InstallerBatchPayload`; `SelectionEngineerLookup` has `T=EngineerSnapshot`;
`SelectionInstantLookup` has `T=SelectionInstant`; and
`SelectionTerminalRequestLookup` has `T=SelectionTerminalRequestRecord`.
Their constructors accept a non-null payload exactly for `found` and null
exactly for the other statuses. This paragraph is a normative type table, not
PHP pseudocode.

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

Snapshot text is trimmed nonempty UTF-8: FIO/position ≤300 chars, source ≤80,
sourceUpdatedAt valid RFC3339 ≤40. Employment status exact `employed`; from is
valid and ≤ selectionDate; to null or valid ≥from and ≥selectionDate. Requested
missing IDs and snapshots are numeric-ordered; duplicate/extra/malformed data is
unavailable. Engineer needs active user/role and exact
`construction_control_engineer`; inactive/absent is ineligible, infrastructure
error unavailable.

```php
final readonly class SelectionIdentitySummary
{ public function __construct(public int $assignmentOrderId,public int $orderVersion,
  public int $selectionRevision,public string $compositionIdentity,
  public string $compositionSha256,public bool $hasAcceptedOriginal) {} }
final readonly class LegacyIdentitySummary
{ public function __construct(public int $assignmentOrderId,public int $orderVersion,
  public string $physicalStatus,public bool $hasAcceptedOriginal) {} }
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
| assignment_order_id | BIGINT UNSIGNED PK AUTO_INCREMENT; CHECK 1..PHP_INT_MAX |
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
| event_id | BIGINT UNSIGNED PK AUTO_INCREMENT CHECK ≤PHP_INT_MAX |
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
| audit_id | BIGINT UNSIGNED PK AUTO_INCREMENT CHECK ≤PHP_INT_MAX |
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
 public function lockedCase():SelectionCasePayload;
 public function findTerminalRequest(SelectionRequestId $id):SelectionTerminalRequestLookup;
 public function selectionState():SelectionStateSnapshot;
 public function allocateIdentity(SelectionSourceKind $kind,SelectionInstant $at):SelectionIdentityAllocation;
 public function stageAccepted(SelectionAcceptedPersistence $payload):SelectionStageResult;
 public function stageTerminalAttempt(SelectionTerminalAttemptPersistence $payload):SelectionStageResult;
}
interface SelectionFreshTerminalReaderFactory
{ public function open():SelectionCloseableTerminalRequestReader; }
interface SelectionCloseableTerminalRequestReader extends SelectionTerminalRequestReader
{ public function close():void; }
interface SelectionAttemptAuditWriter
{ public function append(SelectionSafeAttemptAudit $audit):SelectionAuditWriteResult; }
```

The referenced payloads have these exact constructors (normative type table):

| Type | Constructor fields, in order |
| --- | --- |
| `SelectionIdentityAllocation` | `int assignmentOrderId, int caseId, int orderVersion, SelectionSourceKind sourceKind, SelectionInstant allocatedAt` |
| `SelectionAcceptedPersistence` | `SelectionIdentityAllocation allocation, int selectionRevision, AssignmentOrderCompositionMode mode, ?int previousSelectionOrderId, ?int replacesSelectionOrderId, EngineerSnapshot engineer, list<InstallerSnapshot> installers, string selectionDate, SelectionInstant selectedAt, UserId actor, SelectionNormalizedIntent intent, AssignmentOrderCompositionResult selectedResult, SelectionSelectedEvent event, SelectionSafeAttemptAudit audit` |
| `SelectionTerminalAttemptPersistence` | `SelectionRequestId requestId, SelectionNormalizedIntent intent, AssignmentOrderCompositionResult terminalResult, SelectionSafeAttemptAudit audit` |
| `SelectionSelectedEvent` | `int eventId, SelectionRequestId requestId, int caseId, int orderId, int orderVersion, int selectionRevision, ?int previousSelectionOrderId, ?int replacesSelectionOrderId, string compositionSha256, SelectionInstant occurredAt, UserId actor` |
| `SelectionSafeAttemptAudit` | `int auditId, SelectionRequestId requestId, UserId actor, InstallationObjectId objectId, AssignmentOrderCompositionMode mode, AssignmentOrderCompositionStatus status, ?AssignmentOrderCompositionReason reason, SelectionInstant attemptedAt` |

`SelectionSourceKind` is a backed enum `LEGACY_ORDER='legacy_order'` and
`SELECTION='selection'`; this command allocates only `SELECTION`. Installer list
in accepted persistence is nonempty, unique and numeric-ascending. Event/audit
generated IDs use the same positive PHP-int bound as storage.

`SelectionStageResult` is a closed enum
`STAGED='staged', REQUEST_RACE='request_race', PERSISTENCE_ERROR='persistence_error'`.
`SelectionAuditWriteResult` is a closed enum
`COMMITTED='committed', ROLLED_BACK='rolled_back', OUTCOME_UNKNOWN='outcome_unknown'`.
`SelectionTransactionDecision` is a closed value with constructors
`commit()`, `rollback()` and `observedTerminal(SelectionTerminalRequestRecord)`.
`SelectionUnitOfWorkResult` is a closed value with constructors
`committed(AssignmentOrderCompositionResult)`,
`observedTerminal(SelectionTerminalRequestRecord)`, `requestRace()`,
`rolledBack()` and `outcomeUnknown()`; only its first two carry payloads.

UoW locks exact case row, rechecks request/state, allocates at most once after
acceptance, stages exactly one terminal outcome and commits once. Session exposes
no SQL/connection/commit. Request unique race rolls back then fresh lookup;
other DB violations are persistence failure. No mutation retry/fake conflict.

Typed verification observer exposes registry ownership, selection, request,
event and audit snapshots via explicit test configuration; no production fault
hook or direct-SQL acceptance seam.

## 10. Exact precedence and replay

1. Shape/canonicalization; invalid shape performs no dependency/audit.
2. Authorize; denial/unavailable follow sections5/8.
3. Outer terminal lookup: matching selected→replayed exact success; matching
   rejected/conflict→stored outcome; tuple or digest mismatch→request_id_conflict
   without success disclosure; unavailable→dependency_unavailable.
4. Resolve object/case. Absent→object_not_found; unavailable→dependency.
   Completed precedes PTO.
5. Empty installers→installer_required; null engineer→engineer_required.
6. Read one clock; derive Moscow date. Failure→dependency_unavailable.
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
persists only artifact/template facts.

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

This full two-mode candidate is **not READY for Gate 1 or RED** until the owner
approves or revises the replacement decision in section15 and a separate
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

## 15. OWNER_APPROVAL_REQUIRED — pending replacement

**OWNER_APPROVAL_REQUIRED; no decision exists.** The exact proposed behavior is
already integrated into every enum, schema, transaction rule and acceptance row
above: an FKR actor may append a replacement only for the exact latest ledger
selection while it has no accepted original; the old selection and any template
remain immutable and visible in history; the new selection becomes current;
an original for the replaced identity is not accepted as the current pending
choice. After accepted original, composition changes only through a new
forward-only order lifecycle. No legacy prepared row is converted.

The owner must approve this behavior and user-visible history, reject it, or
direct a separate slice. Until that answer, neither mode in this combined
candidate advances to RED and elapsed time is not approval. All other choices
above are engineering consequences of approved boundaries and need no repeated
owner decision.
