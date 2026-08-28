# Code review: ORDER-PREPARE-001-G

- Reviewer: `Codex agent /root/order_prepare_001_g_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after minimal ORDER-PREPARE-001-G implementation`
- Specification: [`specs/ORDER-PREPARE-001-G.md`](../../specs/ORDER-PREPARE-001-G.md)
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-G.md`](../tests/ORDER-PREPARE-001-G.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d,_e,_f,_g}_test.php; do php "$test_file" || exit 1; done`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/InstallationProcess/order_prepare_001_g_test.php`
  - `git diff --check`
- Verdict: `APPROVED`

## Verification evidence

```text
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
PASS ORDER-PREPARE-001 example D
PASS ORDER-PREPARE-001-E audit projection
PASS ORDER-PREPARE-001-F audit projection
PASS ORDER-PREPARE-001-G combined audit projection
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_g_test.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** for the approved G fixture, `prepareOrder(...)` normalizes the two blank installer values to an empty list, accumulates `INSTALLER_REQUIRED` followed by `CONTROL_ENGINEER_REQUIRED`, and appends exactly one `assignment_order_prepare_rejected` event. The event contains both codes in that same normative order, the fixed timestamp and actor, `installerCount = 0`, and `controlEngineerProvided = false`.
- **One command, one append-only fact:** the implementation invokes `appendEvent(...)` once after all composition violations have been collected, rather than once per violation. The adapter appends through `events[]`, so this command does not replace history. The focused test's strict complete-projection comparison detects a missing event, two per-reason events, a duplicate combined event, or any other extra visible event.
- **No partial domain state:** the rejection branch only appends the approved audit fact and returns the rejected result. It does not change `processState`, `assignmentOrders`, `assignments`, or `openTasks`, and introduces no document, application, legacy-projection, or success-path write. The G test fixes every one of those public fields and therefore catches a partial state transition, order/assignment creation, or task mutation.
- **Normalization and stable ordering:** audit count is derived from the same normalized list used to enforce the installer invariant, after blank and whitespace-only values have been removed. `reasonCodes` is derived from the already ordered violations list, keeping response and audit ordering aligned without a second ordering rule that could drift. The G fixture would fail if raw input length were counted or the two reasons were reordered, omitted, or split.
- **Privacy and security:** the event exposes only the approved aggregate count, boolean presence flag, reason codes, actor, and event time. It does not include the raw installer array, installer identifiers, engineer identifier, names, full command, stack information, or storage identifiers. Exact projection comparison rejects extra payload fields. Authorization remains the first decision in the public command, preserving D's information-hiding behavior; security audit for `FORBIDDEN` and read authorization remain explicitly outside G.
- **Invariant enforcement and integration seam:** authorization, normalization, composition checks, and audit append all execute inside `InstallationProcess::prepareOrder(...)`, the common seam intended for UI and future integrations. G adds no transport-only rule, direct database access, legacy dependency, renderer, network call, or special manual-registration path.
- **Maintainability and minimality:** the implementation generalizes the existing E/F rejection-audit branch to the now-approved combined case by removing the single-violation restriction; it does not add a second branch or duplicate event construction. Deriving codes via `array_column($violations, 'code')` keeps the audit synchronized with the validated command result. The broad internal `object` environment remains a previously documented temporary seam and was not expanded by G.
- **Scope control:** there is no implementation of forbidden-attempt audit, idempotency, successful preparation, DB schema/transactions, HTTP representation, registration in 1C DO, document generation, catalog validation, or legacy synchronization. Those remain separate executable slices. The synchronous in-memory append proves the approved observable behavior but does not by itself claim a concrete production transaction implementation.
- **Test sensitivity:** the independently approved test exercises only public command and observation seams and strictly compares the full projection. Together with the A-F tests it catches plausible regressions in exact violation responses, authorization precedence, whitespace normalization, audit creation for either single reason, combined stable ordering, privacy, and unchanged state.
- **Verification:** all seven tests A-G pass in one regression run. PHP syntax checks pass for production, the in-memory environment, and the focused G test. `git diff --check` exits successfully.

## Required changes

None for the independently reviewed ORDER-PREPARE-001-G slice.

Gate 5 is approved for ORDER-PREPARE-001-G. Security audit for `FORBIDDEN`, audit-read authorization, preservation tests with pre-existing history, concrete database transaction behavior, storage failures, successful preparation, and production integrations remain outside this verdict and require their own SSD/TDD slices.
