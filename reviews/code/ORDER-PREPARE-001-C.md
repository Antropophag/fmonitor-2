# Code review: ORDER-PREPARE-001-C

- Reviewer: `Codex agent /root/order_prepare_001_c_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `6d87c16 + uncommitted working-tree implementation after approved examples A and B`
- Specification: [`specs/ORDER-PREPARE-001.md`](../../specs/ORDER-PREPARE-001.md), example C
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-C.md`](../tests/ORDER-PREPARE-001-C.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `php tests/InstallationProcess/order_prepare_001_test.php`
  - `php tests/InstallationProcess/order_prepare_001_b_test.php`
  - `php tests/InstallationProcess/order_prepare_001_c_test.php`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/InstallationProcess/order_prepare_001_c_test.php`
  - `git diff --check`
- Verdict: `APPROVED`

## Verification evidence

```text
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_c_test.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** for approved example C, `prepareOrder(...)` removes empty and whitespace-only installer identifiers before evaluating required participants, treats the typed seam's absent engineer value as `null`, and returns both exact violations required by sections 5.1–5.3, 8, and example C. The violation accumulation order is installer first and engineer second, matching the normative stable order.
- **Invariant enforcement at the public seam:** normalization and both required-participant checks execute inside `InstallationProcess::prepareOrder(...)`, the approved process command shared by callers. They are not dependent on UI validation, transport behavior, the in-memory test adapter, or a persistence side channel.
- **Minimality and maintainability:** the production change adds one local normalization step and accumulates the two already established violations before returning. `array_values(...)` restores list shape after filtering and `array_unique(...)` implements the specified de-duplication without introducing a speculative domain object or success path. The implementation remains compact and readable for this narrow slice.
- **Security, audit, and append-only history:** this rejection path performs no domain, document, legacy-projection, or external-system mutation. Authorization precedence, rejection-audit persistence, audit counts, unchanged-state observation, and security handling for `FORBIDDEN` remain outside the approved example C test and are not claimed by this approval; they still require their own Gate 2–5 slices.
- **Integration boundaries:** the change stays within the deep process module and makes no catalog, database, renderer, legacy application, network, or `shlz-ui` call. It neither copies legacy behavior nor exposes the internal environment through the result.
- **Regression safety:** examples A, B, and C all pass together. Thus blank normalization and combined accumulation preserve the previously approved exact-empty-installer and missing-engineer outcomes. The C test's strict expected array catches omission of either reason, reversed order, incorrect normalization of its whitespace inputs, extra violations, and changes to code, message, field, or result shape.
- **Scope caveat:** the typed PHP seam accepts `?int` for the engineer, so transport-level conversion of an empty string to `null` remains outside this module test, consistently with the approved technical binding and test review. Duplicate nonblank installer counting, authorization, audit, domain-state observation, and the successful path are not covered by this verdict.

## Required changes

None for the independently reviewed example C slice.

Gate 5 is approved for example C. The next acceptance statement must restart at Gate 2 with a new failing test and independent test review.
