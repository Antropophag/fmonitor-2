# CANONICAL-V12-CONSUMER-FIXTURES-001 v0.1 — independent technical Gate 1 rereview

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Reviewed repository HEAD: `82d283c9dc5714ec3ecd98d197e3acfc4a433daa` plus the
corrected root-authored draft and append-only scope correction identified below.
The reviewer authored none of the reviewed specification, inventory, correction,
schema-owner, engine, support, test, or verifier artifacts.

Verdict: **APPROVED** for this bounded test-fixture amendment.

This is technical Gate 1 approval to prepare the exact proposed test-only patch
and qualifying RED evidence described by the specification. It is not Gate 3,
permission to apply an unreviewed patch, GREEN, Gate 5, parent OpenSpec
completion, integration approval or launch approval. It adds no product behavior
and needs no new product-owner decision. The existing data-free v12 schema owner
approval remains the normative behavior authority.

## Prior blocker resolved

The OTIZ compatibility harness is explicitly removed from this patch allowlist.
The earlier inventory instruction to expand its arbitrary unique-subset range to
12 is superseded and forbidden. The executable spec continues to prohibit
version ranges, optional table lists, broad terminal comparisons and
implementation-derived expectations.

The exclusion is bounded rather than a waiver: the OTIZ harness's real retained
failure remains a mandatory unfinished parent OpenSpec/launch-goal regression and
must receive a separate exact predecessor/expected-`appliedVersions` contract.
It is not skipped, made optional, treated as expected failure, or counted toward
this slice's GREEN/Done result.

## Approved exact amendment boundary

The proposed patch may edit only these eleven existing artifacts:

```text
tests/InstallationProcess/checklist_template_schema_001_test.php
tests/InstallationProcess/classification_provenance_schema_001_test.php
tests/InstallationProcess/identity_access_schema_001_test.php
tests/InstallationProcess/inspection_evidence_schema_001_test.php
tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
tests/InstallationProcess/inspection_planning_schema_001_test.php
tests/InstallationProcess/installation_completion_schema_001_test.php
tests/InstallationProcess/pilot_case_import_001_test.php
tests/InstallationProcess/pilot_http_auth_001_test.php
tests/InstallationProcess/workforce_canonical_runner_001_test.php
rapid-pilot/verify-calendar-projections.php
```

Permitted changes are limited to exact successful full-runner terminal 12,
appending 12 to already exact compatible-predecessor successor lists, exact
repeat terminal 12 with empty versions, the classification race terminal-12/
`[11]` winner, exact v12 table-catalog additions and IIC count 31→33, plus labels
that state those same prerequisites. Full-runner consumers may opt into existing
`columnsV12()` / `indexesV12()`; shared defaults remain byte-identical.

Family-local v1–v11 results and conflicts remain exact, including v11
classification, v10 completion, v9 inspection planning and v7 checklist.
Business payload/hash/replay/authorization/concurrency/rejection/cleanup/
preservation assertions cannot change. `pilot_case_import` keeps its deliberate
v10/v11 removals and the landed v12 tables. `pilot_demo_bootstrap` is rerun only,
not edited. No data population, importer behavior change, skip, xfail, relaxed
catalog or blanket textual replacement is allowed.

The protected `pilot_e2e_flow_001_test.php`, its specification, fixtures,
dependencies, approvals and runner registration remain outside the patch. Its
current exact hash is pinned below and must remain identical through Gate 5.

## Required delivery sequence

After this approval, prepare an exact proposed patch without applying it. With
PATH, vendor dependencies and test MariaDB proven healthy, capture a focused
failure of the old allowlisted fixture caused only by stale version/catalog
expectation. Environment/setup failure does not qualify as RED.

A fresh independent Gate 3 reviewer must inspect this specification, that RED
and the exact proposed patch and approve it before application. Apply exactly
the reviewed patch, then run every affected command, the inherited
`pilot_demo_bootstrap` consumer, architecture/lint/diff checks and a fresh
independent Gate 5. Any unrelated failure or additional edit leaves this
approval boundary and requires its own gate. Full `make verify` remains separate
integration evidence and cannot be called GREEN while the separately retained
OTIZ or another real failure remains.

## Exact reviewed SHA-256

```text
0097ce7e884b7cd8c84e80034adf731765900d0550472e00da596c0920383c0d  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
14de79ea31dcb99c8cf0940dad472bb5624dae6dcfcf20f0bfeee19e465d6ab9  docs/operations/canonical-v12-consumer-fixture-scope-correction-2026-09-05.md
b5e6e08ea023a8361f6eb575086a24c628ae2444e9ba065029e9e09ba4c5336b  docs/operations/canonical-v12-consumer-fixtures-gate1-review-2026-09-05.md
e5798fe4bd4a78d0db244d7f69f27f6b788eea9a869250645106050f3b41352b  docs/operations/object-detail-v12-consumer-fixture-amendment-inventory-2026-09-05.md
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
02e1476203b4646a271f23e3047735ec1ced4de573cbad5b0145df4fe1a88fd8  reviews/code/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-schema-engine-v1.md
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php
43a125ae682b5a8d6c97d9d6a3d22857b68bcba10a17fc9f3905e07c9dba1c11  tests/InstallationProcess/checklist_template_schema_001_test.php
8de39b681a64ef8a74c497c700e15f1a461930214fe2aa8320940b18490061cc  tests/InstallationProcess/classification_provenance_schema_001_test.php
5766681ba1297b13ca9c624da852e89629f7981a46217d79142b95240dd938a3  tests/InstallationProcess/identity_access_schema_001_test.php
534383ee0add17e3ec9d174b95da5d486a0323fc4f3a7094c668210509e2f886  tests/InstallationProcess/inspection_evidence_schema_001_test.php
41be1ad7756bc3403823f07475639a21d1ceb205e595cc06e04b2d0f09465784  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
5016c0a2f1ecc09a4eb03d69118ad4c3141ffd314d10f5c0e0c58b51765db5d4  tests/InstallationProcess/inspection_planning_schema_001_test.php
8f96b6c66042137ea2d8c64afdc8cceca3b87b145ec6c7c0af5c14660228f729  tests/InstallationProcess/installation_completion_schema_001_test.php
c2df066db8a9d152989801f46feddd72a595c684e6fb866a5421dc960291dc45  tests/InstallationProcess/pilot_case_import_001_test.php
1fc3be0f2509943f7b3158c3c93b1442b9d1f3d107e4b7d44944493e927ca1e9  tests/InstallationProcess/pilot_http_auth_001_test.php
e932e73dafbdbf1053734a31eb1a7ede6b754563532d9fe76cc3923a81f405a2  tests/InstallationProcess/workforce_canonical_runner_001_test.php
d27df2d97bc877ffef2ac5f4cd4330684d8b17a1c03fa25be30a25485e571f98  rapid-pilot/verify-calendar-projections.php
a4016301bc2416970a1a28791f31e5071bb9b51803feeb42d85d31a96a0c84fc  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30  tests/Verification/harness_otiz_canonical_compat_001_test.php (excluded retained regression)
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php (protected unchanged)
```
