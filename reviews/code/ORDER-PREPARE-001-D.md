# Code review: ORDER-PREPARE-001-D

- Reviewer: `Codex agent /root/order_prepare_001_d_code_review` (independent; did not author the specification, test, or implementation)
- Implementation author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after minimal Example D implementation`
- Specification: [`specs/ORDER-PREPARE-001.md`](../../specs/ORDER-PREPARE-001.md), example D
- Approved test review: [`reviews/tests/ORDER-PREPARE-001-D.md`](../tests/ORDER-PREPARE-001-D.md)
- Production change: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Verification commands:
  - `for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d}_test.php; do php "$test_file" || exit 1; done`
  - `php -l app/InstallationProcess/InstallationProcess.php`
  - `php -l tests/InstallationProcess/order_prepare_001_d_test.php`
  - `php -l tests/Support/InMemoryInstallationProcessEnvironment.php`
  - `git diff --check`
- Verdict: `APPROVED`

## Verification evidence

```text
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
PASS ORDER-PREPARE-001 example D
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/InstallationProcess/order_prepare_001_d_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

All commands exited `0`.

## Findings

- **Specification conformance:** for approved example D, actor `91` has no `assignment_order.prepare` permission and `prepareOrder(...)` returns the exact single `FORBIDDEN` violation required by sections 4, 5.4, and 9. The response contains neither `INSTALLER_REQUIRED` nor `CONTROL_ENGINEER_REQUIRED`, despite both participant inputs being absent.
- **Authorization first:** the permission check is the first executable decision in the public command, before installer normalization, participant validation, or any other environment access. An unauthorized call returns immediately, so the reviewed path cannot reach composition checks or people-catalog lookups.
- **Information hiding and security:** the forbidden result exposes only the fixed authorization reason, with `field = null`; it contains no order state, participant state, identifiers, counts, or validation details. The environment used by this slice offers only the authorization query, and the production path performs no catalog, database, renderer, legacy projection, or network call after denial.
- **Invariant at the public seam:** authorization is enforced inside `InstallationProcess::prepareOrder(...)`, the approved process seam shared by UI and future integration callers. It is not dependent on UI controls, transport validation, or test-only prevalidation.
- **Audit and append-only history:** the reviewed denial path performs no domain mutation and therefore cannot partially create an order version, assignment, document, projection, or process-task change. This verdict does not claim implementation of the separately specified append-only security audit: the approved D test review explicitly reserves audit persistence and audit visibility for later observable instrumentation and their own Gate 2–5 slices.
- **Integration boundary and maintainability:** the implementation adds one compact early guard and reuses the environment authorization capability already established by the technical seam. The broad `object` constructor type remains a temporary limitation noted in the earlier approved review; introducing a production interface or additional dependencies solely for this response-level slice would exceed the reviewed behavior.
- **Test sensitivity:** the strict example D assertion fails if authorization is omitted, performed after participant validation, supplemented with participant reasons, or changes the exact code, message, field, ordering, or result shape. Because both participant values are absent, it directly catches the plausible validation-first regression demonstrated by the captured red result.
- **Regression:** examples A, B, C, and D pass together. The new early denial preserves all previously approved behavior for authorized actors: exact missing-installer rejection, exact missing-engineer rejection, blank normalization, combined violations, and stable installer-before-engineer ordering.
- **Scope:** approval is limited to example D's response-level authorization precedence and information hiding, with A–C regression. Catalog-call observability, forbidden-attempt audit persistence, audit read restrictions, unchanged-state observation, and successful preparation remain unimplemented or unproved and require separate red tests and independent reviews.

## Required changes

None for the independently reviewed example D slice.

Gate 5 is approved for example D. The next acceptance statement must restart at Gate 2 with a new failing test and independent test review.
