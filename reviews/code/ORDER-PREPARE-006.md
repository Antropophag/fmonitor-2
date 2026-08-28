# Code review: ORDER-PREPARE-006

- Reviewer: `Codex agent /root/order_prepare_006_code_review` (independent; did not author the specification, revised test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-006.md`](../../specs/ORDER-PREPARE-006.md), version `0.1`, `APPROVED 2026-08-28`
- Revised approved test review: [`reviews/tests/ORDER-PREPARE-006.md`](../tests/ORDER-PREPARE-006.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_006_test.php`](../../tests/InstallationProcess/order_prepare_006_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_006_test.php
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
php -l app/InstallationProcess/InstallationProcess.php
php -l tests/InstallationProcess/order_prepare_006_test.php
php -l tests/Support/InMemoryInstallationProcessEnvironment.php
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
git diff --check
```

Observed result: the focused test printed `PASS ORDER-PREPARE-006 concurrent first preparation`; the complete `tests/InstallationProcess` suite printed nineteen PASS lines. All PHP syntax checks exited `0`. `git diff --check` exited `0` with no output.

## Standards

No documented-standard violation or material maintainability issue remains. The persistence boundary now expresses the two concurrency guarantees directly: one operation loads a consistent process/revision observation, and separate compare-and-swap operations cover the business save and rejection-audit append. The public command signature remains unchanged. The small audit retry loop updates only its expected revision and reuses an immutable event, keeping the business preparation and renderer outside the retry.

## Specification and code findings

- **Consistent observation:** `loadInstallationObjectProcessAtRevision(...)` returns the process projection and its revision as one persistence operation. `InstallationProcess` uses that exact revision for the later conditional replacement. This closes the prior window in which stale no-order state could be paired with a newer revision and overwrite the first saved order.
- **Atomic successful save:** `replaceInstallationObjectProcessAtRevision(...)` either installs the prepared process and increments the matching revision or returns the current revision without applying any losing version, assignments, artifacts, success event, task closure, or other partial state. The simulated `7 → 8` winner remains intact.
- **Distinct ORDER-PREPARE-005 behavior:** a version already present in the consistent initial observation still returns `ASSIGNMENT_ORDER_ALREADY_PREPARED` before object/catalog/renderer work. `CONCURRENT_MODIFICATION` is emitted only when the initial observation had no order and the conditional save later loses, preserving the normative distinction between the slices.
- **Revision-safe audit:** after a failed business save, the implementation constructs one fixed non-identifying rejection event, starts from the revision that caused the conflict, and retries only `appendEventAtRevision(...)`. On the simulated intervening audit append (`8 → 9`), it preserves that event and appends the rejection at revision `9`; it does not rebuild artifacts, rerender, or retry the business save. The payload correctly retains `observedProcessRevision = 7` and `currentProcessRevision = 8`, describing the business conflict rather than the later infrastructure-only audit retry.
- **Winner preservation and privacy:** exact final-projection equality preserves the winner's version, snapshots, people, assignments, artifacts, hashes, success event, process state, and task/checklist flags; it also preserves the intervening event. The losing command contributes only one rejection event containing reason, normalized unique installer count, engineer-presence boolean, and the two approved revisions. Losing tab IDs, engineer ID, names, snapshots, rendered bytes, hashes, assignments, and artifacts are absent.
- **Authorization and public seam:** authorization remains before process reads and all downstream work. The implementation is exercised through the unchanged `prepareAssignmentOrder(...)` command and observed through `getInstallationObjectProcess(...)`; concurrency methods remain internal persistence capabilities.
- **Scope:** persistence unavailability and exhaustion of audit-append retries, renderer temporary-file cleanup, idempotency after unknown result, changing orders, UI, HTTP, and production adapters remain explicitly outside this slice.

## Test sensitivity

The revised test now guards the previously missed interleavings. The scheduled save-time replacement proves the stale revision is rejected; the old separate revision-read method throws while that race is scheduled, so a split observation is red. A scheduled audit append forces one failed audit compare-and-swap, and exact equality detects loss, replacement, duplication, or ordering errors. `forbidRepeatedRendering()` proves that retry remains below the business command. Distinct winner/loser people and artifacts plus exact response and full-projection equality catch plausible merging, overwriting, privacy, audit, and automatic-retry regressions.

## Required changes

None for `ORDER-PREPARE-006` version `0.1`.

Gate 5 is approved. Approval is limited to concurrent first preparation at the approved public seam, including a consistent initial process/revision observation, one revision-checked business save, preservation of the first saved order, and revision-safe retry of only the non-identifying rejection-audit append.
