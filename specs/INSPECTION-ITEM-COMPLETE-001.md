# INSPECTION-ITEM-COMPLETE-001 v0.1

Status: `APPROVED` for Gate 1 on 2026-09-01 after independent rereview
`READY_FOR_OWNER_APPROVAL` and explicit owner response `Ок`. Production
composition amendment was independently rereviewed and explicitly approved by
the owner with a second response `Ок`.

## Простыми словами

Инженер сможет отметить один пункт чек-листа выполненным через единое правило,
одинаковое для браузера и отложенной offline-отправки. Любой активный инженер с
правом `inspection.item.complete` может работать с любым объектом, в том числе
за коллегу. Система сохраняет, кто действительно сделал отметку, и не выдаёт
его за назначенного ответственного. Этот срез не переносит фотографии,
завершение раздела, исправления, проценты готовности или планирование инспекций.

## Actor and public seam

Actor: authenticated FMonitor user.

Confirmed public application seam:

```text
InspectionRecording::completeItem(CompleteInspectionItem command)
    -> ItemCompletionResult
```

Confirmed public evidence query seam:

```text
InspectionEvidenceView::getItemCompletion(
    installationCaseId,
    clientOperationId
) -> ItemCompletionEvidence|null
```

Production composition seam:

```text
ProductionInspectionEvidenceFactory::create(
    mysqli connection,
    ProductionInspectionEvidenceConfig config,
    ?InspectionEvidenceClock clock = null
) -> InspectionEvidenceApplication

InspectionEvidenceApplication extends
    InspectionRecording, InspectionEvidenceView

ProductionInspectionEvidenceConfig(
    processTablePrefix
)

InspectionEvidenceClock::now() -> DateTimeImmutable
```

HTTP and offline synchronization adapters translate requests to the command and
map its typed result. The evidence query returns the accepted immutable operation,
installer snapshots and case revision needed by projections. Tests call these
two public interfaces; they do not call a repository, MariaDB adapter, HTTP
helper or rapid-pilot internals.

`ItemCompletionEvidence` contains the client operation id, case/section/item,
actual actor id, nullable assigned control engineer id at receipt, device and
server times, base/accepted revisions, immutable template id/version/hash, the
ordered installer snapshots and current case checklist revision. It never
backfills missing historical evidence. `null` means no accepted operation with
that id in that case.

The factory accepts dependencies and never discovers credentials or opens/closes
the caller-owned `mysqli` connection. It initializes that connection to
`utf8mb4`; one application instance owns transaction use of that connection and
must not be shared concurrently. Parallel workers create separate connections
and application instances. `processTablePrefix` is validated with the canonical
runner's ASCII 0..25-byte contract before DB access. The optional clock makes
server receipt time deterministic in tests; production defaults to a system
clock. Factory creation performs no DDL, schema repair or business mutation.
The application formats the returned instant as exact RFC3339 seconds
`Y-m-d\TH:i:sP` with an explicit numeric offset. The default system clock reads
`now` in `Europe/Moscow`; an injected fixed clock returns a
`DateTimeImmutable` and is never consulted more than once per first-time
command receipt. Replay returns the originally stored server receipt time.

## Command

`CompleteInspectionItem` contains exactly:

- `actorUserId`: positive integer obtained from authenticated server context;
- `installationCaseId`: positive integer;
- `clientOperationId`: canonical lowercase UUID;
- `deviceInstallationId`: canonical lowercase UUID;
- `deviceTime`: valid timestamp with explicit offset, retained as client audit;
- `expectedRevision`: non-negative integer;
- `sectionId` and `itemId`: positive integers;
- `installerTabIds`: non-empty, duplicate-free ordered list of positive numeric
  tab identifiers.

The adapter MUST NOT accept `actorUserId` from client JSON. Payload normalization
for replay uses all command fields except server receipt time and current
assignment audit context. `installerTabIds` are normalized as ascending numeric
identifiers before comparison and persistence.

## Preconditions and validation order

At server receipt the application seam checks, in this observable precedence:

1. actor exists, is active and has exact capability
   `inspection.item.complete`;
2. command syntax is valid;
3. landed canonical inspection-evidence v8 schema is available and compatible;
4. inside the case transaction, an existing client operation id is compared
   with the normalized command: exact match returns its original `DUPLICATE`
   result and changed payload returns `OPERATION_PAYLOAD_CONFLICT` before any
   mutable first-acceptance precondition;
5. for a new operation, installation case exists and is `working`;
6. current immutable checklist-template association exists and contains the
   requested section/item;
7. every selected installer belongs to the current registered assignment-order
   snapshot for the case and has a complete personnel snapshot;
8. exact expected revision is checked inside the same locked transaction as
   persistence.

Current control-engineer assignment is deliberately not an authorization
precondition. Inspection-planning schema, schedule existence and inspection due
date are not preconditions for this slice.

## Accepted result and persisted facts

For a first valid command at current revision `N`, the seam returns:

```text
ACCEPTED(revision = N + 1)
```

One transaction appends or advances only these canonical v8 facts:

- one immutable `item_completed` operation with actor, client/device identity,
  client time, authoritative server receipt time, base/accepted revisions,
  section/item and immutable template id/version/hash;
- one immutable personnel snapshot for each selected installer with
  `assignment_source=completion`;
- case checklist revision from `N` to `N + 1`;
- audit payload distinguishing actual `actorUserId` from nullable
  `assignedControlEngineerUserIdAtReceipt`.

Existing operations, installer snapshots and template identity are never
updated. A later crew or engineer reassignment does not rewrite accepted facts.
Projection reads are read-only and MUST NOT manufacture missing historical
installer attribution.

## Authorization and offline receipt

Any active user holding exact capability `inspection.item.complete` may complete
an item for any installation object. No current-assignment conjunction or
object-specific override capability exists in this slice.

Every receipt, including an exact replay queued offline, rechecks current actor
status and capability. `deviceTime` is never authority time. Reassignment alone
does not revoke access. A blocked user or revoked capability receives
`ACTOR_NOT_AUTHORIZED`, even if `deviceTime` predates the change.

## Idempotency and concurrency

After current authorization, command-syntax and v8 deployment checks succeed, an
existing `clientOperationId` with the same normalized command returns the
original before mutable case/template/crew validation:

```text
DUPLICATE(revision = original accepted revision)
```

It appends no fact and does not advance revision, even if the case subsequently
closed or assignment/template context changed. The same id with any different
normalized field returns `OPERATION_PAYLOAD_CONFLICT` with zero mutation.

Two distinct commands presenting the same `expectedRevision=N` are serialized
per installation case. Exactly one may return `ACCEPTED(N+1)`; the other returns
`STALE_REVISION(currentRevision=N+1)` with zero partial mutation.

## Typed rejections

Every rejected command returns the listed stable business reason and creates no
operation, installer snapshot, revision change or historical backfill:

- `ACTOR_NOT_AUTHORIZED`: actor absent/inactive or exact capability missing;
- `INVALID_COMMAND`: malformed UUID/time/id, negative revision, empty or
  duplicate installer list;
- `CASE_NOT_FOUND`: installation case absent;
- `CASE_NOT_WORKING`: case exists but is not in `working` state;
- `INSPECTION_SCHEMA_UNAVAILABLE`: canonical v8 family absent/incompatible;
- `CHECKLIST_TEMPLATE_UNAVAILABLE`: no immutable association/snapshot;
- `CHECKLIST_ITEM_UNKNOWN`: section/item not in the associated template;
- `INSTALLER_NOT_ASSIGNED`: selected installer is not in the current registered
  assignment-order snapshot;
- `INSTALLER_SNAPSHOT_INCOMPLETE`: required personnel evidence is incomplete;
- `OPERATION_PAYLOAD_CONFLICT`: operation id already binds another payload;
- `STALE_REVISION`: expected revision differs from locked current revision.

### HTTP adapter result mapping

The checklist HTTP adapter SHALL translate the application result without a
legacy mutation fallback:

- `ACCEPTED` -> `accepted` with the returned revision;
- `DUPLICATE` -> `duplicate` with the original accepted revision;
- `STALE_REVISION` and `OPERATION_PAYLOAD_CONFLICT` -> `conflict` with the
  returned revision;
- `INSPECTION_SCHEMA_UNAVAILABLE` -> throw
  `PilotHttpInfrastructureUnavailable`, which the existing outer HTTP adapter
  renders as HTTP 503 with `status=retryable`;
- every other deterministic domain rejection -> `rejected` with the returned
  revision.

`ChecklistSync` SHALL translate its external `objectId` to the canonical
`installationCaseId` through an explicit injected resolver seam before it
constructs `CompleteInspectionItem`; the identifiers MUST NOT be assumed
equal. Exactly one current case returns its id. No current case maps to
`CASE_NOT_FOUND`/`rejected` with revision `0`. Multiple current cases, resolver
failure, or database failure SHALL throw `PilotHttpInfrastructureUnavailable`
and use the HTTP 503 retryable path.

The public adapter result shape is exactly `{status, revision}` for
`accepted`, `duplicate`, `conflict`, and deterministic `rejected` results.

Only `item_completed` is delegated by this slice. Every other checklist
operation retains its existing branch and MUST NOT call `completeItem`.

Infrastructure failure during the transaction rolls back all slice-owned
mutation and is not reported as a business acceptance.

## Independently fixed acceptance examples

### A. Engineer records work on a colleague's object

Given case `4512` is `working`, assigned control engineer is `7302`, actor `7301`
is active with `inspection.item.complete`, current revision is `0`, template
`9101` contains section `1` item `28`, and installer `1042` is in the current
registered order, when `7301` sends operation
`11111111-1111-4111-8111-111111111111`, the result is `ACCEPTED(1)`.

The public evidence query returns actor `7301`, assigned engineer `7302`,
revision `1` and exactly one installer snapshot for `1042`; neither engineer
value replaces the other.

### B. Reassignment before offline receipt does not revoke capability

Given actor `7301` created an offline command while assigned, engineer `7302`
became responsible before receipt, and `7301` remains active with the exact
capability, receipt of the otherwise valid command is authorized. Assignment
change alone produces no authorization rejection.

### C. Earlier device time cannot restore revoked authority

Given an offline command says `deviceTime=2026-09-01T08:55:00+03:00`, actor
`7301` loses the capability at `09:00`, and server receipt occurs at `09:05`,
the result is `ACTOR_NOT_AUTHORIZED` with zero mutation.

### D. Exact replay

Given example A was accepted at revision `1`, the same authorized actor sends the
same normalized command again. The result is `DUPLICATE(1)` and all operation,
installer and revision values returned by the evidence query remain equivalent.

### D2. Exact replay after mutable facts changed

Given example A was accepted, its case later closed, its template association
changed and installer `1042` left the current order, the same still-authorized
actor repeats the exact normalized command. The result remains `DUPLICATE(1)`;
the evidence query returns the original immutable facts. A blocked actor would
instead receive `ACTOR_NOT_AUTHORIZED` before replay resolution.

### E. Same id, different installer

Given example A was accepted for installer `1042`, reuse of its operation id with
installer `2048` returns `OPERATION_PAYLOAD_CONFLICT`; revision remains `1`.

### F. Same-base concurrency

Given revision `0`, two valid operations for distinct items both present
`expectedRevision=0`. Under a real overlap, the unordered result set is exactly
`{ACCEPTED(1), STALE_REVISION(1)}` and only the winner's complete facts exist.
The public evidence query returns one winner operation and `null` for the loser.

## Persistence and architecture boundary

Canonical migration v8 is the sole schema owner. This slice adds no migration
or schema version. HTTP, UI, cron, imports and rapid-pilot do not execute
business persistence SQL, schema DDL, repair or fallback writes. They invoke
`InspectionRecording::completeItem` once and translate its result.

Missing/incompatible v8 schema fails closed without DDL or business mutation.
`make architecture-check` must not increase any existing debt baseline.

Production MariaDB acceptance tests construct the same command/query interfaces
through `ProductionInspectionEvidenceFactory`; they do not instantiate the
MariaDB adapter or repositories directly. The caller owns connection lifecycle,
while the application owns each command transaction on its connection.

## Explicit exclusions

This slice does not define photo behavior, section completion, correction or
retraction, inspection scheduling/cadence, completion percentages, declaration,
PTO, premium/payment behavior, or historical-assignment authorization by client
time.

## Approval trace

- Owner authorization decisions:
  `docs/operations/inspection-item-completion-authorization-decision.md`.
- Characterization evidence:
  `docs/operations/inspection-item-completion-behavior-evidence.md` and
  `specs/CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001.md`.
- Gate 1 requires independent technical review and explicit owner approval of
  this exact version before Gate 2 test edits.
