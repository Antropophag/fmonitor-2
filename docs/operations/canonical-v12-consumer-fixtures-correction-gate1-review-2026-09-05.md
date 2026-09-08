# CANONICAL-V12-CONSUMER-FIXTURES-001 v0.1 — corrective technical Gate 1 review

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Repository HEAD observed at review execution:
`18fa6ef40a4e3edc7952831c1e03c3c5e9cbe5c9`.
The reviewer authored none of the corrected specification, applied test patch,
focused run evidence, production migration, family worker, or protected E2E.

Verdict: **APPROVED** for a two-fixture corrective patch.

This technical approval authorizes preparation of an unapplied correction to
the two exact expectations below, followed by fresh Gate 3 before application.
It is not Gate 3, GREEN, Gate 5, parent OpenSpec completion, integration approval
or permission to edit protected E2E. No product-owner decision is required.

## Independent source reconciliation

The initial inventory and Gate 1/Gate 3 review incorrectly treated the
classification race worker as the full composed production runner. Actual
`tests/Support/classification_provenance_barrier_runner.php` directly calls only
`ClassificationProvenanceSchemaMigration::apply`. It serializes its own fixed
family-local result with `schemaVersion:11` and `[11]` when applied. It neither
calls `bin/fmonitor2-migrate.php` nor knows v12. Therefore the race winner must
remain exact status 0 / schemaVersion 11 / appliedVersions `[11]`; the loser
remains exact exit 70 / `MIGRATION_FAILED`. Only the later ordinary production
CLI repeat reports schemaVersion 12 with an empty applied list.

The second failure is literal binary catalog order. In the actual
`ORDER BY BINARY TABLE_NAME` result, the relevant subsequence is:

```text
fm2_pilot_invitations
fm2_pilot_object_detail_quarantine
fm2_pilot_object_details
fm2_pilot_role_permissions
```

The applied expectation placed the object-detail tables before invitations.
The corrected specification now fixes the exact subsequence and still adds only
the two approved v12 tables.

## Focused evidence disposition

The focused log proves nine amended targets pass. It exposes exactly the two
fixture mistakes above:

- classification expected the race winner as terminal 12 but the family worker
  correctly returned terminal 11;
- workforce full-catalog comparison contained the right 33 identities but the
  two new identities were in the wrong binary position.

These are test-expectation defects, not production failures. The required
correction is limited to the classification race winner literal/label and the
workforce expected table-list ordering. It must not change classification
conflicts, loser output, ordinary production repeat, table membership, production
code, shared catalog defaults or business assertions.

The same log shows `pilot_demo_bootstrap_001_test.php` now reaches the already
retained actor-18 failure inside protected `pilot_e2e_flow_001_test.php`. That is
not a failure of the amended-input fixture slice and must not be folded into the
two-test correction. It remains a real parent/integration blocker. Protected
E2E files, fixtures, dependencies, approvals and registration remain unchanged.

## Required next gate

Prepare an exact unapplied two-test patch against current applied input hashes.
A fresh independent Gate 3 reviewer must inspect this corrected spec, the
focused evidence, the family worker and the proposed patch before application.
The patch must preserve the other nine passing amendments byte-for-byte.

After application, rerun both corrected tests and the full eleven-target focused
set. Rerun bootstrap to retain/report the unrelated protected child failure; do
not require it to pass for bounded amended-input GREEN or claim integration
GREEN. Gate 5 must distinguish the bounded fixture correction from unresolved
parent failures.

## Exact reviewed SHA-256

```text
2f716c5cc308b4897c49a1e687a1b77c283fdac8fba624bd5c3e763990a9e997  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
409a00d9d6c0cb929a6a91800d115cc81245e7349e768ef21f66fb798a6a6c56  tests/Support/classification_provenance_barrier_runner.php
c6d6f02defbad87b1c72d32a8b949eb0c1e95a29ebb48615c17a2d3f4af08d45  tests/InstallationProcess/classification_provenance_schema_001_test.php
08eecd4e3877c570bde98ccf0f9b2b77786f5e43552fcee4396c3d465235c7c9  tests/InstallationProcess/workforce_canonical_runner_001_test.php
f843522a21bc26547d0d1c273eb67b7b5e3b9f3cef67afa9f50adbd745f06f0b  /tmp/fmonitor2-v12-consumer-green.log
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php (protected unchanged)
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php (shared defaults unchanged)
```
