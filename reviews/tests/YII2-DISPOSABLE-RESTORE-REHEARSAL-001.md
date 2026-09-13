# Gate 3 review — YII2-DISPOSABLE-RESTORE-REHEARSAL-001

- Date: 2026-09-13
- Reviewer: independent `gate3_restore_rehearsal`; authored none of the reviewed specification, OpenSpec artifacts, tests, or RED evidence.
- Test author: root delivery agent.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T185817Z-d3d33f1e81/package.json`.
- Reviewed reconstructible source: package candidate source `c653fbb2c531f9c5e74839ad443663388c2b57e9e9f6c17635fe2cff47bb2c6a`, executable source `d733e4040c1d3d200054d9041eec02ea812b2a8302a128f55bd05e3f1af31cf0`, over base `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T185817Z-d3d33f1e81/snapshot/source.patch`, SHA-256 `9f0f9a5a21ac049ae60194327667c568da4254630191d1f1e783de9fba2d4d32`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T185817Z-d3d33f1e81/verification-plan.json`, SHA-256 `d6adadf6969ccc57766c22f0b51b40659701201c9ab9b046bd846ebbbeded659`.
- Agreed scope: authorization/attestation, existing application ownership, real disposable backup/restore roundtrip, explicit rollback, evidence/cutover boundary, and retained legacy inventory.
- Public seams claimed by the specification: `php bin/yii stand-backup/create|verify` and `php bin/yii stand-restore/run --interactive=0` with an exact authorization manifest.
- Verdict: `CHANGES_REQUESTED`.

## Assessment

The normative spec and OpenSpec artifacts consistently require a real disposable MariaDB/Docker/volume rehearsal, exact pre-effect and per-phase attestation, sensitive credential handling, independent post-restart observations, and a separate authorized rollback operation. The verification input maps five acceptance groups and the retained runs are bound without source drift to the package source.

The submitted new tests do not exercise those behaviors. Four files inspect PHP source text or the future runner text for names and tokens; the fifth inventories existing files and documentation. In particular, neither the rehearsal nor rollback test invokes a public Yii seam or a disposable runtime. A future file containing the required words in comments can make both tests green without creating a backup, stopping a service, importing MariaDB, restoring a byte, performing a health request, classifying failure, or rolling back. Therefore the candidate is not sensitive to the central behavior and does not satisfy Gate 2 despite valid missing-artifact RED.

## Findings

1. **CRITICAL — A3 and A4 are lexical file-presence tests, not real-boundary acceptance tests.** Locations: `tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py:9-21`; `tests/Deployment/yii2_disposable_restore_rollback_001_test.py:9-18`; normative spec sections 4–5. The tests merely search `tools/delivery/yii2-disposable-restore-rehearsal.php` for words such as `docker`, `MariaDB`, `AUTO_INCREMENT`, `golden`, operation identifiers, and `/health/ready`. They never invoke backup/create, backup/verify, restore/run, Docker Compose, MariaDB, HTTP health, or rollback; they never create/destroy an attestable disposable target or independently observe post-restart state. Any inert PHP file containing these literals passes. Replace the lexical checks with an authorized real-disposable acceptance harness that executes the exact public commands and independently observes the required boundaries. If destructive execution is intentionally deferred until separate authorization, Gate 3 still needs executable tests whose guarded setup deterministically proves and reports `NOT_AUTHORIZED`/skipped for the real environment while unit/integration doubles exercise the orchestration; absence of authority must not be converted into a token-based GREEN acceptance.

2. **HIGH — authorization, attestation, effect ordering, replay/conflict, and ambiguity rejections are not behaviorally tested.** Locations: `tests/Deployment/yii2_disposable_restore_admission_001_test.py:9-24`; `tests/Architecture/yii2_disposable_restore_boundary_001_test.py:9-25`; normative spec sections 1–3 and 6. Source-string checks for field names, `repeat`, removal of an environment flag, and class names cannot prove canonical binding, expiry/scope, operation/bundle/target digest conflicts, allowlisted absolute paths, duplicate/reuse/symlink/owner/mode/production-like rejection, immutable observed identities, re-attestation before every destructive phase, or zero effects/byte-identical evidence on rejection. No test covers drift before the first effect versus after a possible effect, durable `OUTCOME_UNKNOWN` with retained lease/no confirmed pointer, exact replay without effects, or UUID conflict. Add public-application-seam tests with a recording driver/observer and independent evidence snapshots for the complete representative rejection matrix and each material phase boundary. Assertions must prove credentials and effects occur only after admission and that durable ledger/lease/pointer behavior matches the specified definite/ambiguous outcomes.

3. **HIGH — expected values and restored facts are absent, so independence and sensitivity cannot be assessed.** Locations: `tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py:14-21`; normative spec section 4. The tests define no literal sentinel DB/history rows, schema inventory, independently calculated next AUTO_INCREMENT value, artifact bytes/hash/mode, session identity/relogin result, queued/leased/outbox/recovery facts, or expected golden responses. They consequently cannot distinguish a correct restore from a runner that prints success, reuses source-side values, or copies synthetic summaries. Establish known state from literal setup inputs outside production helpers, destroy or mutate only the authorized disposable targets, and query the restarted system independently. Assert exact database/history/schema and controlled insert results, real artifact bytes/modes, session outcome, job/outbox/lease/recovery facts, and named anonymous/authenticated/FKR/construction-control/OTIZ smoke outcomes. Synthetic `database.json` and `readiness.json` must remain explicitly insufficient.

4. **HIGH — sensitive-data and evidence-root requirements have no executable coverage.** Locations: `tests/Deployment/yii2_disposable_restore_admission_001_test.py:9-18`; normative spec sections 2 and 6. `assertNotRegex` for a PHP array literal shaped like `password =>` neither verifies private regular non-symlink credential files nor detects a secret in argv, subprocess environment lifetime, stdout/stderr, exception messages, trace, ledger, summary, or repository evidence. There is also no hostile credential path/mode/symlink case and no proof that credential files are unread before admission. Add canary secret values and hostile reference cases, execute the seam with a recording subprocess boundary, and inspect argv, captured stdout/stderr, exceptions, traces, ledger, safe summary, and checkout artifacts for exact/encoded fragments. Prove pre-admission rejection makes no credential read and no target/evidence mutation, while full logs remain only in the external evidence root.

5. **HIGH — failure predicate and rollback history are unobserved.** Locations: `tests/Deployment/yii2_disposable_restore_rollback_001_test.py:9-18`; normative spec section 5. Required string tokens do not show that each material non-zero/unknown, identity, integrity, health, or golden failure prevents readiness/cutover declaration; that the failed candidate outcome remains append-only; or that rollback uses a new UUID, separate unexpired authority, independently verified known-good bundle, newly attested same target, second restart, fresh readiness, and the integrity subset. Add deterministic injected candidate failures at representative pre-effect, post-effect, readiness, and integrity boundaries. Assert preserved candidate evidence, distinct authorization/operation binding, actual known-good restore effects, no treatment as replay, and independent second post-restart observations before rollback success.

6. **MEDIUM — ownership and legacy inventory checks are shallow lexical/documentary ratchets.** Locations: `tests/Architecture/yii2_disposable_restore_boundary_001_test.py:9-31`; `tests/Architecture/yii2_disposable_restore_inventory_001_test.py:9-22`. Existence of interface-named files and strings in `StandRestoreApplication` does not prove the controller/runbook/runner cannot own or publish restore success, nor that all production composition sites use the single application seam. The inventory verifies referenced files exist but does not execute the named old-format, v22/v23 forward migration, schema v22–v24, jobs recovery, and legacy CLI contracts as part of the generated plan. Add bounded composition/consumer checks that reject an alternate restore protocol/success publisher, and include the named executable legacy suites (or a documented representative mapping that really executes each responsibility) so retirement safety is behavioral rather than prose-only.

## RED evidence

The four new RED records are source-stable and fail for intended missing behavior at a coarse level:

- `python3 tests/Architecture/yii2_disposable_restore_boundary_001_test.py` fails because production-shaped boundary files are missing and because the retained application still contains `FMONITOR_STAND_RESTORE_TEST_MODE`.
- `python3 tests/Deployment/yii2_disposable_restore_admission_001_test.py` fails because `StandRestoreAuthorization.php` is missing and the retained application still selects the fixture path through the test-mode flag.
- `python3 tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py` fails because the proposed runner file is absent.
- `python3 tests/Deployment/yii2_disposable_restore_rollback_001_test.py` fails because the same runner file is absent.

These failures are not environment/bootstrap failures, but they demonstrate only missing files/lexical structure. They do not validate sensitivity to the behavioral obligations listed above. The retained inventory and prior PR #124 restore tests are GREEN, which is appropriate regression evidence but does not close the new acceptance gaps.

Harness reports PR, CI, and deployment as `UNKNOWN`, and `action_authorized` is false. This review treats none of those states as GREEN and authorizes no destructive rehearsal or production action.

## Required changes

Return to Gate 2. Replace or supplement the lexical contracts with a complete behavioral matrix at the public application/console seams, including independently specified state and observers, representative admission and phase failures, credential/evidence non-disclosure, durable replay/conflict/UNKNOWN behavior, real-disposable guarded roundtrip, and distinct rollback proof. Retain fresh intended RED records on one exact source, regenerate the verification package, and resubmit for independent Gate 3 review. Gate 4 is blocked until then.

---

## Correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T190517Z-c59cd3254d/package.json`.
- Exact reviewed source: reconstructible snapshot over base `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `1a2e048250ab9cda9dfacf25ba5e509441e8c06203e54599106eefab0efda2c3`, executable source `c2a6c44a2aacd5b3663f5207ed602eb0b474d92f3b33bf8854069105bfa41d01`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T190517Z-c59cd3254d/snapshot/source.patch`, SHA-256 `8aeb2de856fda872bff0e6ca2259131f119f4daa0148b7e919a817d22795a3e3`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T190517Z-c59cd3254d/delta.patch`.
- Verification plan SHA-256: `4f13f875d878f9d0dd7b2feb442f069bd874eaf5999ff4f866a8d9fe67fcb668`.
- Independence is unchanged; this reviewer authored none of the correction or fresh evidence.

### Resolved portions

The correction removes the source-token assertions from the authorization, roundtrip, and rollback tests. The no-action-package branches now execute the proposed PHP entrypoint and require exact exit `77`, a canonical `ACTION_NOT_AUTHORIZED` response, empty stderr, and no forbidden-effect marker. This is a useful deterministic safety guard. The authorization probe also gives the value object an executable PHP seam and introduces operation/bundle/target/scope/disposable/path/observed/credential rejection inputs, a secret canary, an unread-before-admission trace, and an independently computed canonical authorization digest.

Those changes resolve the purely lexical form of prior findings 1 and 4, but they do not yet make the submitted acceptance matrix sensitive to the authorized behaviors or demonstrate the rejection cases for their intended reasons.

### Remaining findings

1. **CRITICAL — authorized roundtrip and rollback still accept a synthetic self-report instead of independently observing real effects.** Prior findings 1, 3, and 5 remain open. Locations: `tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py:11-15`; `tests/Deployment/yii2_disposable_restore_rollback_001_test.py:12-15`; normative spec sections 4–5. When authorization is present, both tests invoke the runner and trust its JSON `outcome` and string-valued `assertions`. A runner that performs no backup, Docker, MariaDB, filesystem, restart, health request, failure injection, or rollback can emit the expected object and pass. The tests do not define literal known-state inputs or independently query DB/history/schema/AUTO_INCREMENT, artifact bytes/modes, sessions, jobs, golden endpoints, operation evidence, target identity, or the failed candidate record. The absence of `database.json` text in stdout is not real-boundary proof. Arrange known state and independent observers in the test/action harness; compare observed facts to test-owned literal expectations and externally captured evidence. The runner's summary may be an additional assertion, not the oracle for its own effects. Rollback must independently prove a distinct authorization/UUID, retained failed outcome, known-good bundle/target binding, second restart, and restored facts.

2. **HIGH — the rejection matrix is GREEN for the wrong common reason, so its fresh RED evidence does not prove the individual boundaries.** Prior findings 2 and 4 remain open. Locations: `tests/Support/stand_restore_authorization_probe.php:5`; `tests/Deployment/yii2_disposable_restore_admission_001_test.py:18-24`; retained record `1789326292256245000-0f0bd810974641b383f4a59e92e3b9a5`. `StandRestoreAuthorization` is absent. The probe catches every `Throwable`, including class-not-found, and maps it to the same exit `64`/`TARGET_INVALID`. Consequently all nine rejection subtests pass without parsing any authorization, examining a credential path, or distinguishing the mutated field; only the valid case is RED. This is broken/missing setup for the rejection cases, not intended RED for their validation behavior. Provide a narrow parser/admission baseline or otherwise structure the test so each hostile mutation demonstrably fails because its specific check is missing or wrong. The public result may remain uniformly safe, but an independent recording observer must prove which validation boundary ran, plus no credential read/effect/evidence mutation. Do not let a missing class or bootstrap exception satisfy rejection acceptance.

3. **HIGH — phase re-attestation, durable outcomes, replay/conflict, and ambiguity still have no new behavioral coverage.** Prior finding 2 remains open. Locations: corrected authorization test and guarded runner tests as a whole; normative spec sections 2–3. The authorization test stops at loading a value object. It does not exercise `StandRestoreApplication`, controller composition, driver ordering, repeated observed identities before destructive phases, drift classification before/after possible effect, append-only ledger/lease/pointer facts, exact replay, operation conflict, or secret absence from subprocess diagnostics and persisted evidence. Existing PR #124 fixture tests are GREEN regressions for the old fixture contour, but the correction supplies no test connecting the new authorization/production driver to those guarantees. Add a behavioral application/CLI suite with independently controlled observer/driver events and evidence snapshots for representative admission, pre-effect drift, post-effect ambiguity, replay, conflict, and credential/subprocess redaction cases.

The prior MEDIUM ownership/inventory finding also remains unresolved by this delta. It may be closed by a bounded composition/consumer ratchet plus actual execution of the named legacy responsibility suites in the focused plan; it cannot be inferred from unchanged lexical/document checks.

### Fresh evidence assessment

All four new records bind start and end without drift to candidate source `1a2e048250ab9cda9dfacf25ba5e509441e8c06203e54599106eefab0efda2c3` and executable source `c2a6c44a2aacd5b3663f5207ed602eb0b474d92f3b33bf8854069105bfa41d01`.

- Boundary RED is the same missing production files.
- Authorization has one valid-case RED due to the missing class, while every hostile case is a false GREEN through the probe's catch-all class-not-found mapping.
- Roundtrip and rollback no-authority branches are RED because the runner file is absent; their authorized acceptance branches are skipped because no action packages exist.

The no-authority RED is valid for the missing guard entrypoint. It is not RED evidence for real roundtrip or rollback behavior. `action_authorized`, PR, CI, and deployment remain false/`UNKNOWN`; this review authorizes none of them.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Correct the rejection-test false positives and add independently observed authorized behavior plus production-driver/application ordering and durable-state coverage. Retain fresh exact-source evidence, rebuild the package, and resubmit for independent Gate 3 review.

---

## Third correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191139Z-2cc90659f2/package.json`.
- Exact reviewed source: reconstructible snapshot over base `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `0a084f827ab17a922e51564917074345725c55be1e96c36337497bba0ec407f0`, executable source `33e55a46f0de8a717970d01bc45e41e8d8ef13093c838240c15fd39c93b04a43`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191139Z-2cc90659f2/snapshot/source.patch`, SHA-256 `ef14c2b7646fcbf274d1ab6405bc35a0f07c79c3e7b0bf4c24b7ca362e694ee7`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191139Z-2cc90659f2/delta.patch`.
- Verification plan SHA-256: `20c1f6929fe0fdcf93c6cdbdb0ad3c8ec0576808381789b41d425428bec7e619`.
- Independence is unchanged; this reviewer authored none of the third correction or evidence.

### Resolved portions

This correction is materially stronger. `stand_restore_authorization_probe.php` now returns distinct `IMPLEMENTATION_MISSING` before its catch-all, and the fresh record shows all valid and hostile inputs RED with exit 78 rather than falsely satisfying `TARGET_INVALID`. The new application-level recording-driver suite specifies a deterministic event order, credentials only after preflight, repeated attestation before each material effect, definite pre-effect drift, ambiguous post-effect drift with retained lease/no pointer, success replay without a repeated trace, and safe public output. The real roundtrip branch no longer treats the runner summary as its only oracle: it independently queries MariaDB/history and controlled next insert, reads artifact/session volumes, requests live/ready and golden URLs, and observes jobs/outbox. These changes close the central substance of prior findings 1–4 for the roundtrip and production-driver seam.

The fresh RED records are exact-source-bound with no drift. Authorization and driver suites fail explicitly on `IMPLEMENTATION_MISSING`; unauthorized rehearsal and rollback fail because their guarded runner is absent; authorized destructive branches correctly remain skipped because no action package is present. This is honest pre-implementation evidence and does not imply live authorization.

### Remaining findings

1. **HIGH — the acceptance test itself exposes the database credential in subprocess argv.** Locations: `tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py:16,20`; `tests/Deployment/yii2_disposable_restore_rollback_001_test.py:16`; normative spec sections 2 and 6. The tests build Docker CLI argv containing the literal `MYSQL_PWD=<password>` via `docker compose exec -e`. The contract explicitly forbids credential values in argv, and process listings/evidence tooling can capture this test command just as readily as a production driver command. Passing a canary check in the application probe does not waive leakage introduced by the real acceptance observer. Use a private mounted credential file or another stdin/file-descriptor boundary whose argv contains only a reference; inspect the executed argv and captured evidence to prove the literal/encoded canary is absent.

2. **HIGH — the advertised conflict case is not sensitive to `OPERATION_CONFLICT`.** Location: `tests/Deployment/yii2_disposable_restore_driver_001_test.py:28-30`; normative spec section 3. The third invocation changes the command operation UUID while retaining an authorization document bound to the original UUID, then asserts only `code != 0`. Correct authorization admission may reject it as `TARGET_INVALID` before ledger conflict logic, so an application with no `OPERATION_CONFLICT` implementation passes. Exercise the same operation UUID with a separately valid authorization/request whose bundle, target, or authorization digest differs, and require the exact public conflict outcome/exit with unchanged trace, lease, ledger, pointer, and target evidence.

3. **HIGH — rollback independence still stops at DB/history and readiness while the required integrity subset is self-reported.** Location: `tests/Deployment/yii2_disposable_restore_rollback_001_test.py:13-17`; normative spec section 5. The test independently checks the retained candidate record, one DB row, and `/health/ready`, but trusts runner JSON for known-good bundle verification, target re-attestation, artifact modes, sessions, jobs/recovery, and golden smoke. A rollback implementation can synthesize those assertion strings while restoring only the DB. Repeat the relevant independent volume, session, jobs/outbox, live/ready, and golden-hash observers after rollback, and independently bind the new rollback UUID/authority/known-good bundle/target evidence rather than relying only on the runner response.

4. **MEDIUM — durable ambiguity/replay evidence assertions remain incomplete.** Location: `tests/Deployment/yii2_disposable_restore_driver_001_test.py:22-30`; normative spec sections 2–3. Post-effect drift checks lease presence and pointer absence but does not assert an append-only `OUTCOME_UNKNOWN` operation record, its binding fields, or a different-operation `LEASE_HELD` contender with no further effect. Success replay snapshots only the trace, not exact ledger/pointer bytes. Add bounded evidence snapshots for the UNKNOWN record and success record/pointer, exact byte stability on replay, and one contender proving the retained ambiguous lease actually blocks a new operation. This is necessary to distinguish durable ambiguity safety from an inert lease marker or silently rewritten history.

The earlier MEDIUM ownership/legacy inventory concern is reduced by the existing named regression tests and generated full CI obligation, but the final candidate should ensure the focused plan executes the named legacy responsibility suites or records their authoritative full-CI mapping. It is not the primary blocker compared with the executable defects above.

### Third correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. The remaining changes are narrow: remove secrets from observer argv, make conflict exact and reachable, independently observe the rollback integrity subset, and complete durable UNKNOWN/replay/lease evidence assertions. Retain fresh exact-source RED, rebuild the package, and resubmit for independent Gate 3 review. PR, CI, deployment, and live action authorization remain false/`UNKNOWN` and are not approved here.

---

## Fourth correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191443Z-55d0fe5eac/package.json`.
- Exact reviewed source: reconstructible snapshot over base `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `80186ae1f32958f080bb7a646ba37df0b7992e4b9d8e44f0707522cb1c571e6d`, executable source `8bced86bc4ece94fe68512b1deb748481a5f5238b949eb415738e78da4099a7c`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191443Z-55d0fe5eac/snapshot/source.patch`, SHA-256 `338559f36e980e059f1c92e062da777bbc77bc751da8d8d16fdb98c3b0fa9f4d`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191443Z-55d0fe5eac/delta.patch`.
- Verification plan SHA-256: `2da92f6636fd4e3f9230602f3d233d3fb3f9514022d38ee7b3e1df6dc440e77e`.
- Independence remains unchanged; this reviewer authored none of the fourth correction or evidence.

### Resolved portions

The rollback test now independently observes DB/history, jobs/outbox, read-only artifact bytes/mode, session presence, golden hashes, readiness, and retained candidate evidence. The driver test now inspects an `OUTCOME_UNKNOWN` record, retained lease/no pointer, a new-operation contender, and exact trace/ledger/pointer byte stability on success replay. These materially resolve the observation and durable-evidence portions of prior findings 3 and 4, subject to the admission-binding issue below.

The literal password was removed from the Docker command argv. This fixes direct argv disclosure, but the replacement does not actually deliver the credential to the containerized MariaDB client.

### Remaining findings

1. **HIGH — the real DB observers no longer have a working credential transport.** Locations: `tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py:16,20`; `tests/Deployment/yii2_disposable_restore_rollback_001_test.py:16`. Setting `MYSQL_PWD` in the local `subprocess.run(..., env=...)` environment configures the local `docker` client process; `docker compose exec` does not implicitly copy arbitrary client environment variables into the already-running `db` container. With a normally isolated Compose service, the inner `mariadb` process therefore receives no `MYSQL_PWD`, so the authorized acceptance branch fails at setup even when restore behavior is correct. Use a secret-safe transport that reaches the inner process without putting the value in argv—for example, a private mounted defaults file/reference, stdin/file descriptor, or Compose exec environment-name forwarding whose argv contains no value—and test the actual command boundary. Keep the canary absent from argv, stdout/stderr, and retained evidence.

2. **HIGH — `OPERATION_CONFLICT` and `LEASE_HELD` requests still reuse an authorization bound to different inputs.** Location: `tests/Deployment/yii2_disposable_restore_driver_001_test.py:24-31`; normative spec sections 1–3. The conflict call changes the command bundle digest to `a…a` but reuses the authorization file bound to `f.digest`. The contender changes the command operation UUID but reuses authorization bound to `6161…`. A correct fail-closed admission layer can return `TARGET_INVALID` before ledger/lease logic, making the required exit 65/75 unreachable; ordering ledger checks before authorization merely to satisfy the test would weaken the stated admission contract. For each request, create a separately canonical, otherwise valid authorization document bound to the changed bundle or contender operation. Then require exact `OPERATION_CONFLICT` or `LEASE_HELD` and unchanged target/effect/evidence bytes. This is the same reachability defect identified in the previous conflict case and it remains blocking.

### Evidence and verdict

The eight retained records are source-stable and match the planned outcomes. New admission and driver tests are clean missing-production-seam RED at exit 78; no-action runner tests remain RED due to the absent guarded entrypoint; destructive branches remain honestly skipped without action packages. The fresh evidence cannot reveal the two authorized-path setup/reachability defects because those paths either depend on future implementation or are action-gated.

`CHANGES_REQUESTED`

Gate 4 remains blocked for two narrow test corrections: provide an actually forwarded secret-safe DB credential boundary and construct valid independently bound authorizations for the conflict and lease-contender requests. Retain fresh exact-source RED and resubmit. PR, CI, deployment, and destructive action authorization remain false/`UNKNOWN`.

---

## Fifth correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191712Z-8e72777d1f/package.json`.
- Exact reviewed source: reconstructible snapshot over base `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`, candidate source `7f46d8d6947645cbec45d62949c332df611e17a621926427c7e3d16a17401330`, executable source `c49a38eccc3f18f7267f86d09c4208114b719218e550e86e95f31b07eaf41aed`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191712Z-8e72777d1f/snapshot/source.patch`, SHA-256 `445db92f6d5447ec0233589a3b537eccdafdcbac9b8fdfd2f0293473935d30c0`.
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191712Z-8e72777d1f/delta.patch`.
- Verification plan SHA-256: `24931972829e9bd73bc2374cc135fe3930161abbfc3763bdee5454cfe12843be`.
- Independence remains unchanged; this reviewer authored none of the fifth correction or evidence.

### Finding disposition

Both remaining findings are resolved without broadening the normative scope.

The real MariaDB observers now invoke `docker compose exec -e MYSQL_PWD`: the argv contains only the environment variable name, while the value exists in the short-lived local subprocess environment and is forwarded to the inner process by Compose. This supplies a working observer credential boundary without exposing the value in argv. The application probe and public-output assertions continue to reject the secret canary from stdout/stderr and persisted operation evidence.

The application matrix now constructs separate canonical authorization documents for both changed requests. The post-UNKNOWN contender authorization is bound to the contender UUID before requiring exact exit 75/`LEASE_HELD`. The conflict authorization is bound to the changed bundle while preserving the original operation UUID before requiring exact exit 65/`OPERATION_CONFLICT`. These requests can pass admission and are sensitive to the intended durable lease/conflict branches. Success replay also preserves exact trace, ledger, and pointer bytes; the ambiguous case binds the UNKNOWN record and proves no confirmed pointer.

Together with the prior corrections, the complete candidate now provides:

- executable authorization and hostile-input behavior with missing implementation distinguished from safe rejection;
- a deterministic application-level recording-driver matrix for admission, credential timing, per-phase re-attestation, effect ordering, definite pre-effect rejection, ambiguous post-effect outcome, durable lease/evidence, replay, and conflict;
- guarded no-authority roundtrip/rollback entrypoints that fail before effects;
- authorized real-disposable branches whose acceptance is based on independent MariaDB/history/AUTO_INCREMENT, jobs/outbox, read-only artifact/mode, session, health, golden-hash, candidate-failure, and rollback observations rather than runner assertions alone;
- retained old-contour regression and responsibility inventory, with full exact-source CI still required later.

The eight retained records bind start and end without drift to candidate source `7f46d8d6947645cbec45d62949c332df611e17a621926427c7e3d16a17401330` and executable source `c49a38eccc3f18f7267f86d09c4208114b719218e550e86e95f31b07eaf41aed`. The five new behavioral/architecture commands are `INTENDED_RED` for the absent production seam/guard, while inventory and the two prior restore regressions are GREEN. Destructive branches are correctly skipped because no action package is present; this is not represented as live rehearsal evidence.

No Gate 3 findings remain for the agreed scope.

### Controlling verdict

`APPROVED`

Gate 3 passes for exact candidate source `7f46d8d6947645cbec45d62949c332df611e17a621926427c7e3d16a17401330`. Gate 4 may proceed against this reviewed contract and test matrix. Any later change to normative expectations or executable tests requires fresh Gate 2 evidence and independent Gate 3 review. PR, CI, deployment, and destructive roundtrip/rollback authorization remain false/`UNKNOWN`; this approval authorizes none of them.

---

## Post-Gate-5 correction test-delta review — 2026-09-13

- Reviewer: independent `gate3_restore_rehearsal`; authored none of the reviewed test delta, production correction, or GREEN evidence.
- Baseline: previously approved package snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T191712Z-8e72777d1f/snapshot`, approved candidate source `7f46d8d6947645cbec45d62949c332df611e17a621926427c7e3d16a17401330`.
- Controlling code-review input: `reviews/code/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, Gate 5 verdict `CHANGES_REQUESTED` against source `ccdb416ed6e4cc86988311c14103ce85505c06c63c3e6a50162d83666d3bb367`.
- Exact reviewed corrected test source: candidate source `e54d2e28beea5c253ff9d214bb47622b460ff5a39cba4a70dc637757d1596aea`, executable source `846ef51a2368db4fc06ac9dedece483d5ac891d200ad177818849c94e898cb82`, over head `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`.
- Review scope: changes to `tests/Deployment/yii2_disposable_restore_driver_001_test.py`, `tests/Deployment/yii2_disposable_restore_rehearsal_001_test.py`, and `tests/Deployment/yii2_disposable_restore_rollback_001_test.py` only. Production implementation is outside this review.

### Assessment

The correction does not weaken or replace any previously approved expected outcome. It changes fixture and package preparation so the tests reach the production-shaped contracts identified by Gate 5.

The driver test now creates a target manifest whose source, image, database object, observed database identity, and distinct database/artifact/session volumes are also bound into a newly canonicalized bundle manifest and verified pointer. Its authorization is derived separately and binds that exact target and bundle. The established test-owned expectations remain unchanged: exact phase ordering, credential timing, pre-effect rejection, post-effect `OUTCOME_UNKNOWN`, retained lease/no pointer, independently bound `LEASE_HELD` contender, byte-stable replay, and exact `OPERATION_CONFLICT`. The changes therefore increase regression sensitivity to target/bundle incompatibility without copying implementation-produced expectations.

The guarded roundtrip test now treats the outer action package as the operation inventory and loads the exact nested restore authorization for independent observers. It verifies distinct backup/restore UUIDs and the expected bundle binding, then retains the literal independent MariaDB/history/next-id, live/ready, artifact bytes/mode, session, jobs/outbox, and golden-hash observations. It no longer accepts a list of synthetic `VERIFIED` labels from the runner.

The guarded rollback test likewise loads the exact nested rollback authorization, binds the reported rollback operation and known-good bundle to the outer package, proves it differs from the retained candidate operation, and retains independent DB/jobs/outbox, artifact/mode, session, readiness, golden-hash, and candidate-failure evidence checks. These expectations remain spec-derived and would fail if a runner merely synthesized success output.

No destructive action package was present or executed. This review confirms the executable guarded branch and the non-destructive driver matrix; it does not claim real roundtrip/rollback evidence.

### GREEN evidence

All three supplied records are start/end bound without drift to candidate source `e54d2e28beea5c253ff9d214bb47622b460ff5a39cba4a70dc637757d1596aea` and executable source `846ef51a2368db4fc06ac9dedece483d5ac891d200ad177818849c94e898cb82`:

- `1789328705670779000-bbed0e1c3df341fbb6a9101997653a9b`: driver behavioral matrix GREEN.
- `1789328707605896000-6f0463d22b7440d1b51e04329cfdb64f`: no-authority roundtrip guard GREEN; authorized real branch explicitly skipped.
- `1789328709020969000-27733dfed6d84156a49f7e2bc998dc53`: no-authority rollback guard GREEN; authorized real branch explicitly skipped.

No findings remain within the reviewed test delta.

### Controlling test-delta verdict

`APPROVED`

The corrected tests may be used for the next exact-source Gate 5 review. This approval covers only the test delta and does not approve the production implementation, the earlier Gate 5 findings, destructive rehearsal, PR/CI, deployment, or cutover. `action_authorized` remains false and PR/CI/deployment remain `UNKNOWN`.

---

## Second Gate-5 correction — narrow Gate 3 review — 2026-09-13

- Reviewer: independent `gate3_restore_rehearsal`; authored none of this test delta or its evidence.
- Scope: new `evidence-mismatch` application case in `tests/Deployment/yii2_disposable_restore_driver_001_test.py` and new volume-publication assertion in `tests/Architecture/yii2_disposable_restore_boundary_001_test.py`.
- Controlling Gate 5 input: `reviews/code/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, section “Gate 5 correction rereview,” findings 1–2.
- Exact RED source: candidate source `4b2e6675131a5358aebac6aa191769cf841b5d3f5607378f30c0781575b9dcfd`, executable source `505abc6ca1d8a6f391fd3a856cfe455ad31cf2f52a40be27cc3c34c001425219`.
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789329573065345000-c7142b107fb645b4a0b7dd5ddb62d7c0.json`.

### Assessment and findings

The application-level `evidence-mismatch` case is traceable to Gate 5 finding 1 and the normative requirement that `StandRestoreApplication` publish `RESTORE_VERIFIED` only after complete exact post-restart observations. It preserves the public application seam and independently requires exit 70/`OUTCOME_UNKNOWN`, an integrity observation, retained lease, no confirmed pointer, and an append-only UNKNOWN record. The retained run is stable at start/end and fails for the intended reason: current behavior returns exit 0/`RESTORE_VERIFIED`. This is valid RED and is sensitive to the owner trusting incomplete driver evidence. A representative mismatch is appropriate here because the exact field values are separately bound by the authorization/real-observer matrix.

1. **HIGH — the volume-publication addition is an implementation-token check with neither behavioral sensitivity nor retained RED.** Location: `tests/Architecture/yii2_disposable_restore_boundary_001_test.py:34-38`; normative spec section 3; Gate 5 correction finding 2. The test searches production source for exact strings such as `.restore-`, `tar -tf`, `find "$STAGE" -type l`, `EXPECTED_SHA`, `sync -f`, and `mv "$STAGE" "$ROOT"`. Comments or an unused string can satisfy it while extraction still writes destructively in place. Conversely, a correct implementation using a tar library, openat-style validation, a different staging name, `renameat2`, or directory-fsync helper fails. The exact shell spelling is not spec-derived. The new check is already GREEN on the current source, and no retained run demonstrates that it failed against the unsafe in-place implementation for the intended reason. Replace it with deterministic driver/process-boundary tests that supply traversal and symlink archives plus short/corrupt/interrupted materialization, observe an external canary and live volume before/after, and prove: no escape, no partially published live target, expected bytes/hash/mode, same-volume staging, file/directory fsync before publication, re-attestation, and atomic exchange/rename. Retain intended RED against the unsafe behavior before implementation correction, or provide a reconstructible pre-correction source and exact record if already captured.

### Controlling narrow verdict

`CHANGES_REQUESTED`

The `evidence-mismatch` test is approved, but the combined Gate 2 delta is not. Gate 4/5 correction remains blocked until the volume-publication test is behavioral and has valid exact-source RED evidence. This verdict does not review implementation or authorize the destructive branch, CI, publication, or deployment.

---

## Second Gate-5 correction v2 — narrow Gate 3 review — 2026-09-13

- Reviewer: independent `gate3_restore_rehearsal`; authored none of the reviewed test delta or evidence.
- Exact reviewed source: candidate source `ea49fd032af73016aa38b433104f2e990561bc9ac0bfb032d30468210da49c5c`, executable source `c4f3ae4c5a44d4cff7702b6f3b5ca06d2c4bb114f13e7731a15710046feaac60`, over head `e5a420e0b52162bde19c7d527c3fb1c57eb9f40a`.
- Scope: removal of the rejected lexical volume-publication assertion and retention of only `test_application_rejects_incomplete_exact_integrity_evidence` in `tests/Deployment/yii2_disposable_restore_driver_001_test.py`.
- Controlling Gate 5 input: `reviews/code/YII2-DISPOSABLE-RESTORE-REHEARSAL-001.md`, section “Gate 5 correction rereview,” finding 1. Safe volume staging remains a Gate 5 implementation-inspection concern and is not claimed as test-proven by this delta.

### Assessment

The brittle source-token assertion is absent from `tests/Architecture/yii2_disposable_restore_boundary_001_test.py`; its existing ownership and legacy-inventory checks remain unchanged and GREEN. The only new expectation is the already reviewed application-level integrity mismatch case. It is traceable to the rule that incomplete or mismatched post-effect evidence cannot produce confirmed success, uses the application seam with deterministic recording-driver input, and independently asserts exit 70/`OUTCOME_UNKNOWN`, the integrity observation, retained lease, absent confirmed pointer, and append-only UNKNOWN record.

Expected values remain spec-derived rather than copied from the implementation. The test is regression-sensitive: the exact current behavior returns exit 0/`RESTORE_VERIFIED`, so an implementation that merely preserves the existing nonempty/boolean evidence checks cannot pass.

### Evidence

- Record `1789329708990982000-89fde6e7814047a0b4a843ee5e68957a` is source-stable intended RED at candidate `ea49fd032af73016aa38b433104f2e990561bc9ac0bfb032d30468210da49c5c` / executable `c4f3ae4c5a44d4cff7702b6f3b5ca06d2c4bb114f13e7731a15710046feaac60`. The new case alone fails because the application returns `RESTORE_VERIFIED`; all previously approved driver cases remain GREEN.
- Record `1789329711062251000-d287fca20eb249218a3b96ebaae1111c` is source-stable GREEN for the restored two-test architecture boundary, confirming the rejected lexical assertion is no longer present.

No destructive branch was executed, and no implementation review is implied.

### Controlling narrow verdict

`APPROVED`

The exact-integrity test delta may proceed to implementation correction and a new exact-source Gate 5 review. This approval does not establish safe volume staging, approve production code, or authorize rehearsal, CI, publication, deployment, or cutover. `action_authorized` remains false and PR/CI/deployment remain `UNKNOWN`.
