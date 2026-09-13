# Gate 5 review — YII2-STAND-RESTORE-CONTROL-001

- Date: 2026-09-13
- Reviewer: independent `gate5_restore`; authored none of the specification,
  tests, OpenSpec artifacts, or implementation.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T172713Z-aa4ebc92cd/package.json`.
- Reviewed reconstructible source: candidate source
  `d38b5795ad86d420027ecf9aa35e5b0a02bca98ee8ebb3a18dd58bee64466f61`,
  executable source
  `6bd1c0c24049c4476964eca139c99eb52cd3e2f477ae3ea1d8f93490a34b1eec`,
  over base `11b8587372040ab045d1a69faeeb1432ff85e400`.
- Snapshot patch:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T172713Z-aa4ebc92cd/snapshot/source.patch`,
  SHA-256 `d2ac9d3968450f0ec764c21b30f39db52c08ff4bc399f1534b4ceae8a38b4a15`.
- Gate 3: final narrow rereview in
  `reviews/tests/YII2-STAND-RESTORE-CONTROL-001.md`, verdict `APPROVED` for
  candidate source
  `8b722f14dbae5d2955eeb4335ae4a9597f6dd9930f2e8c2204fb081bd53396fb`
  from package
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170814Z-55cf40b528/package.json`.

## Assessment

The candidate adds the command to the existing Yii2 console composition and
keeps its controller narrow. It does not call `RuntimeRecovery`, emits canonical
safe envelopes, rejects malformed admission before entering the fixture driver,
and retains the legacy CLI. The common `StandBackupBundle` reader is used by
both backup verification and restore. The six retained records in the package
are GREEN and source-bound to the exact candidate/executable hashes above.

Those positives do not establish the required restore safety contract. The
implementation can publish `RESTORE_VERIFIED` without independently observing
the materialized state, and its destructive filesystem and durable-operation
boundaries do not implement the preflight and crash guarantees stated by the
normative specification.

## Findings

1. **HIGH — post-effect verification is fixture-controlled rather than an
   independent observation of restored state.** Locations:
   `app/RuntimeRestore/StandRestoreApplication.php:45-59`;
   `specs/YII2-STAND-RESTORE-CONTROL-001.md:45-53`. The application decodes the
   payload, writes derived JSON files, hard-codes the schema/readiness summaries,
   and then checks only the fixture fields `readiness` and
   `inventory_after_restore`. It never rereads and compares DB facts/schema/AUTO
   state, artifact bytes/modes, sessions, inventory, or readiness through an
   independent verifier. Return values from `file_put_contents`, `mkdir`, and
   `chmod` are also mostly ignored, so a short/failed materialization can still
   reach the durable success record. This violates the rule that only freshly
   verified materialized state may publish confirmed success. **Correction:**
   make the driver expose a separate observation/readiness result (or reread via
   an independent application port), compare every required fact to the verified
   bundle, and treat any incomplete write or mismatch as `OUTCOME_UNKNOWN`
   before recording success. Add a test-sensitive materialization failure that
   cannot be represented merely by setting a desired fixture outcome.

2. **HIGH — target admission is vulnerable to filesystem substitution after
   preflight.** Locations: `app/RuntimeRestore/StandRestoreApplication.php:24-38,
   42-54`; `specs/YII2-STAND-RESTORE-CONTROL-001.md:33-43`. The target is checked
   with pathname `lstat`/`scandir`, but all later `mkdir`, `file_put_contents`,
   and `chmod` operations reopen pathnames without an anchored directory handle,
   no-follow/exclusive semantics, revalidation, ancestor/owner checks, or stable
   inode binding. A target or child can be replaced by a symlink between
   admission and materialization, allowing an existing/external object to be
   overwritten after the command has declared the target safe. The current
   symlink test covers only a symlink already present at initial admission.
   **Correction:** bind restore to a safely opened/validated target hierarchy and
   perform no-follow, exclusive materialization with stable identity checks; add
   a deterministic substitution-at-boundary test proving the external canary and
   prior target remain byte-identical and no success is confirmed.

3. **HIGH — restore lease and operation records are not made durably ordered.**
   Locations: `app/RuntimeRestore/StandRestoreApplication.php:38,63-71`;
   `app/RuntimeRestore/StandBackupFilesystem.php:14-16`;
   `specs/YII2-STAND-RESTORE-CONTROL-001.md:55-67`. Lease creation fsyncs the
   file but not the evidence directory, and `terminal()` appends/fsyncs the
   ledger file but does not fsync the directory when the ledger is first
   created. Definite failure unlinks the lease without a directory fsync. Thus a
   crash may lose the lease or first terminal-record directory entry, or retain
   a supposedly released lease, defeating mutual exclusion and byte-identical
   replay semantics. Only the successful pointer path happens to fsync the
   directory later. **Correction:** define and implement explicit file+directory
   durability ordering for lease acquisition, every first ledger publication,
   confirmed publication, and lease release; fault-test each boundary and prove
   replay never invokes the driver after a possibly-started effect.

4. **MEDIUM — the required legacy responsibility inventory is incomplete.**
   Locations: `tests/Architecture/yii2_stand_restore_boundary_001_test.py:5-10`;
   `openspec/changes/yii2-stand-restore-control/tasks.md:16-19`;
   `specs/YII2-STAND-RESTORE-CONTROL-001.md:88-96`. The executable check proves
   only that the legacy CLI exists and one forward-update test names it. The
   repository also has production-shaped consumers/contracts in
   `tests/Runtime/runtime_recovery_001_test.php`,
   `tests/Runtime/runtime_jobs_recovery_001_test.php`,
   `specs/PRODUCTION-RUNTIME-RESTORE-001.md`, and
   `specs/PRODUCTION-JOBS-RECOVERY-001.md`; no slice artifact enumerates the
   remaining old-format restore, historical forward migration, jobs recovery,
   schema-v22-v24 compatibility, and runbook-command responsibilities. Task 3.1
   is checked without that evidence. **Correction:** record the complete bounded
   consumer/responsibility inventory and make the architecture assertion
   sensitive to those retained responsibilities. Keeping the legacy code is the
   correct current disposition.

5. **MEDIUM — exact-package verification is incomplete against its generated
   focused plan.** Location: package `verification-plan.json` versus
   `package.json`. The plan requires the focused
   `tests/Deployment/pilot_jobs_compose_001_test.py`, but the package retains six
   records and has no record for that command. It also contains no retained
   exact-source execution of the seven existing PR #119 stand-backup tests even
   though acceptance A4 and OpenSpec task 2.1 claim that suite remains GREEN.
   During this review the seven stand-backup commands were run directly and all
   passed (transport, ownership, boundary 4/4, target 3/3, bundle 9/9,
   preservation 3/3, replay 11/11), but those direct runs are not retained
   harness records and do not cure the package lineage gap. **Correction:**
   retain the required focused-plan record and exact-source backup regression
   evidence in the refreshed Gate 5 package. Full CI remains reserved for after
   Gate 5 and is not requested locally.

## Standards axis

The Yii controller/composition boundary is appropriately small and the legacy
owner is not imported. A maintainability concern remains: protocol constants and
bundle-validation logic are still duplicated between
`StandBackupApplication` and `StandBackupBundle` (possible Duplicated Code /
Shotgun Surgery). This is subordinate to the concrete safety findings above;
the correction should consolidate only the contract needed by create/verify and
restore, without broad `RuntimeRestore` cleanup or speculative abstractions.

## Verification and state

Retained GREEN records reviewed:

- restore architecture `1789320232214350000-f7f87b0a6e3446efa4794f8960ae4290`;
- restore failure `1789320235615256000-e6371a37aa8041c8b77d0139b557b2fb`;
- restore roundtrip `1789320215464068000-710da3a4b23f4dbc9ba11d15635d910c`;
- Yii2 console `1789320219891436000-80d243725f3b48b4865a396286c363d7`;
- change verification `1789320261777738000-9681733a4f1d417eaad1f77f65348fbc`;
- architecture guard `1789320308759874000-20f92f8d555442b89eaf9347364900ab`.

Harness reports PR, CI, deployment, and publication readiness as `UNKNOWN`/false;
none is treated as approval or GREEN. No local full `make test`/`make verify` was
run, in accordance with the owner verification decision.

## Verdict

`CHANGES_REQUESTED`

Return findings 1-3 to Gate 4. Finding 4 requires the already-approved legacy
inventory expectation to be completed without widening the slice. If a
correction needs new test sensitivity for independent verification, TOCTOU, or
crash durability, it restarts at Gate 2 and requires fresh independent Gate 3
approval before a new exact-source Gate 5 review.

---

## Correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T174739Z-f3028914ab/package.json`.
- Exact reviewed source: candidate
  `bc0f1bcfea86bc5f0d6cfca7d19542b9e4d6cb3213cd71a9f1696397da0dd773`,
  executable
  `de4841ca7ba3da3610d39370b056d65e175f90a74b445451b057e69e7d293b0f`,
  over base `11b8587372040ab045d1a69faeeb1432ff85e400`.
- Snapshot patch:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T174739Z-f3028914ab/snapshot/source.patch`,
  SHA-256 `dc4e062e4e0e199e09f39f9cb8c7cf4cae24b41926691d2646ae6a93b1a34e57`.
- Verification plan SHA-256:
  `07a55834d17056f3bd09014a90455d1db0c31aa07a2aa4c19a5adf96ff8f496f`.
- The Gate 5 correction tests and executable legacy inventory have independent
  Gate 3 approval in the final correction-test rereview in
  `reviews/tests/YII2-STAND-RESTORE-CONTROL-001.md`.

### Resolved findings

The correction materially resolves four returned areas:

- materialization now uses checked exclusive writes and then rereads DB,
  schema/AUTO summaries, artifact bytes/modes, sessions and readiness before a
  success record; the short-write case proves desired fixture state alone cannot
  produce success;
- lease creation, ledger append, pointer publication and lease removal now have
  explicit file/directory fsync ordering, and the approved trace observes that
  order;
- the delivery record and architecture test enumerate all six retained legacy
  consumers/responsibilities, correctly preserving `RuntimeRecovery` and its CLI;
- all 14 generated focused commands are retained GREEN at the exact candidate,
  including all seven PR #119 backup regressions, restore acceptance,
  `pilot_jobs_compose`, governance and architecture guard. Full CI remains
  correctly deferred until after Gate 5.

### Remaining findings

1. **HIGH — canonical ledger records with an unknown terminal outcome are
   accepted and replayed instead of failing closed.** Locations:
   `app/RuntimeRestore/StandRestoreApplication.php:14-20,78-89`;
   `specs/YII2-STAND-RESTORE-CONTROL-001.md:64-67`. `records()` validates fields,
   digests and canonical bytes but never restricts `outcome` to
   `RESTORE_VERIFIED`, `RESTORE_FAILED`, or `OUTCOME_UNKNOWN`. Its call to
   `result()` maps every unknown string through the default error envelope, so a
   malicious canonical record with a matching exit/result is considered valid.
   A reviewer diagnostic replaced a successful record's outcome/result with
   canonical `UNRECOGNIZED_TERMINAL`; same-operation replay returned exit 1 and
   that attacker-controlled reason instead of exit 70 `OUTCOME_UNKNOWN`.
   Therefore malformed durable evidence is not fail closed, and the public safe
   output vocabulary is no longer bounded. **Correction:** explicitly allowlist
   terminal outcomes and validate the exact outcome-specific record schema before
   replay. Add a public-seam canonical semantic-corruption case, not only the
   existing truncated-byte case, and require `OUTCOME_UNKNOWN` before any driver
   effect.

2. **HIGH — target substitution protection still has pathname race windows that
   can modify an external object before the mismatch is detected.** Locations:
   `app/RuntimeRestore/StandRestoreApplication.php:46-64,81-85`;
   `specs/YII2-STAND-RESTORE-CONTROL-001.md:33-43`. The correction checks parent
   `dev`/`ino` before and after operations, but `mkdir`, `fopen`, `chmod`, and
   subsequent reads still resolve the pathname between those checks. In
   particular, a parent substitution after `assertDirectory()` and before
   `fopen($path, 'x+b')` writes through the substituted hierarchy; the later
   identity check can only turn the outcome UNKNOWN after the external effect.
   `chmod($path, ...)` has another follow-by-name interval after the file handle
   was validated. The deterministic fixture swaps before `assertDirectory()`, so
   it proves detection of that one ordering but not protection of the destructive
   intervals identified in the original finding. **Correction:** move target
   materialization behind a driver/filesystem boundary that can bind operations
   to safely opened directory/file handles with no-follow semantics (or an
   equivalent atomic staging/publication protocol). Add a boundary injection in
   the check-to-write interval and prove the external canary is byte- and
   mode-identical. Merely detecting the substitution after an external write is
   insufficient for the pre-destructive fail-closed guarantee.

The post-effect observation, fsync order, legacy inventory, and retained-plan
findings are otherwise resolved. Protocol duplication remains a non-blocking
maintainability smell; no broad cleanup is requested.

### Correction verdict

`CHANGES_REQUESTED`

The canonical semantic-ledger case requires a narrow Gate 2 test addition and
fresh independent Gate 3 approval. Target substitution must return to Gate 4
(and Gate 2 as well if the approved test needs a new race-boundary observer).
PR, CI, deployment and cutover remain `UNKNOWN`/unauthorized and are not treated
as GREEN.

---

## Final correction rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T180241Z-2fb99288f0/package.json`.
- Exact reviewed source: candidate
  `faf12754591fb8c1932321ff5d94d2994d65daef06f15346ae1868bb528daa24`,
  executable
  `74e28235b259ce23649dad2910a74738942c92c952dfb4590fc11ccd845071af`,
  over base `11b8587372040ab045d1a69faeeb1432ff85e400`.
- Snapshot patch:
  `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T180241Z-2fb99288f0/snapshot/source.patch`,
  SHA-256 `3677211f9065c26144411f3235814454972f2f74634e7a81aef8fefb14331075`.
- Verification plan SHA-256:
  `264c73f51361216799c202a5ab59e5d3c1c83de220ec6a3ba2f462d4c5db4ebc`.
- The two correction tests have independent Gate 3 approval in the
  `Second Gate 5 correction test review` appended to
  `reviews/tests/YII2-STAND-RESTORE-CONTROL-001.md`.

### Finding disposition

Both remaining HIGH findings are resolved without widening the slice.

`records()` now explicitly allowlists only `RESTORE_VERIFIED`,
`RESTORE_FAILED`, and `OUTCOME_UNKNOWN` before deriving or comparing the public
result. The canonical semantic-corruption case is GREEN: an internally coherent
but unknown terminal value now returns `OUTCOME_UNKNOWN` without another driver
effect, so attacker-controlled durable vocabulary cannot be replayed or exposed.

Materialization now occurs exclusively in a random, exclusively created sibling
staging directory. The application independently verifies the staged state,
rechecks the admitted target identity/emptiness, atomically renames the complete
directory onto the target pathname, fsyncs the parent, and independently verifies
the published tree before recording success. A substitution at the former
check-to-file-open boundary therefore cannot redirect any payload write: all
writes remain under staging, and the external byte/mode canary remains unchanged.
The final `rename` replaces a competing pathname atomically rather than following
it. This closes the destructive interval identified in the prior review for the
authorized sibling temporary-root contour.

The package retains all 14 generated focused commands GREEN at the exact source:
four restore/ownership commands, all seven PR #119 backup regressions,
`pilot_jobs_compose`, change verification, and architecture guard. Every record
has matching candidate and executable hashes and no missing test is reported.
The previously resolved post-effect verification, durability ordering, safe Yii2
composition, and complete legacy inventory remain present. No new blocking
standards or specification finding was found in the correction delta.

CI, PR, deployment, live drill and cutover remain `UNKNOWN`/unauthorized. This
review does not infer any of them from focused GREEN.

### Final verdict

`APPROVED`

Gate 5 passes for exact candidate source
`faf12754591fb8c1932321ff5d94d2994d65daef06f15346ae1868bb528daa24`.
The next permitted delivery step is publication of a matching committed source
and one exact-source Quality Graph CI; live restore, operational drill and
production cutover remain outside this slice.
