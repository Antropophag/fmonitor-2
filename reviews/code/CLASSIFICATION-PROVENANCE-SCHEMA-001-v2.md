# CLASSIFICATION-PROVENANCE-SCHEMA-001 v2 — fresh independent Gate 5 rereview

Date: 2026-09-02  
Reviewer: separately tasked independent agent `/root/classification_code_review`  
Gate: 5 rereview after inherited-fixture integration correction  
Verdict: **APPROVED**

The reviewer did not author or edit the reviewed production code or tests. This
append-only review record is the reviewer's only slice edit.

## Reviewed boundary

The approved executable spec, Gate 3 verifier/helper, and production hashes are
unchanged from the first Gate 5 review. This rereview additionally pins the
integration correction:

```text
43a125ae682b5a8d6c97d9d6a3d22857b68bcba10a17fc9f3905e07c9dba1c11  tests/InstallationProcess/checklist_template_schema_001_test.php
5766681ba1297b13ca9c624da852e89629f7981a46217d79142b95240dd938a3  tests/InstallationProcess/identity_access_schema_001_test.php
534383ee0add17e3ec9d174b95da5d486a0323fc4f3a7094c668210509e2f886  tests/InstallationProcess/inspection_evidence_schema_001_test.php
41be1ad7756bc3403823f07475639a21d1ceb205e595cc06e04b2d0f09465784  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
7bcaf243ed9c98d05ba7a3884f7a325f6d7dec46b955545d72ecaa37b6cb4931  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
5016c0a2f1ecc09a4eb03d69118ad4c3141ffd314d10f5c0e0c58b51765db5d4  tests/InstallationProcess/inspection_planning_schema_001_test.php
6185d23e584a892de6ce38046c320e7113c2b755e96b5e458f20a2054b1b62e2  tests/InstallationProcess/installation_completion_runtime_ddl_001_test.php
8f96b6c66042137ea2d8c64afdc8cceca3b87b145ec6c7c0af5c14660228f729  tests/InstallationProcess/installation_completion_schema_001_test.php
c2df066db8a9d152989801f46feddd72a595c684e6fb866a5421dc960291dc45  tests/InstallationProcess/pilot_case_import_001_test.php
0c8074ed4548f34fc12e7c3f6a4a30458939f0726caaa78d7b21c3b1b4b1c118  tests/InstallationProcess/pilot_http_auth_001_test.php
c50356d86e4f961e2e02c6c91e7a438be2e60c4ff2edd760aeed7f7d73656dda  tests/InstallationProcess/production_migration_runner_001_test.php
e932e73dafbdbf1053734a31eb1a7ede6b754563532d9fe76cc3923a81f405a2  tests/InstallationProcess/workforce_canonical_runner_001_test.php
f05528de0c9a0baa504b926f31d93d27cef685f8d0126c471914710cb4bf526c  tests/Support/ProductionMigrationRunnerCatalogContract.php
dd2c8cd847332b950318206777cae7a3b823958aea0018aef9e5225170f44a30  tests/Verification/harness_otiz_canonical_compat_001_test.php
d27df2d97bc877ffef2ac5f4cd4330684d8b17a1c03fa25be30a25485e571f98  rapid-pilot/verify-calendar-projections.php
ef17ad222740d35701df66fffcf45a988fc970c5ad8582e15434b4ee1fa9dfdd  docs/operations/classification-provenance-v11-integration-correction-evidence.md
```

## Standards axis

No finding remains. The correction is test/harness-only and does not change
production behavior. It replaces stale terminal-v10 literals with exact v11
catalogue outcomes and extends independent literal inventories with the exact
classification-provenance columns and ordered indexes. Existing predecessor,
conflict, preservation, prefix and runtime no-DDL assertions remain present;
none was deleted or relaxed.

The planning/completion runtime fixtures no longer execute test-owned
`CREATE TABLE` for the v11-owned table. They insert complete valid provenance
rows into the table created by the public canonical runner, while their full
before/after schema and data comparisons remain intact. This strengthens the
ownership proof and resolves the prior duplicate-table setup failures.

`pilot_case_import_001_test.php` now first requires the exact public v11 result.
It subsequently removes later migration tables, including v11 provenance, only
to isolate its older case-import contract; this mirrors its established
successor-isolation treatment and does not weaken the independent v11 catalogue
or classification verifier. Its canonical admin credential path passes.

## Spec axis

The sole blocker in
`reviews/code/CLASSIFICATION-PROVENANCE-SCHEMA-001.md` is closed. All inherited
canonical-runner consumers now accept and inspect terminal v11, the independent
catalogue includes `fm2_migration_classification_provenance`, and all previously
identified classification-attributable tests pass. The approved exact v2 spec,
Gate 3 test hashes and production hashes remain unchanged, so the first review's
positive findings on the no-op production barrier, inaccessible verifier seam,
absence of locks/delays/ledger, prefix behavior, preservation, semantic
fingerprint and pre-source runtime checks continue to apply.

No assertion weakening or taxonomy/import-transaction expansion was found.

## Reproduced verification

The fresh focused matrix passed:

```text
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.
PASS: PILOT-CASE-IMPORT-001 CLI contract
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
PASS: IDENTITY-ACCESS-SCHEMA-001 canonical runner and runtime ownership
INSPECTION-EVIDENCE-SCHEMA-001 tests passed.
PASS: INSPECTION-ITEM-COMPLETE-001 MariaDB concurrency and persistence
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix
PASS: INSTALLATION-COMPLETION-SCHEMA-001 DML-only runtime matrix
PASS: INSTALLATION-COMPLETION-SCHEMA-001 migration and chain matrix
PASS calendar bounded projections, deterministic DOM and fail-closed overflow
ok - HARNESS-OTIZ-CANONICAL-COMPAT-001 preserves canonical v1-v11
PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE
PASS CLASSIFICATION-PROVENANCE-SCHEMA-001 deterministic verifier
ARCHITECTURE CHECK PASSED (7 rules)
Change 'canonicalize-classification-provenance-schema' is valid
git diff --check: exit 0, empty output
```

A fresh `make verify` reaches and passes every v11 classification,
canonical-runner, runtime-DDL and characterization check. It ends with:

```text
FULL_VERIFICATION_FAILURE count=3 stages=unit-test,db-test,e2e-test
```

Those remaining failures are independently approved/in-progress intentional RED
or predecessor work for session storage, work-navigation removal and RBAC/Pilot
HTTP admission. Characterization and diff-check pass. No remaining transcript
mentions a terminal-v11 mismatch, missing/duplicate classification table, or
classification provenance behavior failure. They therefore do not reopen this
slice's code review, but global integration must not claim `VERIFY_OK`.

## Verdict

The prior Gate 5 finding is fully resolved without changing approved tests or
production behavior. Standards and spec axes have no remaining classification
v11 finding. Verdict: **APPROVED** for the exact hashes above. This approval is
slice-scoped and is not a claim that the repository-wide `make verify` is GREEN.
