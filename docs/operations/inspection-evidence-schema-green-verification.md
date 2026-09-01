# INSPECTION-EVIDENCE-SCHEMA-001 — GREEN integration verification

Date: 2026-09-01  
Scope: composed canonical-runner test contracts after landed literal v8  
Status: test corrections pending fresh independent rereview

## Contract correction

Shared tests that invoke the real composed runner now expect terminal
`schemaVersion=8` and the applicable exact subset of `[1,2,3,4,5,6,7,8]`.
Direct tests of the isolated checklist-template v7 and identity v6 migration
classes retain their predecessor-specific literal versions.

The shared production catalogue contract now contains 26 v1-v8 tables. Its
four new test-owned literal fingerprints cover ordered columns, exact types,
nullability and `EXTRA` for:

- `fm2_checklist_revisions`;
- `fm2_checklist_operations`;
- `fm2_checklist_operation_installers`;
- `fm2_checklist_photos`.

It also records every exact v8 primary, unique and secondary index. The
existing exact FK/CHECK manifests remain unchanged, which asserts that v8 adds
no FK or CHECK constraints.

## Focused verification

All eight changed PHP files and the shared catalogue support file passed
`php -l`.

Passed:

```text
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
PASS: IDENTITY-ACCESS-SCHEMA-001 canonical runner and runtime ownership
PASS: PILOT-CASE-IMPORT-001 CLI contract
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
PASS: WORKFORCE-CANONICAL-RUNNER-001 complete public-runner matrix.
ok - HARNESS-OTIZ-CANONICAL-COMPAT-001 preserves canonical v1-v8 across repeated isolated OTIZ characterization
INSPECTION-EVIDENCE-SCHEMA-001 tests passed.
```

`pilot_http_auth_001_test.php` successfully passed its corrected canonical v8
migration fixture, then failed later on an unrelated current-worktree CSP
expectation:

```text
missing identity despite spoof content-security-policy
Expected: default-src 'none'; style-src 'self'; img-src 'self'; ...
Actual:   default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; ...
```

This is not a v8 catalogue regression and its pre-existing expectation was not
changed. No GRILL-002 expectation was changed.

## Corrected test manifest

```text
d8d96368b9f45d66da15dff91bf6265cacf6eb0f5b7ea44ad71676197a2b0b9d  tests/InstallationProcess/checklist_template_schema_001_test.php
bb411e4bcf6d1d9dbb0fb7f513672923c903bb32c7fe893c6e8e30e9e2068ad4  tests/InstallationProcess/identity_access_schema_001_test.php
a4f03f69b4061cc93b36488ca49319615c5cd6b367a03b761b1233d241d8b2f9  tests/InstallationProcess/pilot_case_import_001_test.php
50fefbdc32e93eee2874db1b0cd67ac782af7e28dd106654771fa6997a27ec73  tests/InstallationProcess/pilot_http_auth_001_test.php
81df9e82583c489380d917d615e00d89021c8e35e199a3639364545a5deb1d03  tests/InstallationProcess/production_migration_runner_001_test.php
7e02179ce41b308a187355b30d15cb527788db32f5827d9b3756828eae3fc7fb  tests/InstallationProcess/workforce_canonical_runner_001_test.php
b0fd777b8748a54611bf6061299b38cb48944f85b6512a7ff8e0b6bd868eb639  tests/Verification/harness_otiz_canonical_compat_001_test.php
e06b02081fcebd70407a6d18c8d294549cc8b16bc1dbd948bb92497d99dbb989  tests/Support/ProductionMigrationRunnerCatalogContract.php
3e9ce3564f3b5645e2c703887cd7782a3d5804911437f83387a2283723e3ea38  tests/InstallationProcess/inspection_evidence_schema_001_test.php
```

No production or executable-specification file was changed by this integration
test correction. Independent test rereview is required for the changed test
bytes.
# Full integration verification

После independent review shared v1–v8 catalogue contracts повторный
`make verify` дал exact established baseline:

```text
VERIFY_STAGE test-db-reset PASS
VERIFY_STAGE migrate PASS (schemaVersion=8, appliedVersions=[1,2,3,4,5,6,7,8])
VERIFY_STAGE architecture-check PASS (7 rules)
VERIFY_STAGE lint PASS
VERIFY_STAGE unit-test PASS
REGRESSION_FAILURE: 8 verifier(s) failed
VERIFY_STAGE db-test FAIL
VERIFY_STAGE characterization-test PASS
REGRESSION_FAILURE: 1 verifier(s) failed
VERIFY_STAGE e2e-test FAIL
VERIFY_STAGE diff-check PASS
FULL_VERIFICATION_FAILURE count=2 stages=db-test,e2e-test
```

Все восемь DB failures — прежние GRILL-002 CSP/local-RBAC fixtures; единственный
E2E failure — прежний missing assignment-order artifact. Новых v8 failures нет.
