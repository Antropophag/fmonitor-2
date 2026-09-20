# Code review: SAFE-RUNTIME-ERROR-CORRELATION-001

- Reviewer: independent Gate 5 reviewer (gpt-5.6-sol / low)
- Implementation author: executor agent; committed by Timofey Grishin
- Reviewed candidate: commit `48ce61755791eb4e598b4484d0161a8f8754c10e`
- Reviewed exact source: `9d3b6d22bc89420b1efb9d40d002301571b9c4df07d6fe5a43305fe818a325f9`
- Base: `e3b39e59b7ba75a3af7dd3d22871c5c9f25d44e9`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T081549Z-957144a030/package.json`
- Specification: `specs/SAFE-RUNTIME-ERROR-CORRELATION-001.md`
- Approved test review: `reviews/tests/SAFE-RUNTIME-ERROR-CORRELATION-001.md`
- Focused GREEN evidence: record `1789892118370925000-1b99eda4a0cb4c6fb0a2022d352464b4.json`, exact source `9d3b6d22bc89420b1efb9d40d002301571b9c4df07d6fe5a43305fe818a325f9`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **High — an explicit handled 503 in the selected `ExecutionController` boundary has neither an error ID nor a diagnostic record.** `app/YiiRuntime/Controllers/ExecutionController.php:34-37` passes every non-`found` selection-portal result directly to `PreopeningController::domain()`. A returned `['status' => 'failed', 'reasonCode' => 'dependency_unavailable']` is a normal, reachable result from `MariaDbSelectionPortalQuery` and `domain()` maps it to the pre-existing 503, but this path never calls `reportResult()`. The client therefore receives a selected controller 503 without `X-FMonitor-Error-ID`, and the operator receives no correlated record, contrary to sections 1, 3, and 6 of `SAFE-RUNTIME-ERROR-CORRELATION-001` and the declared requirement to cover the controller's explicit handled 503 branches. The current test's execution case forces a thrown exception and reaches the catch at lines 38-40, so its GREEN result cannot detect this omission. Correlate the failed portal-result branch exactly once before returning the unchanged `domain()` response, and add a public HTTP/factual-log regression that makes the portal return its ordinary failed result rather than throwing.

2. **Medium — the stable `persistence_failure` result code is misclassified as `unexpected`.** `app/YiiRuntime/Controllers/ExecutionController.php:90-92` reports every failed assignment-order application, but `reportResult()` at lines 108-115 recognizes only `persistence_outcome_unknown` as `database`; `persistence_failure` falls through to `unexpected`. `MariaDbAssignmentOrderApplication::applyAssignmentOrderOriginal()` explicitly returns the stable `persistence_failure` code when its database transaction/query path throws. The contract requires categorization by known type/code or explicit failure location and reserves `unexpected` for unknown causes. Map this known persistence code to `database` and add focused coverage of the explicit result branch so a future category regression is observable.

The remaining inspected behavior is consistent with the bounded contract: bootstrap and global-handler correlation preserve sanitized responses and headers; the original and checklist paths retain their existing envelopes; server-owned IDs and allowlisted records avoid request/exception/document data; the common sink fails safely; controller catches do not rethrow into the global handler; success and representative 4xx behavior remain outside the failure channel; and policy/profile/suite registration is present. The two findings above block approval because a supported selected 503 remains uncorrelated and a known failure code produces the wrong operational category.

## Required changes

- Correlate the `ExecutionController` selection-portal failed-result 503 exactly once while preserving its existing response.
- Classify `persistence_failure` as `database` and cover both explicit result branches through the public HTTP/log seam.

## Decision

`CHANGES_REQUESTED`

## Correction round 1 — final Gate 5 decision — 2026-09-20

- Reviewed candidate: commit `629ec6571d5ae860fcc9327409c21cc69b8e773f`
- Reviewed exact source: `87d2a637847e75220b8a84864b061309f475efb93324e2023cb119764bc93677`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T082850Z-9e20604d3a/package.json`
- Approved Gate 3 regression delta: `reviews/tests/SAFE-RUNTIME-ERROR-CORRELATION-001.md`, owner-authorized Gate 3 regression delta for Gate 5 findings
- Focused GREEN evidence: record `1789892899664701000-4ca01f19a53a4eacb403c89b3a76dcc8`, exact candidate/end source `87d2a637847e75220b8a84864b061309f475efb93324e2023cb119764bc93677`
- Correction delta: `app/YiiRuntime/Controllers/ExecutionController.php`, the two public regressions, and lifecycle/review evidence only
- Verdict: `APPROVED`

### Prior findings disposition

1. **Fixed.** `ExecutionController::actionIndex()` now distinguishes the successful portal projection, reports an ordinary `failed` or `SERVICE_UNAVAILABLE` result before returning the existing `domain()` response, and leaves all other domain outcomes unchanged. The real HTTP regression removes the selection table so `MariaDbSelectionPortalQuery` returns its stable `dependency_unavailable` result; it proves the unchanged plain 503, security headers and `Retry-After`, one server-owned ID, and exactly one physical `execution_controller` record categorized as `dependency`. The branch returns immediately after `domain()` and cannot also reach the catch/global handler, so the correction does not double-report.

2. **Fixed.** `ExecutionController::reportResult()` now maps both stable persistence owner codes, `persistence_failure` and `persistence_outcome_unknown`, to `database`; `dependency_unavailable` remains `dependency` and unknown codes remain `unexpected`. The real apply regression triggers the application insert failure, observes the stable `persistence_failure` result through public HTTP, requires exactly one correlated database record, and proves all domain facts/history unchanged.

### Final findings

None. The correction is limited to the two returned-result gaps and does not broaden selected components or turn success, expected 4xx, or non-503 domain outcomes into diagnostic events. Re-auditing the agreed handled 503 paths confirms that bootstrap and the global handler retain single ownership; all checklist 503s are caught and reported; original form, upload, worker-result, and context 503s report once; and execution thrown, confirmed-opening, apply, legacy-opening, and portal-result 503s now report once with closed categories. The approved regressions and exact-source GREEN evidence are sensitive to response compatibility, category, correlation, duplicate records, and domain-fact changes.

### Required changes

None.

### Final decision

`APPROVED`

## CI correction candidate 1 review — 2026-09-20

- Reviewed candidate: commit `6848581efc3f30be5f796649118dc0c723b9d816`
- Reviewed exact source: `7d0a1dec1d17eb20b89d11871d8281f6c1fdc431fa6fb1fb1f738b5ae51ac52b`
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T085628Z-308a4d91b8/package.json`
- Triggering CI: run `35499759133`, primary failure `governance`; sole reported `REGRESSION_FAILURE` in `tests/Verification/registered_yii2_focused_bootstrap_001_test.py`; `verify` and Quality Graph were aggregate failures, while all other jobs were GREEN
- Exact acceptance GREEN: record `1789894559861068000-1a284a012afd44d7a251131597d02756`, exact candidate/end source `7d0a1dec1d17eb20b89d11871d8281f6c1fdc431fa6fb1fb1f738b5ae51ac52b`
- Verdict: `APPROVED`

### Findings

None. The correction changes only the governance fixture's deliberate exact `focused_command_profiles` allowlist so it recognizes both previously registered routes: the #202 browser navigation profile and the #173 integration runtime profile. It does not relax the assertion to accept arbitrary entries, alter profile selection, change harness algorithms, or touch product code. Adding the changed governance test to `planned_paths` correctly makes the recomputed plan select that obligation.

The correction matches the complete reported CI inventory: it addresses the one primary governance regression rather than treating the aggregate `verify`/Quality Graph failures as independent causes. The targeted failed method, change-verification check, architecture guard, and exact acceptance are reported GREEN. The unrelated full-local governance-file observation (`dependency-failure` producing `UNKNOWN` instead of `SETUP_FAILURE`) is recorded as an environment discrepancy, was GREEN in the exact CI run, and is correctly excluded from correction evidence rather than being claimed as resolved.

### Required changes

None.

### Decision

`APPROVED`
