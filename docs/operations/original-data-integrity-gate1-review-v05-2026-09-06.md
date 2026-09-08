# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.5 — independent Gate 1 rereview

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed commit: `a308a2f08311103f6fe0dcfe0ee262c5f5fd0041`.

Verdict: **CHANGES_REQUESTED**.

Only the final v0.5 bytes were reviewed. The earlier draft commit and
provisional lineage-test expectations are not authority. Reviewer authored
neither the specification, parent nor OpenSpec artifacts. All earlier review
records remain immutable.

## Exact reviewed hashes

```text
82a7f823310a0308ffed4a3d6f7fc25d481c40a6a64faece64e4e876cbcab7c2  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
ffddf5dd3d2d134ba96ec69629309ef4e32629cdeeab348b2ebba5664b07ed77  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
41c79f58093850be318027a46d4615e698fe228ca701d063bf938540b95e7410  docs/operations/original-data-integrity-target-order-clarification-review-2026-09-06.md
17f3662b7eefd0f5c3226ca4b8e7469a3db85863e666b6f119f9189567f91638  docs/operations/original-data-integrity-initial-precheck-and-diagnostic-clarification-review-2026-09-06.md
250cb72f5de051fc1abb9e0642f55bfaf75ecdf144de66b0f511e8f0076f24cb  openspec/changes/replace-pilot-registration-with-original-upload/design.md
90ca386b809455e372d9a1997cb30f24669d06fd6761007079aba4fe8889dae0  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
ff7421be9c0f9405a32c70f98ed52b6f847783343f69bd123e07965da18d1cdc  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
dee0a7e028235c2c27b58c3c2c3513b501a0f40595dc62fb452bfe40ea1a246a  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## Blocking finding

### P0 — unchanged-valid correction after generic commit conflict is not evidence of SEMANTIC_COLLISION

Section 9 now states that after correction AcceptedCommit returns generic
`CONFLICT`, the application performs fingerprint and complete current-lineage
rereads. It correctly maps a fingerprint winner to replay, changed current to
STALE_REVISION, corruption to persistence failure and excludes impossible
post-CAS foreign/absent target mutation.

It then states that an unchanged internally valid root/current after a
non-fingerprint collision selects `CONFLICT/SEMANTIC_COLLISION`.

That result is not proved by the available observations. In the specified state:

- root ID equals the command root;
- case/order and immutable composition match;
- expected/current/target are equal and target remains in the append-only list;
- requested date/PDF differs, so this is not NO_CHANGES;
- accepted fingerprint is absent.

No semantic identity mismatch exists. The repository's generic CONFLICT may have
come from a generated revision-ID collision, request/event uniqueness race or
another constraint/protocol collision. Fingerprint and current-lineage rereads do
not identify which unique key failed. Returning SEMANTIC_COLLISION fabricates a
business cause from a technical collision.

Required correction: if fingerprint is absent and the complete correction
lineage remains unchanged, matching and not NO_CHANGES, map the unresolved generic
commit conflict to retryable `FAILED/PERSISTENCE_FAILURE`. Alternatively add an
exact typed repository conflict cause or a read-only lookup that proves a
specific foreign semantic owner before returning SEMANTIC_COLLISION. Raw SQL
error-code or constraint-name guessing is insufficient unless the repository
contract pins, validates and exposes the cause.

Required tests must include:

- generic correction CONFLICT with fingerprint miss and unchanged valid lineage
  → persistence failure;
- generated revision ID already owned by a different valid root, if a semantic
  owner lookup is chosen, with exact proven mapping;
- fingerprint winner → replay;
- current changed → stale;
- unchanged current but malformed lineage → persistence failure;
- direct repository NO_CHANGES defense remains CONFLICT with zero writes, while
  normal public NO_CHANGES remains pre-finalize rejection.

This is a technical outcome-proof issue and requires no product decision.

## Clarification disposition

### Normal step-11 ordering — PASS

Normal correction current/target/no-change resolution now occurs after stream
inspection and fingerprint miss but before ID allocation and finalize. Selected
STALE, TARGET_NOT_FOUND, TARGET_NOT_CURRENT, SEMANTIC_COLLISION and NO_CHANGES
perform stage/stream cleanup, allocate/finalize/lease/accepted-commit zero, then
write only the required terminal attempt. Protocol failure performs the same
cleanup without terminal persistence.

The revision-owner query occurs only when expected=current and target is absent
from the complete current list. Its absent, foreign, same-root contradictory,
malformed and unavailable mappings remain exact.

### Initial absence precheck and race reread — PASS

After fingerprint miss, INITIAL performs one exact assignment-lineage lookup
before allocation/finalize. Complete FOUND selects INITIAL_ALREADY_EXISTS;
NOT_FOUND proceeds; unavailable/base-only/wrong-echo/malformed/missing interface
fails persistence. Non-accepted outcomes clean the stage/stream and create no
finalized content.

The post-CAS assignment-lineage reread remains separately reachable because a
new root may appear after the normal NOT_FOUND snapshot. Fingerprint winner,
exact lineage winner and final miss/unavailable/malformed outcomes are
distinguished without pretending the precheck and race read are one snapshot.

### Correction post-CAS target reachability — PASS except P0

A correction candidate reaches CAS only after proving
expected=current=target and intact immutable membership. After conflict,
fingerprint winner may replay and changed current may select stale. If current
remains expected, target disappearance/movement or immutable evidence change is
corruption and no global target-owner query is used. Tests are limited to
reachable winner/stale/unchanged/corrupt states.

This resolves the prior impossible post-CAS foreign-target fixture. Only the
unchanged-valid generic-conflict reason remains incorrect as described above.

### NO_CHANGES placement — PASS

Normal public NO_CHANGES is selected at step 11 before allocation/finalize and
does not call AcceptedCommit. The direct MariaDB repository still defensively
returns CONFLICT with confirmed zero-write rollback for a no-op correction DTO.
The contract does not force that defense to be a normal public CAS-race path.

### Fresh-reader close diagnostic — PASS

The event is now exactly
`ASSIGNMENT_ORDER_ORIGINAL_FRESH_READER_CLOSE_FAILED`. The supplied observer
safeFields contain only `phase`, whose allowed values are
`accepted_commit_recovery` and `authorized_attempt_recovery`.

The existing opened owner supplies one `correlationId` in its canonical envelope.
The literal example has the established first-12-lowerhex correlation, event,
phase-only safeFields and sequence. No duplicate requestCorrelation/raw request
field, new logger or alternate envelope is introduced. Best-effort failure cannot
change the validated result, retry close or alter lease cleanup.

## Cumulative preservation

All other v0.4 conclusions remain unchanged: stored-result closure,
INVALID_COMMAND exclusion, denial cardinality deferral, complete lineage and
historical replay, authoritative composition rollback, consistent MariaDB reads,
pre-SQL DTO validation, fresh-connection recovery, degraded versus ready factory
composition, separate clocks, worker safe-log-first order and public observer
ownership.

Parent v70 and all four OpenSpec artifacts link the same step-11, race and
diagnostic corrections. Preliminary author diagnostics are explicitly excluded
from authoritative RED. No product policy, schema, capability, maintenance,
selection or launch behavior is added.

## Gate disposition

Gate 1 is **CHANGES_REQUESTED** only for the unproved post-CAS
SEMANTIC_COLLISION mapping. Do not capture cumulative lineage/data-integrity RED
against v0.5 until that branch has an exact evidence-backed outcome and a fresh
independent Gate 1 review. Unchanged result/composition/scalar test authoring is
unaffected.
