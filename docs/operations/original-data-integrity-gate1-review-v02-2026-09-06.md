# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.2 — independent Gate 1 rereview

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed commit: `326d1e8380e880d474210a941d1d56da50a6ea0c`.

Verdict: **CHANGES_REQUESTED**.

Reviewer authored neither the specification, parent nor OpenSpec artifacts. The
v0.1 review remains immutable. This review covers the cumulative data-integrity
package and the v0.2 corrections only.

## Exact reviewed hashes

```text
cc8c320c0c15dc566c192389f914c1bee0ad383e348f532924fd8887b8f20f7d  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
f94546b62dce67df74149759d1d056078c65cfa63732dd1272b9927afd79e6c0  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
8b965122c0e322bee02a56fdb85e696c9256ed9cb334fed140343c2db16436b7  docs/operations/original-data-integrity-gate1-review-v01-2026-09-06.md
f3f5c56d91ff2e6c72966291bc9bff314ad2ffbd1e6b27710e155db498e6650f  openspec/changes/replace-pilot-registration-with-original-upload/design.md
a6dd3d5ca0111fc72332263226fc012318655a1bb6c725be10bd75f6e5ce409b  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
33d58bb60879bcb7547ef96c89b7a6b956ab7e90dd8595abc044dc393db9a62f  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c1a03ff14ca134d39a256c0d9fa62699505632b72ad4097f9f89191362444bb6  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## v0.1 finding disposition

### Stored INVALID_COMMAND — RESOLVED

`INVALID_COMMAND` is removed from valid stored rejected reasons and from valid
AttemptCommit input. A stored row or audit with that reason is unavailable even
though the broader physical CHECK admits the literal. Current invalid command
responses remain non-persisted application Results with zero business/audit
calls.

The Gate 2 matrix explicitly includes stored INVALID_COMMAND foreign and MariaDB
negative cases. This now matches SHAPE-001 and the parent.

### Denial audit cardinality — RESOLVED without policy choice

A stored authorization-denied terminal requires its original matching audit by
safe identity and terminal attemptedAt. Additional denial-attempt rows are not
rejected and no upper cardinality is imposed. Validation and creation policy for
additional denial attempts remains deferred.

This preserves the inherited atomic first terminal+audit backing while allowing
either future repeated-denial policy. It does not create a denial writer or infer
behavior from the current uniqueness constraints.

## Remaining blocking finding

### P0 — authoritative composition drift uses a generic CONFLICT that cannot be resolved by the specified application rereads

Section 9 now correctly requires initial and correction commits to lock and
derive the authoritative legacy order composition in the owned write transaction.
It states that a valid changed composition, missing requested order/case, or a
composition no longer valid for acceptance returns repository `CONFLICT` after
confirmed rollback.

The existing application conflict protocol, retained by this package, resolves
accepted-commit `CONFLICT` through accepted-fingerprint and lineage rereads. It
does not reread authoritative composition and the repository outcome carries no
typed conflict cause.

This is not constructible for all required cases. Literal initial example:

1. Public application validates order 81 composition and prepares an initial
   accepted commit.
2. Inside the repository write transaction the authoritative order/case is now
   missing.
3. Repository returns generic `CONFLICT` after confirmed rollback as section 9
   requires.
4. Fingerprint is NOT_FOUND and assignment-order lineage is NOT_FOUND because no
   original root was created.
5. Existing initial conflict selection has no composition cause and can return
   `CONFLICT/INITIAL_ALREADY_EXISTS`, falsely claiming an existing original.

The same ambiguity exists for a valid composition drift that is not represented
by root lineage. A self-consistent incoming DTO fingerprint is correctly
insufficient, but generic CONFLICT plus fingerprint/lineage cannot prove what
changed.

Required correction: pin one exact observable protocol. Viable technical shapes
include:

1. Add a closed accepted-commit result carrying a non-disclosing cause that
   distinguishes request/lineage uniqueness conflict from authoritative
   composition changed/missing/invalid, with exact public Result mapping.
2. Require the application conflict path to perform one explicit current
   authoritative composition reread and validate it before choosing a business
   conflict, including NOT_FOUND, FOUND-invalid, UNAVAILABLE and valid-changed
   outcomes.
3. Classify missing/malformed authoritative order as confirmed ROLLED_BACK /
   persistence failure, and reserve CONFLICT only for states that existing
   fingerprint/complete-lineage rereads can resolve without guessing. If valid
   composition drift remains a business conflict, its exact resolver still must
   be specified.

Whichever shape is chosen must cover initial and correction, no-fact rollback,
unavailable/malformed backing, changed composition, disappeared order/case and a
nearby real uniqueness-conflict control. It must not disclose another order or
invent INITIAL_ALREADY_EXISTS/SEMANTIC_COLLISION.

The section 9 NO_CHANGES rule is separately coherent for correction: after
repository CONFLICT, validated current lineage date/PDF evidence can select the
existing `REJECTED/NO_CHANGES`. The required correction must preserve that exact
case.

This is a technical port/outcome clarification, not a new product decision.

## Other v0.2 corrections

### Application versus storage clocks — PASS

The once-per-invocation rule now explicitly belongs to the application
Dependencies clock. Storage receives a separate adapter clock instance and may
not consume the application clock. Production and worker composition bind
separate objects; fixed verification may assign both the same instant without
sharing call ownership. This preserves timestamp equality without accidental
multi-call application behavior.

### Commit validation and synthetic content identity — PASS

Pre-SQL validation remains specific to the real MariaDB adapter. Pure lifecycle
ports may retain their approved valid synthetic opaque lease identity; only a
fixture crossing real MariaDB persistence must use canonical
`content-sha256-<digest>`. This avoids rewriting reviewed pure evidence while
retaining the real adapter integrity rule.

Mode/scalar/event/fingerprint validation, active borrowed-transaction refusal,
confirmed rollback versus unknown acknowledgement, correction lock/CAS and
append-only persistence remain coherent apart from the composition-CONFLICT
finding.

### Complete lineage and historical results — PASS

FOUND still requires the optional complete extension with exact case/order,
ordered contiguous membership, current-last revision, current evidence and
composition binding. Negative base-only lookups preserve source compatibility.

Historical accepted request results remain tied to their own backed revision and
are not forced equal to the root's later current pointer. Request, revision,
root, event, audit and fingerprint validation remains complete.

### Fresh reader public contracts — PASS

Open result has only OPENED/non-null and UNAVAILABLE/null factories. Clone is
private and serialization/unserialization throw the exact fixed LogicException
without adopting a reader. Reader use and close remain one-shot.

Dependencies adds a nullable, non-promoted trailing constructor argument after
the existing 12 names. The readonly public factory property is initialized once
from that argument or the explicit unavailable factory. This avoids illegal
readonly reassignment while preserving old construction.

Production `create` remains explicitly degraded when the provider is absent;
`createRecoveryReady` requires it. Launch readiness still must probe the target
and reject degraded wiring. No writer-connection fallback, credential extraction,
ambient environment or mutable next-read flag is admitted.

### Safe-log-first worker correction — PASS

The worker must acquire the existing approved safe-log owner before password
content, provider or any DB call. Moving that same acquisition earlier corrects
the current source order without adding a logger, selector or new native test
mechanism. Invalid safe-log configuration has exact zero secret/provider/DB
access proof through the public worker boundary.

### Exact class and observer declarations — PASS

The specification now names
`AssignmentOrderOriginalMariaDbCompositionReader` and
`AssignmentOrderOriginalMariaDbRepository` exactly. Each receives only the
specified optional trailing persistence observer after its existing parameters.
Observer phases cover consistent snapshot, pre-SQL validation, native
commit/rollback acknowledgement and fresh-reader close without private timing,
sleeps or payload disclosure.

### Native false and unknown outcomes — PASS

Public mysqli method sentinels and real synthetic MariaDB together can prove
false/Throwable/commit/rollback behavior. Missing-table query-false coverage uses
scoped restored report mode. No private or global native interception, permission
mutation or real data is required.

### Authorized lazy clock and epoch — PASS

Explicit clock absence replaces the epoch sentinel. Authorized order-not-found
and found-invalid composition close the unread stream, acquire one validated
application instant and commit one terminal attempt. Exact epoch is valid data.
Attempt CONFLICT/OUTCOME_UNKNOWN/unconfirmed Throwable uses the fresh recovery
path. Denial remains deferred and does not acquire this clock.

## Deferred-policy check

No remaining denial cardinality choice was found after the v0.2 changes.
Additional denial audits are tolerated but neither created nor limited. The
package still does not choose maintenance, retryable audit-only, selection,
renderer, HTTP or launch behavior.

## Gate disposition

Gate 1 is **CHANGES_REQUESTED** only for the unresolved authoritative-composition
CONFLICT mapping. Do not write cumulative data-integrity RED against v0.2.

After the repository/application conflict protocol and full initial/correction
matrix are made exact, reconcile parent and OpenSpec and obtain a fresh
independent Gate 1 at exact hashes. The v0.1 blockers and other v0.2 self-audit
items do not need reopening unless their bytes change materially.
