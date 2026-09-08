# DATA-INTEGRITY v0.3 — lineage ownership clarification review

Date: 2026-09-06.

Reviewer task: `/root/selection_v04_readiness`.

Reviewed HEAD: `0dd3e7e89536c717f019817f3cedb016fb09f7fa`.

Verdict: **MATERIAL AMBIGUITY; AMEND BEFORE LINEAGE RED**.

This is a bounded follow-up to the approved v0.3 Gate 1 record. It does not edit
or retroactively revoke that record. No specification, test or production source
was changed.

## Exact reviewed hashes

```text
0ad31853e7a348205660110482be776eec29b61e7fc8149d6d3dbe8544858773  specs/ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001.md
153f56abfd1239c9adbd3b1b2e44da3522c4b2d4a141f80cdff7093b158ec74c  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
66a62e402eb05f44390a96cbfa983da811920af9564d68f525c3258048112a03  docs/operations/original-data-integrity-gate1-review-v03-2026-09-06.md
```

## Ambiguity

DATA-INTEGRITY section 4 requires complete FOUND lineage metadata and says
“echoed case/order positive and exact”. It also says only valid but differing
root composition/current/target relations select existing business conflicts.
Those statements do not distinguish the two public lineage query shapes.

The parent correction contract requires a caller-supplied root that does not
belong to the command's case/order/composition to return the pre-stream business
outcome `CONFLICT/SEMANTIC_COLLISION`. A complete lineage returned for the exact
queried root may be internally valid while proving that it belongs to another
positive case/order. Treating that case/order difference as malformed protocol
would incorrectly return `FAILED/PERSISTENCE_FAILURE` and erase the inherited
semantic distinction.

Conversely, `findLineageForAssignmentOrder(caseId, orderId)` is queried by the
case/order pair. A FOUND value echoing another pair violates the query protocol;
it cannot be treated as evidence of a semantic collision for the requested pair.

## Required exact distinction

The next amendment should separate three validation stages.

### Common complete-lineage integrity

Every FOUND value must first be internally complete and valid:

- root/current/member identity grammar;
- positive case/order and contiguous revision numbering;
- unique ordered membership with current as the last revision;
- current date/PDF hash and `containsRevision` agreement;
- composition identity binds the lineage's own returned order;
- MariaDB root/revision/current chain and backing are coherent.

Failure here is protocol/persistence integrity failure.

### Query-key echo

For `findLineage(rootId)`:

- returned root ID must equal the exact queried root ID;
- a different returned root ID is protocol failure;
- returned case/order need only be positive and internally consistent at this
  stage because case/order were not query arguments.

For `findLineageForAssignmentOrder(caseId, orderId)`:

- returned case and order must equal both query arguments;
- either mismatch is protocol failure;
- returned root may be any valid root owned by that exact pair.

### Command semantic comparison

Only after a root-query FOUND lineage passes common integrity and exact root echo
does the application compare its returned case/order/composition with the current
command and current authoritative composition:

- same root but foreign valid case/order → `CONFLICT/SEMANTIC_COLLISION`;
- same case/order but different valid immutable composition identity/hash →
  `CONFLICT/SEMANTIC_COLLISION`;
- matching ownership/composition then proceeds to stale/target/no-change checks.

This preserves nondisclosing result fields while retaining the parent's exact
business reason.

## Negative states

For `findLineage(rootId)` in correction preflight:

- valid NOT_FOUND for the exact requested root selects
  `CONFLICT/SEMANTIC_COLLISION` before stream, as the parent already requires;
- UNAVAILABLE or malformed negative metadata maps to
  `FAILED/PERSISTENCE_FAILURE`;
- NOT_FOUND must not be relabeled `TARGET_NOT_FOUND`, which applies only after a
  valid matching lineage is established and target membership is checked.

For `findLineageForAssignmentOrder(case, order)` in initial commit-conflict
resolution:

- complete valid FOUND selects `INITIAL_ALREADY_EXISTS`;
- NOT_FOUND, UNAVAILABLE or malformed backing maps to persistence failure under
  the approved v0.3 rule, because a generic commit conflict alone cannot prove an
  existing root.

## Required bounded tests

Before lineage RED, the amendment should pin distinct foreign public values for:

1. root lookup exact root, internally valid foreign case/order → semantic
   collision;
2. root lookup exact root and case/order but valid differing composition →
   semantic collision;
3. root lookup returns a different root than queried → persistence failure;
4. root lookup NOT_FOUND → semantic collision;
5. root lookup UNAVAILABLE/base-only FOUND/internally malformed → persistence
   failure;
6. assignment-order lookup exact pair with complete valid root →
   initial-already-exists;
7. assignment-order lookup FOUND with mismatched case or order → persistence
   failure;
8. assignment-order lookup NOT_FOUND/UNAVAILABLE/malformed → persistence
   failure.

Each correction-preflight case must prove no stream read/stage/ID/commit and exact
unread-stream close. Initial conflict-resolution cases occur after the existing
commit conflict and must prove no invented or mutated root. MariaDB controls
should demonstrate that each query returns only its exact key space and never
uses `LIMIT 1` to hide duplicates.

## Disposition

The distinction is technical and follows existing product outcomes; no owner
decision is needed. DATA-INTEGRITY v0.3 is ambiguous for this test boundary, so
lineage RED must wait for a v0.4 amendment and fresh independent Gate 1 review.
Result-lookup RED work under unchanged sections 2 and 3 is unaffected.
