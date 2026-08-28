# Test review: ORDER-PREPARE-001-G

- Reviewer: `Codex agent /root/order_prepare_001_g_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved ORDER-PREPARE-001-F`
- Specification: [`specs/ORDER-PREPARE-001-G.md`](../../specs/ORDER-PREPARE-001-G.md)
- Public seams: `InstallationProcess::prepareOrder(...)` and `InstallationProcess::getOrderProcess(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_g_test.php`](../../tests/InstallationProcess/order_prepare_001_g_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_g_test.php`
- Intended failure: combined rejection reasons are not audited
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught TestFailure:
ORDER-PREPARE-001-G must write one audit event with both violations in stable order.
Expected: process projection with exactly one assignment_order_prepare_rejected event
          whose reasonCodes are [INSTALLER_REQUIRED, CONTROL_ENGINEER_REQUIRED]
Actual: the same process projection with events = []
in tests/bootstrap.php:27
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-001-G` and reproduces its exact order, authorized actor, blank and whitespace-only installer inputs, absent engineer, initial process projection, and fixed server time. The complete expected projection is a direct transcription of sections 2–4 of the approved specification and the approved E observation contract.
- **Public seams:** fixture facts and the clock are arranged through the in-memory environment, while behavior is invoked through `InstallationProcess::prepareOrder(...)` and observed only through `InstallationProcess::getOrderProcess(...)`. No adapter state, storage representation, private method, or collaborator call is used as an assertion side channel.
- **One-event sensitivity:** exact comparison requires the event list to contain exactly one event. Implementations that emit one event per violation, duplicate the combined event, omit it, or add any other visible process event fail.
- **Both-code and stable-order sensitivity:** the expected `reasonCodes` list contains both normative codes in the order fixed by parent Example C. Omitting either code, reversing them, adding a code, or splitting them across events fails strict comparison.
- **No-partial-state sensitivity:** the assertion fixes `processState`, empty `assignmentOrders`, empty `assignments`, and the original single open task. A partial order or assignment, state transition, task closure/replacement/duplication, or any visible fact beyond the one rejection event fails.
- **Normalization sensitivity:** the raw installer array contains two elements, but both normalize away under the approved blank/whitespace rule; the asserted `installerCount` is therefore zero. A raw array-length count or retention of whitespace identifiers fails.
- **Privacy:** the exact payload permits only reason codes, normalized count, and the engineer-presence boolean. It does not include the original installer array, engineer identifier, personal names, command body, or storage identifiers; exposing any extra field fails comparison.
- **Expected-value independence:** expected values come from the approved G specification, parent Example C, and E's public projection contract. They are not calculated from production output, current implementation logic, persistence state, or earlier test assertions.
- **Determinism and isolation:** authorization, initial projection, inputs, and the ISO-8601 timestamp with offset are fixed in memory. The test has no wall-clock, database, filesystem-state, network, legacy-application, production-catalog, or test-order dependency.
- **Scope:** this focused test proves G's newly specified combined audit and unchanged public projection. The exact command result for normalized blank participants is already covered by independently reviewed Example C and should remain in the regression run; security audit for `FORBIDDEN`, idempotency, and successful preparation remain outside this slice.
- **Red cause:** PHP syntax validation passes. Independently running `php tests/InstallationProcess/order_prepare_001_g_test.php` exits `255` after the public command and observation seams execute. Expected state and all unchanged fields match; only the specified combined audit event is absent. The failure therefore demonstrates missing G behavior rather than a broken fixture, unresolved seam, or external dependency.

## Required changes

None for this slice.

Gate 3 is approved. Production implementation may proceed only far enough to append the single specified combined-reason rejection event without changing the reviewed expectation; the focused test and prior ORDER-PREPARE-001 regression tests must be green before Gate 5.
