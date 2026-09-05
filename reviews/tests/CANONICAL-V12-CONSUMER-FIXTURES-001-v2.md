# Gate 3 corrective test review — CANONICAL-V12-CONSUMER-FIXTURES-001 v2

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Repository HEAD observed at final review execution:
`7fede502c056ffd1408aa8468cb453dc647c30ca`.
The reviewer authored none of the corrected specification, focused evidence,
proposed patch, family worker, applied v1 fixtures, shared defaults or protected
E2E artifacts.

Verdict: **APPROVED**.

This approval applies only to the exact unapplied two-file patch hash below. It
permits application for minimal corrective GREEN. It is not production approval,
Gate 5, parent OpenSpec completion, full-verification approval or launch
approval. Any additional test change returns to Gate 2/3.

## Traceability and sensitivity

The focused first-application log proves nine v1 amendment targets pass and two
fail for independently confirmed expectation mistakes:

1. The classification race expected schemaVersion 12, while the actual approved
   barrier worker directly invokes only `ClassificationProvenanceSchemaMigration`
   and serializes fixed schemaVersion 11 with `[11]`.
2. The workforce catalog expected the right 33 identities but placed the two
   v12 names before `fm2_pilot_invitations`, contrary to binary name order.

The corrected Gate 1 contract fixes both expected values from actual public
ownership boundaries rather than accepting production output loosely. The raw
evidence and current input hashes match the reviewed record.

## Exact patch assessment

`git apply --check` passes. The patch changes exactly one line in each of two
approved test files:

- classification race winner changes only
  `schemaVersion:12` to `schemaVersion:11`; `[11]`, loser exit 70/
  MIGRATION_FAILED, race protocol, preservation and the later ordinary
  production repeat at schemaVersion 12 / `[]` remain byte-identical;
- workforce expected table list moves only `fm2_pilot_invitations` before
  `fm2_pilot_object_detail_quarantine` and `fm2_pilot_object_details`, yielding
  the exact binary subsequence invitations → quarantine → details → role
  permissions without changing membership or any result.

No other line, test, production file, support worker, shared catalog default,
business assertion, cleanup path, AI behavior or protected E2E artifact is in
the patch. The other nine passing amendments remain untouched. This correction
cannot manufacture a false full GREEN: `pilot_demo_bootstrap` still reaches the
retained actor-18 protected-E2E failure, which remains a parent/integration
blocker and does not authorize protected edits.

## Required GREEN boundary

Apply only the exact reviewed patch, then rerun both corrected tests and the
full eleven-target focused set. Record the bootstrap child failure separately as
retained unrelated evidence. Fresh Gate 5 must verify the applied diff, nine
unchanged passing amendments, exact shared-default/protected hashes and bounded
GREEN without claiming full `make verify` success.

## Exact reviewed SHA-256

```text
a06f772d4dae6d36006df830f3bf6848753fa6b588c55e97d98e6360dc9080f3  docs/operations/patches/canonical-v12-consumer-fixtures-correction-v2.patch
2f716c5cc308b4897c49a1e687a1b77c283fdac8fba624bd5c3e763990a9e997  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
f6c390e3d3a63a7e92c972cf68fde03d36493cc3732c0b080b7207af65bea64c  docs/operations/canonical-v12-consumer-fixtures-correction-gate1-review-2026-09-05.md
409a00d9d6c0cb929a6a91800d115cc81245e7349e768ef21f66fb798a6a6c56  tests/Support/classification_provenance_barrier_runner.php
c6d6f02defbad87b1c72d32a8b949eb0c1e95a29ebb48615c17a2d3f4af08d45  tests/InstallationProcess/classification_provenance_schema_001_test.php
08eecd4e3877c570bde98ccf0f9b2b77786f5e43552fcee4396c3d465235c7c9  tests/InstallationProcess/workforce_canonical_runner_001_test.php
f843522a21bc26547d0d1c273eb67b7b5e3b9f3cef67afa9f50adbd745f06f0b  /tmp/fmonitor2-v12-consumer-green.log
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php (unchanged)
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php (protected unchanged)
```
