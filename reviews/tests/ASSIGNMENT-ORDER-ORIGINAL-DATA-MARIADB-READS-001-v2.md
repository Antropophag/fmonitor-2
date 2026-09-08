# Test rereview: ASSIGNMENT-ORDER-ORIGINAL-DATA-MARIADB-READS-001 v2

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed commit `895f69264c6377a63c8a741bfb94ba0d4a4c1f61`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Gate 1 review: SHA256 `ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b`
- Test: SHA256 `f3bf983d7d94b53475460b42ab71956b25a0688e8027d4d665eea6676615bb29`
- Public seam: six `AssignmentOrderOriginalMariaDbRepository` read operations
- Red: 79 cases, 63 intended failures, 16 controls, exit `255`
- Verdict: `APPROVED`

## Exact evidence

```text
b1e0c8cfeb5b36c34deeabb101778762754208cbbc92e6437ff36c09fe57172b  final evidence.json
6fc4b8118082b08175933d61324f36e696c03de891a0c8ea2f550d14bc55b058  final reads log
7c1de9364e9f57ace5bfca3778a03cf46d368759e91be0be5c52b79d1add98e1  database helper
5c8f528db01187e27786bb5ce4944adecbac9101a1ac4bc2e8fd51ca96267ba6  persistence observer helper
7e8e9cebb2b56732e1519afa585c6f08a02bfdaabc37c6aa276229c926ccd715  v1 review
```

## Findings

Both v1 findings are closed. Valid canonical arguments exercise terminal request, fingerprint, root lineage, assignment-order lineage, content reference and revision-owner lineage against a closed native connection and a missing owned schema prefix. Every available seam must return typed UNAVAILABLE without raw Throwable, fact/catalog repair or mutation. The missing revision-owner API remains an explicit intended RED rather than a skip.

The shared negative assertion now proves result lookups expose null result, lineages expose null base/current/ownership metadata plus empty revisionIds and false membership, and unavailable reference lookup exposes null rather than false. It is applied to corrupt backing, missing backing, invalid arguments, genuine absence, dangling owner, active caller transaction, duplicate ownership and real query failures. FOUND/false remains the valid zero-reference control.

The unchanged matrix retains independently seeded atomic accepted/rejected/denial facts, one-axis cross-table corruptions, historical replay after correction, fingerprint recomputation, complete current lineage, exact revision-owner historical/foreign/dangling semantics, invalid arguments before SQL, duplicate assignment roots and no-repair state/catalog snapshots. Owned constraint disabling and dropped index are scoped and teardown removes the random database.

Independent execution reproduced `passes=16 failures=63 cases=79`, exit `255`, matching the final archive. No blocking traceability, expected-value independence, query-failure, closed-shape, setup, cleanup, determinism or public-seam finding remains.

## Required changes

None. Real writes/fresh-reader/worker proof remains separately gated.
