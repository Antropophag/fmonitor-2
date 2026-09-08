# Selection transaction ports candidate

Date: 2026-09-05. Status: bounded technical **DRAFT** for a future coherent
Gate 1 batch. It is not approval, RED evidence, implementation permission, or
a migration-version reservation. Reconcile it with the result/replay,
identity/storage, audit and exhaustion candidates before executable spec.

## Boundary

```php
interface AssignmentOrderCompositionSelector
{
    public function select(SelectAssignmentOrderCompositionCommand $command): AssignmentOrderCompositionResult;
}
```

The application service owns authorization order, normalization, dependency
validation, mode/state rules, composition construction, replay comparison and
public-result mapping. Repositories enforce storage invariants and persist
instructions; they do not decide `NEW_ORDER`, `NO_CHANGES`, stale revision, or
a future owner-approved replacement.

Authorization precedes every confidential request lookup, including replay and
post-revocation retry. Denied/unavailable authorization cannot open a
transaction that reads a stored result. Denial auditing remains in the separate
audit candidate.

## Typed outer ports

All DTOs below are immutable, range-checked value objects, never untyped
array-only interfaces.

```php
interface SelectionAuthorizer
{
    public function authorize(int $actorUserId, SelectionCapability $capability): SelectionAuthorization;
}

interface SelectionTerminalRequestReader
{
    public function findTerminalRequest(SelectionRequestId $requestId): SelectionTerminalRequestLookup;
}

interface SelectionDependencyReader
{
    public function findCaseByObject(InstallationObjectId $id): SelectionCaseLookup;
    public function findInstallers(InstallerTabIdSet $ids, SelectionInstant $at): SelectionInstallerBatchLookup;
    public function findEngineer(UserId $id, SelectionInstant $at): SelectionEngineerLookup;
}

interface SelectionClock
{
    public function now(): SelectionInstant;
}
```

Each lookup has closed `found(payload)`, `notFound()` and `unavailable()`
variants. A found terminal record contains request ID, canonical typed intent,
lowercase fingerprint and a valid typed terminal result. Unavailable is never
treated as absence.

## One transaction owner

```php
interface SelectionUnitOfWork
{
    public function executeForCase(InstallationCaseId $caseId, SelectionTransactionalWork $work): SelectionUnitOfWorkResult;
}

interface SelectionTransactionalWork
{
    public function run(SelectionTransactionSession $transaction): SelectionTransactionDecision;
}

interface SelectionTransactionSession
{
    public function lockedCase(): LockedSelectionCase;
    public function findTerminalRequest(SelectionRequestId $id): SelectionTerminalRequestLookup;
    public function selectionState(): SelectionStateSnapshot;
    public function allocateIdentity(SelectionSourceKind $kind, SelectionInstant $at): SelectionIdentityAllocation;
    public function stageAccepted(SelectionAcceptedPersistence $accepted): SelectionStageResult;
    public function stageTerminalAttempt(SelectionTerminalAttemptPersistence $attempt): SelectionStageResult;
}
```

`executeForCase` begins one `mysqli` transaction and locks the exact existing
case row `FOR UPDATE` before invoking application-owned work. The session is
invalid after `run`; it exposes no `mysqli`, commit, rollback or nested
transaction. `lockedCase()` returns only typed identity/revision data.

`selectionState()` reads registry, selection and original-root state after the
lock and returns the storage candidate's typed latest/pending/accepted/effective
snapshot. The application applies domain rules.

`allocateIdentity()` is callable at most once, after in-transaction replay/state
checks and only on acceptance. It inserts the registry identity on the same
connection and returns its ID and case version. There is no injectable ID
generator, sequence peek, caller `MAX()+1`, or allocation outside this unit of
work. The application then builds canonical composition identity/hash.

`SelectionAcceptedPersistence` contains the allocation, selection revision and
mode, optional lineage IDs, immutable engineer and sorted nonempty installer
snapshots, selection date/instant and actor, normalized intent/fingerprint,
terminal success result, success event and accepted audit. It contains no
effective interval, original/template date, renderer value, SQL or metadata map.

`stageAccepted()` verifies that the allocation belongs to this live session,
then inserts the dateless header/members, terminal request, exactly one success
event and one accepted audit. Its closed result variants are `STAGED`,
`REQUEST_RACE` (only the terminal request unique key), and
`PERSISTENCE_ERROR`; it never commits or allocates. Any error rolls the whole
unit back.

`stageTerminalAttempt()` accepts only a typed rejected/conflict result and its
approved safe request/audit DTO. It allocates no identity and writes no
selection, members or success event. Authorization denial never uses this
case-scoped session; it uses the independent audit port described below.

## Directives and outcomes

```php
enum SelectionUnitOfWorkStatus: string
{
    case COMMITTED = 'committed';
    case OBSERVED_TERMINAL = 'observed_terminal';
    case REQUEST_RACE = 'request_race';
    case ROLLED_BACK = 'rolled_back';
    case OUTCOME_UNKNOWN = 'outcome_unknown';
}
```

`SelectionTransactionDecision` is a closed typed value with `commit()`,
`rollback()` and `observedTerminal(SelectionTerminalRequestRecord)` variants.
Observed-terminal is legal only before allocation/staging and causes a clean
rollback/close with no new facts. Its UoW result carries the matching terminal
record so the application can return exact replay/stored rejection. Failure
cleanup never carries or returns success identity.

`SelectionUnitOfWorkResult` is also closed. Committed carries the exact staged
terminal result; observed-terminal carries the matched record; request-race,
rolled-back and outcome-unknown carry no domain identity. Commit without
exactly one staged terminal result is a programming error and rolls back.
Rollback after allocation removes all rows, though an allocator gap may remain.

A unique-request violation maps only to `REQUEST_RACE`: after rollback the
authorized application opens a fresh reader and resolves that same request.
Other unique/FK/check violations map to persistence failure after confirmed
rollback; they never masquerade as stale/domain conflict unless an independent
locked-state read proves that domain condition. Unconfirmed commit maps to
`OUTCOME_UNKNOWN`. The adapter never retries mutation.

Independently proven ID, order-version or selection-revision exhaustion maps to
`failed/allocation_capacity_exhausted`, retryable=false, with null success
fields and no terminal cache, exactly as reviewed at `7a38f4f`. Ambiguous SQL
failure is persistence/dependency failure, never capacity exhaustion.

## Required application order

1. Shape-validate the command and normalize its canonical intent/fingerprint;
   invalid shape performs no authority/dependency read and persists no audit.
2. Authorize, then perform the confidential outer request lookup and return a
   conclusive replay/conflict. Obtain typed dependencies and resolve the case.
3. Execute for that case. Inside, recheck the request, read locked state, apply
   domain rules, allocate only for acceptance, and stage one terminal outcome.
4. Map committed/observed/request-race/rolled-back through the result contract.
5. On `OUTCOME_UNKNOWN`, reauthorize and use a fresh independent connection to
   look up only the same request. Never reuse the uncertain connection, mint a
   request ID, allocate again, or blindly rerun mutation.

The in-transaction request recheck closes the outer-lookup race. A matching
terminal record returns its stored outcome without new facts; a nonmatching
intent/fingerprint gives request conflict without stored success disclosure.

## Fresh lookup and verification

```php
interface SelectionFreshTerminalReaderFactory
{
    public function open(): SelectionCloseableTerminalRequestReader;
}

interface SelectionCloseableTerminalRequestReader extends SelectionTerminalRequestReader
{
    public function close(): void;
}

interface SelectionVerificationObserverFactory
{
    public function open(): SelectionVerificationObserver;
}

interface SelectionAttemptAuditWriter
{
    public function append(SelectionSafeAttemptAudit $audit): SelectionAuditWriteResult;
}
```

The fresh-reader factory creates a new database connection and is not a runtime
adapter selector. The read-only observer exposes typed canonical selection,
request, event, audit and registry-ownership snapshots for deterministic public
verification. Explicit test configuration is allowed; production gets no
native-test hook, environment switch, fault-control surface or safe-log scope.

`SelectionAttemptAuditWriter` owns a separate short transaction for an
authorization-denied attempt. Its audit has an independent generated audit ID
and nonunique request ID, uses only the eight safe fields fixed at `7a38f4f`,
and never reads, inserts or overwrites a terminal request. Its confirmed and
unknown commit outcomes map exactly through that reviewed audit contract.

This candidate does not redesign original upload, general persistence, or the
test harness. Exact DTO scalar construction and owner disposition of
`REPLACE_PENDING` remain open; this candidate does not claim Gate 1 readiness.

## 2026-09-05 reconciliation note

The observed-terminal and request-race variants, pre-authorization shape order,
independent denial-audit port, and exact capacity outcome above supersede the
earlier ambiguous passages while preserving this document as a technical draft.
