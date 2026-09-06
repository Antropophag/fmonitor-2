# Original command — revision-owner lookup feasibility

- Date: `2026-09-06`
- Reviewer: separately tasked read-only agent `/root/registry_engine_gate1`
- Reviewed parent: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v68, SHA-256 `153f56abfd1239c9adbd3b1b2e44da3522c4b2d4a141f80cdff7093b158ec74c`
- Scope: existing correction target-reason precedence and minimum read-only repository extension
- Verdict: **MISSING READ-ONLY SEAM CONFIRMED; OPTIONAL EXTENSION IS VIABLE**

The current worktree had already advanced to parent v69 when this record was written. Its current parent hash is not the contract reviewed for this feasibility determination. No code, test or specification was edited. This is not Gate 1.

## Existing normative distinction

Parent section 9 requires this order for correction:

1. command root not owned by the specified case/order/composition gives `SEMANTIC_COLLISION`;
2. expected-current mismatch gives `STALE_REVISION`;
3. after expected-current agrees, globally unknown target gives `TARGET_NOT_FOUND`;
4. a target that exists in another root gives `SEMANTIC_COLLISION`;
5. a noncurrent target belonging to the same root gives `TARGET_NOT_CURRENT`.

These are existing reason codes and precedence, not a new product choice.

## Current seam cannot distinguish the outcomes

`AssignmentOrderOriginalLineageLookup` exposes only one root and `containsRevision(target)`. `AssignmentOrderOriginalRepository` additionally exposes root lookup, while `AssignmentOrderOriginalAssignmentLineageRepository` exposes case/order lookup. Neither interface can find the owner of an arbitrary revision ID.

`AssignmentOrderOriginalCommitProtocol::correction` and its CAS conflict classifier test only `currentLineage.containsRevision(target)`. A false result immediately becomes `TARGET_NOT_FOUND`. The same false value covers both a globally absent target and a target stored under another root, so the required distinction cannot be implemented from the current public values.

The existing worker wrong-root case proves command-root ownership mismatch before this target branch. It does not cover a valid command root plus expected current plus a foreign target revision.

## Minimal optional extension

Keep the base repository interface source-compatible and add one optional read-only capability:

```php
interface AssignmentOrderOriginalRevisionLineageRepository
{
    public function findLineageForRevision(
        string $revisionId,
    ): AssignmentOrderOriginalLineageLookup;
}
```

Any `FOUND` result must implement the separately planned `AssignmentOrderOriginalCompleteLineageLookup`. `NOT_FOUND` and `UNAVAILABLE` need only the base status interface and expose no metadata. A base-only or malformed FOUND is `FAILED/PERSISTENCE_FAILURE`.

Making this an optional repository extension avoids forcing negative-only stubs and unrelated repository implementations to invent complete metadata. Production composition must implement it. If a path needs global target ownership and the repository lacks the extension, the result is persistence failure, never target-not-found.

The extension is read-only. It adds no commit method, mutation path, authorization bypass or alternate domain owner.

## Exact call precedence

After the command root/ownership/composition and expected-current checks succeed:

1. If the validated current complete lineage contains target:
   - target equal to current continues to no-change/acceptance checks;
   - target different from current returns `TARGET_NOT_CURRENT`;
   - no global revision lookup is called.
2. If the current lineage does not contain target, call `findLineageForRevision(target)` exactly once:
   - `NOT_FOUND` with no metadata gives `TARGET_NOT_FOUND`;
   - `UNAVAILABLE`, Throwable or malformed status/value gives retryable `FAILED/PERSISTENCE_FAILURE`;
   - complete FOUND with a different root gives `SEMANTIC_COLLISION`;
   - complete FOUND with the same root contradicts the already validated current membership set and gives persistence failure, not a business conflict.

Expected-current mismatch retains `STALE_REVISION` without calling the new lookup. Root/case/order/composition mismatch retains the earlier `SEMANTIC_COLLISION`. The same helper and ordering must be used during post-CAS conflict reclassification.

## MariaDB implementation boundary

The production adapter should query the exact revision ID inside the same consistent read-only snapshot used by the complete-lineage validator. It must:

- return NOT_FOUND only for genuine absence;
- resolve exactly one root owner;
- validate the full root/revision chain, case/order ownership, current membership and current evidence before returning FOUND;
- treat duplicates, malformed rows, broken membership, query or snapshot failure as UNAVAILABLE;
- perform no DDL, DML, repair or fallback selection.

This reuses the complete-lineage data-integrity contract rather than adding a second weaker metadata DTO.

## Required evidence

The next Gate 1 and Gate 3 package should fix these public-seam cases:

- valid root and expected current, globally absent target -> `TARGET_NOT_FOUND`;
- valid root and expected current, target owned by another root -> `SEMANTIC_COLLISION`;
- same-root historical target -> `TARGET_NOT_CURRENT`;
- lookup unavailable, Throwable, malformed tuple or base-only FOUND -> `FAILED/PERSISTENCE_FAILURE`;
- zero revision-owner lookup calls when expected current is stale or the current lineage already contains target;
- the same matrix after commit/CAS conflict rereads;
- direct MariaDB FOUND/NOT_FOUND/UNAVAILABLE controls using synthetic rows and no mutation.

All cases must preserve authorization and existing confidential lookup order. The extension must not run before root/expected-current validation.

## Exact unchanged source hashes

```text
014af5a9b72ab93d7e03e9d3dbb1da208b22b28e5bf9c40bead0532ac60e74a8  app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php
ce1e072b08705f6347de23e2867ee53c8e774c528b3abf5f1c5b121d9a21bc3c  app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php
1041c338567898b577b218c6b4bd73c2be872096fd994276e4c3680bdf95dcbd  app/AssignmentOrderOriginal/AssignmentOrderOriginalCommitProtocol.php
```

This feasibility record does not authorize RED or implementation. Root will place the exact interface and precedence in the data-integrity candidate before fresh independent Gate 1.
