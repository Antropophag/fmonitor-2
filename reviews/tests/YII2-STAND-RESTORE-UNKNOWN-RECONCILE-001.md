# Gate 3 review — YII2-STAND-RESTORE-UNKNOWN-RECONCILE-001

- Date: 2026-09-14
- Reviewer: independent `gate3_unknown_reconcile`; authored none of the reviewed specification, OpenSpec artifacts, tests, fixture, or RED evidence.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T063842Z-b25349373d/package.json`.
- Reviewed reconstructible source: candidate source `df0085644d3aa1b31caa6457dc61cc7e78c5d86c3435ac6e4b53d1304af42f87`, executable source `d0c47534c4f2d286615d7f0b23beac7d97bec1df79a09fe099ecdfcaa209f2b3`, over base `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T063842Z-b25349373d/snapshot/source.patch`, SHA-256 `4d2f4dfa9b52146d3d7d595a01a755c2507f15fafa4211e038f0d7b22ad86fd7`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T063842Z-b25349373d/verification-plan.json`, SHA-256 `161be7e9510e6c010bbcbf769c542288e41ce32db5f74b1d32e7cdeadcf313ab`.

## Assessment

The contract chooses the correct narrow public intent and keeps the transition under the existing `RuntimeRestore` application owner. It explicitly preserves the prior `OUTCOME_UNKNOWN`, forbids a confirmed restore pointer, separates reconciliation authority from rollback authority, and specifies fact-before-lease durable order with exact replay repair. The verification input is bound to issue #76 and a reconstructible exact source.

The retained runs are valid missing-seam RED: all five commands are bound to the package source/executable source and exit nonzero because `actionReconcileUnknown`/`reconcileUnknown` is absent. That evidence proves the seam is not implemented; it does not supply sensitivity for most of the admission and crash protocol claimed by acceptances A1, A3, and A4. A small implementation recognizing the fixture's two booleans, writing one `ROLLBACK_ONLY` line, deleting the lease, and publishing a pointer could satisfy the submitted tests while violating the normative authority, bundle, identity, interruption, and conflict boundaries.

## Findings

1. **HIGH — the exact admission/authorization matrix is materially incomplete.** Locations: `openspec/changes/reconcile-unknown-stand-restore/specs/operations/reconcile-unknown-stand-restore/spec.md:14-35`; `tests/Support/stand_restore_reconcile_contract.py:8-11`; `tests/Deployment/yii2_stand_restore_unknown_reconcile_failure_001_test.py:11-20`. The rejection suite covers only missing lease, one semantically mismatched lease, pointer existence, one rewritten terminal outcome, and two fixture booleans. It does not cover missing prior operation, malformed/non-canonical/duplicate ledger, unrecognized outcome, malformed or symlinked lease, independent operation/target/bundle/lease-digest mismatch, expired/malformed/wrong-scope authorization, reconciliation/prior UUID mismatch, authorization/ledger digest mismatch, rollback-bundle digest or verification failure, pinned source/image/runtime mismatch, disposable identity mismatch, or neighbor overlap. The authorization fixture itself omits the required pinned source/image/runtime tuple and observed disposable identities, so an implementation can ignore them and pass. Add representative public-seam mutations for every independent trust boundary and prove the complete evidence/target tree remains byte-identical before effects.

2. **HIGH — durable interruption and repair semantics are not tested.** Locations: `openspec/changes/reconcile-unknown-stand-restore/specs/operations/reconcile-unknown-stand-restore/spec.md:44-57`; `tests/Deployment/yii2_stand_restore_unknown_reconcile_durability_001_test.py:5-10`. The test observes only one successful trace. It injects no interruption before append, after append/before record fsync, after record fsync/before directory fsync, around lease transition, or before rollback-ready publication. It therefore does not prove that pre-durable interruption retains the original lease, that a durable fact plus ambiguous release does not claim rollback readiness, that exact replay repairs only matching state, or that conflicting/symlinked/unexpected post-fact state remains fail-closed. Add fault injection at the specified boundaries, snapshot exact ledger/lease/pointer bytes, and exercise public-seam replay for both exact repair and conflicting state.

3. **HIGH — replay/conflict coverage does not match the deterministic binding contract.** Locations: `openspec/changes/reconcile-unknown-stand-restore/specs/operations/reconcile-unknown-stand-restore/spec.md:59-68`; `tests/Deployment/yii2_stand_restore_unknown_reconcile_001_test.py:8-10`; `tests/Deployment/yii2_stand_restore_unknown_reconcile_durability_001_test.py:10`. Exact replay checks only the result tuple and recovery-ledger bytes after the successful path; it does not require the complete evidence tree, rollback-ready object, or absence of new driver effects to remain byte-identical. The sole conflict changes `bundle_digest` only after success and checks only the reason, not zero effects. There are no same reconciliation-id/prior-operation conflicts for authorization digest, target, rollback bundle, lease, or identity, nor a different reconciliation id attempting to reconcile the already-terminal prior operation. Add these representative conflicts and exact whole-tree/effect snapshots.

4. **MEDIUM — public seam, append-only fact, and authority separation assertions are too lexical/shallow.** Locations: `openspec/changes/reconcile-unknown-stand-restore/specs/operations/reconcile-unknown-stand-restore/spec.md:7-12,37-42,70-79`; `tests/Yii2/yii2_stand_restore_reconcile_console_001_test.php:1`; `tests/Architecture/yii2_stand_restore_reconcile_boundary_001_test.py:4-8`; `tests/Deployment/yii2_stand_restore_unknown_reconcile_001_test.py:8-10`. The Yii test only searches for an action name, and the architecture test only searches for one method name plus two forbidden strings. Neither executes malformed, duplicate, missing, path-only, wildcard/project-only, or interactive argv, proves thin delegation to the existing owner, or proves that no restore/restart/rollback/payload effect is reachable. The success test inspects only `state`; it does not assert the required prior-UNKNOWN/success-unconfirmed/forward-abandoned/next-exact-rollback declarations or all bound IDs/digests, canonicality, and timestamp, and it treats deletion plus `rollback-ready.json` as the only valid representation despite the specification also allowing an exact rollback-only lease. Add real CLI grammar/output tests, a bounded ownership/effect guard, and complete independently computed fact assertions. Either parameterize the two allowed lease-transition representations or narrow the normative design to the one intentionally selected.

## Evidence

The five retained records are marked `INTENDED_RED` and bind source `df0085644d3aa1b31caa6457dc61cc7e78c5d86c3435ac6e4b53d1304af42f87` and executable source `d0c47534c4f2d286615d7f0b23beac7d97bec1df79a09fe099ecdfcaa209f2b3`. The fixture successfully creates a real backup and durable UNKNOWN restore/lease before invoking the absent reconciliation seam, so this is not a setup/bootstrap RED. The records retained by the harness do not include stdout/stderr, but local source inspection makes the missing action/method failure deterministic; fresh evidence after corrections should retain enough bounded output to distinguish missing-seam RED from assertion or fixture failures.

Harness state reports PR, CI, and deployment as `UNKNOWN`; none is treated as GREEN, approval, or authorization. No live stand operation was performed by this review.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2, complete the bounded admission, fact-binding, replay/conflict, and interruption/repair matrix, retain fresh exact-source intended RED, rebuild the harness package, and resubmit for independent Gate 3 review.

---

## Correction review — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T064950Z-1bf3cd6417/package.json`.
- Exact reviewed source: reconstructible snapshot over base `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`, candidate source `b93b0e1fb0b34b71773a962b3d6f3817230b1a22bb62c40124bbb76a59dc81f9`, executable source `c3fcbdfe93f89013145f8bb09b7a27c379b46cb695b0fe6470a65459c986102d`.
- Snapshot patch SHA-256: `7a4c796244043021f4c07aec4498faab1d8c7e33e5e6dd9f61b14c2700137500`.
- Correction delta SHA-256: `54e8934553bd84031db4f0452f4b93f29724ad1cad2fc0f138378cc3cd3b5149`.
- Verification plan SHA-256: `681259fb24842fb7e209b9ecb9f0e638c3f593d6df6c15892082e8ead439b2b0`.
- Independence is unchanged; this reviewer authored none of the correction artifacts or retained evidence.

### Resolution assessment

The correction substantially resolves the original coverage findings. The fixture now supplies source, image, runtime, observed disposable identities and all operation/target/bundle/lease/record/authorization bindings. The failure suite exercises 21 independent rejection classes with distinct public reasons and complete evidence-tree/effect stability. The success suite checks the required canonical recovery fact fields, permits either specified rollback-only lease-transition representation, and proves whole-tree/effect replay stability. The public Yii test now executes invalid CLI grammar through `php bin/yii`, while the architecture guards continue to prohibit generic unlock and controller-owned evidence mutation.

The retained success, failure, durability, and Yii records are fresh `INTENDED_RED`, with matching start/end source `b93b0e1fb0b34b71773a962b3d6f3817230b1a22bb62c40124bbb76a59dc81f9` and executable source `c3fcbdfe93f89013145f8bb09b7a27c379b46cb695b0fe6470a65459c986102d`. The architecture guard is appropriately GREEN because it protects an existing negative boundary rather than requiring the missing seam.

### Remaining finding

1. **HIGH — the post-fact/pre-lease interruption assertion is unsatisfiable.** Location: `tests/Deployment/yii2_stand_restore_unknown_reconcile_durability_001_test.py:15`. For `after_fact_fsync` and `after_fact_directory_fsync`, the conditional expression reduces to `self.assertNotEqual('released', 'released')`; the actual `rollback_state()` value is deliberately replaced by the literal `'released'`. Therefore both cases must fail after any implementation, including one with the correct retained restore lease. Replace the expression with a direct assertion on the observed state—for these two points, require that `f.rollback_state()[0]` is not `released` (normally the unchanged pre-transition lease reports `invalid` in this helper). Preserve the intended assertions for the post-transition points. Line 16 should also capture one repair call result instead of invoking `f.run()` twice inside one tuple, so the test does not make an unasserted state-changing replay before checking the returned outcome.

This is a test-code defect, not a request to change the accepted behavior. The broad correction matrix otherwise addresses the returned Gate 3 findings.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on the narrow durability-test correction and fresh exact-source intended RED/package. CI and deployment remain `UNKNOWN`; this review does not authorize reconciliation, rollback, or any live stand mutation.

---

## Final narrow durability rereview — 2026-09-14

- Review scope: only the correction to `tests/Deployment/yii2_stand_restore_unknown_reconcile_durability_001_test.py` requested by the preceding correction review.
- Exact corrected source: candidate `028abb6f81ed0019e964b645504571a8e26264454215f56996aec07686f4b0a5`, executable source `d5a9fc956813f72ccc178f5b72a17c779ea101e04ac1eaef703dd9499650cf61`.
- Fresh retained record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789368774774861000-ba30292cc78748b285dc8d59d8b0ef5e.json`.
- Reviewer independence is unchanged.

### Finding disposition

The sole remaining finding is resolved. The two post-fact/pre-transition interruption cases now inspect the actual retained lease bytes and require them to equal the exact original lease. The post-transition interruption cases permit the implementation's specified released or transferred representation, including an incomplete publication state, only before exact replay repair. Repair is invoked once and its result is captured; one subsequent replay then proves whole-tree and effect stability. The impossible literal comparison and the double invocation inside one assertion are gone.

The fresh durability record is `INTENDED_RED`, with matching start/end source `028abb6f81ed0019e964b645504571a8e26264454215f56996aec07686f4b0a5` and executable source `d5a9fc956813f72ccc178f5b72a17c779ea101e04ac1eaef703dd9499650cf61`. It remains valid missing-seam RED rather than a failure caused by the corrected assertion.

No findings remain for the agreed Gate 3 scope. The broader correction assessment in the preceding review remains controlling for the admission, fact, replay, conflict, CLI, and ownership matrix.

### Final verdict

`APPROVED`

Gate 3 passes for exact corrected source `028abb6f81ed0019e964b645504571a8e26264454215f56996aec07686f4b0a5`. Gate 4 may proceed against this reviewed contract and test matrix. The executor package must be refreshed to bind this corrected source before implementation. CI and deployment remain `UNKNOWN`; no reconciliation, rollback, or live stand mutation is authorized by this verdict.

---

## Post-Gate 5 identity-drift regression review — 2026-09-14

- Scope: only the new `test_partial_fact_replay_rechecks_fresh_identity` regression in `tests/Deployment/yii2_stand_restore_unknown_reconcile_durability_001_test.py:25-30`.
- Exact test source: candidate `c704d919a0645279a642e382df464fad919ac1f002be3ee71efe950be5df4dde`, executable source `c8d580f5874e284ec91e6d241704746f007dd1aeaf6379cbe7887fed47d76279`.
- Retained record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789370497634458000-8493001ace4f42f7aa8b8ba049c7c59b.json`.
- Reviewer independence is unchanged.

### Assessment

No findings. The regression is sensitive to the reported Gate 5 defect and remains within the approved contract. It first reaches the real public seam and creates the specified durable reconciliation fact while interruption retains the pre-transition lease. It then changes the recording adapter's freshly observed network identity, leaving the exact authorization and durable fact unchanged, and invokes exact replay. Requiring exit 64 `TARGET_INVALID` demonstrates that replay repair cannot treat a matching historical fact as authority to skip current target attestation. This is consistent with the admission rule that identity drift fails before lease effects; the distinct conflicting-authorization path remains covered separately as `OPERATION_CONFLICT`.

The evidence-tree snapshot is taken after the durable partial fact and before replay, while the effect trace is separately captured. Requiring both to remain unchanged ensures that a rejected replay neither transfers/releases the retained lease nor publishes rollback readiness or adds effects. The expected value is not supplied as a driver outcome: the fixture supplies only the changed observation, so the application must perform the comparison and fail closed.

Record `1789370497634458000-8493001ace4f42f7aa8b8ba049c7c59b` is clean `INTENDED_RED`, bound at start and end to candidate source `c704d919a0645279a642e382df464fad919ac1f002be3ee71efe950be5df4dde` and executable source `c8d580f5874e284ec91e6d241704746f007dd1aeaf6379cbe7887fed47d76279`. It fails because the current implementation succeeds and transitions the lease, directly demonstrating test sensitivity rather than a fixture/bootstrap failure.

### Test-delta verdict

`APPROVED`

The implementation correction may proceed against this exact regression. The corrected durability suite and complete focused plan must be GREEN on the new exact source before independent Gate 5 rereview. CI and deployment remain `UNKNOWN`; this approval does not authorize reconciliation, rollback, or live stand mutation.

---

## Post-Gate 5 canonical compose-service regression review — 2026-09-14

- Scope: only `test_production_attestation_uses_canonical_compose_database_service` in `tests/Architecture/yii2_stand_restore_reconcile_boundary_001_test.py:9-12`.
- Exact test source: candidate `614910d0a0593d3ac726e957bbae91683f98528393513bf8062849ddf54df58c`, executable source `c969901589c83b4d7c8baa12f4100180332e0fa7033ebdbcf6ab35ccdc247d90`.
- Retained record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789371132656377000-919df5539f6441b2903adf1077ee6404.json`.
- Reviewer independence is unchanged. No live command or stand mutation was performed.

### Assessment

No findings. `deploy/runtime/compose.yaml` canonically declares the MariaDB service as `db`; `database` is the named volume, not a service. Production reconciliation attestation currently executes `docker compose ... ps -q database`, so a correct disposable target can never yield the required database container identity and the read-only preflight necessarily fails closed. The regression directly distinguishes the faulty and corrected argv by requiring `ps -q db` and forbidding `ps -q database` in the reconciliation owner.

For this narrow literal topology defect, the architecture assertion is proportionate: it neither invokes Docker nor touches a stand, and it fixes no domain behavior beyond using the repository-owned canonical service name for identity observation. Existing behavioral tests continue to own target/authorization equality, production-overlap rejection, zero effects, and replay semantics; this guard prevents the production adapter from drifting from the checked-in compose topology.

Record `1789371132656377000-919df5539f6441b2903adf1077ee6404` is clean `INTENDED_RED`, with matching start/end candidate source `614910d0a0593d3ac726e957bbae91683f98528393513bf8062849ddf54df58c` and executable source `c969901589c83b4d7c8baa12f4100180332e0fa7033ebdbcf6ab35ccdc247d90`. It fails on the exact stale `database` service literal present in `StandRestoreReconciliation`, not on environment or live Docker availability.

### Test-delta verdict

`APPROVED`

The one-token production-attestation correction may proceed against this regression. The architecture suite and complete focused plan must be GREEN on the corrected exact source before independent Gate 5 rereview. CI and deployment remain `UNKNOWN`; this verdict authorizes no reconciliation, rollback, or live stand operation.
