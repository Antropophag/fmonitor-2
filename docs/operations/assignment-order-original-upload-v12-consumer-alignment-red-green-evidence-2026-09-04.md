# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v12 consumer alignment evidence

Timestamp: 2026-09-04T03:38:42+03:00  
Production base: `a8f325004c21485abcde06add7023674c8a6db60`  
Pre-v12 control: `84971d3adef997c70bc33dfcbe28b54be7ead113`

## Scope

This Gate 2 regression-alignment record updates only successful canonical-runner
consumers from terminal v11 to terminal v12. It preserves direct historical
v11 conflict and classification race expectations, and does not change
production code, navigation, prepare UI, PDF rendering, or session behavior.

The successful frontier now requires exact `schemaVersion=12`, ordered
`appliedVersions=[1,2,3,4,5,6,7,8,9,10,11,12]`, the literal 38-table catalogue,
and the seven approved process capability literals. Multi-prefix fixtures remove
their v12 successor-only tables after each observation because MariaDB foreign
key symbols are database-scoped; this does not weaken any predecessor assertion.

## Intended RED on the exact pre-v12 production commit

In a disposable worktree at the exact pre-v12 commit, the corrected checklist
and identity consumers were applied without any production changes and run with:

```text
php tests/InstallationProcess/checklist_template_schema_001_test.php
php tests/InstallationProcess/identity_access_schema_001_test.php
```

Both exited `255`. Each expected terminal v12 / `[1..12]` and observed the
healthy pre-v12 runner result terminal v11 / `[1..11]`. The disposable worktree
was checked for the verifier-owned patch, removed, and `git worktree prune` was
run.

## GREEN on v12 base

Focused commands that passed on the v12 base include:

```text
php tests/InstallationProcess/checklist_template_schema_001_test.php
php tests/InstallationProcess/classification_provenance_schema_001_test.php
php tests/InstallationProcess/identity_access_schema_001_test.php
php tests/InstallationProcess/inspection_evidence_schema_001_test.php
php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
php tests/InstallationProcess/inspection_planning_schema_001_test.php
php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
php tests/InstallationProcess/installation_completion_schema_001_test.php
php tests/InstallationProcess/pilot_case_import_001_test.php
php tests/InstallationProcess/pilot_http_auth_001_test.php
php tests/InstallationProcess/workforce_canonical_runner_001_test.php
php tests/Verification/harness_otiz_canonical_compat_001_test.php
php rapid-pilot/verify-calendar-projections.php
```

`pilot_demo_bootstrap_001_test.php` reaches an inherited
`artifact_store_001_test.php` failure because the TCPDF dependency is unavailable;
that existing prepare/PDF blocker is outside this alignment.

The completeness inventory command:

```text
rg -n "schemaVersion.?[=:> ]+11|schemaVersion\\\":11|v1-v11|terminal v11|canonical v1-v11|31-table catalogue|> 11" tests rapid-pilot --glob '*.php'
```

leaves only the verifier-only classification v11 barrier result, three explicit
pre-v12 v11 setup fixtures in the v12 schema test, and unrelated numeric data.

## Full verification

`make verify` on the changed worktree reached and passed all corrected canonical
v12 consumers, including checklist template, classification provenance, identity
access, inspection evidence, inspection item MariaDB, inspection planning schema
and runtime, installation completion, pilot case import, workforce runner,
calendar, and OTIZ compatibility. Stages `test-db-reset`, `migrate`,
`architecture-check`, `lint`, and `diff-check` passed.

The overall result remains honestly RED:

```text
FULL_VERIFICATION_FAILURE count=4 stages=unit-test,db-test,characterization-test,e2e-test
```

Observed independent failures remain in navigation/UI expectations, prepare/PDF
rendering and its inherited demo contract, checklist HTTP/UI, auth-hot-path, and
pilot E2E. A full-suite `pilot_http_auth_001_test.php` cleanup observation also
found a connection left by the preceding failed inherited demo, while the same
HTTP auth verifier passes in isolation. None was modified in this slice.
