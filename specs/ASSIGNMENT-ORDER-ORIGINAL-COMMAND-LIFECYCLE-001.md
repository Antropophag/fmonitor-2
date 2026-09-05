# ASSIGNMENT-ORDER-ORIGINAL-COMMAND-LIFECYCLE-001 — one resource lifecycle

Версия0.1, 2026-09-06. **DRAFT / INDEPENDENT GATE1 REQUIRED**.

## 1. Authority and public boundary

Existing public `submitAssignmentOrderOriginal` retains command/result types,
permissions, immutable evidence, accepted-fingerprint semantics and CAS rules.
This technical amendment makes exact parent sections3/5/10/ports/transcript
constructible across every outcome. Inputs: independent observer/acquisition
contract audits dated2026-09-06. No new product policy, route, data source or
maintenance operation is introduced.

The application owns closure of the supplied stream from invocation admission,
including invalid command, denied actor and terminal-request replay. Read begins
only after approved scalar/auth/request/composition/clock/confirmation/date and
correction-preflight gates. Every stream/stage close primitive is attempted at
most once; no post-commit stage/stream operation is permitted.

One application resource owner tracks actual acquired stage, attempted cleanup
operations and a returned lease. Observer callbacks report the real operations;
no second mutation path, synthetic BEGIN/DONE, worker-local fabricated phase or
config selector is allowed. Production observers remain final inert objects.

## 2. Exact pre-stream and replay order

After shape and authorization: one terminal-request lookup. On hit, copy the
validated stored result under existing request echo/replay rules, close the
supplied unread stream once, then return. No composition/clock/ID/inspector,
stream read, storage/lifecycle event, commit/audit or delivery call. A close
Throwable cannot replace the selected stored outcome and invokes the existing
single `STREAM_CLOSE_FAILED` safe diagnostic; logging failure remains isolated.
The former two-operation retry transcript is clarified to include this closure.

On miss, exact composition and the single validated UTC clock precede confirmation/
date gates and existing correction preflight. Then lifecycle
`AFTER_REQUEST_MISS_BEFORE_STREAM` is attempted exactly once immediately before
stage acquisition. Before that point failures/rejections emit no lifecycle event.
The empty-fingerprint availability probe is removed: parent execution order has
its sole semantic fingerprint lookup only after complete PDF inspection. A prior
probe cannot establish later availability. Existing tests which asserted the
extra empty probe require separately reviewed expectation amendment; this does
not weaken the real post-stream unavailable sensor.

Post-stream fingerprint FOUND returns selected REPLAYED only after stage abort,
stage close and stream close are each attempted once in order with the same
storage events and primitive-failure diagnostics used for a rejected command.
First cleanup failure cannot skip later cleanup or replace replay. No ID/finalize/
lease/commit/audit/delivery work follows this replay. NOT_FOUND permits exactly
one `AFTER_FINGERPRINT_MISS_BEFORE_CAS`; UNAVAILABLE permits none of the later
fingerprint-miss/finalize/committed lifecycle events.

## 3. Stream and stage total outcomes

Each read uses maximumBytes65536. BYTES requires1..65536 actual bytes, EOF/FAILED
requires empty bytes. Malformed combinations, empty BYTES, oversized returned
chunk or read Throwable are STREAM_FAILURE, retryable. They cannot cause an
unbounded loop, discarded nonempty EOF payload, inspector call or private finalize.
The existing total received-size limit20MiB and boundary rejection remain unchanged.

| Operation failure | Selected pre-commit outcome |
| --- | --- |
| beginStage Throwable | FAILED/STORAGE_FAILURE, stream close1; no stage obtained |
| read FAILED/Throwable/malformed tuple | FAILED/STREAM_FAILURE, acquired-stage cleanup |
| stage write non-OK/Throwable | FAILED/STORAGE_FAILURE, acquired-stage cleanup |
| completedBytesForInspection Throwable | FAILED/STORAGE_FAILURE, cleanup, inspector0 |
| inspector Throwable/INSPECTOR_FAILED | FAILED/STORAGE_FAILURE, cleanup |
| finalize Throwable/non-success/malformed lease/content | FAILED/STORAGE_FAILURE, cleanup and release any actually returned lease |
| stage close Throwable on otherwise accepted candidate | FAILED/STORAGE_FAILURE, no accepted commit |
| stream close Throwable on otherwise accepted candidate | FAILED/STREAM_FAILURE, no accepted commit |

These mappings depend on the failing port, never concrete WorkerFaults type or
its target. Invalid PDF, not-PDF, size and existing business rejection mappings
remain unchanged. Cleanup failures on an already selected rejected/conflict/
replayed/failure result preserve it and attempt the existing safe diagnostic once.

## 4. Finalize validation and ownership

Capture finalize outcome, its status and returned lease once; do not ask a mutable
port to return the lease or content a second time. Any returned lease object is
owned for one release attempt even if status/content validation later fails.
No artificial raw handle adoption is introduced; this is the existing typed lease.

Success requires outcome OK or ALREADY_PRESENT_VERIFIED; lease non-null with
status OK; one non-null content; opaque identity valid under the existing1..80
ASCII/no-control/no-slash/backslash storage grammar; exact lowercase SHA256 equal
to acquired-byte digest; exact positive byteSize equal to acquired byte count.
Failure/non-success with a non-null lease is malformed but still requires release.
Any getter Throwable maps storage failure while retaining any lease already
returned. If an adapter throws before returning a lease object, that adapter owns
its unreturned resources; application cannot invent an inaccessible lease.

For success, stage close then stream close occur once while the lease is held;
only their success yields an accepted candidate. The existing content object is
opaque: verification fixtures may use a valid synthetic identity, production
FileStorage still guarantees exact content-sha256 identity and bytes.

After finalize failure or validation failure, abort/close/read ownership rules
apply; returned lease is released once with phase `rolled_back` after cleanup.
This phase means no accepted commit was performed/confirmed; it does not claim a
SQL rollback happened when no transaction was started. Content is never deleted
by the command. Failed release remains storage/recovery-owned as in parent.

If an accepted-candidate close itself fails, never repeat that close. Attempt
abort of only the owned stage (never finalized content), then any remaining
unattempted close, and release the lease. Ordering records actual attempts:
stage-close failure → abort → stream close → release; stream-close failure after
successful stage close → abort → release. This exceptional unwind cannot be
rewritten as a second normal abort/close/close sequence.

## 5. Exact storage events

| Event | Causal point | opaqueIdentity argument |
| --- | --- | --- |
| STAGE_BEGIN | immediately before beginStage primitive | null |
| STAGE_WRITE | after each confirmed successful write | null |
| STAGE_DONE | after EOF and successful writes, before completed bytes/inspection | null |
| FINALIZE_BEGIN | immediately before finalize primitive | null |
| FINALIZE_DONE | after validated successful finalize/lease/content | content opaque identity |
| ABORT_BEGIN | immediately before each owned stage abort attempt | null |
| ABORT_DONE | only after abort status OK | null |
| STAGE_CLOSE | immediately before the sole stage close attempt | null |

No stage identity is fabricated: existing stage port exposes no opaque-ID getter.
A reached BEGIN boundary may be observed even when its callback itself throws:
the primitive is then not started and no DONE is emitted. Otherwise no event
for an unreached operation and no DONE after failure. Maintenance
DIGEST_LOCK/DELETE phases are outside this command and remain separate contracts.

Storage-observer Throwable during acquisition/normal candidate completion maps
STORAGE_FAILURE and triggers required resource unwind. Cleanup-associated
ABORT_BEGIN/ABORT_DONE/STAGE_CLOSE callbacks never prevent the corresponding
cleanup primitive, including STAGE_CLOSE on an accepted candidate. Such a close
callback failure still attempts the primitive once, cancels candidate acceptance
with STORAGE_FAILURE, and unwinds remaining resources without a second callback
or close attempt for that stage. During cleanup of an
already selected non-accepted outcome it cannot skip primitives or later event
attempts, repeat operations, or replace that outcome. No false primitive-failure
safe log is emitted for an observer-only error. This is the precise cleanup
exception to generic port error mapping; existing actual primitive errors still
produce their approved diagnostics.

## 6. Lease through commit and lifecycle errors

`AFTER_PRIVATE_FINALIZE_BEFORE_COMMIT` occurs once after successful stage/stream
closes, while the validated lease remains held, before commit. Pre-commit lifecycle
Throwable maps PERSISTENCE_FAILURE. It unwinds only resources still owned: before
finalize, stage/stream cleanup; after their closes, lease release only, with no
repeated abort/close. No accepted commit, attempt audit or delivery is invented.

Typed commit outcomes retain parent selection/recovery semantics. A generic
commit Throwable cannot prove rollback: treat it as OUTCOME_UNKNOWN and perform
exactly one fresh terminal-request recovery read through the repository contract.
FOUND/NOT_FOUND/UNAVAILABLE select existing accepted/persistence failure/unknown
outcomes. Actual fresh-connection construction and stored-data validation belong
to the separate data-integrity correction; they are mandatory for combined Gate5,
not waived by pure application-port tests in this component.

After typed CONFLICT, reread failures still release once after selecting the
existing failure outcome. After any post-lease repository/getter Throwable,
release must occur exactly once with the phase matching the selected outcome;
no outer catch may bypass it. No blind commit retry or second allocation.

After known COMMITTED or fresh recovery FOUND carrying ACCEPTED: lease release
attempt → lifecycle AFTER_COMMIT_BEFORE_RETURN → delivery observer → return the
selected accepted result. CAS/terminal/fingerprint REPLAYED emits no post-commit
lifecycle or delivery because this invocation created no accepted commit.
Release diagnostic failure cannot suppress the later lifecycle/delivery.

## 7. Post-commit response loss

Post-commit lifecycle or delivery Throwable represents response loss, not a
no-fact FAILED Result. Public fixed exception:

```php
final class AssignmentOrderOriginalResponseDeliveryLost extends \RuntimeException
{
    public function __construct() { parent::__construct('Assignment order original response delivery lost.', 0, null); }
}
```

No Result is returned for that invocation; durable facts remain. The exception
contains no underlying previous exception, request/payload/path or secret.
No stage/stream/lease/commit/observer operation is repeated. A failed post-commit
lifecycle means delivery was not reached; a failed delivery is attempted once.
A following same-request invocation uses ordinary authorized terminal replay and
unread-stream closure. Worker existing outer failure boundary returns exit70/
fixed worker stderr without a result, preserving persisted evidence.

This is the explicit response-boundary exception to total business Result mapping,
matching parent section10 “caller did not receive result”. Production observers
are inert; it is not a new runtime fault selector or user policy.

## 8. Required public evidence

Fixed ExampleA327 bytes/hash/clock/IDs unchanged. Pure fixtures expose one ordered
trace across source ports, lifecycle/storage callbacks, primitive calls, logs,
commit, lease release and delivery. Expected traces are literals from this contract,
not captured from a nonthrowing implementation.

Mandatory controls/negatives: accepted initial; canonical invalid-PDF abort OK/
FAILED/Throwable; begin/read/write/completedBytes/inspector/finalize Throwable;
malformed read tuples; successful and malformed finalize/lease/content; both
accepted-candidate closes; cleanup primitive/observer/log combined failure;
terminal replay closure and post-stream replay cleanup; precommit lifecycle throw
before/after finalize; typed commit outcomes and generic commit throw resolved by
one recovery lookup; conflict reread failure; known/fresh accepted post-commit
lifecycle/delivery response loss followed by same-request replay. Each row asserts
full Result or fixed exception, every resource call count, no unintended audit/
accepted facts, and preservation of pre-existing evidence.

Real worker/regression tests remain required for lease/storage persistence and
response-loss retry; pure callback tests cannot claim physical FD or fresh DB
connection proof. Existing assertion changes for the empty-probe removal require
an exact reviewed patch with demonstrated fresh failure evidence before application.

Gate1 → RED → independent Gate3 → minimal resource-owner/phase GREEN → relevant
regressions/architecture → independent Gate5. No integration/launch claim until
data-integrity, public API/storage-maintenance and all combined gates also pass.
