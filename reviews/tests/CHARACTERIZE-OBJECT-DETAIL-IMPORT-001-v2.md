# Test review: CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2 — corrected oracle v2

- Date: `2026-09-05`
- Reviewer: fresh separately tasked agent `/root/object_detail_import_gate3_v2`
- Test author: a different previously tasked agent
- Reviewed worktree: dirty authoritative worktree at HEAD `12779bb44f90ed09587e2feb26c565f1e8ac8f03`
- Specification: `CHARACTERIZE-OBJECT-DETAIL-IMPORT-001 v0.2`
- Public seam: `php tests/Verification/characterize_object_detail_import_001_test.php`, which invokes the real `rapid-pilot/import-production-object-details.php` CLI as a child process
- Verdict: `CHANGES_REQUESTED`

The reviewer did not author or edit the specification, test, importer, migration,
owner decision, or RED evidence. The only executable characterization run used
fresh private synthetic Docker containers. It did not access VPN, production or
legacy data, shared databases, production credentials, or protected E2E state.

## Reviewed identities

```text
a2e9f65a20bd6e33c740c774094508b32a33faa6e0bdf4ace498e31f024e24c9  specs/CHARACTERIZE-OBJECT-DETAIL-IMPORT-001.md
eb0988403082e823bb53fb5f6d5d315ebda3376ed3878835d1de3c91664c8c2c  tests/Verification/characterize_object_detail_import_001_test.php
668b51891c956202cc4d6fe0d37287fdf615489e56bdfa66eea087d0d899b449  exact six-line expected normalized transcript, LF after every line
09c611c95afae3b04ebeb40f1bacc6c1da1208330669a42f569f46129cb404fc  docs/operations/object-detail-import-red-evidence-v2-2026-09-05.md
442fd28a8fe5ea4628068084b0ddfaf3b8d166b58cd26339763b7dbec38791d1  docs/operations/object-detail-import-v02-owner-approval-2026-09-05.md
069f8d75334380b1b0348ab0ed60b508c6152e0cf4f8daa118827c5f79696950  rapid-pilot/import-production-object-details.php
2bc47395cdaf61974c493907a84d8f8f25dc034e10c140a8fd851ca4845ace8a  app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
```

The dated owner record explicitly approves the exact v0.2 specification hash.
Its decision supersedes the unchanged draft-status prose inside the approved
specification bytes.

## Blocking findings

### 1. HIGH — cleanup does not preserve regression classification or attempt all phases

The deadline contract requires a cleanup failure to remain `SETUP_FAILURE`
while preserving an already established regression in safe stderr and keeping
exit `1`. It also requires bounded cleanup after behavioral failure. The
`odciRun()` `finally` block instead calls `odciFail()` immediately on the first
ownership-proof, container-remove, absence-proof, unexpected-volume-remove, or
artifact-directory failure (lines 270–280). A cleanup exception raised from
that `finally` replaces the active behavioral exception. The worker then sees
only a `SETUP_FAILURE` and exits `2`. An early cleanup exception also prevents
all later cleanup phases from being attempted; for example a container removal
failure skips the absence check and artifact cleanup.

The outer meta-test has the same precedence defect in a simpler form: its
`finally` sets `$exit=2` whenever decoy cleanup or common-root preservation
fails, even if `$failure` already holds a regression (lines 323–325). This
contradicts the exact rule that an existing regression keeps exit `1` when
cleanup also fails.

Required correction: accumulate behavioral and cleanup failures separately,
attempt every independently safe bounded cleanup phase, report a safe cleanup
failure alongside any earlier regression, and implement the specified exit
precedence. Add a focused sensitivity probe that proves a regression plus a
cleanup failure remains exit `1` and that later cleanup phases are attempted.

### 2. HIGH — failed child reap can still enter an unbounded `proc_close()`

`odciProcess()` does perform TERM, KILL, pipe draining and a three-second status
poll for timeout and output overflow. If that poll still reports a running
child, however, line 36 throws `SETUP_FAILURE` from inside the `try`; the
unconditional `finally` then calls `proc_close()` at line 38. PHP's
`proc_close()` waits for process termination, so this path has no remaining
deadline and can hang beyond both the three-second reap budget and the outer
cleanup budget. The two current probes cover ordinary timeout and overflow
where termination succeeds; they do not exercise or prove the failed-reap
branch.

Required correction: make the final close/reap operation itself bounded and
ensure a failed reap cannot invoke a blocking wait. Retain a deterministic
sensitivity probe for the branch or otherwise make the bounded assertion
observable rather than dead error-handling code.

## Traceability and corrected-v1 findings

The corrected test resolves the previously recorded functional and isolation
findings on its ordinary execution path:

- it dynamically resolves the local MariaDB tag once and creates each server by
  immutable image ID;
- `--tmpfs /var/lib/mysql`, empty `.Mounts`, and the exact tmpfs key prevent the
  image-declared anonymous volume leak;
- the setup-root, target-child, and source-child passwords are distinct, and the
  importer receives no root credential;
- normalized `SHOW GRANTS` facts are compared to exact table-level allowlists,
  with broad/global/schema/role/delegating forms rejected;
- full `SHOW CREATE TABLE`, engine/collation/options, and complete rows for all
  five target fixtures are captured around every behavioral call, including all
  eight schema refusals;
- clean detail and quarantine rows are asserted column-for-column, including the
  exact detail payload bytes and all fixed metadata; replay compares the full
  accepted snapshot;
- only serial replay uses the repeat capture; clean, dry-run, conflict, source
  rejections, and all schema refusals use the first capture;
- every schema-refusal combination has the positive listener control and empty
  post-child accept queue proof;
- the common artifact root survives, the decoy uses exclusive creation plus an
  inode/device/byte latch, and the test removes neither the common root nor a
  rejected foreign decoy;
- behavioral actions use the approved external CLI argv. Test-owned target and
  source DML is limited to fixture setup, scenario arrangement/restoration, and
  independent observation; it does not reproduce importer DML.

These points establish seam choice, expected-value independence, rejection
coverage, ordinary-path isolation, and sensitivity to the missing production
behavior. They do not cure the mandatory cleanup/error-path failures above.

## Independent RED and cleanup evidence

The exact focused wrapper command produced the intended RED twice inside the
meta-test:

```text
$ tools/verification/run.sh red tests/Verification/characterize_object_detail_import_001_test.php
REGRESSION_FAILURE: real importer attempted runtime CREATE on the exact precreated v12 family under the verified DDL-denied principal
RED_ASSERTION: expected failing behavior observed in tests/Verification/characterize_object_detail_import_001_test.php
[wrapper exit 0]
```

This is a real importer failure, not a missing-verifier or setup failure. Before
the call the v12 family existed and exact child grants had been verified; the
real CLI attempted `CREATE` using the DDL-denied target principal. The meta-test
compared two complete worker results before publishing the safe category.

Reviewer pre/post cleanup checks found:

```text
docker volume ls -q | sort  # identical before and after; diff exit 0, no output
docker ps -a --filter 'label=fmonitor2.object-detail-token'  # no output after
find .test-artifacts/object-detail-import -maxdepth 2 \
  \( -name 'object-detail-*' -o -name 'ambient-decoy.txt' \) -print  # no output
test -d .test-artifacts/object-detail-import  # exit 0
php -l tests/Verification/characterize_object_detail_import_001_test.php  # no syntax errors
git diff --check  # exit 0, no output
```

No pre-existing Docker volume was inspected or removed. The common root was
preserved.

## Verdict

`CHANGES_REQUESTED`

Gate 4 remains paused. Correct the fully bounded, attempt-all cleanup and the
specified regression/cleanup classification precedence; capture a new RED at a
new exact test hash; then obtain a fresh independent Gate 3 review before any
production change.
