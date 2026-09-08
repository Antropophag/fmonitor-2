# Gate 3 test-amendment review — CANONICAL-V12-CONSUMER-FIXTURES-001 v1

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Repository HEAD observed at final review execution:
`f594c4fa62b6e02fb85915c27242c11a7bccdc82`.
The reviewer authored none of the specification, RED evidence, proposed patch,
schema engine, migration runner, support catalogue, tests or verifier.

Verdict: **APPROVED**.

This approval applies only to the exact unapplied patch hash below. It permits
the patch author to apply those bytes for minimal test-only GREEN. It is not
production approval, Gate 5, parent OpenSpec completion, full-verification
approval or launch approval. Any test change outside the reviewed patch returns
to Gate 2/3.

## RED and expected-value independence

The control `production_migration_runner_001_test.php` passed first, proving the
landed v12 runner, PHP/vendor/PATH and disposable MariaDB environment healthy.
The unchanged `inspection_item_complete_001_mariadb_test.php` then reached that
real runner and exited 255 solely because its prerequisite expected terminal
11/[1..11] while actual was exact terminal 12/[1..12]. Cleanup completed. This
is the approved stale-fixture RED, not an environment failure or missing
production behavior despite the old assertion's SETUP_FAILURE label.

Expected terminal/repeat/successor values are fixed by the approved v12 schema
contract and predecessor construction, not copied or relaxed from observed
output. Exact table identities and IIC count derive from the approved two-table
manifest and literal binary-sorted catalog.

## Exact proposed-patch assessment

`git apply --check` succeeds against all current input bytes. The patch has
exactly eleven targets and each is in the approved allowlist:

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

The hunks change only exact successful composed-runner terminal values/lists,
exact repeats, exact compatible successor lists, the classification race
winner's terminal 12 with `[11]`, two literal v12 catalog members, IIC catalogue
count 31→33 and directly corresponding labels. No arbitrary range, optional
member, broad `>=11`, implementation-derived list or blanket replacement is
introduced.

The binary table order is correct:
`fm2_pilot_object_detail_quarantine` precedes
`fm2_pilot_object_details`. `workforce_canonical_runner` adds only these two
members. `inspection_item_complete` likewise adds exactly those members and
retains its literal complete catalog check. Shared
`ProductionMigrationRunnerCatalogContract` defaults are absent from the patch.

Family-local failures remain byte-identical: classification v11 conflict,
completion v10 results, inspection-planning v9 conflict, checklist v7 behavior
and earlier workforce/identity conflicts are not relabelled as v12. Existing
business payloads, hashes, authorization, replay, concurrency/barrier logic,
rejected outcomes, decoys, cleanup and preservation assertions are unchanged.

`pilot_case_import` changes only its successful migration prerequisite and keeps
the deliberate v10 completion and v11 classification table removals. It neither
removes v12 tables nor changes importer DDL/DML behavior. Calendar verification
changes only its exact migration prerequisite/error label. No production code,
fixture population, real data, AI restoration mechanism, protected spec or E2E
artifact appears in the patch.

## GREEN and retained-blocker boundary

After exact patch application, run all eleven target commands and relevant
schema/runner checks. `pilot_demo_bootstrap_001_test.php` must also be rerun as
the inherited consumer of `pilot_case_import`, but it is not an edit target.
Its child contracts include the protected pilot E2E test; after the stale
migration prerequisite is removed it may reach the already observed actor-18
failure. That failure must remain real and keep parent/integration verification
blocked. It does not justify editing protected E2E through this patch and does
not invalidate bounded GREEN for the exact amended fixture inputs if Gate 5
records the retained unrelated failure accurately.

The protected E2E hash and shared catalog-default hash below must remain exact at
Gate 5. Full `make verify` cannot be claimed green until the separately gated
OTIZ amendment and every retained real failure are resolved.

## Exact reviewed SHA-256

```text
ca22acc2980d7505c215cc20019fac60307c36ad21d04b11c7879f41a83bf5a4  docs/operations/patches/canonical-v12-consumer-fixtures-v1.patch
4837b493e2893ebe3bc6e2984c325f287cdfa62e5f9fb87c76d5e51c79ef31c3  docs/operations/canonical-v12-consumer-fixtures-patch-red-evidence-2026-09-05.md
0097ce7e884b7cd8c84e80034adf731765900d0550472e00da596c0920383c0d  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
7038044235a57e163f4cdd71cc56881ca81def3eb58c43fcdb42e1e1acadf5be  docs/operations/canonical-v12-consumer-fixtures-gate1-rereview-2026-09-05.md
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
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php (protected shared defaults)
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php (protected unchanged)
```

This review omits its own circular hash. The repository HEAD must be recorded
again by the applying author because concurrent append-only evidence commits may
advance it after this review; artifact hashes above define the approved patch
boundary.
