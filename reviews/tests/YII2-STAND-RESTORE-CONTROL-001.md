# Gate 3 review — YII2-STAND-RESTORE-CONTROL-001

- Date: 2026-09-13
- Reviewer: independent `gate3_restore`; authored none of the reviewed specification, OpenSpec artifacts, tests, fixture, or RED evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170151Z-8190f5142a/package.json`.
- Reviewed reconstructible source: package candidate source `473371eca02a0456886d190133765329cc72c6b7c83a0ed9932f110a9df2f4ad`, executable source `77eba5dc5eb67d936414e5f20511368f72c2eadf9787ff6984919117164450dd`, over base `11b8587372040ab045d1a69faeeb1432ff85e400`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170151Z-8190f5142a/verification-plan.json`, SHA-256 `1284597a0151fb58b33f4bef28bb4b9f90ef54db908479d4e6f795b7a90484f1`.

## Assessment

The proposed behavioral tests use the correct production-shaped public seams. The fixture first invokes the real `php bin/yii stand-backup/create` command, and restore is intended to invoke the real `php bin/yii stand-restore/run` command. Expected DB, artifact, session, readiness, exit, and outcome values are supplied independently by the external fixture rather than by production helpers. The four retained runs are exact-source-bound, complete without source drift, and fail because the new application/controller files are absent after the Composer environment was restored. This is clean missing-seam RED rather than bootstrap failure.

That clean RED does not make the submitted acceptance matrix complete. Most of the normative fail-closed, persistence, replay, and ambiguity contract is not observed. A small implementation that recognizes the few fixture flags and writes the three expected JSON files can satisfy all submitted tests while violating the required bundle protocol and operation safety boundaries.

## Findings

1. **HIGH — the required corrupt/incompatible bundle matrix is largely untested.** Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:24-31`; `tests/Deployment/yii2_stand_restore_failure_001_test.py:7-11`. The sole bundle rejection appends bytes to `database.sql`. There is no case for corrupt/non-canonical `manifest.json` or `verified.json`, pointer/digest mismatch, missing/extra member or field, coherent re-hash with wrong source/image/database/volume observed identity or inventory digest, unreadable object, directory, symlink/special object, traversal, or file replacement/stability violation. Consequently an implementation that hashes only `database.sql` and ignores the common PR #119 manifest/pointer protocol can pass. Add deterministic black-box mutations for the material schema, identity, allowlist, filesystem-object, and stable-read classes; prove each returns the specified safe class before driver effects, ledger creation, confirmed-pointer mutation, or target mutation. Reuse representative cases where one test genuinely covers the same validation boundary rather than enumerating syntax trivia.

2. **HIGH — target admission and production authorization are not sensitive to the promised safety boundary.** Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:33-43`; `tests/Deployment/yii2_stand_restore_failure_001_test.py:12-16`. A single fixture boolean `target_empty=false` proves neither a non-empty DB nor foreign artifact/session content is observed and preserved; no symlink, device/socket, unsafe path/owner, ambiguous observation, target identity mismatch, non-`test-*` project, unauthorized fixture path, or production-driver invocation is tested. Add public-seam fixtures with actual pre-existing DB/persistent facts and hostile filesystem objects, snapshot them independently, and assert byte-identical preservation plus zero driver effects. Add the recording-mode/project/path and no-live-authority cases, including `PRODUCTION_DRIVER_UNAVAILABLE` before effects.

3. **HIGH — operation durability, replay, lease, and failure ordering are under-covered.** Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:55-67`; `tests/Deployment/yii2_stand_restore_failure_001_test.py:12-20`; `tests/Deployment/yii2_stand_restore_roundtrip_001_test.py:8-11`. Same-id success replay and one conflict are asserted, but tests do not inspect an append-only operation record, argument digest, lease, confirmed pointer, or their ordering. There is no contender/`LEASE_HELD`, malformed/truncated/conflicting ledger or lease, definite pre-effect `RESTORE_FAILED` with byte-identical replay, exception/timeout after possible effect, durable UNKNOWN replay that preserves the lease and forbids a new driver call, or crash-after-confirmed replay/pointer repair. The interrupt/readiness cases assert only a non-success envelope and could pass while publishing confirmed state, discarding diagnostics, repeating effects, or silently rewriting history. Add independent evidence-tree and effect-log snapshots around each material definite/ambiguous boundary and assert exact mutation/non-mutation, retained lease/evidence, pointer absence or repair, and replay behavior.

4. **HIGH — the roundtrip does not prove several stated restored facts or the post-effect verifier.** Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:45-53`; `tests/Deployment/yii2_stand_restore_roundtrip_001_test.py:8-11`; `tests/Support/stand_restore_contract.py:8-29`. The test asserts three generated JSON summaries, but never establishes then destroys/mutates an actual target state, never asserts exact schema/table inventory separately from a `schema_version` value, does not independently observe AUTO_INCREMENT behavior (for example, the next inserted ID), does not compare artifact file bytes/modes, and does not demonstrate that inventory/readiness are freshly derived rather than copied from fixture output. An implementation that serializes the fixture payload JSON to these filenames and writes `{"readiness":"ready"}` can pass without restoring the promised DB/persistent contract. Make the proof prepare observable target state, back it up, destructively alter it through the authorized disposable contour, restore it, and interrogate the resulting DB/persistent filesystem through independent observers. Assert history rows, schema/table inventory, next generated ID, literal artifact bytes/modes, sessions, inventory, and fresh readiness. Add post-restore inventory failure separately from readiness failure.

5. **MEDIUM — the Yii transport/output and ownership checks are lexical and incomplete.** Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:9-19,69-78`; `tests/Yii2/yii2_stand_restore_console_001_test.php:1-6`; `tests/Architecture/yii2_stand_restore_boundary_001_test.py:5-8`. The PHP transport test only checks controller file existence. No test covers missing/duplicate/malformed arguments, interactive mode, exact exit 64/envelope, canonical single-object stdout/newline, empty stderr, or secret/path/native-fragment redaction. The architecture test can be satisfied by named files and one config string; it neither proves the controller is thin nor inventories all production consumers/responsibilities of `RuntimeRecovery` and the legacy CLI. Add real CLI rejection/output-safety cases and a bounded production composition/consumer inventory that will fail if restore logic delegates to legacy recovery or if an undisclosed production consumer is removed/ignored.

## Evidence

The retained architecture, failure, roundtrip, and PHP records are all marked `INTENDED_RED` and bind start/end to candidate source `473371eca02a0456886d190133765329cc72c6b7c83a0ed9932f110a9df2f4ad` and executable source `77eba5dc5eb67d936414e5f20511368f72c2eadf9787ff6984919117164450dd`. The roundtrip fixture successfully executes the existing stand-backup setup before reporting the intentionally missing restore controller, so the former Composer/bootstrap blocker is resolved. The failures are valid for the missing seam but provide no sensitivity for the unasserted behaviors above.

Harness reports PR, CI, and deployment as `UNKNOWN`; none is treated as GREEN, approval, or authorization.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2, complete the bounded public-seam matrix without changing the approved behavioral intent, retain fresh exact-source intended RED records, rebuild the package under harness freshness rules, and resubmit it for independent Gate 3 review.

---

## Correction review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170550Z-de1d2fee30/package.json`.
- Exact reviewed source: reconstructible snapshot over base `11b8587372040ab045d1a69faeeb1432ff85e400`, candidate source `987927bfcdb5b1fe32888cb73c100c2af1f6f8ef3cccbfa9c41f53f12b56e384`, executable source `66b8146cd50f0a9ea624b60b31877f6b8f58a650e00c99c2ba240bc217f42b08`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170550Z-de1d2fee30/snapshot/source.patch`, SHA-256 `9d3df1edd2743252a6d3278fd2ad3ca994e83a5ccb6ee9f7c90eba7838244f7f`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170550Z-de1d2fee30/verification-plan.json`, SHA-256 `36a698ee1e3da2939cb355489688b6ddf4b8903c227a335e6eb41aba22cf5acb`.
- Independence is unchanged; this reviewer authored none of the corrections or fresh evidence.

### Resolved portions

The correction materially improves the candidate. The failure suite now covers corrupt manifest and pointer, missing/extra members, symlink/directory bundle members, source/image/database/volume identity mismatch, a real preserved foreign target, production-driver denial, definite pre-effect failure, UNKNOWN replay without a repeated driver call, and separate readiness/inventory-after-restore failures. The roundtrip now observes schema inventory, the declared next generated ID, literal artifact bytes and mode, sessions, readiness/inventory, confirmed pointer, one operation record, and effect-free replay. The PHP test now exercises the real Yii CLI for malformed/missing/duplicate/interactive argv and exact safe output.

All four fresh records are start/end bound without drift to candidate source `987927bfcdb5b1fe32888cb73c100c2af1f6f8ef3cccbfa9c41f53f12b56e384` and executable source `66b8146cd50f0a9ea624b60b31877f6b8f58a650e00c99c2ba240bc217f42b08`. The deployment fixtures complete the existing real stand-backup command before failing on the intentionally absent restore controller, and the PHP transport failure is likewise the absent controller. These remain clean `INTENDED_RED`, not setup failure.

### Remaining findings

1. **HIGH — required inventory and unsafe-target admission remain untested.** Prior findings 1 and 2 are only partially resolved. Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:24-43`; `tests/Deployment/yii2_stand_restore_failure_001_test.py:12-35`. The identity matrix omits the explicitly required inventory-digest mismatch. The target case covers one ordinary foreign file, but no target-root symlink or special object/unsafe path is arranged; the fixture boolean `target_inventory_matches` is never set false. Thus implementations that ignore target inventory or follow/adopt an unsafe target can pass. Add at minimum a coherent target/bundle inventory mismatch and one real target symlink (or representative special-object/unsafe-root case), with byte-identical external canary/target evidence, no ledger/pointer, and zero driver effects.

2. **HIGH — UNKNOWN mutual exclusion and corrupted durable evidence are still not observed.** Prior finding 3 is only partially resolved. Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:55-67`; `tests/Deployment/yii2_stand_restore_failure_001_test.py:36-46`. The test checks that a lease pathname exists after UNKNOWN and same-operation replay avoids a second effect, but never submits another operation and requires `LEASE_HELD`; an implementation may leave an inert marker while allowing a new destructive restore. It also has no malformed/truncated/conflicting ledger-or-lease case, although the normative contract explicitly requires `OUTCOME_UNKNOWN` before a new effect. Add one post-UNKNOWN different-operation contender proving exit 75/`LEASE_HELD` with unchanged evidence/effects, and representative malformed durable evidence proving fail-closed UNKNOWN before the driver. This is the minimum sensitivity needed for interruption/ambiguous-outcome safety rather than exhaustive crash simulation.

3. **MEDIUM — confirmed/ledger state assertions are still too shallow to prove binding and append-only facts.** Prior finding 3 remains partially open. Locations: `specs/YII2-STAND-RESTORE-CONTROL-001.md:55-60`; `tests/Deployment/yii2_stand_restore_roundtrip_001_test.py:10-14`; `tests/Support/stand_restore_contract.py:29-31`. The roundtrip checks only the ledger count and one pointer field; it never checks that the record binds operation UUID, target/argument digest, bundle digest and terminal outcome, or that replay leaves their exact bytes unchanged. An implementation writing an arbitrary one-line placeholder ledger and partial pointer passes. Assert the normative binding fields and snapshot exact ledger/pointer bytes before replay. For definite failure and UNKNOWN, assert the corresponding durable terminal record rather than only output and lease existence.

The stronger materialized roundtrip and CLI corrections resolve prior findings 4 and 5 for Gate 3 purposes. The remaining items above are bounded omissions from the already approved contract, not requests for new behavior or live tooling.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Complete the narrow inventory/unsafe-target and durable operation/lease assertions, retain fresh exact-source intended RED, rebuild the harness package, and resubmit for independent Gate 3 review. CI and deployment remain `UNKNOWN` and are not treated as approval or authorization.

---

## Final narrow rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170814Z-55cf40b528/package.json`.
- Exact reviewed source: reconstructible snapshot over base `11b8587372040ab045d1a69faeeb1432ff85e400`, candidate source `8b722f14dbae5d2955eeb4335ae4a9597f6dd9930f2e8c2204fb081bd53396fb`, executable source `56b4cdc156667cc762429580feb4323c8aa62e4d68f9e8251a959d121ee981bb`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170814Z-55cf40b528/snapshot/source.patch`, SHA-256 `09a9271a1272a44f0ab322ce6cb68acd5b5aa8465477e5c22eb10572a610034c`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T170814Z-55cf40b528/verification-plan.json`, SHA-256 `f265e0ca6638fbc213fb5f2f8332f2d46ca1a37e1741437a39e84fa4cf2253be`.
- Independence remains unchanged; this reviewer authored none of the specification, tests, corrections, or retained evidence.

### Finding disposition

All remaining findings are resolved without expanding the normative scope.

The target-admission correction now drives an explicit failed target-inventory observation and requires exit 64 `TARGET_INVALID` before ledger creation or driver effects. It separately makes the actual target root a symlink to an external directory and proves the external canary remains byte-identical, with no ledger or effects. These cases are sensitive to both ignoring inventory admission and following/adopting an unsafe target.

The ambiguity correction creates durable `OUTCOME_UNKNOWN`, verifies that outcome in the operation ledger, then submits a different operation UUID and requires exit 75 `LEASE_HELD` without another driver effect. It also places representative truncated bytes at each ledger and lease path and requires exit 70 `OUTCOME_UNKNOWN`, no target, and no effect. An inert lease marker or permissive parse can no longer satisfy the tests.

The roundtrip correction independently computes the canonical target and argument digests and checks that the confirmed pointer binds operation, target, and bundle while the append-only record binds operation, target, arguments, bundle, outcome, exit, and public result. It snapshots exact pointer and ledger bytes and requires both to remain byte-identical after same-operation replay, alongside the already required unchanged effect log. This closes the prior placeholder-record and silent-rewrite holes.

All four retained runs are fresh `INTENDED_RED`, bound at start and end without drift to candidate source `8b722f14dbae5d2955eeb4335ae4a9597f6dd9930f2e8c2204fb081bd53396fb` and executable source `56b4cdc156667cc762429580feb4323c8aa62e4d68f9e8251a959d121ee981bb`. The deployment fixtures first complete the existing production-shaped stand-backup command and then fail on the intentionally absent restore controller; the architecture and PHP transport records fail on the same missing seam. No environment/bootstrap failure is present.

No findings remain for the agreed Gate 3 scope. PR, CI, and deployment remain `UNKNOWN`; this review neither treats them as GREEN nor authorizes live restore, drill, or cutover.

### Final verdict

`APPROVED`

Gate 3 passes for exact source `8b722f14dbae5d2955eeb4335ae4a9597f6dd9930f2e8c2204fb081bd53396fb`. Gate 4 may proceed against this reviewed contract and test matrix. Any later change to normative expectations or executable tests requires fresh Gate 2 evidence and independent Gate 3 review.

---

## Gate 5 correction test review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T173401Z-b0e1915b76/package.json`.
- Exact reviewed source: reconstructible snapshot over base `11b8587372040ab045d1a69faeeb1432ff85e400`, candidate source `0372bd30656b16986b090913d8b5277090773cb567f9dad0a186112f600b429a`, executable source `b691e375853ce1cf050ad528d797bb51597f60ca84b6e830474532a8830e062c`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T173401Z-b0e1915b76/snapshot/source.patch`, SHA-256 `fc0a26aa22938d0884369c949313066e05e729744fd70630b4e1bf8a405ebff5`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T173401Z-b0e1915b76/verification-plan.json`, SHA-256 `5530d0074d2f0867ecd8ba9a1dcc9059d03da908b54e12568549f86d6d8e6603`.
- Scope is limited to new correction-test sensitivity and the legacy-responsibility inventory. Independence is unchanged; this reviewer authored none of them.

### Assessment

The three new public-seam cases are appropriate and sensitive to the Gate 5 defects.

- `materialize_failure="short-artifact-write"` does not supply the desired public outcome. It asks the real Yii/application contour to encounter incomplete materialization, then requires independent observation to prevent success, suppress the confirmed pointer, and durably record `OUTCOME_UNKNOWN`. The current implementation incorrectly returns `RESTORE_VERIFIED`, so a fixture-outcome-only check cannot satisfy it.
- `swap_target_after_preflight` deterministically replaces the admitted pathname at the effect boundary and protects an external byte canary. It requires ambiguous failure and no confirmed pointer. The current implementation reaches success, demonstrating sensitivity to the post-preflight substitution defect rather than to static admission alone.
- The trace requires the safety-relevant order: durable lease, durable terminal record, confirmed-pointer publication and durability, then lease release and release durability. The current implementation supplies no trace. This is a bounded test-only observer of the required crash ordering, not a new production entry point.

Record `1789320812588188000-a955ce9cb5e04fe1937bb0599e20eff6` is clean `INTENDED_RED`: it has no source drift and fails exactly on the absent durability trace, trusted short materialization, and successful target substitution. Architecture, roundtrip, and console acceptances are GREEN at the same candidate/executable source. The expected failure values remain derived from the normative no-success-on-ambiguity contract.

The delivery artifact correctly identifies the currently retained old-format restore, v22/v23 forward migration, jobs recovery, v22-v24 compatibility, production jobs contract, and legacy runbook command, and correctly concludes that `RuntimeRecovery` and its CLI must remain.

### Remaining finding

1. **MEDIUM — the legacy inventory is documentary but not executable as required by the returned Gate 5 finding.** Locations: `docs/operations/yii2-stand-restore-control-delivery-2026-09-13.md:6-17`; `tests/Architecture/yii2_stand_restore_boundary_001_test.py:5-8`; originating finding `reviews/code/YII2-STAND-RESTORE-CONTROL-001.md:73-86`. The architecture test still asserts only that the legacy CLI exists and that `runtime_recovery_forward_update_001_test.php` names it. It remains GREEN if the delivery artifact silently drops old-format recovery, jobs recovery, either normative production contract, schema-v22-v24 responsibility, or the runbook consumer. The originating finding explicitly required the architecture assertion to be sensitive to these retained responsibilities. Correction: extend the bounded architecture inventory to assert the enumerated consumer/contract/runbook paths and their continued binding to `RuntimeRecovery`/legacy CLI as applicable; retain fresh exact-source evidence. Do not remove legacy code or add new behavior.

### Verdict

`CHANGES_REQUESTED`

The three Gate 5 correction behavior tests are approved and may be implemented without expectation changes. The package as a whole needs only the narrow executable legacy-inventory correction above and a fresh source-bound architecture result before the correction Gate 3 can pass. CI and deployment remain `UNKNOWN` and are not treated as GREEN or authorization.

---

## Final Gate 5 correction test rereview — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T173612Z-49ff7dfbd6/package.json`.
- Exact reviewed source: reconstructible snapshot over base `11b8587372040ab045d1a69faeeb1432ff85e400`, candidate source `c4de541576151b79cdf63318ed3ae87b4198baa73e275a1a053d752d587b902f`, executable source `2b2bf22ac4f1c05e35b432b684872ad095341e315850a634c0c32a49a682cce9`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T173612Z-49ff7dfbd6/snapshot/source.patch`, SHA-256 `473147be974dfdb39067b558d2837dbab392a6251142a6b4d06320915ec46bfb`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T173612Z-49ff7dfbd6/verification-plan.json`, SHA-256 `05dc2c656db86eab5331ef3a543c03b68cbe382e9c37671da3c37567ec9fa196`.
- Independence remains unchanged; this reviewer authored none of the corrected artifacts or evidence.

### Finding disposition

The sole remaining finding is resolved. The architecture test now enumerates all six retained responsibility paths, requires the delivery inventory to name each path and its exact responsibility, requires each artifact to exist, and requires every consumer/contract/runbook artifact to retain its `fmonitor2-runtime-recovery.php` binding. It will therefore fail if old-format restore, historical v22/v23 migration, jobs recovery, v22-v24 compatibility, the production jobs contract, or the legacy runbook command is silently omitted or detached while this slice deliberately preserves the legacy owner.

Architecture record `1789320949255976000-755de6d65bd444cd877ee03fce4ff95a` is GREEN, exact-source bound, and has no drift. The previously approved observation/materialization, target-substitution, and durability-order cases remain clean `INTENDED_RED` in record `1789320951206195000-8d7b348a8f8e4cd4907c9ac0e35ca56f`: exactly those three assertions fail, while the pre-existing failure cases pass. Roundtrip and console records are GREEN at the same candidate/executable source.

No findings remain in this correction-test scope. CI and deployment remain `UNKNOWN`; this review does not treat either as GREEN or authorize live operations.

### Final correction test verdict

`APPROVED`

Gate 4 corrections may proceed against the three approved failure tests and executable legacy inventory at exact source `c4de541576151b79cdf63318ed3ae87b4198baa73e275a1a053d752d587b902f`. Any expectation change requires fresh Gate 2 evidence and independent review.

---

## Second Gate 5 correction test review — 2026-09-13

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T175154Z-75ffb5ead6/package.json`.
- Exact reviewed source: reconstructible snapshot over base `11b8587372040ab045d1a69faeeb1432ff85e400`, candidate source `3a58761680f1936120e323c091fc15815853211c36e7af7590c68ea21456d823`, executable source `ed822d20284f81487a9f1a8ed1921d7e5b3714c1d36dda963b448afa7f5e1695`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T175154Z-75ffb5ead6/snapshot/source.patch`, SHA-256 `66c75d37d877d3d33aea5d0d66d63c54a69d66e4218223d8297cc7c4a0bff7e6`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T175154Z-75ffb5ead6/verification-plan.json`, SHA-256 `17e52d59ed8a629fca246e4587f4ad4a5d60791cd477b33e9db7aa6859b6f885`.
- Scope is limited to the two new failure cases. Independence is unchanged; this reviewer authored neither the tests nor their evidence.

### Assessment

No findings.

`test_canonical_unknown_ledger_outcome_fails_closed` first creates a valid completed operation through the real Yii restore seam, then replaces only its terminal vocabulary/result with independently canonicalized, internally coherent but unrecognized values. Replay must return exit 70 `OUTCOME_UNKNOWN` and preserve the driver-effect inventory. The current source instead returns the attacker-controlled reason with exit 1, so the test is sensitive to semantic allowlisting rather than merely malformed JSON parsing. The expected result follows the normative rule that malformed/conflicting durable evidence is UNKNOWN before any new effect.

`test_substitution_between_check_and_file_open_cannot_touch_external_file` places an external file canary with independently captured bytes and mode, requests deterministic substitution at the narrow identity-check-to-file-open boundary, and drives the real application command. It requires a non-success safe target/UNKNOWN outcome, byte- and mode-identical external state, and no confirmed restore pointer. The current source reaches success, proving this case covers the remaining destructive pathname interval rather than duplicating the already-GREEN preflight-substitution test. The fixture arranges timing only; it does not supply the expected public outcome or replace restore logic.

Record `1789321829116947000-f9a0c2a4b872482a89089949c2429545` is clean `INTENDED_RED`, source-bound without drift, and fails only these two new assertions. Restore architecture, roundtrip, and console plus all seven retained PR #119 backup groups are GREEN at the same candidate/executable source. Expected values are independently observed at the public process and external-filesystem boundary.

CI and deployment remain `UNKNOWN`; this review neither treats them as GREEN nor authorizes live restore or cutover.

### Verdict

`APPROVED`

The two corrections may proceed against exact source `3a58761680f1936120e323c091fc15815853211c36e7af7590c68ea21456d823`. Both cases and the complete focused plan must be GREEN on the corrected source before final Gate 5 rereview; any expectation change requires another independent Gate 3 review.
