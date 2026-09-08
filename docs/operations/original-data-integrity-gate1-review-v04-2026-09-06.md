# ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 v0.4 — independent Gate 1 rereview

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed commit: `110b05489091b4fb062f6786c77c078e9b0ec42e`.

Verdict: **APPROVED**.

Reviewer authored neither the specification, parent nor OpenSpec artifacts. All
earlier reviews remain immutable. Result and composition test drafting under
unchanged v0.3 sections was not reviewed here. This review covers the complete
v0.4 lineage correction and cumulative Gate 1 readiness.

## Exact reviewed hashes

```text
e232bbdfb01c1671ff57e6f353c5b5b1b77c2b60680627b9be37299a2ff27e29  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
5274f339b6c73890438297135b739319d30bf787c781c89db7135079df616969  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
ffbd47d4a074fca6a47310e861156a92b09db735cfa55e838ec88adb7923d390  docs/operations/original-data-integrity-lineage-ownership-clarification-review-2026-09-06.md
66a62e402eb05f44390a96cbfa983da811920af9564d68f525c3258048112a03  docs/operations/original-data-integrity-gate1-review-v03-2026-09-06.md
a23944cf5a610aa0db0ee0e54f8e6559d0deced631cc47cbd83753dc12a076da  openspec/changes/replace-pilot-registration-with-original-upload/design.md
773abfa5ebec2c4670495ba55a168d1196fe8c597942a347ee172fe280857549  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
a9a88f60a0b785197f58ea27b40d75e66ccbc33865c80ec1da523bbc5f8f1fe8  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
28a30bacc812c4b1c9c5e321a012dab512decd96b319b1c4c0704897060fa855  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
```

## Ownership clarification

### Common lineage integrity — PASS

Every FOUND lineage first requires a complete internally coherent snapshot:
valid root/current/member identities, positive case/order, contiguous unique
ordered revisions, current as the final member, current date/PDF evidence,
`containsRevision` agreement and composition identity bound to the returned
order. Base-only or malformed FOUND remains persistence failure.

This common validation does not prematurely compare root-query case/order with
the command. It therefore preserves the distinction between protocol corruption
and valid evidence of foreign ownership.

### Root query mapping — PASS

For `findLineage(rootId)`, returned root must equal the exact sole query argument.
A different returned root is protocol failure. Once exact-root and common
integrity pass, a different positive case/order or immutable composition is valid
foreign ownership and selects pre-stream
`CONFLICT/SEMANTIC_COLLISION`, as required by the parent.

Valid root NOT_FOUND also selects semantic collision. UNAVAILABLE, contradictory
negative metadata or incomplete/malformed FOUND maps to retryable persistence
failure. NOT_FOUND is not confused with TARGET_NOT_FOUND, which is evaluated only
after a valid matching lineage exists.

### Assignment-order query mapping — PASS

For `findLineageForAssignmentOrder(caseId, orderId)`, both case and order are
query keys. A FOUND echo mismatch is therefore protocol failure. Only a complete
valid root owned by the exact pair may select INITIAL_ALREADY_EXISTS. NOT_FOUND,
UNAVAILABLE, missing required extension or malformed backing maps to persistence
failure and cannot invent an existing root.

This remains consistent with the v0.3 initial generic-CONFLICT resolver:
fingerprint FOUND wins as replay; only the exact assignment lineage can prove the
business conflict.

## Revision-owner lookup

### Public extension and source compatibility — PASS

The new optional read-only interface adds exactly
`findLineageForRevision(string revisionId): AssignmentOrderOriginalLineageLookup`
without changing the base repository interface. Implementations that do not need
the query remain source-compatible. On the required target-miss path, absence of
the optional interface is explicitly persistence failure rather than fabricated
absence.

The returned FOUND value is subject to the same CompleteLineage and current-
evidence validation. It must contain the exact queried revision in its immutable
ordered member list. No caller data is used to synthesize root ownership.

### Exact precedence — PASS

The target-owner query occurs only after:

1. complete current-root validation;
2. expected-current equality;
3. target absence from that current root's complete member list.

No target query runs for stale expected-current, fingerprint replay, a target
already in the list or any earlier protocol failure. If the target is in the
current list, noncurrent target selects TARGET_NOT_CURRENT and current target
continues to no-change/acceptance checks.

On an absent target:

- valid revision-owner NOT_FOUND selects TARGET_NOT_FOUND;
- complete valid FOUND containing the target under a different root selects
  SEMANTIC_COLLISION;
- FOUND under the same root is contradictory to the already complete current
  list and maps to persistence failure;
- FOUND not containing the query revision, base-only FOUND, malformed metadata,
  UNAVAILABLE or Throwable maps to persistence failure.

The same target helper and ordering are mandatory in normal correction preflight
and post-CAS conflict reclassification. This prevents the post-CAS path from
using an unrelated latest root or losing the foreign-target distinction.

### MariaDB constructibility — PASS

The production repository starts from the exact revision ID, returns NOT_FOUND
only for genuine absence, and otherwise resolves and validates the full owning
root and chain in one owned consistent read-only snapshot. A dangling revision or
missing root is UNAVAILABLE rather than hidden by an inner join. All roots and
ordered revisions are selected explicitly; LIMIT 1 cannot hide duplicate or
corrupt ownership.

The existing schema has globally identified revision rows and root ownership, so
the query is implementable without schema change or mutation. No new public write
seam, fallback root or target adoption is introduced.

## Cumulative preservation

All v0.3 approved conclusions remain unchanged:

- stored INVALID_COMMAND is invalid backing;
- authorization-denial backing requires its original audit without choosing an
  upper cardinality or additional-denial policy;
- result lookup and stored Result snapshots are closed and getter-once;
- historical accepted request evidence remains tied to its accepted revision,
  not the root's later current pointer;
- public composition is independently recomputed and MariaDB composition reads
  one consistent snapshot;
- authoritative composition disappearance/change/invalid state yields confirmed
  rollback and persistence failure rather than generic domain conflict;
- generic accepted-commit CONFLICT is reserved for resolvable
  root/current/uniqueness states;
- AcceptedCommit/AttemptCommit validation precedes SQL and native confirmed/
  unknown outcomes remain distinct;
- genuine fresh recovery uses one explicit new connection and never the writer;
- degraded versus recovery-ready construction, separate application/storage
  clocks, worker safe-log-first ordering and observer ownership remain exact.

Correction NO_CHANGES remains repository CONFLICT followed by a validated
current-lineage reread selecting exact `REJECTED/NO_CHANGES`. The new revision
query is not called when target is already in the matching current list.

## Test constructibility

The v0.4 matrix can use type-correct foreign public lookups to distinguish:

- exact-root foreign case/order and foreign composition semantic conflicts;
- wrong root echo, base-only and malformed complete lineage persistence failures;
- root NOT_FOUND semantic collision versus UNAVAILABLE persistence failure;
- exact assignment-pair FOUND initial-already-exists versus wrong echo/miss/
  unavailable persistence failure;
- target in current list, genuine absent target, foreign target, contradictory
  same-root owner, owner missing target, missing optional interface and Throwable;
- identical target precedence in normal and post-CAS paths.

Pure cases assert pre-stream closure and zero later work where applicable. Direct
MariaDB cases use only synthetic task-owned rows and public adapter/observer
seams to prove exact revision ownership and one consistent snapshot. No private
method, real data, nondeterministic sleep or new product decision is required.

## Parent and OpenSpec coherence

Parent v69 references the cumulative v0.4 amendment. Proposal, design and delta
state the same root-query semantic versus assignment-query protocol distinction,
revision-owner target resolution and normal/post-CAS reuse. Tasks preserve
already started result/composition drafting, require fresh v0.4 Gate 1 before
lineage RED, and do not infer implementation or combined approval.

No new capability, actor, denial policy, domain fact, schema version, selection,
renderer, HTTP, maintenance or launch behavior is introduced.

## Findings and disposition

No blocking correctness, precedence, public-contract, source-compatibility,
MariaDB constructibility, deferred-policy or test-observability finding remains.

`ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001` v0.4 satisfies Gate 1 at exact
SHA256 `e232bbdfb01c1671ff57e6f353c5b5b1b77c2b60680627b9be37299a2ff27e29`
and is **APPROVED** for the cumulative public-port and real-adapter RED. All RED
artifacts still require independent Gate 3 before minimal GREEN. This approval
does not approve drafted tests, production implementation or combined command
readiness by implication.
