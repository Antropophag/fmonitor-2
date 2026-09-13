# Independent Gate 5 code review — YII2-DISPOSABLE-RESTORE-REHEARSAL-001

- Review date: 2026-09-13.
- Reviewer: separately tasked `gate5_restore_rehearsal`; authored none of the normative contract, tests, Gate 4 implementation, or verification evidence.
- Controlling Gate 3 review: `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, verdict `APPROVED`.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T193718Z-a66201a3f7/package.json`.
- Exact reviewed source: reconstructible snapshot over base `f4dab7d6b576edc3bb6e9578a939e90b9a1f36ae`, head `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `ccdb416ed6e4cc86988311c14103ce85505c06c63c3e6a50162d83666d3bb367`, executable source `3f63b477318b2b621773aaeb514efa9206b4abd54c5da39f296fe9bab97f6b5f`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T193718Z-a66201a3f7/snapshot/source.patch`, SHA-256 `74f910b87cf12686075888090fbafe0e90e92c77f53e567cfd9101a17eda1013`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T193718Z-a66201a3f7/verification-plan.json`, SHA-256 `33f48006785f0c40e3d14500d0a12c4a97f9a79c05a8880552c59247b7360fb8`.

## Findings

1. **CRITICAL — the rehearsal runner performs destructive effects outside the application owner's authorization and attestation boundary.** Locations: `tools/delivery/yii2-disposable-restore-rehearsal.php:7-17`; contract `specs/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, sections 1–3. The runner treats equality between an environment variable and caller-supplied path as action authorization, parses that JSON directly, and then executes `docker compose stop` and interpolated `DROP DATABASE`/`CREATE DATABASE`. It never loads `StandRestoreAuthorization`, binds authorization id/scope/expiry/operation/bundle/target/source/image/observed identities, or re-attests immediately before these effects. Thus the compose project, service names, database and credential file used for the most destructive phase are merely caller-controlled names, while `StandRestoreApplication` is bypassed. This violates single application ownership and permits a malformed or stale action package to target an unintended database. **Correction:** move destruction/recreation behind the existing application owner and the validated driver port; require a separately issued exact action authorization, perform exact immutable observation immediately before every effect, and make the runner only orchestrate public seams and independent observations. Add a public-seam test proving malformed, expired, wrong-scope, mismatched and drifted action packages cannot stop or mutate anything.

2. **HIGH — authorization parsing is not exact and does not bind the requested action to its scope or pinned runtime.** Locations: `app/RuntimeRestore/StandRestoreAuthorization.php:19-29`. `load()` and `loadForBackup()` both accept either restore or backup scope; extra keys and secret values are accepted; `authorization_id`, `health`, `evidence_root`, `database_user`, credential map shape, service/volume names, and the manifest's source/image/topology values are not validated or cross-bound. In particular a restore command accepts `disposable-stand-backup`, the declared source/image need only be syntactically shaped rather than equal to the reviewed/pinned target, and arbitrary health URLs can certify success. The volume observer also accepts a volume name as a substitute for the required observed immutable ID (`ProductionStandRestoreDriver.php:17`). **Correction:** define exact canonical schemas per action, reject unknown keys and embedded secrets, require the exact scope, validate all absolute/private paths and URL allowlists, bind authorization fields to the admitted manifest and reviewed source/image/topology, and compare only real immutable engine IDs. Add sensitivity cases for every binding and for name-equals-observed-ID substitution.

3. **HIGH — the production restore driver does not implement the required database restore or independently verified outcome.** Locations: `app/RuntimeRestore/ProductionStandRestoreDriver.php:13-20`. It streams the SQL dump into the existing database without proving it is empty or recreating it with the migration principal, so rollback mode (which performs no prior drop) will commonly collide with existing schema/data and cannot restore the known-good state. It never verifies schema inventory, AUTO_INCREMENT by controlled insert, DB/history/job facts, artifact/session bytes and modes, or authenticated/golden flows. Two caller-selected health URLs plus another identity observation are enough for the driver to return `RESTORE_VERIFIED`, after which the application publishes the confirmed record/pointer. This contradicts the required effect completeness and confirmed-outcome ownership. **Correction:** implement an exact, attest-guarded empty/recreate/import path and return structured phase evidence; have the application validate all required fresh post-restart observations before publishing success. Exercise the real driver through a deterministic production-shaped adapter or authorized disposable environment; recording traces alone are insufficient.

4. **HIGH — roundtrip and rollback orchestration cannot substantiate the outcomes it publishes.** Locations: `tools/delivery/yii2-disposable-restore-rehearsal.php:14-30`. Roundtrip reuses one authorization file for a distinct `backup_operation_id`, but `loadForBackup()` requires its `operation_id` to equal that backup UUID; it also expects the newly created bundle digest to equal a digest already embedded in that same authorization. Rollback performs no candidate failure injection/predicate evaluation, no separate known-good bundle verification, no target recreation, and no second explicit restart. Lines 28–29 simply label `known_good_bundle_verified`, `separate_authorization`, `target_reattested`, `second_restart`, and `FAILED_RETAINED` as verified without observing them or writing/checking the candidate-failure evidence required by the test. The authorized destructive branches were skipped, so these contradictions remain hidden. **Correction:** use distinct canonical backup, candidate, and rollback packages/UUIDs; retain and verify the actual failed candidate evidence; perform a separately authorized known-good restore plus second restart and assertions; never synthesize assertion labels.

5. **HIGH — current tests are insensitive to the actual production path and the package is incomplete against its own focused plan.** `yii2_disposable_restore_driver_001_test.py` injects `RecordingStandRestoreDriver`, so it cannot catch invalid Docker observation, database import/recreate, volume materialization, health, or subprocess behavior. The real roundtrip/rollback tests skip unless destructive authorization exists, and no such authorization was provided. The package records only eight GREEN commands; the plan additionally requires `pilot_jobs_compose_001_test.py`, `change_verification_001_test.py`, and `architecture_guard_001_test.py`, for which no evidence is present. `make test` remains correctly deferred to exact-source CI. **Correction:** route the production-driver and runner gaps back through Gate 2/3, add sensitive non-destructive driver-port/process witnesses, and obtain fresh evidence for every focused-plan command before Gate 5 resubmission.

## Evidence and deferred state

The eight package records are source-bound and GREEN for architecture boundary, inventory, admission value parsing, recording-driver behavior, unauthorized runner rejection, and preserved PR #124 failure/roundtrip tests. They do not execute the authorized destructive branches and therefore do not prove a real MariaDB/volume/runtime roundtrip or rollback. No destructive action was run during this review.

Harness reports `action_authorized: false`, PR/CI `UNKNOWN`, deployment `UNKNOWN`, and `merge_ready: false`. These states are not treated as GREEN, approval, publication, deployment, or permission to use live credentials/targets. Full CI remains deferred until a corrected exact committed source; production deployment/cutover remains separately unauthorized.

## Verdict

`CHANGES_REQUESTED`

Gate 5 does not pass for candidate source `ccdb416ed6e4cc86988311c14103ce85505c06c63c3e6a50162d83666d3bb367`. Correct the owner-bound destructive seam, exact authorization/attestation, real driver outcome, and runner semantics; independently approve the required test delta at Gate 3; capture a fresh exact-source package with the complete focused plan GREEN; then resubmit to independent Gate 5. Authorized disposable roundtrip/rollback, exact-source CI, PR/publication, and deployment remain deferred and must be reported separately.

---

## Gate 5 correction rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T195214Z-2132e33816/package.json`.
- Corrected exact source: reconstructible snapshot over base/head `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `5cbd5155f00734542387eb7e28aedde9570cb3b9ec1b89164ba9a2f41bba2526`, executable source `846ef51a2368db4fc06ac9dedece483d5ac891d200ad177818849c94e898cb82`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T195214Z-2132e33816/snapshot/source.patch`, SHA-256 `d5e8263bd3fbd4000188fa62754edd62fb763792e0ae592a5214d9f85d15fcce`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T195214Z-2132e33816/delta.patch`.
- Verification plan SHA-256: `0bc424f91d5f0091c04e9f2943e14d21aebb9243678868250c9d6cf5fa10db96`.
- Controlling test-delta review: `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, section “Post-Gate-5 correction test-delta review,” verdict `APPROVED`.
- Reviewer independence is unchanged.

### Prior finding disposition

Prior findings 1, 2, 4, and 5 are resolved. Destruction/recreation now occurs inside `StandRestoreApplication`'s leased driver operation after exact authorization loading and preflight; the runner only composes public backup/verify/restore seams. Authorization uses per-action exact schemas, binds operation/bundle/target/source/image/compose/project/database/evidence and target volumes to the admitted manifest, validates private credential references and local health URLs, and the driver compares authorization values to hashed engine observations rather than accepting names as identities. Roundtrip and rollback use distinct nested authorization references and operation UUIDs, and rollback reads a retained candidate-failure record instead of manufacturing the candidate UUID. The package contains all eleven focused-plan commands, each GREEN and bound to the corrected candidate/executable source.

Prior finding 3 is only partially resolved. The driver now recreates the database, restores through the application owner, restarts the runtime, and returns a structured evidence map which the application requires before publishing `RESTORE_VERIFIED`. Two production completeness defects remain.

### Remaining findings

1. **HIGH — the owner can confirm `RESTORE_VERIFIED` without verifying the exact independently specified state.** Locations: `app/RuntimeRestore/ProductionStandRestoreDriver.php:12`; `app/RuntimeRestore/StandRestoreApplication.php:93`; contract sections 3–4. The evidence map proves only `SELECT 1`, a nonzero table count, a nonempty AUTO_INCREMENT query result, a nonzero rehearsal-table row count, one job count, existence of any artifact/session file, health responses, and authorization-provided golden hashes. It does not compare the literal sentinel/history rows, exact schema/migration inventory, expected next id via a controlled real insert, artifact bytes/hash/mode, session identity outcome, outbox/lease/recovery facts, or duplicate absence required by the contract. Because these expected facts are not carried in an independently bound input, the application cannot distinguish a partial/wrong restore from the required known state; nevertheless all booleans can be true and the owner publishes its confirmed pointer. The outer authorized acceptance test would notice some errors later, but by then the application has already incorrectly published success. **Correction:** bind the independently specified expectation inventory into immutable authorization/operation input, return exact observations, and have `StandRestoreApplication` compare every required value before appending `RESTORE_VERIFIED` or publishing the pointer. Add a recording/production-shaped test in which each plausible wrong-but-nonempty state is rejected as UNKNOWN with retained lease/no pointer.

2. **HIGH — artifact and session restore is destructive in-place extraction, not the required safe staging/fsync/rename materialization.** Location: `app/RuntimeRestore/ProductionStandRestoreDriver.php:11`; contract section 3. `volume()` runs `find <live-root> -delete` and then `tar -xpf` directly into that same live root. A short write, corrupt archive, process loss, capacity failure, unsafe archive entry, or extraction error leaves the live target partially deleted/materialized. Returning `OUTCOME_UNKNOWN` and retaining the lease is correct ambiguity bookkeeping, but it does not implement the specified safe staging/fsync/rename boundary or validate archive members before they affect the target. **Correction:** validate the archive inventory, extract into an exclusive attest-bound staging root, verify bytes/modes and fsync files/directories, re-attest, then atomically exchange/rename into the live root while retaining recoverable prior state until publication. Add deterministic short/corrupt/traversal/capacity/interruption witnesses proving no unapproved path access and no partially published live target.

### Evidence and deferred state

All eleven focused records in the package are GREEN and source-bound. The recording-driver suite covers admission, credential timing, ordered attest/effect events, pre-effect rejection, post-effect UNKNOWN, retained lease, append-only record, contender blocking, replay stability, and conflict. The guarded runner tests cover no-authority rejection; their real destructive branches remain skipped because no action package exists. Consequently this evidence establishes the corrected non-destructive seams but is not actual disposable roundtrip or rollback evidence.

Harness reports `action_authorized: false`, PR/CI `UNKNOWN`, deployment `UNKNOWN`, and `merge_ready: false`. No destructive action was performed in this rereview. Exact-source full CI, PR/publication, authorized roundtrip/rollback, deployment, and production cutover remain deferred and are not treated as GREEN.

### Correction verdict

`CHANGES_REQUESTED`

Gate 5 remains blocked for candidate source `5cbd5155f00734542387eb7e28aedde9570cb3b9ec1b89164ba9a2f41bba2526`. Route the exact-integrity and safe-volume-publication test gaps through Gate 2/3, correct the production driver/application boundary, capture a new complete exact-source focused package, and resubmit for independent Gate 5. This verdict does not authorize the destructive branch, CI, publication, or deployment.

---

## Final non-destructive Gate 5 correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T200751Z-40e73a50d4/package.json`.
- Exact reviewed source: reconstructible snapshot over base/head `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `a3c8b282813058b4a424bfc5596eccdfe0f271ec40110866a07473e52bdb598c`, executable source `ba532e1b125f084a5d97b9b84746f9cf6a29431b9e69dcba6913ee1e18de9e7a`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T200751Z-40e73a50d4/snapshot/source.patch`, SHA-256 `0d5ba5062e59773d85b93ce905be6432a70f2300b0336af389a0baeb27eaea0c`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T200751Z-40e73a50d4/delta.patch`.
- Verification plan SHA-256: `51bf671b4e786b602321b9bb6d141914c5ade1a9744ff2ba82064d2d714b82c7`.
- Controlling narrow test-delta review: `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, section “Second Gate-5 correction v2 — narrow Gate 3 review,” verdict `APPROVED`.
- Reviewer independence is unchanged.

### Remaining finding disposition

No findings remain in the reviewed non-destructive implementation scope.

Prior finding 1 is resolved. The exact authorization schema now requires a canonical `expected` inventory containing sentinel/history rows, schema digest, controlled next id, jobs, outbox, lease and recovery rows, and artifact/session path and hashes plus artifact mode. The production driver compares direct post-restart observations to those independently supplied values, performs the controlled insert and verifies its generated id, checks live/ready and golden hashes, and returns the complete evidence map. `StandRestoreApplication` requires every evidence category to be exactly `true`; the approved `evidence-mismatch` case proves incomplete/mismatched evidence becomes a durable `OUTCOME_UNKNOWN`, retains the lease, and publishes no confirmed pointer.

Prior finding 2 is resolved by implementation inspection. Artifact and session archives are written to operation-specific staging directories inside the same mounted volume. The driver rejects absolute/parent-traversing archive names, rejects extracted symlinks, verifies the expected path/hash and artifact mode, fsyncs staged files and directories, then renames the existing root aside and the staged root into place and fsyncs the containing directory. Publication occurs only after the preceding exact attestation; any subprocess, validation, fsync, or rename failure propagates as `OUTCOME_UNKNOWN` through the application with its lease retained and no confirmed pointer. The prior live-root `find ... -delete; tar ... -C <live-root>` path is gone.

The complete candidate also retains the previously reviewed corrections: one application owner and durable PR #124 ledger/lease/replay/conflict semantics; exact per-action authorization, target/manifest/source/image/topology binding and immutable engine observations; credentials read only after admission and omitted from argv/output/evidence; database recreation/import, restart and exact integrity evidence; orchestration-only runner with distinct backup/restore/rollback UUIDs and authorization references; retained actual candidate-failure evidence; and preserved legacy `RuntimeRecovery` inventory.

### Evidence and deferred actions

All eleven focused-plan records are GREEN and bound without reported drift to candidate source `a3c8b282813058b4a424bfc5596eccdfe0f271ec40110866a07473e52bdb598c` and executable source `ba532e1b125f084a5d97b9b84746f9cf6a29431b9e69dcba6913ee1e18de9e7a`. They cover architecture/inventory, authorization, application-driver ordering and ambiguity, guarded runner behavior, PR #124 restore regressions, jobs composition, governance, and architecture guard. `git diff --check` is clean.

The real destructive roundtrip and rollback branches were not executed because harness reports `action_authorized: false`. This approval is therefore code readiness for the reviewed exact source, not disposable rehearsal evidence. PR/CI and deployment remain `UNKNOWN`; full exact-source Quality Graph CI, separately authorized disposable roundtrip/rollback, publication/merge, deployment, and production cutover remain deferred and are not inferred GREEN.

### Controlling verdict

`APPROVED`

Gate 5 passes for exact candidate source `a3c8b282813058b4a424bfc5596eccdfe0f271ec40110866a07473e52bdb598c`. Root must preserve the reviewed bytes when committing/publishing, obtain the required exact-source CI, and continue to report the authorized destructive rehearsal and deployment as separate deferred gates. This review authorizes none of those actions.

---

## Final documentation/bookkeeping Gate 5 review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T201355Z-07c968883f/package.json`.
- Exact reviewed source: reconstructible snapshot over base/head `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `1e2fd475d5be43dd26eb4cbbbbc7bb26d33530122d3afa5eea0dce164ab014ce`, executable source `7e65e367427ef621debecaf118a9ef30d6c49f277f07448c73701d17da71fb61`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T201355Z-07c968883f/snapshot/source.patch`, SHA-256 `6d62559d2ca60f1a0e474bf8a8b2073b146741845cddb0558e16e7c5da3532d9`.
- Documentation delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T201355Z-07c968883f/delta.patch`.
- Verification plan SHA-256: `dee0bbb17c723bbe8ed16697590d4beab3da698190628c4222023a0ed017efc1`.
- Baseline implementation approval: preceding “Final non-destructive Gate 5 correction review,” candidate `a3c8b282813058b4a424bfc5596eccdfe0f271ec40110866a07473e52bdb598c`, verdict `APPROVED`.
- Reviewer independence is unchanged.

### Assessment

No findings.

The delta adds the previously planned runbook and safe repository evidence summary, marks only Gate 5 preparation/review and the executable legacy-responsibility inventory complete, and leaves real stand preparation, roundtrip, rollback, CI and delivery tasks unchecked. It does not modify the approved production implementation or executable tests.

The runbook requires newly created disposable identities, exact pinned source/image/compose inputs, separate canonical roundtrip and rollback packages, distinct inner authorization scopes and UUIDs, private credential-file references, exact observed identities and expected facts, and an explicit owner statement authorizing the exact package digests. Commands are presented only under “Execution after authorization”; the document explicitly excludes production credentials/targets, production deployment/cutover, legacy deletion, and a local full suite. It correctly assigns destructive effects and confirmed outcome to `StandRestoreApplication` and describes drift/partial effects as `OUTCOME_UNKNOWN` with retained lease/no pointer. No secret value, production credential, live target identity, private payload, or executable authorization package is persisted in the documentation.

The safe evidence summary accurately distinguishes reviewed implementation and 11/11 focused recording/non-destructive evidence from operational proof. It records `action_authorized=false`; every real backup, destructive restore, restart/integrity assertion and rollback item is `NOT RUN`; PR, exact-source CI and deployment are `UNKNOWN`; and it explicitly says none is inferred GREEN. It retains the outstanding `RuntimeRecovery` responsibilities and lists disposable evidence, CI and separate production authorization as prerequisites. There is no overclaim of merge readiness, deployment, production cutover or legacy retirement.

All eleven focused-plan records are freshly GREEN and source-bound to candidate `1e2fd475d5be43dd26eb4cbbbbc7bb26d33530122d3afa5eea0dce164ab014ce` / executable source `7e65e367427ef621debecaf118a9ef30d6c49f277f07448c73701d17da71fb61`. Harness reports `action_authorized: false`, PR/CI `UNKNOWN`, deployment `UNKNOWN`, and `merge_ready: false`. No destructive command or action package was executed during this review.

### Controlling verdict

`APPROVED`

Gate 5 passes for exact candidate source `1e2fd475d5be43dd26eb4cbbbbc7bb26d33530122d3afa5eea0dce164ab014ce`. Root must preserve these reviewed bytes for commit/publication. Exact-source CI, authorized disposable roundtrip/rollback, PR/merge, deployment and production cutover remain distinct deferred actions; this documentation review authorizes none of them.

---

## Runtime-configuration blocker correction Gate 5 review — 2026-09-13

- Reviewer: independent `gate5_runtime_mismatch`; authored none of the reviewed production code, tests, or evidence.
- Exact package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T205524Z-860375cbdd/package.json`.
- Reviewed reconstructible source over base/head `56a65b5d0e166721e3c6aa62f7a96378fe45dbee`: candidate source `db0b6c09ab43f6faddec218de2af49a28ba4c43fd09c982eae4874621f4eaa29`, executable source `8400429fbafcd9eebd434b2a223ec381a3a7eaf3ce6bd91b4d7251cc240485ca`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T205524Z-860375cbdd/snapshot/source.patch`, SHA-256 `0785621bcbd931730f0d826c09055803df4cea723c4d4a8c212c23c6857bf288`.
- Controlling test review: `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, sections “Runtime-configuration correction v2” and “Runtime-configuration post-implementation test-delta review,” both `APPROVED`.

### Assessment

No findings remain in the blocker-only correction delta.

`StandRuntimeConfiguration` is the single validated owner of the canonical process-table prefix and the artifact/Yii-session children relative to `FMONITOR_SESSION_STATE_ROOT`. Both production drivers receive that configuration and the shared `StandProcess` port; their native default preserves the production subprocess behavior. Backup archive, restore materialization, and post-restore evidence consistently use the configured state-relative paths. Restore evidence constructs the jobs, job-event, outbox-intent, outbox-attempt, worker-heartbeat, and rehearsal-probe identifiers through the shared prefix owner. The drivers contain no second list of those table names and no legacy `/state/fmonitor2/...` path.

The approved process-boundary test invokes both real production drivers with prefix `fm2_`, records emitted argv/stdin length/environment names, requires exact `fm2_fm2_*` identifiers and `/state/artifacts` plus `/state/yii-sessions`, and rejects unprefixed/legacy names and the doubled `/state/fmonitor2/...` layout. It is therefore sensitive to missing, doubled, or wrongly placed prefixes and paths rather than merely checking source tokens. The configuration test separately rejects invalid prefixes and paths outside or below the direct managed-state children.

Secret handling is unchanged in substance: the database secret is read only after admission/preflight, is supplied through the subprocess environment, and is not placed in argv, returned evidence, or the recording test's captured values. The correction introduces no new state owner, destructive operation, scope expansion, or duplicated hard-coded runtime identity.

### Evidence and deferred state

The exact package contains all 13 planned focused records, each GREEN and source-bound to candidate `db0b6c09ab43f6faddec218de2af49a28ba4c43fd09c982eae4874621f4eaa29` / executable source `8400429fbafcd9eebd434b2a223ec381a3a7eaf3ce6bd91b4d7251cc240485ca`. During this review the two new PHP boundary tests were rerun and GREEN, all four changed production PHP files passed `php -l`, and `git diff --check` was clean.

Harness reports `action_authorized: false`, PR/CI `UNKNOWN`, deployment `UNKNOWN`, and `merge_ready: false`. No destructive action or authorization package was executed during this review. Authorized disposable roundtrip/rollback, exact-source full CI, publication/merge, deployment, and production cutover remain separate deferred actions and are not inferred GREEN.

### Controlling verdict

`APPROVED`

Gate 5 passes for the runtime-configuration blocker correction at exact candidate source `db0b6c09ab43f6faddec218de2af49a28ba4c43fd09c982eae4874621f4eaa29`. Root must preserve the reviewed implementation and test bytes when recording or publishing the next candidate. This review authorizes no destructive rehearsal, CI, publication, merge, deployment, or cutover.

---

## Jobs-readiness blocker correction Gate 5 review — 2026-09-14

- Reviewer: independent `gate5_jobs_readiness`; authored none of the reviewed contract, test, implementation, or evidence.
- Exact package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T211129Z-5a2762fe34/package.json`.
- Reviewed reconstructible source over base/head `56a65b5d0e166721e3c6aa62f7a96378fe45dbee`: candidate source `b46bf3fa09a25f57a5132c62252bee65c27e3a28bb79b1d8735d8e82ee04e930`, executable source `79e1b2b94d6773e3be58c55fa86e8851777e5d5a1653993a33baba98c79ff501`.
- Snapshot patch SHA-256: `c2ffbd632628fd4030454353720e4911d34fa55f390a488cce33faf3cbfcdef1`; verification plan SHA-256: `7cc685011446dbf28d0c562b14a05c95e3f5a4ec61d2d7626c8a536ba5196574`.
- Controlling test review: `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, sections “Jobs-readiness correction v2” and “Jobs-readiness post-implementation observation correction,” both `APPROVED`.

### Assessment

No findings remain in the jobs-readiness blocker correction.

Normative A6 requires the disposable jobs worker alone to receive the fixed `FMONITOR_BITRIX_CONFIG=/run/fmonitor-secrets/bitrix-config.json` reference from the existing exact private `secrets` volume, excludes caller-provided path/value and rendered contents, excludes the scheduler, and preserves the fail-closed `php bin/yii jobs/health --interactive=0` healthcheck. The stable specification and bound OpenSpec delta state the same requirement, and the independently approved test delta exercises the rendered Compose model at that boundary.

Both `deploy/runtime/compose.yaml` and the synchronized `tools/delivery/compose.runtime.yaml.in` add only the fixed worker environment value; the files are byte-identical. The worker continues to inherit the existing named `secrets:/run/fmonitor-secrets` volume. No host path, config contents, credential value, new mount, or scheduler configuration is introduced. The hostile caller value is ignored rather than interpolated, and the worker/scheduler commands, dependency ordering, stop policy, restart policy, and exact healthcheck definitions are unchanged.

The focused jobs-readiness test was rerun during review and is GREEN. It proves the fixed worker reference, scheduler exclusion, hostile-value non-disclosure, logical named-volume source `secrets`, and exact health command for both jobs services. `git diff --check` is clean. The exact package contains all 14 planned focused records, each GREEN and source-bound to candidate `b46bf3fa09a25f57a5132c62252bee65c27e3a28bb79b1d8735d8e82ee04e930` / executable source `79e1b2b94d6773e3be58c55fa86e8851777e5d5a1653993a33baba98c79ff501`.

Harness reports `action_authorized: false`, PR/CI `UNKNOWN`, deployment `UNKNOWN`, and `merge_ready: false`. No destructive action or authorization package was executed during this review. Authorized disposable roundtrip/rollback, exact-source CI, publication/merge, deployment, and production cutover remain separate deferred actions and are not inferred GREEN.

### Controlling verdict

`APPROVED`

Gate 5 passes for the jobs-readiness blocker correction at exact candidate source `b46bf3fa09a25f57a5132c62252bee65c27e3a28bb79b1d8735d8e82ee04e930`. Root must preserve the reviewed compose, contract, test, and review bytes when recording or publishing the next candidate. This review authorizes no destructive rehearsal, CI, publication, merge, deployment, or cutover.

---

## Runtime authorization binding and outbox SQL correction Gate 5 review — 2026-09-14

- Reviewer: independent `gate5_runtime_binding`; authored none of the reviewed contract, test, implementation, or retained verification evidence.
- Exact package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T224213Z-ccb05c098f/package.json`.
- Reviewed reconstructible source over base/head `0997a37a9ef9921c7960358e13ac804685ebf1d8`: candidate source `618380433c45e19d0ff754b502f4826ba50876d6ec8deb7d8c994afd96fcfcd7`, executable source `127eb8360c10a272e6f07e96cd5f79020620f6ad1464d0b3ff2e7b2693d569ec`.
- Snapshot patch SHA-256: `5b0584c88b7541f0d028d0ebebb83682e74f1e9b3b3b79f15b490772d91d61ac`; verification plan SHA-256: `43b7e962ff4ac156ec2bd3600787046927aa04ce0ad65c4235d20e970e323d2d`.
- Controlling narrow test reviews: `reviews/tests/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, runtime-tuple and outbox-identity correction sections, verdicts `APPROVED`.

### Assessment

No findings remain in this correction scope.

The production authorization exact schema now includes the canonical `runtime` tuple and validates its exact key set and values. `StandRuntimeConfiguration` compares that tuple byte-for-byte with its validated process-table prefix, artifact-volume child and Yii-session-volume child. `ProductionStandRestoreDriver::preflight()` performs this comparison before `observe()` and therefore before any `StandProcess` call. The approved process-boundary test independently varies each of the three tuple members, requires `TARGET_INVALID`, and proves the process call count remains unchanged for every mismatch.

After successful binding, guarded restore observers and effects consume the same bound configuration owner: rehearsal and jobs tables are derived from its prefix, while artifact and session restore/verification paths are derived from its two managed volume children. The outbox recovery assertion now queries the canonical unique identity `(domain_event_id, channel)` and no longer refers to the nonexistent jobs `idempotency_key` column on `fm2_outbox_intents`.

The candidate preserves the previously approved table/path correction, jobs-readiness Compose correction, application-owned authorization/attestation boundary, immutable target observations, operation-specific staged volume replacement, exact expected-state comparison, retained lease/no pointer on ambiguous outcome, private credential handling, and durable replay/conflict history. This delta introduces no new destructive seam or alternate state owner.

All 14 focused-plan records in the exact package are GREEN and source-bound to candidate `618380433c45e19d0ff754b502f4826ba50876d6ec8deb7d8c994afd96fcfcd7` / executable source `127eb8360c10a272e6f07e96cd5f79020620f6ad1464d0b3ff2e7b2693d569ec`. During review, both changed PHP boundary checks were rerun and GREEN, and `git diff --check` was clean.

Harness reports `action_authorized: false`, PR/CI `UNKNOWN`, deployment `UNKNOWN`, and `merge_ready: false`. No destructive action or authorization package was executed. Authorized disposable roundtrip/rollback, exact-source full CI, publication/merge, deployment, and production cutover remain separate deferred actions and are not inferred GREEN.

### Controlling verdict

`APPROVED`

Gate 5 passes for exact candidate source `618380433c45e19d0ff754b502f4826ba50876d6ec8deb7d8c994afd96fcfcd7`. Root must preserve the reviewed bytes when recording or publishing the next candidate. This review authorizes no destructive rehearsal, CI, publication, merge, deployment, or cutover.
