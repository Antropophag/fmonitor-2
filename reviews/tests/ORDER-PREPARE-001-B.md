# Test review: ORDER-PREPARE-001-B

- Reviewer: `Codex agent /root/order_prepare_001_b_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved Example A implementation`
- Specification: [`specs/ORDER-PREPARE-001.md`](../../specs/ORDER-PREPARE-001.md), example B
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_b_test.php`](../../tests/InstallationProcess/order_prepare_001_b_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_b_test.php`
- Intended failure: missing control-engineer rejection is not implemented after the approved Example A slice
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught LogicException: The accepted preparation path is not specified by this slice.
in app/InstallationProcess/InstallationProcess.php:36
Stack trace:
#0 tests/InstallationProcess/order_prepare_001_b_test.php(16):
   FMonitor2\InstallationProcess\InstallationProcess->prepareOrder()
#1 {main}
  thrown in app/InstallationProcess/InstallationProcess.php on line 36
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-001`, example B, and uses its exact inputs: order `4512`, installer `[1042]`, absent control engineer, and authorized actor `18`. Its expected command result is the single exact `CONTROL_ENGINEER_REQUIRED` violation required by sections 5.2 and 9.
- **Public seam:** the test invokes only the approved `InstallationProcess::prepareOrder(...)` process seam. The in-memory environment is used solely to arrange deterministic authorization and is not queried as an assertion side channel.
- **Sensitivity:** the test fails if the command accepts the missing engineer, throws instead of returning the specified rejection, returns the wrong code/message/field, adds an unrelated violation, or changes the public result shape.
- **Expected-value independence:** the expected literal comes directly from the approved normative specification. It is not copied from or calculated using the Example A implementation, an internal algorithm, persistence layout, or test adapter behavior.
- **Rejected case and scope:** this is the smallest red test for the missing-engineer rejection in example B. It intentionally does not claim coverage of rejection audit, unchanged domain state, normalization, combined violations, authorization precedence, or the successful path; each remaining acceptance statement requires its own red-green slice and independent review.
- **Determinism and isolation:** all command inputs and authorization setup are fixed and in memory. The test has no clock, database, filesystem state, network, legacy application, production catalog, or ordering dependency.
- **Red cause:** independently running `php tests/InstallationProcess/order_prepare_001_b_test.php` exits `255` at the existing explicit exception for paths beyond approved Example A. The command reached production `prepareOrder(...)` with valid setup and a nonempty installer list; therefore the failure demonstrates that the specified missing-engineer rejection is absent, rather than a broken fixture or unresolved seam.
- **Regression compatibility:** the previously approved Example A implementation is context only. No production return value or assertion from Example A was used as the source of expected values for Example B.

## Required changes

None for this one-test slice.

Gate 3 is approved for example B. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectation; remaining acceptance statements require separate red tests and independent review.
