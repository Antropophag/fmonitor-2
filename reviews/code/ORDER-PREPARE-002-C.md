# Code review: ORDER-PREPARE-002-C

- Reviewer: `Codex agent /root/order_prepare_002_c_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-002-C.md`](../../specs/ORDER-PREPARE-002-C.md), version `0.1`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-002-C.md`](../tests/ORDER-PREPARE-002-C.md)
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_002_c_test.php`](../../tests/InstallationProcess/order_prepare_002_c_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_002_c_test.php
php tests/InstallationProcess/order_prepare_002_b_test.php
php tests/InstallationProcess/order_prepare_002_test.php
for test_file in tests/InstallationProcess/order_prepare_001_test.php tests/InstallationProcess/order_prepare_001_b_test.php tests/InstallationProcess/order_prepare_001_c_test.php tests/InstallationProcess/order_prepare_001_d_test.php tests/InstallationProcess/order_prepare_001_e_test.php tests/InstallationProcess/order_prepare_001_f_test.php tests/InstallationProcess/order_prepare_001_g_test.php tests/InstallationProcess/order_prepare_001_h_test.php; do php "$test_file"; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_002_c_test.php
php -l tests/InstallationProcess/order_prepare_002_b_test.php
php -l tests/InstallationProcess/order_prepare_002_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
git diff --check
```

Observed output:

```text
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
No syntax errors detected in tests/InstallationProcess/order_prepare_002_c_test.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_b_test.php
No syntax errors detected in tests/InstallationProcess/order_prepare_002_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

Every command exited `0`; `git diff --check` produced no output.

## Standards

No documented-standard violations or material new code smells were found. The implementation is a narrow guard in the existing command orchestration and preserves the prototype's established environment boundary. The adjacent address and entrance rejection branches are structurally similar, but extracting a generalized required-field mechanism in this one-field slice would add policy and ordering abstraction beyond the approved behavior; the small duplication is preferable at this gate. The inherited `object` environment and array-shaped snapshots remain known prototype debt and are not materially worsened by this change.

## Specification and code findings

- **Conformance and exact result:** after reading the order snapshot, `prepareOrder(...)` treats `null`, `""`, and trim-empty string values as a missing entrance. It returns the exact approved `ORDER_REQUIRED_DATA_MISSING` code, Russian message, and `field = entrance`. A populated entrance proceeds unchanged into the already approved successful snapshot path.
- **Invariant order:** authorization remains first. Normalization and mandatory installer/engineer validation remain second. The order snapshot is then loaded once, address is checked before entrance, and only after both required fields pass can execution obtain the successful-path time, read either people catalog, or invoke the renderer. This matches the required `auth -> composition -> snapshot -> address -> entrance -> catalogs` sequence and preserves address priority when both fields are absent.
- **Integration boundaries:** the entrance rejection returns before `getInstallerSnapshot(...)`, `getEngineerSnapshot(...)`, and `renderAssignmentOrder(...)`. The focused fixture actively forbids all three interactions, so its green result proves the approved example cannot cross those boundaries. No legacy projection operation exists on this branch.
- **No partial changes:** before the entrance guard, the command performs only read/validation work. On rejection it calls only `appendEvent(...)`; it does not call `replaceProcess(...)`, construct or persist a version, create assignments or artifacts, close the preparation task, open installation, or enable the checklist. Strict equality over the public process projection confirms that the original state is retained with exactly one appended event.
- **Audit and PII:** the rejection event contains the approved type, authenticated actor, server timestamp, one reason code, `[entrance]`, normalized installer count, and engineer-presence boolean. It does not retain the entrance value, address or other order fields, installer identifiers or names, or the engineer identifier. Existing events are append-only through the environment contract.
- **Scope:** production behavior adds only the approved entrance guard. It does not validate entrance format or existence, combine missing fields, introduce rejections for registration number/dates, or change state, PTO, personnel, renderer, persistence, concurrency, UI, or transport behavior.
- **Regression and sensitivity:** `ORDER-PREPARE-002-B`, the successful `ORDER-PREPARE-002` example, and all eight `ORDER-PREPARE-001` slices remain green. The focused test would fail on a weakened whitespace check, changed violation or audit shape, PII leakage, a partial process mutation, or a premature catalog/renderer call. The preceding tests continue to protect authorization/composition and address precedence.
- **Boundary of approval:** the implementation handles all three normalization forms stated in the specification, while the independently reviewed executable example directly proves only whitespace-only entrance. Simultaneously missing address/entrance is protected indirectly by the earlier address test and branch order, but a separately approved executable example would be required before claiming that case independently. Other deferred preconditions remain outside this verdict.

## Required changes

None for the independently reviewed `ORDER-PREPARE-002-C` example A slice.

Gate 5 is approved. This approval is limited to early rejection of a whitespace-only entrance/section and its minimal append-only audit; it does not approve the deferred production preconditions outside this specification.
