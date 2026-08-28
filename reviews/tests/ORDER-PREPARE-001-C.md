# Test review: ORDER-PREPARE-001-C

- Reviewer: `Codex agent /root/order_prepare_001_c_test_review` (independent; did not author the specification or test)
- Test author: Codex, рабочая сессия `2026-08-27`
- Reviewed commit: `working tree after approved examples A and B`
- Specification: [`specs/ORDER-PREPARE-001.md`](../../specs/ORDER-PREPARE-001.md), example C
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareOrder(...)`
- Test: [`tests/InstallationProcess/order_prepare_001_c_test.php`](../../tests/InstallationProcess/order_prepare_001_c_test.php)
- Red command: `php tests/InstallationProcess/order_prepare_001_c_test.php`
- Intended failure: blank installer identifiers are not normalized and combined violations are not returned
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error: Uncaught TestFailure:
ORDER-PREPARE-001 example C must normalize blank participants and return both violations.
Expected: array containing, in order,
  INSTALLER_REQUIRED for installerTabIds,
  CONTROL_ENGINEER_REQUIRED for controlEngineerUserId
Actual: array containing only
  CONTROL_ENGINEER_REQUIRED for controlEngineerUserId
in tests/bootstrap.php:27
```

Exit code: `255`.

## Findings

- **Traceability:** the test cites `ORDER-PREPARE-001`, example C, and uses its exact command values: order `4512`, installer elements `""` and `"   "`, blank engineer normalized at the typed seam to `null`, and authorized actor `18`. The asserted result reproduces the two exact violations required by sections 5.1–5.3, 8, and example C.
- **Public seam:** the test invokes only the approved `InstallationProcess::prepareOrder(...)` process seam. The in-memory environment is used only to arrange authorization; no adapter or persistence method is read as an assertion side channel.
- **Sensitivity to normalization:** both installer array elements are nonempty at the raw PHP-array level while becoming empty under the specified blank/whitespace normalization. Consequently, an implementation that merely checks `installerTabIds !== []` fails the test, as the captured red result demonstrates.
- **Sensitivity to combined violations and stable order:** strict comparison requires one rejected result containing both complete violation records, with `INSTALLER_REQUIRED` before `CONTROL_ENGINEER_REQUIRED`. An implementation that short-circuits after either check, reverses the reasons, adds an unrelated reason, or changes a code, message, field, or result shape fails.
- **Expected-value independence:** the expected literal is taken from the approved normative example C. It is not calculated from current production behavior, prior test assertions, the environment adapter, or a persistence representation.
- **Determinism and isolation:** command inputs and authorization are fixed and in memory. The test has no clock, database, filesystem-state, network, legacy-system, production-catalog, or cross-test ordering dependency.
- **Scope:** this is the smallest test that jointly proves example C's inseparable acceptance statement: blank participant normalization followed by returning the full violation set in stable order. It does not claim coverage of audit persistence, unchanged domain state, authorization precedence, duplicate installer counting, absent-array transport normalization, or successful preparation.
- **Red cause:** independently running `php tests/InstallationProcess/order_prepare_001_c_test.php` exits `255` after reaching the production public seam. The actual result contains the already supported engineer violation but omits `INSTALLER_REQUIRED`, proving the missing blank-installer normalization/combined-result behavior rather than malformed bootstrap or fixture setup.

## Required changes

None for this one-test slice.

Gate 3 is approved for example C. Production implementation may proceed only far enough to make this reviewed test pass without changing its approved expectation; remaining acceptance statements require separate red tests and independent review.
