# Code review: ORDER-PREPARE-002-D

- Reviewer: `Codex agent /root/order_prepare_002_d_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-002-D.md`](../../specs/ORDER-PREPARE-002-D.md), version `0.1`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-002-D.md`](../tests/ORDER-PREPARE-002-D.md)
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_002_d_test.php`](../../tests/InstallationProcess/order_prepare_002_d_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_002_d_test.php
php tests/InstallationProcess/order_prepare_002_c_test.php
php tests/InstallationProcess/order_prepare_002_b_test.php
php tests/InstallationProcess/order_prepare_002_test.php
for test_file in tests/InstallationProcess/order_prepare_001_test.php tests/InstallationProcess/order_prepare_001_b_test.php tests/InstallationProcess/order_prepare_001_c_test.php tests/InstallationProcess/order_prepare_001_d_test.php tests/InstallationProcess/order_prepare_001_e_test.php tests/InstallationProcess/order_prepare_001_f_test.php tests/InstallationProcess/order_prepare_001_g_test.php tests/InstallationProcess/order_prepare_001_h_test.php; do php "$test_file"; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_002_d_test.php
php -l tests/InstallationProcess/order_prepare_002_c_test.php
php -l tests/InstallationProcess/order_prepare_002_b_test.php
php -l tests/InstallationProcess/order_prepare_002_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
git diff --check
```

Observed output:

```text
PASS ORDER-PREPARE-002-D blank object registration number
PASS ORDER-PREPARE-002-C blank entrance
PASS ORDER-PREPARE-002-B blank address
PASS ORDER-PREPARE-002 example A
PASS ORDER-PREPARE-001 example A
PASS ORDER-PREPARE-001 example B
PASS ORDER-PREPARE-001 example C
PASS ORDER-PREPARE-001 example D
PASS ORDER-PREPARE-001-E audit projection
PASS ORDER-PREPARE-001-F audit projection
PASS ORDER-PREPARE-001-G combined audit projection
PASS ORDER-PREPARE-001-H security audit
No syntax errors detected in app/InstallationProcess/InstallationProcess.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_d_test.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_c_test.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_b_test.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

Every command exited `0`; `git diff --check` produced no output.

## Standards

No documented-standard violation or material new maintainability issue was found. The implementation is one narrow guard in the existing command orchestration and preserves the established environment boundary. The three required-order-field branches are now structurally repetitive, but introducing a generalized validation policy in this single slice would exceed the approved behavior and could obscure the explicitly normative field priority. The inherited `object` environment and array-shaped snapshots remain prototype debt and are not materially worsened here.

## Specification and code findings

- **Exact conformance:** `prepareOrder(...)` treats `null`, an empty string, and a trim-empty string as a missing `objectRegistrationNumber`. It returns the exact approved code, Russian message, and `field = objectRegistrationNumber`. A nonblank value is neither modified nor newly format-validated.
- **Invariant order:** authorization remains first, followed by normalized mandatory composition, one order-snapshot load, address, entrance, and then object registration number. Only after all three fields pass can execution determine the business date, read the installer or engineer catalogs, or invoke the renderer. This implements the required `authorization -> composition -> snapshot -> address -> entrance -> objectRegistrationNumber -> catalogs` sequence and preserves the earlier failure priorities.
- **Integration boundaries:** the rejection returns before `getInstallerSnapshot(...)`, `getEngineerSnapshot(...)`, and `renderAssignmentOrder(...)`. The focused fixture forbids each call, so the green result proves example A does not cross these boundaries. The branch also performs no legacy write or process replacement.
- **No partial state:** before the new guard, the command performs only authorization, normalization, validation, and a snapshot read. On rejection it invokes only append-only `appendEvent(...)`; it does not create a version, assignments or artifacts, close `prepare_order`, open installation, enable the checklist, or call `replaceProcess(...)`. Exact comparison of the public process projection confirms preservation of all prior state with one appended event.
- **Audit and PII:** the event contains the approved type, actor, server timestamp, reason code, missing-field name, normalized installer count, and engineer-presence boolean. It contains neither the missing or populated order values nor installer/engineer identifiers or personal data. The containing process supplies the observable `orderId`, consistent with the established event projection.
- **Scope:** the production diff adds only the registration-number absence guard. It does not confuse this value with the later 1С ДО order registration number, validate nonblank formatting, combine missing fields, or introduce plan-date, PTO, personnel, state, renderer, persistence, concurrency, UI, or HTTP policy.
- **Regression and test sensitivity:** D, C, B, the successful 002 example, and all eight 001 slices are green. The D test would fail for a weakened whitespace guard, wrong result or audit shape, partial state mutation, PII/order-value leakage, or any premature people-catalog/renderer call. C and B protect the preceding field guards; 001 protects authorization and mandatory-composition priority.
- **Boundary of approval:** the production condition supports all three normalization forms stated by the specification, while the independently reviewed executable example directly proves the whitespace-only form. Simultaneous missing-field priority is supported by branch order and neighboring tests but remains outside the independently executed D example. Other deferred prerequisites are not approved by this verdict.

## Required changes

None for the independently reviewed `ORDER-PREPARE-002-D` example A slice.

Gate 5 is approved. This approval is limited to early rejection of a whitespace-only object registration number and its minimal append-only audit; it does not approve deferred production prerequisites outside this specification.
