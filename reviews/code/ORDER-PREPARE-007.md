# Code review: ORDER-PREPARE-007

- Reviewer: `Codex agent /root/order_prepare_007_code_review` (independent; did not author the specification, approved test, or production implementation)
- Implementation author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: `working tree / HEAD 6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-007.md`](../../specs/ORDER-PREPARE-007.md), version `0.1`, `APPROVED 2026-08-28`
- Approved test review: [`reviews/tests/ORDER-PREPARE-007.md`](../tests/ORDER-PREPARE-007.md), verdict `APPROVED`
- Production implementation: [`app/InstallationProcess/InstallationProcess.php`](../../app/InstallationProcess/InstallationProcess.php)
- Test: [`tests/InstallationProcess/order_prepare_007_test.php`](../../tests/InstallationProcess/order_prepare_007_test.php)
- Test adapter: [`tests/Support/InMemoryInstallationProcessEnvironment.php`](../../tests/Support/InMemoryInstallationProcessEnvironment.php)
- Verdict: `APPROVED`

## Verification evidence

Commands run independently from `/home/antropophag/code/fmonitor-2`:

```text
php tests/InstallationProcess/order_prepare_007_test.php
for test_file in tests/InstallationProcess/*_test.php; do php "$test_file" || exit 1; done
for php_file in app/InstallationProcess/*.php tests/InstallationProcess/*.php tests/Support/*.php; do php -l "$php_file" >/dev/null || exit 1; done
git diff --check
```

Observed result: the focused test printed `PASS ORDER-PREPARE-007 renderer failure`; the complete `tests/InstallationProcess` suite printed twenty PASS lines (including the focused test run a second time in the suite). All PHP syntax checks exited `0`. `git diff --check` exited `0` with no output.

## Standards

No documented-standard violation or material maintainability issue was found in the slice. The new failure handling is local to the renderer boundary and introduces no new abstraction or duplicated business path. Catching `\Throwable` is appropriately scoped to the single external `renderAssignmentOrder(...)` invocation: it converts any renderer-boundary failure, including a partially completed internal render, while exceptions from validation, audit persistence, successful-result assembly, and process persistence remain outside that catch.

## Specification and code findings

- **Ordering and call count:** authorization, required composition, existing-order detection, required object data, installer eligibility, and engineer eligibility all complete before the renderer call. The command invokes `renderAssignmentOrder(...)` once. The catch returns immediately, so there is no renderer retry or continuation into successful-result assembly and process replacement.
- **Exact non-disclosing response:** every caught renderer failure becomes the exact `ASSIGNMENT_ORDER_RENDER_FAILED` violation with the approved Russian message and `field: null`. The caught throwable is unnamed and never interpolated, serialized, logged to the process projection, or returned, so exception text, stack trace, template/path details, and renderer configuration do not leak.
- **No partial process facts:** artifact metadata and hashes are constructed only after the renderer returns successfully. Version, assignments, success event, process-state transition, task closure, checklist effects, and the revision-checked aggregate replacement are all below the catch's early return. The fixture's `forbidProcessReplacement()` independently proves this boundary for the failure path.
- **Audit privacy and cardinality:** the failure branch appends exactly one `assignment_order_prepare_rejected` event containing only the fixed reason code, normalized unique installer count, and engineer-presence boolean, together with the standard event envelope. It includes no person identifiers or snapshots, object data, document bytes or hashes, throwable details, paths, or configuration. The full-projection assertion detects extra events or any hidden successful fact.
- **Retry semantics:** renderer failure does not mark the preparation successful or unknown. Because the process remains `needs_assignment_order` with no order or assignment, a later ordinary invocation can rerun all validations and prepare version `1`; there is no hidden retry in the failed invocation.
- **Regression and scope:** all earlier authorization, validation, successful preparation, duplicate-preparation, concurrency, audit, and terminology tests remain green. Persistence failure after successful rendering, renderer temporary-file cleanup, infrastructure retry policy, UI, HTTP, and production adapters remain outside this slice as specified.

## Test sensitivity

The approved test is sensitive to the plausible regressions relevant to this implementation: an uncaught renderer error fails before assertions; a changed or leaking response fails exact equality; any process replacement trips the fixture guard; any partial fact, extra/unsafe audit field, missing event, or duplicate event fails exact full-projection equality; and an automatic renderer retry fails the exact call-count assertion. The valid fixture preconditions also ensure the tested call reaches the renderer only after all earlier checks.

## Required changes

None for `ORDER-PREPARE-007` version `0.1`.

Gate 5 is approved. Approval is limited to conversion of a renderer-boundary failure into the specified safe rejection, with one renderer call, no successful persistence or partial process facts, and exactly one privacy-safe rejection event.
