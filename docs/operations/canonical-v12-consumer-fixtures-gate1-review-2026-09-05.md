# CANONICAL-V12-CONSUMER-FIXTURES-001 v0.1 — independent technical Gate 1 review

Date: 2026-09-05. Reviewer: `/root/selection_contract_reconciliation`.
Reviewed repository HEAD: `82d283c9dc5714ec3ecd98d197e3acfc4a433daa` plus the
root-authored draft identified below. The reviewer authored none of the reviewed
specification, inventory, schema owner, engine, support, or allowlisted fixture
artifacts.

Verdict: **CHANGES_REQUESTED**.

This is a technical Gate 1 consistency review. It is not owner approval, Gate 3,
permission to edit tests, RED, GREEN, Gate 5, parent OpenSpec completion, or a
product/release decision. The already approved data-free v12 schema ownership
and reviewed engine are not being reopened.

## Blocking finding

### P0 — the OTIZ amendment required by the inventory violates the executable spec's no-range rule

Section “Observable result and literal expectation”, item 5, explicitly forbids
“ranges разрешённых versions”. The reviewed inventory nevertheless directs
`tests/Verification/harness_otiz_canonical_compat_001_test.php` lines 203–206 to
expand its successful migration “terminal/range” from 11 to 12. The actual
harness accepts `appliedVersions` when it is any unique subset of the inclusive
integer range 1..11; merely changing the bound to 12 preserves exactly the form
the executable contract prohibits.

This is observable and material. It allows an omitted or unordered migration
history to pass whenever the returned values happen to form a unique subset,
whereas the draft otherwise requires exact clean, repeat and compatible-
predecessor successor lists and says expected versions must not be derived from
production output. The future proposed patch cannot simultaneously follow the
inventory and the executable specification.

Choose and state one exact resolution before Gate 1 approval:

1. Keep the no-range invariant and amend the OTIZ harness to establish or
   observe a fixed predecessor, then assert the one exact expected
   `appliedVersions` list (and terminal 12); or
2. If that harness intentionally runs against several known canonical
   predecessors, enumerate the finite exact allowed lists and define how the
   pre-run fixture proves which one applies. Do not accept an arbitrary subset;
   or
3. Remove the OTIZ harness from this amendment allowlist and leave it as a
   separately gated compatibility-harness correction if its shared-state
   prerequisite cannot be made exact within this slice.

Update both the executable spec and inventory coherently. The current direction
“expand range to 12” is not an acceptable proposed patch under the current Gate
1 text.

## Confirmed coherent boundaries

- The remaining allowlist is traceable to stale successful composed-runner
  prerequisites after landed v12. Clean success, exact repeat and listed v5/v7/
  v8 predecessor results append only version 12 and retain exact ordering.
- The classification race rule is correctly special: after an initial full v12
  run, only v11 is removed, so the winner reports terminal schemaVersion 12 with
  `appliedVersions:[11]`; its loser remains exit 70/MIGRATION_FAILED. Direct v11
  conflict remains terminal v11.
- Family-local conflicts remain unchanged: v11 classification, v10 completion,
  v9 inspection planning, v7 checklist and earlier workforce/identity failures
  retain their owning migration version and exact failure mapping.
- Literal catalog additions are exactly the two v12 tables in binary order:
  `fm2_pilot_object_detail_quarantine`, then
  `fm2_pilot_object_details`. The inspection-item catalog count changes only
  from 31 to 33. Full-runner consumers may explicitly opt into existing
  `columnsV12()` / `indexesV12()`; shared default v1–v11 methods remain unchanged.
- `pilot_case_import_001_test.php` may update only its successful migration
  prerequisite. Its deliberate v10/v11 table removals remain, v12 tables remain
  present, and importer DDL/DML semantics are not altered. The demo-bootstrap
  test is rerun only and is not an edit target absent a new direct failure.
- Business payloads, hashes, authorization, replay, concurrency, rejection,
  cleanup and preservation assertions remain outside amendment bytes. Missing/
  extra catalog members or changed business outcomes remain hard failures; no
  skip, xfail, optional list, broad `>=11`, blanket replacement or data
  population is authorized.
- The protected `pilot_e2e_flow_001_test.php` is excluded. Its observed hash is
  pinned below and must be byte-identical at Gate 5. Its specification,
  fixtures, dependencies, approvals and registration also remain untouched.
- The delivery sequence is otherwise correct: after approved Gate 1, prepare
  an exact proposed test-only patch without applying it; demonstrate a clean
  focused old-fixture RED after PATH/vendor/test-DB setup is healthy; have a
  fresh independent Gate 3 reviewer inspect spec, RED and proposed patch; apply
  only the approved patch; then focused GREEN, architecture/lint/diff checks and
  fresh Gate 5. Environment failures do not qualify as RED, and full
  `make verify` remains separate integration evidence.
- These are test-fixture prerequisites for already approved v12 production
  behavior. No new product policy or owner decision is required.

## Required rereview

Resolve the OTIZ exact-version contract, append a correction rather than
rewriting this review, and request a fresh independent technical Gate 1 review
before preparing or applying the fixture patch. No test or production edit is
authorized by this record.

## Exact reviewed SHA-256

```text
7e344d5adc9266a1316b9d1a1a5da4905633a269d2617a60fe905dc74c7d3ef6  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
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
a4016301bc2416970a1a28791f31e5071bb9b51803feeb42d85d31a96a0c84fc  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
1fc3be0f2509943f7b3158c3c93b1442b9d1f3d107e4b7d44944493e927ca1e9  tests/InstallationProcess/pilot_http_auth_001_test.php
e932e73dafbdbf1053734a31eb1a7ede6b754563532d9fe76cc3923a81f405a2  tests/InstallationProcess/workforce_canonical_runner_001_test.php
d27df2d97bc877ffef2ac5f4cd4330684d8b17a1c03fa25be30a25485e571f98  rapid-pilot/verify-calendar-projections.php
dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30  tests/Verification/harness_otiz_canonical_compat_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```
