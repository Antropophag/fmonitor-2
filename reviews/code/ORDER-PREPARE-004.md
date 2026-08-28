# Code review: ORDER-PREPARE-004

- Reviewer: `Codex agent /root/order_prepare_004_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-004.md`](../../specs/ORDER-PREPARE-004.md), version `0.1`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-004.md`](../tests/ORDER-PREPARE-004.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_004_test.php`](../../tests/InstallationProcess/order_prepare_004_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_004_test.php
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
php -l tests/InstallationProcess/order_prepare_004_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
git diff --check
```

Observed output:

```text
PASS ORDER-PREPARE-004 control engineer eligibility
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
No syntax errors detected in tests/InstallationProcess/order_prepare_004_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

Every command exited `0`; `git diff --check` produced no output.

## Standards

No documented-standard violation or material maintainability issue was found. The implementation adds the approved invariant as one compact guard in the existing command and reuses the environment's nullable catalog lookup. It introduces no speculative abstraction or dependency. The inherited `object` environment and array-shaped snapshots remain prototype debt, but this slice does not materially worsen those boundaries.

## Specification and code findings

- **Nullable lookup and exact conjunctive rule:** `findEngineerSnapshot(...)` returns `null` for an absent catalog record, which is handled as the same business rejection rather than an infrastructure exception. A found snapshot is eligible only when `active` is the boolean `true` and `role` is exactly `construction_control_engineer`; missing keys, truthy non-booleans, inactive users, and every other role are rejected.
- **Unified non-disclosing response:** missing, inactive, and wrong-role users all produce the exact approved `CONTROL_ENGINEER_NOT_ELIGIBLE` violation, Russian message, and `controlEngineerUserId` field. The response does not reveal which directory predicate failed.
- **Ordering and integration boundaries:** authorization, mandatory composition, required order data, and the complete installer eligibility pass occur first. Installer rejection returns before engineer lookup, as retained by the green 003 fixture. Engineer rejection returns before rendering, hashing, assignment/version creation, task closure, and process replacement; all three 004 fixtures actively forbid renderer access.
- **Audit, privacy, and append-only state:** each rejected command appends exactly one ordinary process event with the approved server time, actor, singleton reason code, installer count, `controlEngineerProvided = true`, and `controlEngineerEligible = false`. Neither the response nor audit persists the selected engineer's ID, name, position, role, activity, or snapshot, and it includes no installer identity or employment facts. Exact process-projection assertions prove the event is the only change: no version, assignment, artifact, state transition, task closure, work opening, or checklist opening occurs.
- **Positive path and compatibility:** `ORDER-PREPARE-002` still accepts the exact positive combination `active = true` plus `role = construction_control_engineer` and reaches the established renderer/persistence path. The 003 workforce test, all required-data tests E-D-C-B, and all eight 001 tests remain green, preserving earlier priority, validation, audit, and authorization contracts.
- **Security and scope:** authorization remains the first command decision and the engineer check does not add external writes or legacy access. Engineer-directory unavailability, multiple-role canonicalization, role validity periods, engineer replacement, concurrency, renderer/persistence failures, UI, HTTP, and production adapters remain outside the approved slice.
- **Test sensitivity:** the approved test fails for a non-nullable lookup, an OR instead of AND, loose truthiness for `active`, accepting a wrong role, separate disclosure of the failing predicate, renderer access, audit identity leakage, extra events, or partial state mutation. The successful 002 regression catches an implementation that rejects every engineer or uses the wrong positive predicate. The 003 regression catches moving engineer lookup ahead of workforce validation.

## Required changes

None for `ORDER-PREPARE-004` version `0.1`.

Gate 5 is approved. The approval is limited to the exact active-and-role eligibility invariant, its unified rejection contract, and its minimal append-only non-identifying audit behavior at the approved in-memory public seam.
