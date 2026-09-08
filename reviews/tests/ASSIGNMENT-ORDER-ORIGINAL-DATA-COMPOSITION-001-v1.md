# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-COMPOSITION-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed test/source commit `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`
- Reviewed ledger HEAD: `7b68eccdfcb5137f7deaebb19b01cbe2a809b4ee`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Public seam: passive composition snapshot consumed by `submitAssignmentOrderOriginal`
- Red: 76 cases, 62 intended failures, 14 controls, exit `255`
- Verdict: `CHANGES_REQUESTED`

## Exact hashes

```text
f65b475a4275fff294b2fe3b93cdfee2a2e9361e723d7a196c1af9097a7491e1  tests/InstallationProcess/assignment_order_original_data_composition_001_test.php
a05c4a22f3c0360ecb865c44ffb5043aa55b02e3ed11374c8743a7537114ce75  tests/Support/AssignmentOrderOriginalIntegrityFixture.php
caaeeee7e756b70bfe0bed939858d3a46f6cacf5489b1639a86f5c40687f5907  tests/Support/AssignmentOrderOriginalIntegrityValues.php
b7c8540017186fad7a4462db0df9beae1434b1ce2c8154a830fa432067bad69d  private archive 02.log
```

## Blocking finding

V0.6 requires snapshot case/order to echo the exact positive requested IDs **for every status**. The test varies wrong/zero case and order only while status is FOUND. NOT_FOUND and UNAVAILABLE controls retain correct `4512/81` and vary only identity/hash/installers/engineer.

An implementation can validate case/order only in the FOUND branch, accept `NOT_FOUND(case=9999)` as ordinary ORDER_NOT_FOUND, or accept `UNAVAILABLE(order=82)` as ordinary persistence failure and pass all 76 cases. Add one-axis wrong and nonpositive case/order cases for both NOT_FOUND and UNAVAILABLE, with all other payload fields null/empty. Each must map protocol failure before clock, terminal attempt, stream/stage, IDs or delivery. Retain nearby correct-echo controls.

## Covered assessment

FOUND protocol ownership, identity/order/version grammar, engineer, list shape/type/order/duplicates/positivity, independently recomputed canonical hash, valid initial/correction acceptance, lazy-clock terminal persistence for genuine NOT_FOUND/FOUND-invalid, and query Throwable are otherwise traceable and sensitive. Intentional invalid FOUND snapshots remain business INVALID_COMPOSITION rather than setup failures. Independent execution reproduced `passes=14 failures=62 cases=76`, exit `255`.

## Required changes

Add NOT_FOUND and UNAVAILABLE case/order echo negatives, preserve fresh RED, and request Gate 3 rereview before GREEN.

