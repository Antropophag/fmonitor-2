# Code review: ORDER-PREPARE-002-B

- Reviewer: `Codex agent /root/order_prepare_002_b_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-002-B.md`](../../specs/ORDER-PREPARE-002-B.md), version `0.1`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-002-B.md`](../tests/ORDER-PREPARE-002-B.md)
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_002_b_test.php`](../../tests/InstallationProcess/order_prepare_002_b_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_002_b_test.php
php tests/InstallationProcess/order_prepare_002_test.php
for test_file in tests/InstallationProcess/order_prepare_001_test.php tests/InstallationProcess/order_prepare_001_b_test.php tests/InstallationProcess/order_prepare_001_c_test.php tests/InstallationProcess/order_prepare_001_d_test.php tests/InstallationProcess/order_prepare_001_e_test.php tests/InstallationProcess/order_prepare_001_f_test.php tests/InstallationProcess/order_prepare_001_g_test.php tests/InstallationProcess/order_prepare_001_h_test.php; do php "$test_file" || exit 1; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_002_b_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
git diff --check
```

Observed output:

```text
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
No syntax errors detected in tests/InstallationProcess/order_prepare_002_b_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

Every command exited `0`; `git diff --check` produced no output.

## Findings

- **Specification conformance and precedence:** `prepareOrder(...)` still checks `assignment_order.prepare` first and returns the existing authorization result without reading the order. It then normalizes and validates mandatory composition and returns those violations before loading the order snapshot. Only after both gates pass does it read `orderSnapshot.address`; a `null`, empty, or trim-empty string returns the exact `ORDER_REQUIRED_DATA_MISSING` violation required by the approved contract.
- **Early integration boundary:** the address rejection is immediately after the single order-snapshot read. It precedes `now()` for successful date calculation, both people catalogs, renderer invocation, process replacement, task or assignment mutation, and any legacy projection. The reviewed fixture explicitly forbids installer reads, engineer reads, and rendering, and the passing focused test proves none occurs for example A.
- **No partial process changes:** the rejected branch performs only `appendEvent(...)` and returns. It does not call `replaceProcess(...)`, construct or persist a version, create assignments or artifacts, close `prepare_order`, open installation, or enable the checklist. The exact public process assertion confirms all original facts remain unchanged and precisely one append-only event is added.
- **Audit and PII:** the event contains the exact server moment, authenticated actor, one reason code, `[address]`, normalized unique installer count, and the engineer-presence boolean. It does not persist the blank address, other order fields, names, installer tab IDs, or the engineer ID. The forbidden branch remains separated in the security journal, preserving the earlier non-disclosure behavior.
- **Regression safety:** the successful `ORDER-PREPARE-002` example and all eight `ORDER-PREPARE-001` slices remain green. Thus the new branch does not change the accepted snapshot/document path, authorization precedence, composition normalization, rejection audit projections, or protected security-audit behavior covered by those tests.
- **Maintainability and scope:** the implementation is a small guard at the existing orchestration seam and adds only the approved public reason. It does not speculate about other required order fields, combine missing-field errors, validate nonblank address syntax, introduce renderer/catalog behavior, or add transport/UI concerns. Array-shaped environment contracts remain prototype debt inherited from the preceding slices and are not worsened materially here.
- **Test sensitivity:** strict equality on the result and full process projection catches wrong code/message/field, missing or duplicate audit events, PII leakage, task/state/assignment/document mutation, or changed work/checklist gates. Dependency guards catch a plausible regression that moves address validation after either catalog or rendering. Because the order snapshot is required to discover the address, its read is intentionally allowed. Authorization and composition ordering continue to be covered independently by the eight `ORDER-PREPARE-001` tests.
- **Boundary of approval:** the specification states that `null` and `""` are equivalent missing values, and the implementation handles them, but this slice's independently reviewed executable example proves only whitespace. Other missing order fields, combined ordering, adapter errors, existing versions, PTO, personnel eligibility, persistence failures, and concurrency remain outside this verdict and require their own approved slices before production exposure.

## Required changes

None for the independently reviewed `ORDER-PREPARE-002-B` example A slice.

Gate 5 is approved. This approval is limited to early rejection of a whitespace-only address and its append-only audit behavior; it does not approve the deferred production preconditions listed above.
