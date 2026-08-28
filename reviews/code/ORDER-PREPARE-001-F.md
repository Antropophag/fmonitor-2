# Code review: ORDER-PREPARE-001-F

- Reviewer: `Codex agent /root/order_prepare_001_f_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after minimal ORDER-PREPARE-001-F implementation`
- Specification: [`specs/ORDER-PREPARE-001-F.md`](../../specs/ORDER-PREPARE-001-F.md)
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-F.md`](../tests/ORDER-PREPARE-001-F.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d,_e,_f}_test.php; do php "$test_file" || exit 1; done`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/InstallationProcess/order_prepare_001_f_test.php`
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
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_f_test.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** for the approved F fixture, `prepareOrder(...)` returns the already approved single `CONTROL_ENGINEER_REQUIRED` rejection and appends exactly one `assignment_order_prepare_rejected` event. `getOrderProcess(4512)` exposes the exact F projection: fixed timestamp and actor, the single reason code, `installerCount = 1`, and `controlEngineerProvided = false`, while the process remains `needs_order` with no order version or assignment and with the original `prepare_order` task unchanged.
- **No partial state and append-only history:** the only production-side operation after validation fails is `appendEvent(...)`; there is no write to process state, orders, assignments, tasks, documents, or a legacy projection. The in-memory adapter appends with `events[]` and does not replace the event collection. The focused test starts with the specified empty history and strictly compares the entire resulting projection, so it detects a missing or duplicate F event and every visible partial domain mutation. Preservation of a non-empty pre-existing event history is supported by the append operation but is not separately proved by this F example.
- **Privacy and security:** the persisted/public payload contains only the approved reason code and aggregate presence/count fields. It does not include installer tab ID `1042`, `controlEngineerUserId`, names, the command body, internal storage identifiers, or diagnostics. Exact projection comparison would fail on any extra payload or top-level field. Authorization still runs before normalization, audit, or observation behavior, preserving the approved D information-hiding boundary.
- **Invariant enforcement at the public seam:** authorization, normalization, composition validation, and rejection auditing execute inside `InstallationProcess::prepareOrder(...)`, the seam shared by UI and future integration callers. F does not rely on a tab, HTTP handler, client-side validation, direct persistence access, or a special manual-only path.
- **Scope and maintainability:** using the existing single-violation branch for both E and F is a compact generalization of the two approved audit slices. It does not prematurely audit combined reasons or `FORBIDDEN`, both explicitly reserved for later slices, and it does not implement the success path, workforce lookup, document generation, or legacy synchronization. The broad internal `object` environment dependency remains a known temporary seam from earlier approved slices; F adds only the already justified `now()` and `appendEvent(...)` capabilities and no new coupling.
- **Integration boundary:** the implementation introduces no database, network, legacy-application, renderer, or `shlz-ui` dependency. Synchronous append-before-return gives the in-memory behavior required by the approved seam. Concrete transactional guarantees for a future production adapter remain outside F and are not implied by this approval.
- **Test sensitivity:** strict comparison of the complete public projection catches the plausible regressions relevant to F: no audit, duplicate audit, wrong reason/time/actor/count/engineer flag, privacy-sensitive extra data, creation of a partial order or assignment, process-state change, or task closure/replacement. The independently approved example B test retains exact command-result sensitivity, while A–E cover installer rejection, normalization and ordering, authorization precedence, and the prior audit path.
- **Regression verification:** all six tests A–F pass together. Syntax checks for production, adapter, and focused test files pass, and `git diff --check` reports no whitespace errors.

## Required changes

None for the independently reviewed ORDER-PREPARE-001-F slice.

Gate 5 is approved for ORDER-PREPARE-001-F. Audit of combined participant violations and `FORBIDDEN`, read authorization, concrete database transactions, storage failures, successful preparation, and production integrations remain outside this verdict and require their own SSD/TDD slices.
