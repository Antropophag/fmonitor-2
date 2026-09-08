# Test review: ASSIGNMENT-ORDER-ORIGINAL-DATA-VALUES-001 v1

- Reviewer: separately tasked agent `/root/admission_oracle_gate3`
- Test author: `/root`; reviewed test/source commit `0bcd8f3a605c3d41556cfc5b842b68f63bb8119e`
- Reviewed ledger HEAD: `7b68eccdfcb5137f7deaebb19b01cbe2a809b4ee`
- Specification: DATA-INTEGRITY-001 v0.6, SHA256 `c3d170ec5ccfee425d13a77c8d121e932c31df0b27591ee666fe1455a35f06dd`
- Public seam: result/lookup interfaces consumed by `submitAssignmentOrderOriginal`
- Red: 133 cases, 84 intended failures, 49 controls, exit `255`
- Verdict: `APPROVED`

## Exact hashes

```text
b1e198c67d679a58859da8bc2e40012e2a69c6e4dcadf236d55094eed9703ead  tests/InstallationProcess/assignment_order_original_data_values_001_test.php
caaeeee7e756b70bfe0bed939858d3a46f6cacf5489b1639a86f5c40687f5907  tests/Support/AssignmentOrderOriginalIntegrityValues.php
a05c4a22f3c0360ecb865c44ffb5043aa55b02e3ed11374c8743a7537114ce75  tests/Support/AssignmentOrderOriginalIntegrityFixture.php
98b9e080f7dbd44bcd7b2f40d905a3aa9842ff807abe79dbeb78012884c15780  tests/Support/AssignmentOrderOriginalIntegrityResources.php
dfbf0849e34ea941477b238049b29a7e3cdf3566b8bd8b3fdb9931ac14ee551e  tests/Support/AssignmentOrderOriginalIntegrityTestBootstrap.php
dbb17374533cd542757f41d5ed4a0f76eb48c0d1b7adac22a789dab9bbe23794  tests/Support/AssignmentOrderOriginalIntegrityCommits.php
ce9a24c650c10cd71f3f71203bf900cc733978d25e862b28ee9b18a62280153b  docs/operations/original-data-integrity-gate1-review-v06-2026-09-06.md
47f197bf7bb067ca5bf586bca50bb4cad7fb6055c99cc4dcfd3ce879cc54aa33  private archive 05.log
```

## Findings

The test independently fixes all six lookup status/payload combinations at both terminal and fingerprint positions and snapshots both getters exactly once even for negative states. Impossible combinations and getter Throwables fail at the correct pre/post-stream boundary; valid NOT_FOUND and FOUND controls prevent unavailable-all behavior.

Stored Result validation covers status, reason, retryability, UUID, every evidence scalar grammar/nullability and all eleven getter Throwables. It separately proves terminal request UUID equality and permitted cross-request fingerprint replay under the current caller request. Every allowed stored rejection/conflict reason has a positive terminal control; stored INVALID_COMMAND, wrong reason family, retryability, evidence leakage and missing reason are rejected. Fingerprint FOUND is restricted to accepted evidence.

Expected values are literal v0.6 values. Conditional test marker interfaces define no production alias; the missing complete-lineage API is an explicit RED while remaining value cases execute through foreign old-compatible interfaces. Fresh fixtures isolate cases and assert no unintended write/audit. No skip or accepted-failure list exists.

Independent execution reproduced `passes=49 failures=84 cases=133`, exit `255`, matching archive evidence. No blocking traceability, independence, sensitivity, determinism or seam finding remains.

## Required changes

None. Real MariaDB backing/rehydration remains separately gated.

