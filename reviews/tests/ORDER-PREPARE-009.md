# Test review: ORDER-PREPARE-009

- Reviewer: `Codex agent /root/order_prepare_009_test_rereview` (fresh independent re-review; did not author the specification, test, fixture changes, or production implementation)
- Test author: `Codex agent /root`, working session `2026-08-28`
- Reviewed commit: working tree at HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`
- Specification: [`specs/ORDER-PREPARE-009.md`](../../specs/ORDER-PREPARE-009.md), version `0.1`, `APPROVED — inherited end-to-end invariant`
- Public seam: `FMonitor2\InstallationProcess\InstallationProcess::prepareAssignmentOrder(...)` and `::getInstallationObjectProcess(installationObjectId)`
- Fixture seam reviewed: operation-ID generation, revision-checked atomic replacement, typed `PersistenceCommitOutcomeUnknown`, and reconciliation lookup in `InMemoryInstallationProcessEnvironment`
- Red command: `php tests/InstallationProcess/order_prepare_009_test.php`
- Verdict: `APPROVED`

## Captured red result

```text
PHP Fatal error:  Uncaught LogicException: Preparation operation id is required for unknown commit reconciliation. in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php:419
Stack trace:
#0 /home/antropophag/code/fmonitor-2/app/InstallationProcess/InstallationProcess.php(342): FMonitor2\Tests\Support\InMemoryInstallationProcessEnvironment->replaceInstallationObjectProcessAtRevision()
#1 /home/antropophag/code/fmonitor-2/tests/InstallationProcess/order_prepare_009_test.php(52): FMonitor2\InstallationProcess\InstallationProcess->prepareAssignmentOrder()
#2 {main}
  thrown in /home/antropophag/code/fmonitor-2/tests/Support/InMemoryInstallationProcessEnvironment.php on line 419
```

Exit code: `255`.

The fresh re-review reproduced exit code `255`. The failure is the intended RED: production reaches the first atomic replacement after all approved validations and one render, but does not create or pass the required preparation operation ID. The fixture has not committed state at this failure point, so the test is not red because of an accidentally pre-applied implementation.

## Findings

- **Traceability and seam:** the scenario cites executable example A, invokes only the approved public command, and observes the command result and public process projection through the approved module seam. Fixture controls and call counters are appropriate adapter-boundary instrumentation for the unknown-commit scenario.
- **Setup and determinism:** authorization, process revision `7`, initial state, snapshots, fixed clock, renderer artifacts, operation ID, and persistence outcome are all fixed in memory. The test does not depend on wall clock, network, filesystem, production database, or legacy state.
- **Success recovery:** the exact command-result literal independently asserts the approved successful response, including date and organization type. The fixture commits before throwing typed `PersistenceCommitOutcomeUnknown`, stores the result under the operation ID passed to atomic persistence, and only returns it when reconciliation uses a matching ID. This makes the same-ID save/reconcile path behaviorally relevant.
- **No automatic renderer/save retry:** exact renderer and revision-checked replacement counts of one are present. Reconciliation is also required exactly once.
- **Complete public result and no duplicates:** exact equality against the complete literal projection requires exactly one version, the approved object/person snapshots, exactly two artifact records with their literal metadata and hashes, the installer and engineer preliminary assignments, a closed preparation task, unchanged installation/checklist flags, and exactly one complete success event. A second version, assignment, artifact, success event, or rejection event necessarily fails the assertion.
- **Expected-value independence for the response:** the expected response is a specification literal. Although the fixture's stored reconciliation response extracts date and organization type from the candidate process, an incorrect production value still disagrees with the independently fixed expected response, so this part is not tautological.
- **Technical-ID non-disclosure:** because the entire recursively nested projection must exactly equal a literal containing no `preparationOperationId`, the ID cannot leak at the root or inside an order, snapshot, artifact, assignment, event, or audit payload; any extra key at any depth fails strict array equality.
- **Exactly-once operation-ID creation:** the amended fixture increments its generation counter at the boundary and the test requires exactly one call. Together with the fixed `prep-op-9f4c`, save/reconcile matching behavior, and the exact one-call persistence and reconciliation counters, the test is sensitive to missing, repeated, or mismatched ID use.
- **Deterministic literal evidence:** the fixed input bytes yield the asserted SHA-256 values and sizes; all remaining expected values come directly from the approved scenario and inherited `ORDER-PREPARE-002` projection contract rather than production output or the technical operation index.

## Re-review resolution

All three changes requested in the first review are resolved:

1. The coarse projection checks were replaced with one complete exact literal projection assertion.
2. The fixture now exposes operation-ID generation count and the test requires exactly one generation.
3. The strengthened focused test was rerun and remains genuinely RED for the missing production operation-ID/persistence behavior, with exit code `255`.

No new blocking or non-blocking findings were identified. Gate 3 is approved; Gate 4 may proceed without changing the reviewed expectations.

## Review history

- Initial independent review by `/root/order_prepare_009_test_review`: `CHANGES_REQUESTED` because projection coverage was coarse, nested technical-ID disclosure was not excluded, and operation-ID generation was not counted.
- Fresh independent re-review by `/root/order_prepare_009_test_rereview`: `APPROVED` after verifying the complete literal projection, recursive non-disclosure by exact equality, exactly-once generation instrumentation, and reproducing the intended RED.
