# Selection audit and exhaustion candidate

Date: 2026-09-05. Technical DRAFT, not Gate1 approval. Companion to the
storage and typed-result/replay candidates. This candidate must be reconciled
into the complete executable contract before tests or production edits.

## Exhaustion disposition

Add one reason enum case `ALLOCATION_CAPACITY_EXHAUSTED` with backing string
`allocation_capacity_exhausted`. It covers either registry ID space beyond
PHP_INT_MAX=9223372036854775807, per-case order_version beyond65535, or
selection_revision beyond4294967295. It is `failed`, retryable=false, with
all successful identity fields null and the current requestId echoed.

This explicitly supersedes the earlier draft's all-failed→retryable=true rule.
Other failed reasons retain retryable=true. Capacity exhaustion is not terminal
cached: a separately approved future capacity migration may allow retry of the
same request. No wrap, negative cast, reused ID, hidden version skip or fake
successful fact is permitted. Database outages/lock timeouts are not exhaustion.
Only independently proven frontier arithmetic/typed allocator result may select
this reason; ambiguous SQL failure remains persistence/dependency failure.

Rollback occurs before returning exhaustion. No new registry/source/member,
terminal request, success event or accepted audit survives. DB AUTO_INCREMENT
may retain its documented consumed gap; such a gap is not a domain identity and
must not be reused. Failure before allocation must not advance the allocator.
The exact allocation sensitivity includes boundary65535 vs65536, signed64
maximum vsmaximum+1 inspected as decimal without lossy conversion, and selection
revision maximum vsmaximum+1. No extreme value enters a PHP int before bounds
are proven. Worked fixtures are synthetic and isolated.

## Audit ownership and replay precedence

Keep terminal request facts and attempt audits distinct. Terminal requestId is
unique, immutable and belongs to its stored canonical intent. Attempt audits
have independent generated auditId; requestId is a nonunique correlation field.
A denial or different-intent collision must never insert/update/delete a terminal
row over an already accepted request. Authorization precedes confidential lookup.

The audit safe fields are exactly auditId, requestId, actorUserId,
installationObjectId, mode, status, reasonCode, attemptedAt. They contain validated
caller identities and UTC time only: no resolved case/order/composition/member
identities, filenames, content, credential, stored result or raw exception.
Mode/status/reason are canonical lowercase enum values; requestId canonical UUID;
actor/object are validated positive integers. Success event carries the separate
approved immutable selection identity facts; audit payload does not duplicate them.

Audit status values are `selected`, `rejected`, `conflict`. The phrase denied
audit means status=`rejected`, reasonCode=`authorization_denied`; accepted audit
means status=`selected`, reasonCode=null. Collision means status=`conflict`,
reasonCode=`request_id_conflict`. No new `denied`/`accepted` status is added.

Exact outcome matrix:

| Invocation | Terminal request | Attempt audit | Selection event |
| --- | --- | --- | --- |
| Invalid command shape | none/read none | none; no unvalidated input persisted | none |
| Authorization denied | no lookup or mutation | one denied audit for this invocation, even if requestId already exists | none |
| Authorized matching accepted request | original unchanged | none | none |
| Authorized matching rejected/conflict request | original unchanged | none | none |
| Authorized different tuple under existing requestId | original unchanged | one request_id_conflict audit for this invocation | none |
| Fresh authorized business rejection/conflict | immutable terminal result | one matching audit atomically | none |
| Fresh accepted selection | immutable terminal success | one accepted audit atomically | one selected event atomically |
| Technical failure/exhaustion | no new terminal cache | no business-outcome audit claimed | no success event |

“one” refers to a completed invocation, not deduplication across denied attempts.
A separate denied invocation is a separate audit fact. Same-request accepted
replay remains silent. The denied path never calls confidential terminal lookup,
so an unavailable accepted-request store cannot itself authorize disclosure.
Failure of audit persistence gives failed/persistence_failure on confirmed
rollback, or failed/persistence_outcome_unknown if its commit is uncertain;
it must not falsely claim an audited business outcome. An ambiguous audit commit
may leave one row from that attempt, and a later retry is a new audited attempt;
there is no blind retry inside the invocation or claim of exactly-once network
delivery. Audit failures disclose no accepted request data.

Authorized terminal-rejection and accepted commits retain transactional atomicity:
request, their audit, and (for selected) registry/source/member/event facts commit
together or roll back together. After uncertain commit, fresh same-request lookup
returns the persisted terminal result, confirmed absence or unavailable according
to the companion replay contract. No audit-only retry may manufacture selection.

## Fixed authorization/replay worked sequence

Given request `00000000-0000-4000-8000-000000000401`, actor18, object4512 and the
fixed selection intent from the replay candidate:

1. Allowed fresh selection: one accepted request, audit and event.
2. Owner revokes required authority through the approved fixture seam.
3. Same request from actor18: denied result, one new denied audit, no terminal
   lookup, all accepted identities/rows/event unchanged and undisclosed.
4. Repeat denied invocation: a second denied audit; accepted facts unchanged.
5. Restore authority through the fixture seam; same request: replayed original
   success, no additional request/audit/event/selection.

Final counts: terminal requests1, selection events1, audit rows3. Independent
public evidence compares the actual facts; expected counts are fixed here,
not obtained by reading current implementation output. Rejection/collision,
audit rollback/unknown and reauthorization branches require their own deterministic
public-port evidence. No private SQL result fabrication is an acceptance action.
