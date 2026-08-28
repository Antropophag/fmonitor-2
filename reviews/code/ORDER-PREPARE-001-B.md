# Code review: ORDER-PREPARE-001-B

- Reviewer: `Codex agent /root/order_prepare_001_b_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `6d87c16 + uncommitted working-tree implementation after approved Example A slice`
- Specification: [`specs/ORDER-PREPARE-001.md`](../../specs/ORDER-PREPARE-001.md), example B
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-B.md`](../tests/ORDER-PREPARE-001-B.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `php tests/InstallationProcess/order_prepare_001_test.php`
  - `php tests/InstallationProcess/order_prepare_001_b_test.php`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/InstallationProcess/order_prepare_001_b_test.php`
  - `git diff --check`
- Verdict: `APPROVED`

## Verification evidence

```text
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_b_test.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** for approved example B, `prepareOrder(...)` receives installer `[1042]` and `controlEngineerUserId = null` and returns the exact single `CONTROL_ENGINEER_REQUIRED` violation required by sections 5.2 and 9, including its message, field, ordering, and public result shape.
- **Invariant at the public seam:** the missing-control-engineer guard is enforced directly in `InstallationProcess::prepareOrder(...)`, the approved process seam shared by UI, integration, and service callers. It is not delegated to transport validation or the test adapter.
- **Standards and maintainability:** the production change is the minimal second guard needed for B, preserves the already approved A behavior, and adds no speculative success path, persistence structure, or integration coupling. The literal result duplicates the established violation shape, but extracting an abstraction in this two-case slice would be speculative; later combined-validation behavior may provide the justified seam for refactoring.
- **Security, audit, and append-only history:** the reviewed rejection path performs no domain mutation and does not call a catalog, renderer, database, legacy projection, or other production integration. This approval does not claim that authorization precedence, rejection audit, unchanged-state observation, or append-only persistence is implemented: the approved B test explicitly reserves those acceptance statements for later independent slices.
- **Integration boundaries:** B changes only the deep process module and consumes no private legacy or `shlz-ui` implementation. The environment remains an internal constructor dependency and is not exposed through the command result.
- **Regression sensitivity:** the approved B test fails if the missing engineer is accepted, throws, returns the wrong code/message/field, adds an unrelated violation, or changes the result shape. Running example A alongside it proves the new guard did not regress the previously approved empty-installer behavior.
- **Scope caveat:** combined missing-participant reporting, whitespace normalization, authorization, audit persistence, and the path with both participants selected remain deliberately unimplemented. They must proceed through their own Gate 2–5 cycles and are not covered by this verdict.

## Required changes

None for the independently reviewed example B slice.

Gate 5 is approved for example B. The next acceptance statement must restart at Gate 2 with a new failing test and independent test review.
