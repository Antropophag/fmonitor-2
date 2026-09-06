# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 — total persistence contracts

Version0.3, 2026-09-06. **DRAFT / INDEPENDENT GATE1 REQUIRED**.

## 1. Authority and scope

Parent ORIGINAL-UPLOAD-001 sections3,8,11,15 fixes result/commit semantics,
immutable revision evidence, same-request/fingerprint replay and fresh recovery.
This cumulative technical package closes the gaps listed in
`original-command-data-integrity-contract-audit-2026-09-06.md` and
`original-fresh-recovery-factory-feasibility-review-2026-09-06.md` together.
It covers application port-value validation, MariaDB consistent read/rehydration,
pre-SQL commit validation, genuine fresh-connection recovery and authorized
attempt time. It adds no new product workflow, capability, schema version,
selection writer, renderer, HTTP route, download or public domain write seam.

Parent resource lifecycle, safe-log owner/isolation, metadata shape, exact
fingerprint, registry compatibility and PDF HISTORY contracts remain normative.
No reviewed historical evidence may be rewritten; any incompatible old helper
or expectation needs an exact separately approved patch before application.

Denied-invocation audit cardinality, audit-only retryable failures and maintenance
remain separate dependencies. The owner is asleep and requested product choices
be deferred; this package does not choose denial audit policy or claim combined
command/launch closure while it is unresolved.

## 2. Common scalar and immutable snapshot grammar

Canonical request UUID is lowercase, version1..5, RFC variant8/9/a/b as already
used by command shape. Case/order/actor/installer/engineer/version IDs are positive
PHP integers1..PHP_INT_MAX. Revision number is1..4294967295, the exact existing
unsigned INT schema range; next revision beyond it fails persistence before
allocation/finalization/commit. No float, decimal exponent, sign or padded SQL
string is cast into a plausible value.
The once-per-invocation clock means the application-owned Dependencies clock.
Storage owns its own stage/finalization timestamps and must use a separate
adapter clock instance; it may not consume that application clock behind the
command's back. Production and worker composition bind separate clock objects
(the worker may give both the same fixed instant), preserving timestamp values
without extra calls to the application clock.

Root/revision IDs retain SHAPE-001 printable ASCII1..80 excluding slash and
backslash. Composition identity is exactly
`composition-<canonical orderId>-v<canonical positive version>` within160bytes.
Hashes are lowercase64hex. Byte size1..20971520. Document dates retain SHAPE-001
real Gregorian year0001..9999. UTC instants retain the existing exact canonical
UTC-second grammar and roundtrip, including exact1970-01-01T00:00:00Z; fractional,
normalized or timezone-offset values are invalid. This package does not widen
clock grammar or silently truncate stored microseconds.

A public port is passive and may return a foreign interface implementation.
The consumer reads required getters once into an immutable internal snapshot,
validates that snapshot, and never uses subsequent mutable getter answers.
Getter Throwable is a protocol failure, not a missing row or business conflict.
All failure results retain current requestId, retryable=true, evidence7null and
normal already-owned resource cleanup. No exception text becomes result/log.

## 3. Closed result lookup and stored Result

Every result lookup permits exactly FOUND/non-null, NOT_FOUND/null or
UNAVAILABLE/null. Read status and result once even for non-FOUND, so hidden
contradictory payload is rejected. Impossible combination/getter Throwable is
UNAVAILABLE semantically; outside unknown recovery it maps PERSISTENCE_FAILURE.
FOUND/null never behaves like a miss or starts a new upload.

A stored terminal Result has canonical requestId, retryable=false and exactly
one of:

| status | reason | evidence7 |
| --- | --- | --- |
| ACCEPTED | null | all present and valid |
| REJECTED | one rejected reason below | all null |
| CONFLICT | one conflict reason below | all null |

Rejected reasons: AUTHORIZATION_DENIED, ORDER_NOT_FOUND,
COMPOSITION_NOT_CONFIRMED, INVALID_COMPOSITION, FILE_TOO_LARGE, NOT_PDF,
INVALID_PDF, UNSAFE_PDF, FUTURE_DOCUMENT_DATE, NO_CHANGES.
Conflict reasons: SEMANTIC_COLLISION, STALE_REVISION, TARGET_NOT_FOUND,
TARGET_NOT_CURRENT, INITIAL_ALREADY_EXISTS.
REJECTED/INVALID_COMMAND is not valid stored history: SHAPE-001 rejects before
reliable identity/audit ownership and writes no terminal/audit. Such a stored row
or AttemptCommit is invalid despite the broader physical CHECK. Public invalid
command responses remain valid non-stored Results.
FAILED/REPLAYED are not newly persisted terminal facts. The historical table
CHECK admits REPLAYED syntactically, but the parent replay paths write no new
terminal; a stored REPLAYED row is an unavailable representation, not silently
promoted into an accepted fact. This reader restriction changes no table DDL.

Evidence7 means rootOriginalId,currentRevisionId,revisionNumber,documentDate,
sha256,byteSize,uploadedAt with section2 grammar. Terminal lookup requires stored
requestId equal to the queried UUID. Fingerprint FOUND requires ACCEPTED with a
valid stored UUID, which may differ from the current request. Never force a
rejected/conflict result into a successful fingerprint replay.

Authorized terminal hit: validated ACCEPTED becomes REPLAYED under current
requestId; validated REJECTED/CONFLICT is returned unchanged. Fingerprint hit
returns validated winner evidence as REPLAYED under current requestId. A fresh
unknown-accepted-commit recovery hit returns the validated stored status; it does
not falsely report no fact or rewrite the stored request. Snapshot all11 Result
getters exactly once before resource release/delivery. Preserve the parent's
response-delivery-loss rules for a confirmed accepted recovery.

## 4. Complete lineage boundary

FOUND lineage must expose complete validated root, current revision/number,
composition identity/hash, current date/PDF hash, case/order ownership and ordered
revision membership. A scalar getter/cast alone cannot prove that ownership.
Keep the base LineageLookup and CurrentEvidenceLookup unchanged. Add this exact
read-only extension, required for FOUND and optional for negative states:

```php
interface AssignmentOrderOriginalCompleteLineageLookup
    extends AssignmentOrderOriginalLineageLookup,
            AssignmentOrderOriginalCurrentEvidenceLookup
{
    public function installationCaseId(): ?int;
    public function assignmentOrderId(): ?int;
    /** @return list<string> ordered by ascending revision number */
    public function revisionIds(): array;
}
```

FOUND without the extension is protocol failure. Negative base-only lookups keep
source compatibility and must expose null base metadata; when an extension is
present its extra fields must be null/empty too. Existing FOUND helpers require
an exact independently reviewed additive metadata patch. No implementation may
infer missing case/order/list from caller data or accept absent current evidence.

Required invariants: root/current/every member ID grammar; unique revision IDs;
current revision is the last member; current number equals the contiguous
revision count; current date/hash valid; echoed case/order positive and exact;
root lookup root equals its argument; assignment-order lookup ownership equals
both arguments; exact composition identity binds that order. NOT_FOUND and
UNAVAILABLE expose null metadata and empty membership. `containsRevision` must
agree with the immutable member list for current/target IDs used by this command.

Validate before semantic drift, stale target, missing target or NO_CHANGES
selection. Corrupt FOUND is PERSISTENCE_FAILURE; only valid but differing root
composition/current/target relations select existing business conflict reasons.
No fallback `findLineage('')` is allowed. Initial conflict recovery uses the
explicit assignment-order lineage query; an adapter without it fails persistence
rather than selecting an arbitrary root. Latest revision overflow is persistence
failure, never a wrapped number or acknowledged conflict.

## 5. Composition protocol versus invalid business content

Snapshot case/order must echo exact positive requested IDs for every status.
NOT_FOUND/UNAVAILABLE require identity/hash/engineer null and installerIds=[];
otherwise protocol failure. FOUND may represent invalid business composition.
After correct ownership echo, FOUND content is valid only if identity follows
section2 with exact order, engineer positive PHP int, installers an actual
nonempty list of positive PHP ints strictly increasing without duplicates, and
hash equals SHA256 of the exact canonical JSON below:

`{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":31,"installers":[7001,7002],"orderId":81}`

Key order, unpadded numeric JSON values and no insignificant whitespace are fixed;
SHA256 of those literal bytes is
`388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5`.
A custom reader cannot bypass recomputation with a plausible64hex hash. Wrong
status/payload/ownership is PERSISTENCE_FAILURE. FOUND invalid content is the
existing REJECTED/INVALID_COMPOSITION; genuine NOT_FOUND is ORDER_NOT_FOUND;
UNAVAILABLE remains retryable persistence failure. No new business reason.

## 6. MariaDB consistent composition read

The physical legacy reader still reads its approved tables; registered-source
selection compatibility is separate. One owned REPEATABLE READ read-only
consistent snapshot covers exact order row and all member rows, with no write,
DDL, repair or fallback. An already-active caller transaction must not be
committed/replaced: return UNAVAILABLE. Release the owned transaction on every
outcome before returning; release failure makes the read UNAVAILABLE.

Losslessly validate SQL numeric representations before converting to PHP int;
mysqlnd native int and canonical decimal string are allowed when exact/in-range,
but float, sign, padding, overflow or other representation is invalid. Exact
order row ID must equal query; zero rows or different case is NOT_FOUND without
member probing. Multiple/malformed identity rows or query/transaction failures
are UNAVAILABLE. Positive-version/engineer failures, invalid real order/member
dates, missing members and invalid membership rules produce FOUND invalid
content, with correct case/order and null identity/hash/engineer, empty installers.

Each member must echo requested order, have positive unique installer ownership
across all actions, and action exactly assign/retain/release. Every valid_from is
a real date<=order_date; valid_to null or real date>=valid_from. Release requires
non-null valid_to<=order_date. Assign/retain participates when valid_to is null or
>=order_date. Read rows numeric ascending, validate and emit the exact sorted
active list and canonical hash. Reader returns typed unavailable on Throwable.

A deterministic two-connection snapshot race proves order+members never combine
pre-change and post-change states; barriers live only in explicitly declared
verification observers, not private SQL timing or sleeps. The exact observer API is fixed in section12; no private timing hook is used.

## 7. MariaDB terminal result backing

Every public request/fingerprint read owns one consistent read-only snapshot as
in section6. Invalid lookup arguments fail typed UNAVAILABLE before SQL. Absence
means zero exact request/fingerprint rows, not a malformed join hiding an orphan.
More than one matching row, query/transaction/rehydration failure, or missing
required backing is UNAVAILABLE; never repair or synthesize missing facts.

Stored row status/reason/retry/evidence fields obey section3 with lossless SQL
conversion. UTC storage is exact `YYYY-MM-DD HH:MM:SS.000000` (or a driver form
without the all-zero fraction), roundtrips to the canonical UTC instant; nonzero
microseconds are invalid. Validate mode, actor_identity as canonical positive
integer text, case/order and attemptedAt, even though those fields are not
returned in the public Result.

ACCEPTED requires exactly one matching revision, its root, one matching domain
event and one accepted audit. Request/revision agree on request, root, accepted
revision ID/number/date/hash/size/upload time, mode/actor and case/order via root;
revision event type matches INITIAL1 versus CORRECTION>1. Event agrees on type,
root/revision/case/order/time/actor. Audit agrees on request/mode/actor/case/order,
accepted/null reason and attemptedAt=uploadedAt. Private content identity equals
`content-sha256-<pdfSha256>`. Revision fingerprint is canonical64hex; fingerprint
lookup additionally requires exact queried fingerprint. Recompute it from the
mode/root/previous/current-at-commit identities, date and immutable root
composition hash/identity using the parent's exact length-prefixed grammar.

An accepted terminal is an immutable response snapshot. After a later correction,
its `currentRevisionId` still refers to the revision accepted by that request;
it MUST NOT be forced equal to the root's latest current_revision_id. Historical
replay remains valid while later revisions/current pointer advance. Root and
historical chain integrity are verified without rewriting that earlier response.

Non-denial REJECTED/CONFLICT requires exactly one matching safe audit with exact
identity, status/reason and attemptedAt; no accepted evidence fields. A stored
AUTHORIZATION_DENIED terminal requires presence of its original matching safe
audit (same terminal attemptedAt and safe identity); this package does not impose
an upper cardinality or reject additional denial-attempt rows. The inherited
first terminal+audit atomicity requires that original backing under either
future repeated-denial policy. Validation/creation/cardinality of additional
denial attempts is deferred, never inferred from the current UNIQUE constraint. No revision/event may
claim that same request as an accepted operation. Denial cardinality handling is
not changed by this reader; corrupt backing returns unavailable.

## 8. MariaDB lineage and reference reads

Lineage queries own one consistent snapshot, explicitly select all matching roots
(no LIMIT1 hiding duplicates) and all ordered revisions. Validate exact ownership,
root composition, current pointer, unique IDs, contiguous positive numbers,
previous chain, valid scalar evidence and selected current metadata. A root row
with a missing revision is UNAVAILABLE, not absence via an inner join. Validate
currentRevisionId identifies the highest/latest revision. Return the complete
section4 metadata snapshot only after read transaction release succeeds.

`hasCommittedContent` validates opaque content argument before SQL and returns
FOUND/Boolean from exactly one canonical nonnegative COUNT row; zero means
FOUND/false, not NOT_FOUND. Query, malformed/missing/multiple aggregate rows or
transaction failure returns UNAVAILABLE/null. Public reference lookup admits
only FOUND/Boolean or NOT_FOUND|UNAVAILABLE/null; contradictory values never
permit maintenance deletion. Maintenance changes remain a separate package.

## 9. Commit DTO validation before mutation

AcceptedCommit/AttemptCommit constructors remain passive. Before transaction,
SQL escaping/query or fault callback, validate every scalar/status/mode
relationship. Invalid DTO returns ROLLED_BACK with zero DB calls and zero owned
resource mutation. Existing caller transaction is never committed or rolled back;
valid DTO against active borrowed transaction returns ROLLED_BACK without writes.
No schema change or auto-repair belongs to these methods. These are real
MariaDB adapter validation rules. The application lease boundary retains
COMMAND-LIFECYCLE001§4: pure verification storage/repository ports may use a valid
synthetic opaque identity. Do not change their approved FINALIZE_DONE/evidence
oracles merely to imitate a filesystem filename. Only fixtures crossing the real
MariaDB persistence adapter must provide its canonical digest-bound identity.

Accepted scalar checks: section2 identities/hashes/date/time/size, positive
case/order/actor, exact privateContentIdentity digest binding, canonical
composition identity order binding, canonical fingerprint recomputation, normalized
SHAPE-001 correction reason. INITIAL requires number1, previous/expected/reason
null and exact event `assignment_order_original_accepted`. CORRECTION requires
number2..4294967295, valid previous=expected, distinct new revision ID and exact
event `assignment_order_original_corrected`. Event/mode disagreement is invalid.

Inside one owned READ COMMITTED write transaction, lock/read the authoritative
legacy order and its members using the same section5/6 composition derivation,
without starting a nested reader transaction. Initial and correction commits
must both match that valid current composition identity/hash and exact case/order.
A changed composition, missing requested order/case, or current composition now
invalid for acceptance gives ROLLED_BACK after confirmed rollback; malformed SQL
row identity/protocol/query does likewise. These are unacknowledged persistence
attempts, not a fabricated domain conflict cause. The existing generic CONFLICT
return cannot identify those authoritative-source changes through fingerprint/
lineage alone, so it is reserved for actual root/current/unique-winner states
resolved by the exact rereads below. These checks precede root/current-pointer mutation. No self-consistent
but fabricated DTO composition hash is sufficient. Registered-source routing
remains its later compatibility slice; this package locks the existing declared
legacy source only.

Lock/validate exact root and current revision before correction mutation. Root case/order/composition match,
previous=expected=current, number=current+1 and no current evidence NO_CHANGES
must hold. Same current date and PDF hash is a valid NO_CHANGES business state:
return CONFLICT after confirmed rollback so the existing application reread can
select REJECTED/NO_CHANGES; do not insert a revision or classify corruption.
Valid current drift/unique winner produces CONFLICT after confirmed
rollback; malformed stored state or invalid DTO relation produces ROLLED_BACK
after confirmed rollback. Insert immutable revision/request/event/audit and apply
exact current-pointer CAS atomically; no update/delete of historical evidence.
Initial uniqueness collision is CONFLICT only after confirmed rollback. On
application initial CONFLICT, validated accepted-fingerprint FOUND wins as replay;
a valid miss is followed by the explicit case/order lineage query. Only a complete
valid FOUND root for that case/order selects INITIAL_ALREADY_EXISTS. NOT_FOUND,
UNAVAILABLE or malformed lineage is PERSISTENCE_FAILURE, never invented existing
original. Correction CONFLICT keeps its validated fingerprint/current-lineage
stale/target/no-change precedence. NO_CHANGES remains CONFLICT at the repository
and becomes the exact rejected reason after that correction reread. No blind
commit retry. Distinguish before-commit error with confirmed rollback from commit
acknowledgement uncertainty; rollback after a lost acknowledgement cannot prove
that the preceding commit did not succeed.

AttemptCommit permits only REJECTED/CONFLICT+matching section3 reason,
retryable=false, canonical UUID/attemptedAt and positive actor/case/order. It
atomically inserts exact same terminal/audit values; status/retry are never
silently overwritten. Exact request-key collision after confirmed rollback is
CONFLICT; another integrity/query failure with confirmed rollback is ROLLED_BACK;
commit acknowledgement uncertainty or unconfirmed rollback is OUTCOME_UNKNOWN.
Unknown must never be reclassified ROLLED_BACK merely because a later rollback
call returned successfully. No retryable failure becomes a terminal row.

## 10. Genuine fresh terminal recovery

A caller-owned mysqli does not supply credentials for a new connection. No
credential extraction, connection cloning, same-connection fallback, ambient env
or mutable next-read flag is allowed. The exact public contracts are:

```php
enum AssignmentOrderOriginalFreshReaderOpenStatus: string
{
    case OPENED = 'opened';
    case UNAVAILABLE = 'unavailable';
}
enum AssignmentOrderOriginalFreshReaderCloseStatus: string
{
    case CLOSED = 'closed';
    case FAILED = 'failed';
}
interface AssignmentOrderOriginalFreshTerminalReaderFactory
{
    public function open(): AssignmentOrderOriginalFreshTerminalReaderOpenResult;
}
interface AssignmentOrderOriginalFreshTerminalReader
{
    public function findTerminalRequest(string $requestId): AssignmentOrderOriginalResultLookup;
    public function close(): AssignmentOrderOriginalFreshReaderCloseStatus;
}
final readonly class AssignmentOrderOriginalFreshTerminalReaderOpenResult
{
    private function __construct(
        public AssignmentOrderOriginalFreshReaderOpenStatus $status,
        public ?AssignmentOrderOriginalFreshTerminalReader $reader,
    ) {}
    public static function opened(AssignmentOrderOriginalFreshTerminalReader $reader): self;
    public static function unavailable(): self;
}
final readonly class AssignmentOrderOriginalFreshReaderConfig
{
    public function __construct(
        public string $databaseHost,
        public int $databasePort,
        public string $databaseName,
        public string $databaseUser,
        public string $databasePasswordFile,
        public string $tablePrefix,
    ) {}
}
final class AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory
    implements AssignmentOrderOriginalFreshTerminalReaderFactory
{
    public function __construct(
        AssignmentOrderOriginalFreshReaderConfig $config,
        ?AssignmentOrderOriginalPersistenceObserver $observer = null,
    ) {}
    public function open(): AssignmentOrderOriginalFreshTerminalReaderOpenResult;
}
```

OpenResult has exactly the two static constructions: OPENED/non-null and
UNAVAILABLE/null. Clone is private; __serialize/__unserialize throw exactly
LogicException with message `AssignmentOrderOriginalFreshReaderOpenResultNotSerializable`,
code0, previousnull, without adopting/serializing a reader. No alternative raw
construction/adoption route is exposed. Config construction is
passive/lazy. Host/port/database/user/prefix/password path and password bytes use
the parent's exact evidence-reader/worker connection grammar, with explicit
mysqli host/user/password/database/port and utf8mb4; no DSN/query reinterpretation.
`open()` validates and reads the password only then. Any construction/open/query
failure is typed unavailable with no raw exception; a partially opened native
connection is owned and closed once before unavailable is returned.

Add one optional trailing constructor argument
`?AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders = null`
to the existing Dependencies constructor, preserving all12 existing names/order.
The readonly public dependency property has concrete type
AssignmentOrderOriginalFreshTerminalReaderFactory and is initialized once from
the argument or an explicit unavailable factory. The nullable constructor
argument is not promoted then reassigned; no readonly property is written twice. The application recovery owner
uses this dependency directly; it does not ask the ordinary writer repository
for a fresh-looking lookup. Reader is one-shot: second find or find-after-close
returns UNAVAILABLE without SQL; repeated close returns cached CLOSED/FAILED
without another native close. Selected reader result is copied/validated before
close, never retaining mutable reader-backed objects across closure.

Required semantics:

- lazy factory construction makes no DB/password/file access;
- open at most once after accepted or authorized-attempt commit OUTCOME_UNKNOWN,
  unconfirmed commit Throwable, or authorized attempt request-key CONFLICT;
- reader owns one newly opened connection to the same trusted configured server,
  database/prefix, utf8mb4; its public surface offers one request read and close,
  no query/write credentials or general repository;
- normal accepted commit, initial terminal replay, fingerprint/lineage reads and
  accepted-commit semantic CONFLICT never open a fresh reader;
- use section3/7 validation on its exact requested terminal result, within a
  read-only consistent snapshot; no write connection usage during recovery;
- validated FOUND resolves stored outcome, validated NOT_FOUND gives
  PERSISTENCE_FAILURE, open/read/validation unavailable gives
  PERSISTENCE_OUTCOME_UNKNOWN; no second open/commit/clock/allocation;
- close once after selected read and before content lease release; close failure
  preserves an already validated read result and produces one fixed safe
  diagnostic, never a retry or forged absence;
- absent factory is an explicit unavailable provider, never borrowed writer reuse.

Accepted-commit unknown FOUND preserves stored ACCEPTED and delivery/response-loss
semantics. Authorized terminal-attempt CONFLICT/unknown FOUND returns accepted as
REPLAYED (the competing terminal owns the request), or exact stored rejection/
conflict. In either context all evidence/status/reason validation precedes
selection. Recovery lookup getters may throw and must map unknown without leaks.

Production compatibility signature is
`create(mysqli $db, AssignmentOrderOriginalProductionConfig $c,
?AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders = null)`.
Add `createRecoveryReady(mysqli $db, AssignmentOrderOriginalProductionConfig $c,
AssignmentOrderOriginalFreshTerminalReaderFactory $freshTerminalReaders)` with
return type AssignmentOrderOriginalApplication. The former remains usable with
explicit degraded unknown recovery; the latter requires a provider for launch
wiring. A non-null provider is a dependency requirement, not proof its external
database is available; actual readiness still requires the fresh connection probe. Both retain actual safe-log acquisition before DB,
private-root validation/composition, and no provider open until unknown recovery.
Later launch readiness must reject degraded construction. Worker wiring supplies
a trusted lazy provider from already validated canonical DSN/user/password-file
inputs; it must acquire the existing actual safe-log owner before password content or
any DB call. The current worker's later safe-log construction is a known source
defect corrected by moving that same approved acquisition earlier; it does not
introduce a new native/permission testing mechanism. Invalid safe-log worker
configuration must prove password/provider/DB access0 through the approved public
worker/config boundary and fixed failure transport. Proof requires a new
server connection ID and matching configured target, including recovery when the
writer is unusable after committed acknowledgement loss.

## 11. Authorized attempt clock and recovery

Unacquired time is explicit absence. After ALLOWED→terminal NOT_FOUND, composition
NOT_FOUND or FOUND-invalid selects ORDER_NOT_FOUND/INVALID_COMPOSITION. Cleanup
unread stream once, then lazily acquire the absent canonical clock once and send
one terminal attempt. Clock unavailable/malformed gives PERSISTENCE_FAILURE,
no attempt and no repeated closure. Normal path clock stays before confirmation/
date/stream and is never called again while finishing. Exact epoch is valid data,
so authorized terminal outcomes at1970 persist normally.

COMMITTED acknowledges selected terminal. Confirmed ROLLED_BACK maps persistence
failure. Authorized attempt CONFLICT/OUTCOME_UNKNOWN/commit Throwable uses the
section10 one-shot fresh recovery, never confidential lookup before authorization.
Invalid shape, denied/unavailable authorization, terminal unavailable, composition
unavailable and authorized terminal replay acquire no speculative clock/attempt.
Denied audit policy remains unchanged and explicitly incomplete pending owner.

## 12. Exact diagnostics and verification ownership

New fresh-reader close failure emits exactly
`ORIGINAL_FRESH_READER_CLOSE_FAILED` with sole fields
`{requestCorrelation,phase}`; correlation retains the approved request hash codec,
phase is exactly `accepted_commit_recovery` or `authorized_attempt_recovery`.
Best-effort logging Throwable never changes selected read/result/cleanup. Generic
persistence/audit-only diagnostic policy remains outside this bounded addition.

Pure RED uses type-correct foreign public lookup/result/lineage objects, frozen
snapshots, exact port calls and immutable inventories. MariaDB RED uses only
approved synthetic task-owned databases/prefixes and public adapter/factory seams;
malformed rows are independently seeded only under that test ownership, with
before/after catalogs/data proving zero repair/mutation. Use this exact optional read-only observer for deterministic observation/faults:

```php
enum AssignmentOrderOriginalPersistenceEvent: string
{
    case AFTER_COMPOSITION_ORDER_READ = 'after_composition_order_read';
    case BEFORE_READ_RELEASE = 'before_read_release';
    case BEFORE_WRITE_BEGIN = 'before_write_begin';
    case BEFORE_NATIVE_COMMIT = 'before_native_commit';
    case AFTER_NATIVE_COMMIT = 'after_native_commit';
    case BEFORE_WRITE_ROLLBACK = 'before_write_rollback';
    case BEFORE_FRESH_READER_CLOSE = 'before_fresh_reader_close';
}
interface AssignmentOrderOriginalPersistenceObserver
{
    public function observe(AssignmentOrderOriginalPersistenceEvent $event): void;
}
```

AssignmentOrderOriginalMariaDbCompositionReader adds optional trailing
`?AssignmentOrderOriginalPersistenceObserver $observer = null` to its db,p
constructor; AssignmentOrderOriginalMariaDbRepository preserves db,p,existing faults then adds optional trailing
`?AssignmentOrderOriginalPersistenceObserver $observer = null`. Production default is a no-op. These are
observations of owned adapter execution, not SQL/domain write APIs. No domain
payload, credentials, SQL or exception is passed to observe().

AFTER_COMPOSITION_ORDER_READ fires once after the order SELECT has established
its snapshot and before any members SELECT. BEFORE_READ_RELEASE fires once after
select/validation and before owned read-transaction release. Observer Throwable
fails that read unavailable but still attempts release once. BEFORE_WRITE_BEGIN
fires only after complete scalar validation and no active caller transaction.
BEFORE_NATIVE_COMMIT fires immediately before commit; AFTER_NATIVE_COMMIT fires
only after native success. A Throwable after native success models loss of the
acknowledgement before the adapter can return; it yields OUTCOME_UNKNOWN, no
second commit. In a synthetic test this callback may close its independently
retained task-owned writer, so fresh recovery must succeed without it.
BEFORE_WRITE_ROLLBACK observes a required owned rollback; observer failure never
suppresses the native rollback attempt and makes rollback confirmation unavailable.
BEFORE_FRESH_READER_CLOSE observes the reader's once-only close; observer failure
still attempts native close once and reports FAILED. Lifecycle/resource release
and fixed close diagnostic retain section10/12 precedence.

A test-only mysqli subclass may count/deny its public query, escaping,
begin_transaction,commit,rollback methods to prove invalid DTO zero-DB calls,
following existing approved DB-sentinel tests. It is not runtime global/native
interception. Real rollback/commit/query cases also use actual synthetic MariaDB
and immutable before/after inventories; a fake alone cannot prove atomicity.
Native false returns are checked equivalently to Throwable; missing table with
scoped/restored mysqli report mode gives real query-false coverage. No private
method invocation, monkey patch, OS permission mutation or nondeterministic sleep
is permitted. No native/permission rejection mechanism is retried.

## 13. Complete Gate2 matrix and delivery gates

One cumulative matrix covers result lookup6combinations at request/fingerprint/
fresh stages; every result status/reason/evidence grammar/getter failure including stored
INVALID_COMMAND; lineage
complete/incomplete/ownership/membership/corruption; composition ownership/list/
identity/hash and SQL numeric/date/action rules; consistent snapshot race; every
AcceptedCommit mode/scalar/relationship invalid with zero-SQL proof; every
AttemptCommit status/reason/retry/time invalid; malformed request/revision/root/
event/audit/fingerprint backing including valid historical replay after correction;
non-denial exact audit backing and denial original-audit presence without a
cardinality choice; authoritative composition drift/NO_CHANGES with zero writes;
reference lookup closure; fresh real connection found/miss/unavailable/close/
write-unusable proof; authorized epoch/lazy-time/unknown race and all unchanged
normal/denial/replay controls. Every negative has nearby independent valid control.

Fresh independent Gate1 for this exact API/observer grammar precedes
demonstrated public/real-adapter RED→independent Gate3→minimal
GREEN→all affected original/support checks+architecture/lint→independent Gate5.
No partial component success closes the persistent launch goal.
