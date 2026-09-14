# Gate 5 review — YII2-STAND-RESTORE-UNKNOWN-RECONCILE-001

- Date: 2026-09-14
- Reviewer: independent `gate5_unknown_reconcile`; authored none of the reviewed specification, OpenSpec artifacts, tests, fixture, or implementation.
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T071852Z-a93287d49a/package.json`.
- Reviewed reconstructible source: candidate source `4017721baa16aaeedfe8806c982784f3fee8246a91d1cc0474a05c7701180887`, executable source `ec2f2e804e28cb762560c4dbcabb371d1da5b92250c58cc0258f8479dd9d3ccf`, over base `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T071852Z-a93287d49a/snapshot/source.patch`, SHA-256 `784d365dc17e5efe3f64a9e04f209773dd7de4b3f4ec6ecc61f36171db23b0a9`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T071852Z-a93287d49a/verification-plan.json`, SHA-256 `fbc3b51994aba5949cbbde888b11f795fa5ffe10e73c7a110606d1ab9fcfd774`.

## Assessment

The implementation exposes only the exact `stand-restore/reconcile-unknown` intent, keeps argv adaptation thin, preserves the original restore ledger and absence of `restored.json`, appends a canonical `ROLLBACK_ONLY` fact before atomically transferring the retained lease, and does not execute restore, restart, or rollback. The initial path validates the exact operation, authorization, lease digest, ledger digest, verified rollback bundle and disposable attestation. The eight package records are GREEN and consistently bind the reviewed candidate and executable source.

The crash-repair path, however, does not preserve that admission boundary. Once a matching recovery fact exists, `run()` returns directly to `repair()` before rechecking the authorization-bound ledger digest, rollback bundle, or current target/neighbor attestation. A changed target identity can therefore receive the rollback-only lease capability after the authorization's exact observed state has ceased to be true.

## Finding

1. **HIGH — post-fact replay transfers the lease without re-attesting the exact target and authorization-bound inputs.** Locations: `app/RuntimeRestore/StandRestoreReconciliation.php:20-22,28-30,39-50`; `tests/Deployment/yii2_stand_restore_unknown_reconcile_durability_001_test.py:5-23`. The existing-fact branch calls `repair()` before the initial-path checks of the full restore-ledger digest, independently verified rollback bundle and observed disposable identities. `repair()` checks only pointer absence, matching lease bytes/digest and authorization digest. This violates the requirement that repair be limited to the same exact admitted fact/lease state and the explicit preflight binding to current target identities/absence of production or neighbor overlap. A bounded public-seam reproduction interrupted at `after_fact_fsync`, changed `observed.network_id`, then replayed the same authorization: the first call returned `(70, OUTCOME_UNKNOWN)`, while the drifted replay returned `(0, UNKNOWN_RECONCILED_FOR_ROLLBACK)` and transferred the lease. Revalidate all immutable authorization-bound admission inputs before any repair mutation (including ledger digest/UNKNOWN record, verified rollback bundle, and fresh production-shaped attestation), then add post-fact drift/conflict cases proving zero effects for identity/overlap, ledger, and rollback-bundle changes. Test changes require renewed Gate 2/3 review.

## Evidence and boundaries

All eight generated-plan records are `GREEN`, with source `4017721baa16aaeedfe8806c982784f3fee8246a91d1cc0474a05c7701180887` and executable source `ec2f2e804e28cb762560c4dbcabb371d1da5b92250c58cc0258f8479dd9d3ccf`. They cover the architecture boundary, success/replay, durability, rejection matrix, Yii console grammar, compose regression, verification inventory, and architecture guard. The finding is a sensitivity gap in the post-fact repair state, not a contradiction of those retained results.

Harness state reports `action_authorized: false`, PR/CI/deployment `UNKNOWN`, and no publication readiness. This review performed only repository inspection and an isolated test fixture reproduction. It did not read from or mutate the real stand, retained production lease, UNKNOWN ledger, rollback package, deployment, or neighboring resources.

## Verdict

`CHANGES_REQUESTED`

Gate 5 does not pass for the reviewed source. Return the repair-path admission correction and the added post-fact drift tests through Gate 2/3, regenerate exact-source GREEN evidence, and resubmit an independent Gate 5 package. This verdict grants no reconciliation, rollback, deployment, or generic unlock authority; the real UNKNOWN state remains immutable and unverified.

---

## Correction rereview — 2026-09-14

- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T072924Z-66da405662/package.json`.
- Exact corrected source: candidate `7764bd5efbfcd2a09609a17db5829ed26b7248130bd5a17abf2fca292228653c`, executable source `5935d5eecbc70bd43b54e7c186aae467a72938cc75b07ce94c297e07408a8675`, over base `9d0ca509a99a39cc765c8fad5afd0ef1b2087721`.
- Snapshot patch SHA-256: `95c468e3a652e9548c45037208f4fd3b09359c8b4b7f7d410c126dff9edbaeef`.
- Verification plan SHA-256: `dd13e294b45ca0c6d7ff0f000044876487751ef783127ae1d810c9fc8a7025b9`.
- Independence is unchanged; this reviewer authored none of the correction code, regression test, Gate 3 test-delta review, or retained evidence.

### Finding disposition

The prior HIGH finding is resolved. Before an existing durable fact can enter lease repair, `replayAdmission()` now rechecks the complete restore-ledger digest against the fact, the exact prior operation's unchanged `OUTCOME_UNKNOWN`/target/bundle binding, absence of any restored pointer, independent verification of the bound rollback bundle, and fresh runtime/target/neighbor attestation. Only then can `repair()` compare and transfer the exact retained lease. This preserves the same admission authority across interruption instead of treating the historical recovery fact as sufficient current authority.

The independently approved regression is sensitive to the defect. A bounded public-seam reproduction at the reviewed bytes interrupted after fact fsync, changed the freshly observed network identity, and replayed the unchanged authorization. The corrected result was `(64, TARGET_INVALID)`; the complete evidence snapshot remained byte-identical and the original non-rollback lease remained in place. The former candidate returned success and transferred that lease under the same reproduction.

All eight generated focused-plan records are `GREEN` and bind source `7764bd5efbfcd2a09609a17db5829ed26b7248130bd5a17abf2fca292228653c` plus executable source `5935d5eecbc70bd43b54e7c186aae467a72938cc75b07ce94c297e07408a8675`. No findings remain for the agreed correction or complete Gate 5 scope.

Harness state still reports `action_authorized: false`, PR/CI/deployment `UNKNOWN`, and no publication readiness. This rereview used only the isolated fixture and repository/package evidence. It did not inspect or mutate the real stand, retained production lease, UNKNOWN ledger, rollback state, deployment, or neighboring resources.

### Controlling verdict

`APPROVED`

Gate 5 passes for exact corrected source `7764bd5efbfcd2a09609a17db5829ed26b7248130bd5a17abf2fca292228653c`. This approval covers the repository implementation and focused evidence only. It grants no authority to reconcile the real UNKNOWN operation, prepare or execute rollback, deploy, publish, or perform any generic unlock; those remain separately gated and currently UNKNOWN/not authorized.

---

## Canonical compose-service correction rereview — 2026-09-14

- Scope: only the production attestation correction from `docker compose ... ps -q database` to the canonical service `docker compose ... ps -q db` in `app/RuntimeRestore/StandRestoreReconciliation.php`.
- Exact corrected source: candidate `fc0a9d745f1eacf60c04d40b322ed13c872c1ee62403d32cfba89bf96e9efc59`, executable source `979a5cf639982737fb6e00fe174672080504384d602da6067c05a9563f2002be`.
- Independence is unchanged; this reviewer authored neither the production correction, defect-specific RED, Gate 3 approval, nor retained GREEN evidence.

### Assessment

No findings. `deploy/runtime/compose.yaml` declares the MariaDB service as `db`; `database` names its volume. The production attestor now queries the actual service while leaving the existing project/file pinning, observed container/project/network/volume comparison, fail-closed behavior, replay admission, and application ownership unchanged. The correction performs no state-changing Docker action.

The independently approved regression requires the canonical `ps -q db` argv and rejects the stale `ps -q database` literal. It is GREEN at the reviewed bytes. The executor-supplied focused records for architecture (`1789371251295574000-00c303ee93e54c88863876f0d369dd79`), console (`1789371254172483000-96a6716fc2184d06a80f6728c1e783a0`), valid reconciliation (`1789371256114280000-94e6e5868323400cacfc83f903518bcc`), durability (`1789371259139125000-2997a69f740f4ad9a417f50f7ec52062`), rejection (`1789371264317910000-19ff629b1f624f8bbf02a312852f7a4b`), change verification (`1789371272278823000-2bc619c3ce614f078a910372d8408eb6`), and architecture guard (`1789371294463450000-d261bac2838b4ceeb53524bb33cec054`) are all `GREEN` and bind candidate `fc0a9d745f1eacf60c04d40b322ed13c872c1ee62403d32cfba89bf96e9efc59` plus executable source `979a5cf639982737fb6e00fe174672080504384d602da6067c05a9563f2002be`.

Harness state continues to report `action_authorized: false`, CI/deployment `UNKNOWN`, and no publication readiness. This rereview inspected repository and retained evidence only; it did not query or mutate the real stand, UNKNOWN ledger, retained lease, rollback state, deployment, or neighbors.

### Controlling narrow verdict

`APPROVED`

Gate 5 passes for the canonical service correction at exact source `fc0a9d745f1eacf60c04d40b322ed13c872c1ee62403d32cfba89bf96e9efc59`. The preceding complete Gate 5 approval remains controlling for the rest of the slice. This verdict grants no reconciliation, rollback, deployment, publication, or generic-unlock authority.
