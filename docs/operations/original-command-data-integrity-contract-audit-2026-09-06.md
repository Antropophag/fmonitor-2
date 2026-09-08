# Original command cumulative data-integrity contract audit

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Repository HEAD: `bca89b4853a7106fac3194d3724f27cba38b3b2f`.
Scope: read-only audit of the last unexamined command boundary. No source, test,
specification, database, native resource, primary evidence or remote system was
changed or probed.

## Determination

The active parent already supplies the needed outcomes; no product decision is
missing. A cumulative correction should close three related trust boundaries in
one Gate 1/RED package:

1. application validation of passive public lookup/snapshot values;
2. MariaDB rehydration validation before returning FOUND values;
3. MariaDB commit DTO validation before any transaction or SQL mutation.

Treating only thrown adapters as unavailable is insufficient. Public port DTOs
can be type-correct and semantically impossible, and database rows can satisfy
individual SQL column constraints while violating the cross-table/result rules.

## Exact inherited outcome taxonomy

- A malformed repository lookup/result/lineage DTO is an adapter protocol or
  persistence-integrity failure: application result is retryable
  `FAILED/PERSISTENCE_FAILURE`, with normal stream/stage/lease cleanup for the
  point reached and no new acknowledged fact.
- A repository query or rehydration failure returns typed `UNAVAILABLE`; it is
  not `NOT_FOUND` and maps to the same persistence failure.
- A production composition row set that is absent or belongs to another case is
  `NOT_FOUND` and maps to nonretryable `REJECTED/ORDER_NOT_FOUND`.
- A structurally found order whose version/engineer/member/date composition is
  invalid remains `FOUND` with an invalid snapshot and maps to nonretryable
  `REJECTED/INVALID_COMPOSITION`.
- A composition query/snapshot failure is `UNAVAILABLE` and maps to retryable
  `FAILED/PERSISTENCE_FAILURE`.
- Invalid `AcceptedCommit` or `AttemptCommit` supplied to the repository must be
  rejected before mutation as `ROLLED_BACK`/unconfirmed persistence at the port;
  through the application this maps to `FAILED/PERSISTENCE_FAILURE`. No partial
  root/revision/request/event/audit write is allowed.

These outcomes restate v61 sections 8, 11 and the explicit repository-validation
paragraph around section 15. They do not add a user-visible reason.

## A. Passive result lookup validation

`AssignmentOrderOriginalResultLookupValue` has a public constructor and permits
all six status/payload combinations. The service currently validates only
`FOUND && result !== null` on the outer terminal lookup. Consequently:

- `FOUND/null` falls through as if the request were absent and may read/upload/
  mutate;
- `NOT_FOUND/result` and `UNAVAILABLE/result` silently ignore contradictory data;
- a FOUND foreign `AssignmentOrderOriginalResult` is replayed without validating
  its status/reason/retry/evidence tuple;
- terminal result `requestId` is not required to equal the requested ID; the
  service overwrites it with the caller ID in its returned value.

The post-stream fingerprint lookup has the same closed-combination requirement.
`FOUND` there may legitimately contain another request ID because cross-request
fingerprint replay returns winner evidence under the current caller request, but
the stored winner itself must still be a valid accepted/replayed evidence tuple.
`FOUND/null`, terminal rejected/conflict under an accepted fingerprint, or any
invalid evidence tuple must become persistence failure, not miss, conflict or
accepted replay.

Required application checklist:

- exactly `FOUND/non-null`, `NOT_FOUND/null`, `UNAVAILABLE/null` are admissible;
- outer terminal FOUND requires stored request ID equal to lookup request;
- accepted terminal hit becomes replayed; stored rejected/conflict retains exact
  status/reason; stored replayed/failed is accepted only where the parent storage
  contract explicitly allows it (current terminal table stores selected terminal
  accepted/rejected/conflict, not technical failure);
- validate exact reason/status/retryability and all-seven-evidence nullability;
- validate UUID, opaque IDs, revision number, date, lower-hex hash, positive byte
  size and canonical UTC-second time before using a stored result;
- fingerprint FOUND requires a valid accepted evidence result and does not expose
  its stored request ID as the current response request ID;
- any getter Throwable or impossible combination is persistence failure with no
  blind retry/mutation.

Public-seam RED should use type-correct foreign lookup/result implementations for
each impossible combination. A separate direct MariaDB rehydration test should
seed malformed rows only in a task-owned database with constraints disabled where
necessary and require typed UNAVAILABLE/zero repair.

## B. Lineage lookup validation

The lineage interface also permits contradictory status/getter combinations.
Service branches currently compare nullable getters ad hoc. A FOUND lineage with
null/malformed root/current revision/revision number/composition/hash or an
inconsistent `containsRevision` response can be converted into business
`SEMANTIC_COLLISION`, `STALE_REVISION`, `TARGET_NOT_FOUND` or even accepted
correction rather than persistence failure.

The MariaDB lineage implementation casts `current_revision_number` to int and
does not validate complete row grammar or cross-field ownership. Both lineage
queries use `LIMIT 1`, so duplicate/corrupt roots are selected by precedence
rather than classified unavailable.

Required checklist:

- enforce the same closed status rule: FOUND has one complete valid lineage;
  NOT_FOUND/UNAVAILABLE expose no lineage/evidence values;
- validate root/current/all revision opaque IDs, positive bounded revision,
  composition identity/hash, current document date/PDF hash, and exact membership
  of revision IDs before conflict precedence;
- require requested root equality for root lookup and exact case/order ownership
  for assignment lookup;
- duplicates, malformed rows, root/current-revision mismatch and current revision
  absent from the lineage set return UNAVAILABLE;
- only a validated NOT_FOUND becomes `TARGET_NOT_FOUND`/initial absence according
  to the existing precedence; malformed FOUND never becomes a business conflict.

## C. Composition snapshot and production reader

The public `AssignmentOrderCompositionSnapshot` is passive. Service currently
checks only nonempty identity, lower-hex hash, nonempty/unique/positive installers
and positive engineer. It does not validate:

- the status/payload combination;
- snapshot case/order equality with the command;
- strict numeric ordering/list shape of installers;
- identity equality to `composition-<orderId>-v<positive version>`;
- recomputation of canonical composition JSON/hash;
- PHP-int upper bounds or exact integer provenance after adapter casts.

Application validation should treat impossible status/payload combinations as
dependency/persistence failure. A complete FOUND snapshot with correct identity
ownership but invalid composition content maps to `INVALID_COMPOSITION`, as the
parent explicitly requires. A wrong case/order must never disclose or accept
another order; production reader returns NOT_FOUND for case mismatch.

The current MariaDB reader performs two independent autocommit SELECTs, rather
than the required one consistent read-only snapshot. It also:

- casts IDs/version/engineer without lossless decimal/range validation;
- does not validate positive version before building the identity;
- does not validate `order_date`, `valid_from` or `valid_to` as real ISO calendar
  dates, relying on lexical comparison;
- uses a physical query constrained by order ID but does not independently prove
  returned row ID/source column fidelity before projection;
- returns FOUND/null fields for many malformed rows but can construct a plausible
  identity/hash for malformed version/date values;
- does not explicitly detect multiple/cross-source ownership (the later registry
  reader handoff remains a separate compatibility contract).

Required adapter checklist:

- start one consistent read-only transaction/snapshot; read exact order then all
  members numeric-ascending; release it on every outcome;
- validate lossless positive bounded order/case/version/engineer/installer IDs;
- require exact requested order ID and case; another case is NOT_FOUND without
  member probing;
- validate real Gregorian order/member dates and all action/coverage rules;
- validate full-row unique installer ownership across assign/retain/release;
- return FOUND invalid snapshot for found-but-invalid composition, NOT_FOUND only
  for genuine absence/case mismatch, and UNAVAILABLE for query/snapshot/protocol
  failure;
- application independently recomputes/validates the canonical identity/hash so
  a custom verification adapter cannot bypass the same public boundary.

## D. Stored result rehydration in MariaDB repository

`AssignmentOrderOriginalMariaDbRepository::result()` currently applies enum
`from`, Boolean/int casts and timestamp string rewriting, then returns a result.
Enum failure is caught by the caller and becomes UNAVAILABLE, but every other
cross-field invariant is unchecked. Concrete gaps include:

- canonical UUID/request identity;
- exact stored status/reason/retryable matrix;
- all evidence non-null for accepted and all evidence null for rejected/conflict;
- valid opaque root/revision IDs and revision bounds;
- valid calendar date, lower-hex SHA-256, positive byte size and exact UTC second;
- consistency between the terminal request row and its committed revision/root/
  event evidence;
- request lookup row identity equal to the queried request;
- fingerprint lookup row actually backed by the exact queried fingerprint.

Rehydration must validate the complete stored representation before constructing
FOUND. Malformed or contradictory data returns typed UNAVAILABLE with no repair,
fallback or partial result. Accepted evidence must be represented by the committed
root/revision/event facts required by the parent, not only by a request row.

## E. `commitAccepted` DTO validation before SQL

The current repository begins a transaction and interpolates every public DTO
field directly into INSERT/UPDATE statements. It performs no explicit validation
of the normative relationships. Database keys/checks cover only part of them.

Validate before `begin_transaction()`:

- canonical request UUID; positive bounded case/order/actor/size/revision;
- operation fingerprint/composition/PDF hashes exact lowercase 64-hex;
- root/new/previous/expected/content IDs use their approved grammars;
- real document date and canonical UTC-second upload time;
- INITIAL: revision 1, previous/expected/correction null, exact event
  `assignment_order_original_accepted`;
- CORRECTION: revision `n+1` relative to the validated current root, previous and
  expected-current relationships, nonempty normalized correction reason, exact
  event `assignment_order_original_corrected`;
- composition identity/hash match the immutable order composition used by the
  command;
- private content identity/digest/size match the lease-derived content admitted
  by the application;
- selected Result derivable from precisely these commit fields.

For correction, relationship validation that needs current DB state belongs
inside the owned transaction with an exact locked/CAS predicate; scalar/grammar/
mode-event checks still occur before mutation. Invalid DTO or inconsistent state
returns ROLLED_BACK/CONFLICT only according to the existing typed distinction and
writes no partial row. SQL duplicate code alone must not classify every unique
fault as the same semantic conflict without the required rereads.

## F. `commitAttempt` DTO validation before SQL

Current code accepts any enum-compatible attempt and hardcodes retryable `0` in
the request row without validating the DTO. It must require:

- canonical request UUID and positive bounded actor/case/order;
- canonical UTC-second attemptedAt;
- status only REJECTED or CONFLICT;
- retryable exactly false;
- reason allowed for that exact status by the parent matrix;
- no accepted evidence/domain event fields (the DTO carries none);
- request/audit rows derived from the same exact values.

Invalid attempt DTO returns ROLLED_BACK before transaction/SQL. A valid audit
commit remains one atomic request+audit write. Unknown/failed commit handling
retains existing outcome rules; validation must not invent a terminal fact.

## G. Reference lookup and total-port closure

`hasCommittedContent()` casts COUNT and returns FOUND/Boolean without verifying
the scalar result shape. Require exactly one canonical nonnegative count row;
malformed/multiple/no row is UNAVAILABLE. `FOUND` must always carry Boolean and
NOT_FOUND/UNAVAILABLE must carry null at the public reference lookup seam.

Every repository/composition/lookup method must contain adapter Throwable and
return its typed unavailable/failure outcome. The application must also protect
against Throwable from any custom public port getter/method, preserving the
cleanup appropriate to the point reached and never exposing exception text.

## One cumulative executable package

Avoid another sequence of single-branch reviews. The next contract/test package
should contain these independently fixed groups:

1. outer terminal lookup: all six status/payload combinations, malformed stored
   status/reason/evidence and wrong request ID;
2. post-stream fingerprint lookup: all six combinations, non-accepted FOUND and
   malformed accepted evidence, with exact abort/close/no-ID/no-finalize effects;
3. lineage lookup: all status/value contradictions, malformed IDs/revision/hash/
   current evidence, duplicate/current-not-in-set and getter Throwable;
4. composition: wrong echoed case/order, unordered/duplicate/out-of-range IDs,
   malformed identity/hash and recomputation mismatch, plus NOT_FOUND versus
   FOUND-invalid versus UNAVAILABLE controls;
5. MariaDB reader consistent-snapshot race and malformed numeric/date/action/
   ownership rows in task-owned DB;
6. direct repository accepted-commit validation for every mode relationship and
   scalar grammar, proving zero mutation on invalid DTO;
7. direct attempt-commit validation for status/reason/retry/time/identity, proving
   zero mutation;
8. malformed stored request/root/revision/event/fingerprint rehydration returning
   UNAVAILABLE with no repair;
9. reference lookup impossible shapes and query failure;
10. valid initial, correction, rejection, conflict, replay and content-reference
    controls so reject-all/unavailable-all implementations cannot pass.

Use the same public application and repository interfaces. Direct repository
tests are justified only for the explicitly declared adapter-validation MUSTs;
they do not become an alternate domain write seam. Expected rows/results must be
literal parent values and before/after catalogs/evidence must prove no mutation.

## Separate scopes retained

This package must not absorb lifecycle/storage event completeness, response-loss
clarification, safe-log ownership/isolation, declaration parity, selection
registry-reader handoff, maintenance redesign or HTTP behavior. Those already
have separate owners/gates. The data-integrity correction may reference their
types but cannot decide their behavior.

## Exact reviewed hashes

```text
d7113dcdf79915751f266f8026c5415fab8ec3a9878cd8c51ce43ddb1c7ab6a8  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
a7767a8bb6ae53baa87bdcc04f598f8b399a0411b0faff80e5ddee27c0665647  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
```

These are contract/readiness findings, not a production RED or implementation
approval. Each executable correction still requires approved Gate 1, demonstrated
RED, independent Gate 3, minimal GREEN and independent Gate 5.
