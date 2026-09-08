# Test review: VERIFICATION-CANONICAL-FRONTIER-015

- Reviewer: `/root/original_gate3`, independently tasked agent; not author of specification or test changes.
- Test author: root implementation agent.
- Reviewed current base: `88925774f7ca805758a94930b0ea62926c0fba6b`, with the 13 bounded consumer changes identified by the external manifest.
- RED source: `fbbb41e54aedf240211ba56041269e8d2264cf63`, isolated pre-registration canonical-13 worktree.
- Specification: v0.1, independently Gate 1 APPROVED; approved original-audit and registry/selection canonical registration authority reused.
- Public seam: real migration CLI and existing consumer subprocess workflows against synthetic MariaDB fixtures.
- Verdict: `APPROVED`.

## Findings

Read the normative contract, Gate 1 approval, original-audit and selection-registration code approvals, bounded consumer diff, and existing composed catalogue proof in `production_migration_runner_001_test.php`. The patch changes only the 13 enumerated consumers: successful full-runner terminal expectations become literal 15, exact application sequences add absent 13/14/15, and empty repeat stays empty. Partial recovery sequences retain their required predecessors; workforce partial v5 expects exactly 5–15. Values are fixed literals from approved registration, not derived from the current production registry.

Historical scope remains intact. Isolated ObjectDetailSnapshot engine results and earlier failures remain version 12; the bounded classification race composition remains version 11. Only its later ordinary full-runner repeat moves to 15. No early-conflict reason/version, stderr/exit, permission, prefix, interruption, rows/counters, metadata/index/FK/CHECK or no-later-write assertion is weakened. Existing fixture omissions used by downstream workflows are untouched.

The workforce global table list remains an exact sorted no-extras comparison. Its 12 additions are literal five original-family names plus two registry and five selection names, matching the separately approved catalogue. Existing names and workforce/predecessor metadata assertions remain unchanged. Exact original-family metadata proof is reused from `ProductionOriginalAuditCatalogV13` through the unchanged composed runner test. Registry/selection exact metadata and coherence are explicitly delegated there to the independently approved registry completion/selection readiness checks. This review records that inherited delegation; it does not replace any predecessor check with a general readiness boolean.

Protected `pilot_e2e_flow_001_test.php` SHA-256 remains `8f0d3626401b4a638bb56be40e17129626f1fc320ceeda6be798e3079294909b`, matching `protected-e2e-before.sha256`. No production or protected E2E patch occurs in this slice. The existing demo-bootstrap consumer must be rerun unchanged after its import precondition is reconciled.

## RED evidence and isolation

Verified every one of the 13 test hashes against both active checkout and isolated worktree using `/Users/antropophag/.local/state/fmonitor2-verification/canonical-frontier-20260907/red-manifest-v1.json`. The RED worktree HEAD is exactly the source above and its only modifications are those 13 consumers. No driver, migration result or runtime function is replaced.

Root ran each file through real PHP; all 13 exit 255. Reviewed individual logs: canonical CLI execution succeeds at terminal 13 (clean 1–13 or empty repeat), then the updated terminal/application/catalogue assertion fails for missing approved successors. Workforce additionally exposes the seven absent registry/selection tables. Some inherited assertion messages say SETUP_FAILURE, but the captured actual result has success/exit zero and exact canonical13 output; this is intended pre-registration RED, not broken setup.

The calendar verifier's combined guard initially lacked raw subprocess evidence in its generic exception. Requested and reviewed the companion native capture `calendar-native-cli-capture.php` and `.json` in the same external directory. It executes the unchanged old-source CLI with the calendar's environment merge, synthetic database charset/collation, database-name shape and process-prefix shape. The real result is exit 0, exact schemaVersion13/applied1–13 JSON plus LF, empty stderr, and task-owned DB cleanup in finally. No test modification or output interception is used. Together with the original calendar guard failure, this resolves the ambiguity about intended terminal mismatch.

Execution belongs to root; this reviewer inspected logs, exact artifacts and capture provenance rather than claiming a separate native run. Earlier old12/current15 diagnostic failures are not used as RED. No new blocking test findings remain.

Gate 4 may apply this test-only reconciliation to the already approved current implementation. All affected-consumer GREEN, unchanged demo-bootstrap rerun, independent Gate 5, architecture/lint/diff and full exact-source verification remain required. The separate protected E2E label failure is neither fixed nor hidden by this review. Only this review record was written by the reviewer.
