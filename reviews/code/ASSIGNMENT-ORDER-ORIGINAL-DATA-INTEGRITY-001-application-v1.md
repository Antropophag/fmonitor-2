# Code review: ASSIGNMENT-ORDER-ORIGINAL-DATA-INTEGRITY-001 application owner v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Reviewed implementation: `94a17bfef8175669a2265ebd03a33e77c143bfee`
- Baseline: `8350038`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Verification evidence: `/Users/antropophag/.local/state/fmonitor2-verification/original-data-integrity-green-vrynb89_/evidence.json`, SHA256 `7616880e290a58c0fe5671c80998a498f087b9e89249eb39e2afd6c755c5283c`
- Verdict: `CHANGES_REQUESTED`

## Blocking source finding

`AssignmentOrderOriginalLineageSnapshot::read()` validates null/empty metadata for NOT_FOUND and UNAVAILABLE, but it never reads or validates `containsRevision()` in the negative branch. A type-correct negative lookup can expose null base/current/complete fields and `revisionIds=[]` while returning true for the queried current/target revision. The snapshot accepts it as genuine absence.

For a correction root lookup, that malformed NOT_FOUND can then select business `SEMANTIC_COLLISION` instead of retryable `PERSISTENCE_FAILURE`. DATA-INTEGRITY section 4 requires negative empty membership and requires `containsRevision` to agree with the immutable member list for current/target values.

Required correction: add public-seam REDs where an otherwise exact NOT_FOUND or UNAVAILABLE lineage returns true for a revision the invocation already knows it must query. Snapshot and validate `containsRevision` exactly once for the known current/target/query-revision values before selecting absence. Do not invent arbitrary probe IDs merely to interrogate a negative adapter. Obtain independent Gate 3 before the implementation fix.

## Verification blockers already present

The exact-SHA runner terminated with exit `1`. Its final record has `complete=true`, clean reviewed HEAD `94a17bfef8175669a2265ebd03a33e77c143bfee`, and 45 commands: 42 passed and these three failed:

- `assignment_order_original_lease_race_001_test.php`: expected READY barrier after finalize, received EOF/false;
- `assignment_order_original_worker_post_finalize_negative_001_test.php`: empty worker result causes JSON syntax error;
- `assignment_order_original_worker_transport_001_test.php`: empty canonical base64 expected NOT_PDF/result but worker exits 70 with fixed failure stderr.

The three worker failures have a common confirmed fixture cause: older tests pass a raw temporary password path under `/var/...`, while the new canonical validator observes the Darwin-resolved `/private/var/...`; the newer DATA fixture canonicalizes its control path. The validator must not be weakened. Correct the old fixture path construction through its own exact test-owner patch, RED and independent Gate 3.

These are not accepted downstream failures for this Gate 5. Preserve their exact logs, apply only the separately reviewed fixture canonicalization, then rerun the complete exact-SHA command list in a new Gate 5 review. The completed failing run is sufficient evidence for this `CHANGES_REQUESTED` verdict and is not represented as partial or pending.

## Non-blocking source assessment

Result snapshots read all eleven getters once, validate terminal/fingerprint request correlation and status/reason/evidence closure, and distinguish terminal accepted replay from cross-request fingerprint replay. Composition validation checks every-status case/order echo, negative payload closure, strict member list and canonical hash. Normal initial/correction state validation occurs before ID allocation and private finalize. Authorized early rejection uses nullable lazy clock state, preserving exact epoch values. Attempt conflict/unknown and accepted unknown use the supplied fresh-reader dependency without a second ordinary repository read. Fresh result is copied before close; close precedes lease release and its failure is isolated through the exact diagnostic phase. Commit-conflict terminal audits occur after lease release, while recovered accepted results receive delivery once.

The listed conforming behavior does not offset either blocking finding. A fresh independent Gate 5 v2 is required after the separately gated membership fix and worker-fixture correction.
