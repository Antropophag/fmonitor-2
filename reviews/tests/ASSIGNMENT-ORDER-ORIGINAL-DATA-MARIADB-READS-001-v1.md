# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-READS-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit `8ff730b100866d670dd1ab6ba473c6f6426eb666`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Gate 1: `APPROVED`, review SHA256 `ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b`
- Test: SHA256 `0fce414dc6f51e7631d18b600f06827ba0050500d08bee4d990e826efbebd1cb`
- Public seam: `AssignmentOrderOriginalMariaDbRepository` read methods
- Red: 67 cases, 61 intended failures, 6 controls, exit `255`
- Verdict: `CHANGES_REQUESTED`

## Exact supporting hashes

```text
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  tests/Support/AssignmentOrderOriginalIntegrityDatabase.php
5c8f528db01187e27786bb5ce4944adecbac9101a1ac4bc2e8fd51ca96267ba6  tests/Support/AssignmentOrderOriginalIntegrityPersistenceObserver.php
003c98cec1a7c161b34fb4ac82511d1242437333ba6bfef6d12afad90953172e  final archive evidence.json
9c5ffef661cd54052d390a4a41be2621ade0fb5ce354695ff085d98cfa2406f1  final reads.log
```

## Blocking findings

### G3-READ-01: real query failure is not exercised

The contract requires query/transaction/rehydration failure to return typed UNAVAILABLE. The test covers invalid arguments before SQL, active caller transaction, malformed/missing backing and duplicates, but it never invokes a valid read on a closed connection or otherwise forces the SELECT itself to fail. An adapter that validates arguments and rows correctly but leaks/throws on actual query failure can pass.

Add a task-owned connection, close it, construct the repository with it, and invoke each valid read seam: terminal request, fingerprint, root lineage, assignment-order lineage, revision-owner lineage and content reference. Each must return the exact typed UNAVAILABLE shape without raw Throwable or mutation. This requires no production or external database.

### G3-READ-02: real adapter closed payload shapes are asserted only by status

Most corrupt/missing/invalid cases assert only `lookup->status()`. They do not assert `result()===null` for result lookups, null/empty metadata for negative lineage, or `referenced()===null` for unavailable reference lookup. Group A proves the application rejects impossible foreign values, but it does not prove the production MariaDB adapter itself returns its required closed representation.

Add shared assertions for every genuine NOT_FOUND and UNAVAILABLE adapter result. At minimum cover request/fingerprint `result()`, all base/current/complete lineage getters and revisionIds, and reference `referenced()`. Apply them to one valid absence and representative corruption/query-failure cases so an adapter cannot hide a payload behind the correct status.

## Covered assessment

The accepted/rejected/denial controls, 39 one-axis accepted-backing corruptions, missing atomic families, wrong recomputed fingerprint, duplicate accepted audit, historical request/fingerprint after correction, complete lineage, invalid arguments before SQL, exact revision-owner historical/foreign/dangling behavior and duplicate assignment roots are otherwise independently literal and sensitive. Corruptions occur only in the owned database with scoped constraint disabling and state/catalog no-repair assertions. The final four revision-owner/duplicate cases close the earlier archive gap.

Independent reproduction matched `passes=6 failures=61 cases=67`, exit `255`. Setup and strict owned cleanup completed without secondary failure.

## Required changes

Add valid-input real query-failure coverage and assert closed negative payloads, then preserve fresh RED and request Gate 3 rereview.
