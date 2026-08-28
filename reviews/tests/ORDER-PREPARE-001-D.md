# Test review: ORDER-PREPARE-001-D

- Reviewer: `Codex agent /root/order_prepare_001_d_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved examples A–C`
- Specification: [`specs/ORDER-PREPARE-001.md`](../../specs/ORDER-PREPARE-001.md), example D
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_d_test.php`](../../tests/InstallationProcess/order_prepare_001_d_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_d_test.php`
- Intended failure: authorization is not checked before participant validation
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught TestFailure:
ORDER-PREPARE-001 example D must reject before revealing participant violations.
Expected: array containing only
  FORBIDDEN with field null
Actual: array containing, in order,
  INSTALLER_REQUIRED for installerTabIds,
  CONTROL_ENGINEER_REQUIRED for controlEngineerUserId
in tests/bootstrap.php:27
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-001`, example D, and uses its exact command values: order `4512`, no installers, no control engineer, and actor `91` without `assignment_order.prepare`. The asserted result is the single exact `FORBIDDEN` violation required by sections 4, 5.4, and example D.
- **Public seam:** the test invokes only the approved `InstallationProcess::prepareOrder(...)` process seam. The in-memory environment arranges the actor as unauthorized by withholding permission and is not queried as an assertion side channel.
- **Authorization-before-validation sensitivity:** both participant inputs are absent, so validation-first behavior produces the two participant violations and fails strict comparison. Only an authorization decision made before exposing composition validation can produce the specified sole `FORBIDDEN` result for this example.
- **Information hiding:** strict comparison rejects any response that adds `INSTALLER_REQUIRED`, `CONTROL_ENGINEER_REQUIRED`, or other order/composition information, as well as a changed code, message, field, or result shape. This proves response-level non-disclosure for example D. It does not by itself observe whether people catalogs were called or whether the required security audit was persisted; those are separate acceptance statements requiring dedicated observable instrumentation/tests.
- **Expected-value independence:** the expected literal is taken directly from the approved normative example D and section 5.4. It is not derived from the current implementation, earlier tests, adapter behavior, or a storage representation.
- **Determinism and isolation:** all command inputs and authorization setup are fixed and in memory. The test has no clock, database, filesystem-state, network, legacy-system, production-catalog, or cross-test ordering dependency.
- **Scope:** this is the smallest red test for example D's response-level authorization precedence and information hiding. It intentionally does not claim coverage of forbidden-attempt audit persistence, inability of the actor to read that audit, absence of catalog calls, unchanged domain state, or authorized composition checks already covered by examples A–C.
- **Red cause:** independently running `php tests/InstallationProcess/order_prepare_001_d_test.php` exits `255` after reaching production `prepareOrder(...)`. The actual result is the already implemented pair of missing-participant violations, demonstrating that authorization is not yet checked before participant validation; the failure is not caused by bootstrap, fixture setup, or an unresolved public seam.

## Required changes

None for this one-test slice.

Gate 3 is approved for example D. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectation; catalog non-access, security audit, and remaining acceptance statements require separate red tests and independent review.
