# Test rereview: CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 — oracle v4

- Date: `2026-09-05`
- Reviewer: fresh separately tasked agent `/root/object_detail_import_gate3_v2`
- Test author: a different previously tasked agent
- Reviewed worktree: dirty authoritative worktree at HEAD `e1b11daba94c09dd18b3af599e791d4fa06bebb3`
- Public seam: `php tests/Verification/characterize_object_detail_import_001_test.php`, invoking the real importer CLI as a child
- Verdict: `CHANGES_REQUESTED`

The reviewer authored none of the reviewed test, specification, production,
migration, approval, or RED-evidence bytes. The executable review used only
fresh synthetic Docker containers and did not access VPN, legacy/production
data, shared databases, production credentials, or protected E2E state.

## Pinned identities

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
e92dca0bbf7ce5050245c9b4e9338af26699aea8c3482b8c8c9b9ccc147e38f2  tests/Verification/characterize_object_detail_import_001_test.php
668b51891c956202cc4d6fe0d37287fdf615489e56bdfa66eea087d0d899b449  exact six-line expected normalized transcript, LF after every line
5197a93690db2e66321c0c42b90740445707b3d0e91cba2f332bcb3617b7abad  docs/operations/object-detail-import-red-evidence-v3-2026-09-05.md
433cc527aae0a4f3c397b76f2ea97b01dabfc98923228a475f2c5235f51cccbe  docs/operations/object-detail-import-red-evidence-v4-2026-09-05.md
442fd28a8fe5ea4628068084b0ddfaf3b8d166b58cd26339763b7dbec38791d1  docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
```

The owner record approves the exact v0.2 specification hash above and
supersedes the stale pending-status prose inside those unchanged spec bytes.

## Blocking findings

### 1. HIGH — the 300-second supervisor timeout can orphan the worker's container and importer child

The meta-test starts each worker through `odciProcess(..., 300.0)` at line 336.
That helper sends TERM/KILL only to the direct worker PID. It creates no process
group/session or other descendant containment, and the parent has no token-bound
fallback cleanup. The worker itself starts importer processes and owns the
Docker container inside `odciRun()`.

If the 300-second deadline expires while a worker is inside an importer call,
the parent can terminate the worker before its PHP `finally` executes. Its
importer grandchild is not targeted by the parent's signals, and its exact
`fm2-odci-<token>` container and artifact child are not removed by the outer
cleanup, which handles only the ambient decoy and common root (lines 345–350).
The generated token remains known to the parent, but it is never used for an
ownership proof or fallback cleanup.

This is reachable under the declared budgets: a worker includes private-server
setup plus multiple importer calls whose individual 20-second limits can
consume most of the 300-second outer budget. The specification explicitly gives
cleanup its own bounded budget after behavioral expiry, so killing the whole
worker at the behavioral-plus-cleanup boundary cannot rely on worker `finally`.

Required correction: provide real descendant containment and bounded termination
for worker plus importer, and/or parent-owned exact token/container/artifact
fallback cleanup with label/identity proof. Reserve cleanup time outside the
worker behavioral deadline. Add an executable fault probe that interrupts a
worker while its nested child and owned fixture are live, then proves child reap,
container absence, artifact-child absence, decoy preservation, and correct
failure classification without wildcard/process-name cleanup.

### 2. HIGH — the failed-reap self-check does not exercise the real process path

The v3/v4 correction prevents `proc_close()` when the local `$running` value is
true, and inspection confirms the helper is used on line 38. The only new
assertion for this critical branch, however, calls
`odciMayProcClose(true/false)` directly (line 323). That is only a truth-table
test of the one-line boolean helper. It neither creates a child that survives
TERM/KILL through the bounded reap deadline nor observes whether
`odciProcess()` avoids `proc_close()` and returns both regression and cleanup
categories. A plausible regression that bypasses the helper in
`odciProcess()` leaves this self-check green.

The ordinary timeout and overflow probes on line 324 exercise children that
terminate normally after the signal. They do not cover the unreaped branch that
caused the prior blocker. Therefore the mandatory failed-reap assertion remains
effectively untested.

Required correction: exercise the real `odciProcess()` failed-reap path through
a safely contained deterministic child, or instrument the process wrapper so a
test observes that the real close operation is not invoked while the child is
reported running. The probe must leave no descendant process.

### 3. MEDIUM — listener setup failure is outside the listener cleanup guard

`odciSchemaRefusal()` creates the listener at line 140, derives its address and
runs the positive probe on lines 141–143, but enters `try/finally` only at line
144. Address parsing failure or positive-control failure throws before the
explicit listener/probe cleanup. PHP scope teardown will ordinarily release
these resources during stack unwinding, but the test does not perform or prove
the specified attempt-all bounded listener close for these failure phases.

Required correction: put every resource-owning listener setup step inside the
cleanup guard, close whichever of listener, probe, accepted socket, or unexpected
socket was acquired, and add a setup-fault self-check that observes closure.

## Verified corrections and coverage

The earlier v2 findings are otherwise corrected in the actual code. Cleanup
phases inside a normally running worker are attempted independently, and
behavioral regression plus cleanup failure retains exit `1` with both safe
categories. A child still observed running no longer reaches `proc_close()`.
Output reads remain bounded. The common root and foreign decoy boundary are
preserved.

V4 also establishes a healthy prerequisite before the intended RED: public
`apply()` must return exact v12 and the two exact prefixed family names; the
DDL-denied target connection must return `true` from public
`isCompleteCompatible()`; and full five-table snapshots must match around that
read-only check. `WITH GRANT OPTION` is rejected before the `USAGE` special case,
and the executable grant self-check covers delegating global `USAGE`.

The real CLI seam, exact argv/capture values, immutable image-ID launch, tmpfs
mount proof, distinct credentials, exact grants, complete target snapshots and
fixed rows/payload, eight schema-refusal cases, ordinary listener control,
replay/conflict/source-rejection coverage, and test-owned-DML boundary remain
traceable to the approved specification. Test-owned DML arranges/restores
fixtures and observes results; it does not substitute for importer behavior.

## Independent RED and cleanup evidence

The exact focused command produced:

```text
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[wrapper exit 0]
```

This is the genuine two-worker RED after the exact v12/compatible/read-only
prerequisite, caused by the real importer attempting runtime `CREATE` through
the verified DDL-denied target principal.

Reviewer cleanup evidence for this ordinary RED path:

```text
docker volume ls -q | sort  # identical before and after; diff exit 0, no output
docker ps -a --filter 'label=fmonitor2.object-detail-token'  # no output after
find .test-artifacts/object-detail-import -maxdepth 2 \
  \( -name 'object-detail-*' -o -name 'ambient-decoy.txt' \) -print  # no output
test -d .test-artifacts/object-detail-import  # exit 0
ps ... | match verifier/importer names  # no surviving relevant process after run
php -l tests/Verification/characterize_object_detail_import_001_test.php  # no syntax errors
git diff --check  # exit 0, no output
```

No old Docker volume was inspected or removed. The ordinary successful cleanup
does not prove the supervisor-timeout and failed-reap paths identified above.

## Verdict

`CHANGES_REQUESTED`

Gate 4 remains paused. Add parent-level/nested-child containment and exact
fallback cleanup, exercise the real failed-reap path, and close listener
resources on control-setup failures. Capture a new RED at the resulting exact
test hash and obtain another fresh independent Gate 3 review before production
changes.
