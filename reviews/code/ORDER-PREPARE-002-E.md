# Code review: ORDER-PREPARE-002-E

- Reviewer: `Codex agent /root/order_prepare_002_e_code_review` (independent; did not author the specification, test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-27`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-002-E.md`](../../specs/ORDER-PREPARE-002-E.md), version `0.2`, `APPROVED 2026-08-27`
- Approved test review: [`reviews/tests/ORDER-PREPARE-002-E.md`](../tests/ORDER-PREPARE-002-E.md)
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_002_e_test.php`](../../tests/InstallationProcess/order_prepare_002_e_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_002_e_test.php
php tests/InstallationProcess/order_prepare_002_d_test.php
php tests/InstallationProcess/order_prepare_002_c_test.php
php tests/InstallationProcess/order_prepare_002_b_test.php
php tests/InstallationProcess/order_prepare_002_test.php
for test_file in tests/InstallationProcess/order_prepare_001{,_b,_c,_d,_e,_f,_g,_h}_test.php; do php "$test_file"; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_002_e_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
git diff --check
```

Observed output:

```text
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
No syntax errors detected in tests/InstallationProcess/order_prepare_002_e_test.php
No syntax errors detected in tests/Support/InMemoryInstallationProcessEnvironment.php
```

Every command exited `0`; `git diff --check` produced no output.

## Standards

No documented-standard violation or material maintainability issue was found. Replacing the three sequential required-field branches with one ordered field-to-message table removes repetition while retaining the business-significant order explicitly in one place. The collector remains local to the command and does not introduce an abstraction before another caller needs it. The inherited `object` environment and array-shaped contracts remain prototype debt but are not worsened by this refactor.

## Specification and code findings

- **Full collector and exact contract:** the ordered table contains exactly `address`, `entrance`, `objectRegistrationNumber`, `plannedStartDate`, and `plannedFinishDate`, with the five exact approved Russian messages. The loop evaluates every field rather than returning on the first absence and emits one `ORDER_REQUIRED_DATA_MISSING` violation per absent field.
- **Normalization and stable order:** lookup with `?? null` treats an absent key like `null`; explicit `null`, `''`, and trim-empty strings are rejected. Nonblank values are preserved and no excluded format, range, or date-order validation was added. PHP insertion order makes both `violations` and `missingFields` follow the normative five-field order.
- **Audit:** one append-only rejection event is written after collection. Its `reasonCodes` contains `ORDER_REQUIRED_DATA_MISSING` exactly once regardless of violation count, while `missingFields` contains every absent field in the same stable order. The remaining payload is limited to normalized installer count and engineer-presence boolean; no order values, installer IDs, engineer ID, names, or other personal data are recorded. `orderId`, actor, and server time remain observable through the established keyed event projection.
- **No partial state or downstream calls:** rejection occurs before business-date derivation, installer and engineer catalogs, renderer, artifact hashing, assignment/version construction, task closure, or `replaceProcess(...)`. The focused fixture forbids both catalog reads and rendering, and its exact public projection proves that only one event was appended while state, task, work, checklist, versions, assignments, and artifacts stayed unchanged.
- **Priority:** authorization still returns before composition normalization and before the order snapshot. Mandatory composition returns before the snapshot. Required-order validation starts only after both gates pass and returns before catalogs/rendering. The eight 001 tests protect authorization/composition behavior and priority.
- **Preservation of B–D and success:** the generalized collector retains the exact single-field responses and audits for address, entrance, and registration number; B, C, and D all pass. The complete-data `ORDER-PREPARE-002` example also passes, showing that the collector does not block the already-approved successful path.
- **Regression sensitivity:** E fails if either missing date is overlooked, collection stops early, ordering changes, either exact message/field changes, the reason code is duplicated, a forbidden dependency is called, or partial state/audit leakage occurs. B–D independently catch regressions in the first three field contracts; 001 catches priority regressions.
- **Scope boundary:** this verdict covers collection of the five required order fields and executable example A's simultaneous missing dates. It does not approve date formats or ordering, legacy date selection, PTO policy, personnel eligibility, process-state/concurrency handling, renderer/persistence failures, transport, or production integration.

## Required changes

None for `ORDER-PREPARE-002-E` version `0.2`.

Gate 5 is approved. The approval is limited to the reviewed five-field missing-data collector, its ordered multi-violation response, and its minimal append-only rejection audit.
