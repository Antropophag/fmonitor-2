# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-LINEAGE-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed test/source commit `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`
- Reviewed ledger HEAD: `7b68eccdfcb5137f7deaebb19b01cbe2a809b4ee`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Public seam: complete lineage/revision-owner interfaces consumed by `submitAssignmentOrderOriginal`
- Red: 86 cases, 70 intended failures, 16 controls, exit `255`
- Verdict: `CHANGES_REQUESTED`

## Exact hashes

```text
ce20aacb15c90bc81093495e39dc60ae1df45d922df7ee8995f96ce4533744cd  tests/InstallationProcess/assignment_order_original_data_lineage_001_test.php
caaeeee7e756b70bfe0bed939858d3a46f6cacf5489b1639a86f5c40687f5907  tests/Support/AssignmentOrderOriginalIntegrityValues.php
a05c4a22f3c0360ecb865c44ffb5043aa55b02e3ed11374c8743a7537114ce75  tests/Support/AssignmentOrderOriginalIntegrityFixture.php
3944f5e73af067c2d977306b6b2d07af62ac87250d20628b7c8b3a01f1f4bfdc  private archive 03.log
```

## Blocking finding

V0.6 requires NOT_FOUND and UNAVAILABLE lineage to expose null root/current/number/composition/current-evidence/case/order and empty revisionIds. The matrix has one `not-found-payload` and one `unavailable-payload`, each changing only status on an otherwise fully populated default lineage.

Those cases detect some payload leakage but are not independently sensitive to every extension field. An implementation that rejects negative state when root is non-null, then ignores leaked `installationCaseId`, `assignmentOrderId`, `revisionIds`, current date or PDF hash passes both cases. Add one-axis negative-state cases for each base/current-evidence/complete-extension field, for both NOT_FOUND and UNAVAILABLE, starting from `OriginalIntegrityLineage::absent()`. Each altered field must produce persistence failure; retain exact all-null/empty controls.

## Covered assessment

FOUND scalar grammar, complete-interface requirement, getter Throwables, revision membership/list/current/count invariants, root semantic drift versus assignment/revision query echo corruption, target-owner classification, initial precheck, normal pre-finalize stale/historical/no-change precedence, reachable post-CAS states and fingerprint-winner precedence are otherwise extensive and exact. Literal winner facts remain separate from current accepted calls. Independent execution reproduced `passes=16 failures=70 cases=86`, exit `255`.

## Required changes

Add one-axis NOT_FOUND/UNAVAILABLE payload-closure cases for all lineage metadata fields, preserve fresh RED, and request Gate 3 rereview before GREEN.
