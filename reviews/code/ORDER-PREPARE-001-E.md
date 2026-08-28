# Code review: ORDER-PREPARE-001-E

- Reviewer: `Codex agent /root/order_prepare_001_e_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after minimal Example E implementation`
- Specification: [`specs/ORDER-PREPARE-001-E.md`](../../specs/ORDER-PREPARE-001-E.md)
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-E.md`](../tests/ORDER-PREPARE-001-E.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d,_e}_test.php; do php "$test_file" || exit 1; done`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/Support/InMemoryInstallationProcessEnvironment.php`
  - `php -l tests/InstallationProcess/order_prepare_001_e_test.php`
  - `git diff --check`
- Verdict: `APPROVED`

## Verification evidence

```text
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
PASS ORDER-PREPARE-001 example D
PASS ORDER-PREPARE-001-E audit projection
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_e_test.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** for the approved Example E fixture, the rejected `INSTALLER_REQUIRED` command appends exactly one `assignment_order_prepare_rejected` event and `getOrderProcess(4512)` returns the complete public projection required by sections 4–6. The state remains `needs_order`; order versions and assignments remain empty; the existing `prepare_order` task is preserved; and the event has the exact fixed time, actor, reason-code ordering, normalized installer count, and engineer-presence flag.
- **Append-only history and absence of partial domain state:** the implementation only appends to the existing `events` list after validation has produced the specified rejection. It does not replace prior events or mutate `processState`, `assignmentOrders`, `assignments`, or `openTasks`. A repeated server-accepted command would append another fact rather than overwrite history, consistent with the parent specification's one-event-per-command rule; command idempotency is explicitly outside this slice.
- **Security and privacy:** the event payload contains only the approved reason code, aggregate installer count, and boolean engineer-presence value. It does not persist installer identifiers, `controlEngineerUserId`, names, the command body, internal row identifiers, or diagnostic details. The read-authorization behavior of `getOrderProcess(...)` remains explicitly outside E and is not claimed by this verdict.
- **Transaction and integration boundary:** within the approved in-memory scope, the rejection event is appended synchronously before the rejected result is returned, while no domain or legacy projection mutation exists on this path. The change introduces no database, renderer, catalog, legacy, network, or `shlz-ui` coupling. A production adapter's concrete atomic transaction mechanism remains future work and is not falsely implied by this in-memory approval.
- **Maintainability and minimality:** `now()`, `appendEvent(...)`, and `getProcess(...)` are the smallest environment capabilities needed for the approved audit and observation seams. The production branch is intentionally limited to E's single `INSTALLER_REQUIRED` case; generalizing rejection auditing to the combined, engineer-only, and forbidden cases before their independently approved slices would exceed scope.
- **Test sensitivity:** strict comparison of the entire projection detects a missing or duplicate event, mutation or replacement of existing domain facts/tasks, extra privacy-sensitive fields, wrong event metadata or payload, and changes to the public projection shape. Example A independently retains the exact command-result assertion that E intentionally does not duplicate.
- **Regression verification:** examples A, B, C, D, and E pass together. The added event side effect and observation seam preserve the previously approved response-level behavior: exact installer and engineer violations, combined stable ordering, whitespace normalization, and authorization precedence.
- **Scope:** this verdict covers only Example E's installer-required rejection audit, unchanged public projection apart from that append, and A–D regressions. Audit for other rejection combinations, audit read authorization, database schema/transactions, storage failures, legacy projection behavior, pagination, idempotency, and successful preparation remain unimplemented or unproved and require later SSD/TDD slices.

## Required changes

None for the independently reviewed Example E slice.

Gate 5 is approved for Example E. The next acceptance statement must restart at Gate 2 under an already approved executable specification, or at Gate 1 if its observable contract has not yet been approved.
