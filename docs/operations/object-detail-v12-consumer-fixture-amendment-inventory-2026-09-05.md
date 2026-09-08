# Landed v12 consumer fixture amendment inventory

- Date: `2026-09-05`
- Reviewer: `/root/importer_authority_review`, read-only bounded inventory
- Inspected HEAD: `82d283c9dc5714ec3ecd98d197e3acfc4a433daa`
- Source log: `/tmp/fmonitor2-verify-d1a5d09.log`
- Scope: stale composed-runner/catalogue expectations only; protected
  `PILOT-E2E-FLOW-001` was not inspected and is excluded from every amendment
- Disposition: **FIXTURE_AMENDMENTS_REQUIRED; NO APPROVAL**

The log's initial PATH/vendor failures are environment/setup evidence and are
not classified here. The repeated successful runner output
`{"ok":true,"schemaVersion":12,"appliedVersions":[1,...,12]}` proves the
listed v11 expectation failures are stale fixture contracts after landed v12,
not migration failures.

## Exact amendments required

For every ordinary full production-runner clean result below, change terminal
`schemaVersion` 11 to 12 and append 12 to `appliedVersions`. For complete repeat,
retain `appliedVersions:[]` and change only terminal version to 12. For a
compatible predecessor/partial result, append 12 after the actually missing
versions. Update assertion labels/messages from v11 to v12.

- `checklist_template_schema_001_test.php`: lines 228–231 clean composed result.
- `classification_provenance_schema_001_test.php`: lines 65–66 clean/repeat;
  line 70 verifier race winner remains `appliedVersions:[11]` because only v11
  was removed while landed v12 remained exact, but terminal version becomes 12;
  ordinary repeat terminal becomes 12. Its intentional v11 family conflict at
  line 69 remains `SCHEMA_MIGRATION_CONFLICT/schemaVersion:11`.
- `identity_access_schema_001_test.php`: lines 277, 286, 309, 342, 344, 351,
  354; append v12 to clean/partial successor lists and use terminal 12.
- `inspection_evidence_schema_001_test.php`: lines 304–305 and 315.
- `inspection_item_complete_001_mariadb_test.php`: line 19 runner result and
  labels; extend `iicTables()` with exact sorted v12 members
  `fm2_pilot_object_detail_quarantine` and `fm2_pilot_object_details`, changing
  the literal full-catalogue count label from 31 to 33.
- `inspection_planning_schema_001_test.php`: shared terminal in line 102 and
  clean/partial/full runner lists at 110–111 and 132. The later intentional v9
  conflict remains terminal `schemaVersion:9`.
- `installation_completion_schema_001_test.php`: line 61 clean/repeat composed
  results only. Direct family-local `schemaVersion:10` assertions remain exact.
- `pilot_case_import_001_test.php`: line 12 migration precondition becomes full
  v12. Keep the deliberate post-migration removal of v10 completion tables and
  v11 classification table; do not remove the landed v12 object-detail family,
  because importer consumers now inherit canonical schema ownership.
- `pilot_demo_bootstrap_001_test.php`: no direct v11 literal was found. Its log
  failure is inherited from invoking `pilot_case_import_001_test.php` at line
  144, so no independent terminal-version edit is justified. Re-run after the
  child fixture correction; separately reassess catalogue counts only if a new
  direct assertion then fails. Do not touch its protected E2E dependency.
- `pilot_http_auth_001_test.php`: line 54 full runner JSON becomes v12/[1..12].
- `workforce_canonical_runner_001_test.php`: clean result/table catalogue at
  lines 310–343 gains terminal 12 and the two exact v12 tables in binary-sorted
  order; repeat line 357 terminal becomes 12; compatible v5 partial line 367
  becomes `[5,6,7,8,9,10,11,12]`. Intentional v5 and v1 conflicts remain their
  family-local versions.
- `rapid-pilot/verify-calendar-projections.php`: line 21 terminal and full list
  become v12/[1..12], with message updated. This is characterization wiring,
  not new rapid-pilot domain logic.
- `harness_otiz_canonical_compat_001_test.php`: lines 197, 203, 205–206 expand
  the successful make-migrate terminal/range to 12. Compatibility labels at
  258–259 and 290 should say v1–v12; no OTIZ behavior assertion changes.

## Shared catalogue source

`tests/Support/ProductionMigrationRunnerCatalogContract.php` already exposes
`columnsV12()` and `indexesV12()` while retaining default historical v1–v11
methods for untouched callers. Any amended full-catalogue fixture should opt in
explicitly to these v12 methods, matching the landed
`production_migration_runner_001_test.php` pattern. Do not globally change the
helper defaults: verifier-composed historical scopes and exact earlier-family
conflicts legitimately depend on older catalogues.

Tests with independent literal table lists must add only the two v12 members
above. They must not derive expected membership from live `information_schema`
or production migration implementation.

## Authority and exclusions

The owner-approved `OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` v0.4 and reviewed landed
schema engine authorize the data-free v12 family and full composed-runner
terminal. These fixture corrections add no product behavior and require normal
test amendment/independent review appropriate to the affected existing specs.
They do not approve importer DML, fixture population or consumer semantics.

Never blanket-replace every `11`: retain v11 classification family conflicts,
v10 completion results, v9 inspection-planning conflicts, v7 checklist family
results, and all other direct migration-local versions. No protected
`PILOT-E2E-FLOW-001` file or expectation is in scope.

## Exact inspected SHA-256

```text
43a125ae682b5a8d6c97d9d6a3d22857b68bcba10a17fc9f3905e07c9dba1c11  tests/InstallationProcess/checklist_template_schema_001_test.php
8de39b681a64ef8a74c497c700e15f1a461930214fe2aa8320940b18490061cc  tests/InstallationProcess/classification_provenance_schema_001_test.php
5766681ba1297b13ca9c624da852e89629f7981a46217d79142b95240dd938a3  tests/InstallationProcess/identity_access_schema_001_test.php
534383ee0add17e3ec9d174b95da5d486a0323fc4f3a7094c668210509e2f886  tests/InstallationProcess/inspection_evidence_schema_001_test.php
41be1ad7756bc3403823f07475639a21d1ceb205e595cc06e04b2d0f09465784  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
5016c0a2f1ecc09a4eb03d69118ad4c3141ffd314d10f5c0e0c58b51765db5d4  tests/InstallationProcess/inspection_planning_schema_001_test.php
8f96b6c66042137ea2d8c64afdc8cceca3b87b145ec6c7c0af5c14660228f729  tests/InstallationProcess/installation_completion_schema_001_test.php
c2df066db8a9d152989801f46feddd72a595c684e6fb866a5421dc960291dc45  tests/InstallationProcess/pilot_case_import_001_test.php
a4016301bc2416970a1a28791f31e5071bb9b51803feeb42d85d31a96a0c84fc  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
1fc3be0f2509943f7b3158c3c93b1442b9d1f3d107e4b7d44944493e927ca1e9  tests/InstallationProcess/pilot_http_auth_001_test.php
e932e73dafbdbf1053734a31eb1a7ede6b754563532d9fe76cc3923a81f405a2  tests/InstallationProcess/workforce_canonical_runner_001_test.php
d27df2d97bc877ffef2ac5f4cd4330684d8b17a1c03fa25be30a25485e571f98  rapid-pilot/verify-calendar-projections.php
dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30  tests/Verification/harness_otiz_canonical_compat_001_test.php
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php
c75ba4b017bae4e6ef2be25dfb1c9f3a859d70d2afdbb4ecf843e836aeb9399e  tests/InstallationProcess/production_migration_runner_001_test.php
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
02e1476203b4646a271f23e3047735ec1ced4de573cbad5b0145df4fe1a88fd8  reviews/code/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-schema-engine-v1.md
2dcb1386e7c4cc39e38c6f718e283e4b7ea0f76385af9070b5269d22f59b8f80  /tmp/fmonitor2-verify-d1a5d09.log
```
