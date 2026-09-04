# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent worker-cleanup Gate 3 rereview v4

- Reviewer: `Codex agent /root/original_upload_worker_cleanup_gate3` (fresh
  independent reviewer; did not author the specification, test, RED evidence,
  support runner, or production)
- Review timestamp: `2026-09-04T00:08:51Z`
- Reviewed correction commit: `a0480f0b4ac3c3c01d1a10e1e0cc8dd6bbb8c606`
- Exact correction base: `9284996a557f165afbf2c23592d86b315c14447a`
- Verdict: **APPROVED**

## Exact reviewed inputs

- Owner-approved executable specification
  `specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md`: SHA-256
  `97a2527db60750089a53311856756b7db7b4682baf5c426a45503639ebde5479`
- Normative OpenSpec delta
  `openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md`:
  SHA-256
  `127eddc8a0e7b3ce270b5c704ddf6a55022de22cd3d3447592402b426256cee2`
- Corrected MariaDB two-worker verifier
  `tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php`:
  SHA-256
  `24f2fb020c981d4e3d1bd7227282223b9cc41920ef291fa1a347cfcc4878c9de`
- Unchanged worker runner
  `tests/Support/assignment_order_original_worker_runner.php`: SHA-256
  `2a9cfc055ed2262e607a6d365bb7ee21b1a0f19595a02fb6f224d08ad97cd109`
- Append-only correction evidence
  `docs/operations/assignment-order-original-upload-gate2-worker-cleanup-red-correction-2026-09-04.md`:
  SHA-256
  `f5419165e9305c4202227c6ec7436f438cfe48bd55db11835832d7d380595193`
- Prior cumulative worker-seed review retained as history:
  `reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-worker-seed-v3.md`

## Independent review

The correction changes only the verifier's process cleanup and its Gate 2
evidence. `aoocTerminateAndReap()` closes every still-owned pipe, returns when
`proc_close()` has already invalidated the process resource, and otherwise
checks the child, sends signal 9 only while it is live, and calls
`proc_close()` to reap it. The successful two-worker `finally` now delegates to
that helper, so both partially completed live workers and workers already
consumed by `aoocFinish()` have one bounded cleanup path. The existing protocol
failure path still closes all five owned worker descriptors and reaps through
`aoocFinishFailure()`.

The new pre-database fixture starts a real PHP child, closes its three pipes,
requires its zero exit through `proc_close()`, and passes the invalidated handle
through the same cleanup helper. It executes before database creation and
before the missing production-type guard. Removing only the
`!is_resource($worker['process'])` return makes this fixture fail with
`TypeError: proc_get_status(): supplied resource is not a valid process
resource`; therefore it is sensitive to the exact regression that prompted
this correction rather than merely documenting the guard.

An independent live-child probe using the exact helper body started a 30-second
PHP child and observed cleanup in under one millisecond, zero open pipes, an
invalidated process resource, and no live PID afterward. Thus the closed-handle
guard does not weaken terminate-and-reap behavior for a still-running child.

The full verifier with the test contour's correct database credential passes
the closed-process sensitivity, canonical v12 migration, and complete seed,
then reaches the intended missing production composition at
`ProductionAssignmentOrderOriginalFactory`. The correction does not alter the
specification-derived concurrency status multisets, revision counts, barrier
protocol cases, support runner, production, or expected application behavior.

## Reproduction

From a detached home-directory worktree at the exact reviewed commit:

```text
$ php -l tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php

$ php -l tests/Support/assignment_order_original_worker_runner.php
No syntax errors detected in tests/Support/assignment_order_original_worker_runner.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/assignment_order_original_upload_001_mariadb_concurrency_test.php
TestFailure: INTENDED_RED: predecessor seed complete; canonical production MariaDB/worker seam is missing: FMonitor2\AssignmentOrderOriginal\ProductionAssignmentOrderOriginalFactory
exit 255

$ closed-process fixture with only the resource guard removed
TypeError: proc_get_status(): supplied resource is not a valid process resource
exit 255

$ live-child exact-helper probe
{"elapsed":0.0002560615539550781,"processResourceAfter":false,"pipesOpenAfter":0,"pidAliveAfter":false}
exit 0

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check 9284996..a0480f0
exit 0 (no output)
```

Before the run, three unrelated pre-existing `t_aooc_identical_*` databases
were visible. The exact same three remained afterward; this reviewed run added
none. No task-owned `.verification-artifacts/aooc-*` directory and no
`assignment_order_original_worker_runner.php` process remained after the run.

## Decision

No blocking Gate 3 finding remains. The corrected verifier is demonstrably
sensitive to cleanup of an already reaped process, preserves terminate-and-reap
ownership for a live worker, reaches the intended missing-factory RED with the
correct database credential, and leaves no newly owned database, filesystem,
pipe, or process resource.

Fresh Gate 3 is **APPROVED** for exact correction commit
`a0480f0b4ac3c3c01d1a10e1e0cc8dd6bbb8c606`. Gate 4 may proceed against this
exact test/support inventory. Any subsequent test, runner, specification, or
RED-evidence byte change requires another Gate 2/3 cycle.
