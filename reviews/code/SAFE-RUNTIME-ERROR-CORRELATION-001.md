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
