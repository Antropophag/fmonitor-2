# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-DATA-LINEAGE-001 v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit `14dece9479f80a62773d66d3587b486bf8600169`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Test: SHA256 `651084f63e959f9c3cfcbbd30b959cee08289b3e7109e8b27f59e748dc79407c`
- Public seam: complete lineage and revision-owner lookups consumed by `submitAssignmentOrderOriginal`
- Red: 106 cases, 90 intended failures, 16 controls, exit `255`
- Verdict: `APPROVED`

## Exact evidence

```text
69130cd804ffa6b7d742a3f0ee01d86e7713d07f620111dc874c7ff3059d839d  private evidence.json
39562c2983cf7b450c6670199a45a8b9bde86bba2824555a5f12d60421f65fb6  private 01.log
e15a5b7708b3487ee8563c7126942ea607cd47725518ddade2b71d43511988eb  v1 review
```

## Findings

The v1 finding is closed. For both NOT_FOUND and UNAVAILABLE, ten added cases start from the exact absent snapshot and leak one field only: root, current revision, number, composition identity/hash, current date/PDF hash, case, order or revisionIds. Each requires persistence failure and explicitly proves the leaked getter was read once; an implementation cannot short-circuit after checking another null field. No target-owner query follows contradictory negative metadata.

The unchanged matrix retains complete-interface presence, FOUND scalar/getter/membership validation, valid correction controls, semantic root drift versus query corruption, assignment/revision ownership, initial precheck, pre-finalize stale/historical/no-change ordering, reachable post-CAS classifications and fingerprint-winner precedence. Winner facts remain separate from current accepted calls.

Independent execution reproduced `passes=16 failures=90 cases=106`, exit `255`, matching the final archive. No blocking traceability, expected-value independence, closure sensitivity, determinism or public-seam finding remains. Real MariaDB lineage proof remains separate.

## Required changes

None.
