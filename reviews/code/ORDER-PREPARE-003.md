# Code review: ORDER-PREPARE-003

- Reviewer: `Codex agent /root/order_prepare_003_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-003.md`](../../specs/ORDER-PREPARE-003.md), version `0.2`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-003.md`](../tests/ORDER-PREPARE-003.md), independent re-review verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_003_test.php`](../../tests/InstallationProcess/order_prepare_003_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_003_test.php
php tests/InstallationProcess/order_prepare_002_e_test.php
php tests/InstallationProcess/order_prepare_002_d_test.php
php tests/InstallationProcess/order_prepare_002_c_test.php
php tests/InstallationProcess/order_prepare_002_b_test.php
php tests/InstallationProcess/order_prepare_002_test.php
php tests/InstallationProcess/order_prepare_001_test.php
php tests/InstallationProcess/order_prepare_001_b_test.php
php tests/InstallationProcess/order_prepare_001_c_test.php
php tests/InstallationProcess/order_prepare_001_d_test.php
php tests/InstallationProcess/order_prepare_001_e_test.php
php tests/InstallationProcess/order_prepare_001_f_test.php
php tests/InstallationProcess/order_prepare_001_g_test.php
php tests/InstallationProcess/order_prepare_001_h_test.php
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_003_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
git diff --check
```

Observed output:

```text
PASS ORDER-PREPARE-003 workforce eligibility
PASS ORDER-PREPARE-002-E missing required dates
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
No syntax errors detected in tests/InstallationProcess/order_prepare_003_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

Every command exited `0`; `git diff --check` produced no output.

## Standards

No documented-standard violation or material maintainability issue was found. The eligibility collector is kept inside the existing command, uses one linear pass over the normalized input, and adds no speculative abstraction. The repeated date predicates are short and directly mirror the approved rules. The inherited `object` environment and array-shaped snapshots remain prototype debt, but this slice does not worsen those boundaries.

## Specification and code findings

- **Nullable lookup and complete collection:** each normalized installer is read through `findInstallerSnapshot(...)`; a missing record becomes `INSTALLER_NOT_IN_CATALOG` and processing continues. Found records are independently validated, so the command returns every invalid installer rather than stopping at the first failure. A missing record never falls through to snapshot-field checks and therefore receives only the catalog violation.
- **Normalization, order, and exact violations:** the existing normalization removes empty values and duplicates while preserving first appearance. The loop index is consequently the approved normalized-input index. Violations remain in that order and use the exact approved codes, Russian messages, and `installerTabIds[index]` fields. Each installer contributes at most one violation.
- **Employment rules and inclusive boundaries:** eligibility requires exact `status = employed`, a string `employedFrom` no later than the Moscow order date, and an absent or string `employedTo` no earlier than both the order date and current `plannedFinishDate`. Thus a known end before planned finish is rejected even when the installer is employed on the order date. Equality at `employedFrom = orderDate` and `employedTo = plannedFinishDate` is accepted by the inclusive `<=`/`>=` comparisons; example record `4001` exercises both boundaries.
- **Business date and check ordering:** `occurredAt` is obtained after mandatory order-data validation, converted explicitly to `Europe/Moscow`, and used for personnel eligibility. The complete installer pass and rejection return precede engineer lookup, rendering, hashing, version/assignment creation, task closure, and process replacement. The focused fixture forbids engineer and renderer calls.
- **Audit and privacy:** one rejection event is appended after all installer violations have been collected. `reasonCodes` is deduplicated in first-violation order; counts are the normalized installer count and invalid-installer count. The payload contains no tab IDs, names, employment dates, engineer ID, order values, or snapshots. Exact projection equality proves that this event is the only state mutation and that versions, assignments, preparation task, work gate, and checklist gate remain unchanged.
- **Success and earlier-gate regression:** the complete-data `ORDER-PREPARE-002` success example still prepares the order, proving that eligible nullable snapshots continue into the established renderer and persistence path. The combined required-data test, B-D single-field tests, and all eight 001 tests pass, preserving required-data aggregation, authorization/composition priority, audit projection, and security-audit behavior.
- **Test sensitivity:** the approved 003 test would fail on non-nullable lookup, first-error return, skipped status/from/to rule, exclusive equality, wrong violation order/index/text, duplicated audit codes, PII leakage, downstream engineer/renderer access, or partial state mutation. The success regression catches accidentally rejecting an eligible open-ended employment snapshot.
- **Scope boundary:** this approval covers the approved in-memory public seam and example A. It does not approve catalog freshness/unavailability, malformed date formats, concurrency, engineer eligibility, opening-time recheck, successful brigade preparation, transport, or production adapters; those remain explicitly outside version `0.2`.

## Required changes

None for `ORDER-PREPARE-003` version `0.2`.

Gate 5 is approved. The approval is limited to aggregate installer workforce eligibility, its exact ordered rejection contract, and its minimal append-only non-PII audit behavior.
