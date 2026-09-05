# CANONICAL-V12-CONSUMER-FIXTURES-001 proposed patch and RED evidence

- Date: `2026-09-05`
- Role: test-patch author `/root/importer_authority_review`; not Gate 3 reviewer
- Base HEAD: `d584633c0f602b10a821e8929eaee8ee3fc91941`
- Scope: exact unapplied patch for the eleven Gate-1-approved artifacts
- Status: **PROPOSED_PATCH_READY; QUALIFYING_OLD_FIXTURE_RED CAPTURED; GATE 3 REQUIRED**

## Proposed patch

Artifact:
`docs/operations/patches/canonical-v12-consumer-fixtures-v1.patch`.

SHA-256:
`ca22acc2980d7505c215cc20019fac60307c36ad21d04b11c7879f41a83bf5a4`.

`git apply --check` succeeds against the exact base inputs. The patch contains
exactly eleven `--- a/` targets, all from the approved allowlist. It is not
applied. Actual test/verifier bytes remain at their Gate 1 hashes. Shared
catalog defaults, OTIZ, `pilot_demo_bootstrap`, protected E2E, specs and
production code are absent from the patch.

The patch changes only successful full-runner terminal/version lists, exact
repeat terminals, compatible successor lists, the classification race winner's
terminal version, two literal v12 table-catalog members, IIC count 31 to 33,
and directly corresponding labels. Classification v11 conflicts, completion
v10, planning v9, checklist v7 and all business assertions remain unchanged.
`pilot_case_import` retains its deliberate v10/v11 table removals and does not
remove v12 tables.

## Healthy control and old-fixture RED

Exact command, with the documented test-only local password and prepared tool
PATH:

```text
export PATH="/opt/homebrew/bin:/Applications/Docker.app/Contents/Resources/bin:$PATH"
export FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local
php tests/InstallationProcess/production_migration_runner_001_test.php
php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

Combined exit: `255`.

Exact relevant output:

```text
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
PHP Fatal error:  Uncaught TestFailure: SETUP_FAILURE: exact runner result.
Expected: array (
  'ok' => true,
  'schemaVersion' => 11,
  'appliedVersions' => array (0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5,
    5 => 6, 6 => 7, 7 => 8, 8 => 9, 9 => 10, 10 => 11),
)
Actual: array (
  'ok' => true,
  'schemaVersion' => 12,
  'appliedVersions' => array (0 => 1, 1 => 2, 2 => 3, 3 => 4, 4 => 5,
    5 => 6, 6 => 7, 7 => 8, 8 => 9, 9 => 10, 10 => 11, 11 => 12),
)
```

The fatal block is printed twice by the configured PHP display/error channels;
both copies report the same assertion at
`tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php:19`.
The first command proves the landed v12 public runner and environment are
healthy. The second reaches that same real runner successfully and fails only
because the unchanged fixture expects terminal v11/[1..11]. This is the
approved stale-prerequisite RED, not MariaDB, PATH, vendor or setup failure
despite the old assertion's `SETUP_FAILURE` label.

The test's cleanup completed before process exit; no test file was changed to
obtain this evidence.

## Exact identities

```text
0097ce7e884b7cd8c84e80034adf731765900d0550472e00da596c0920383c0d  specs/CANONICAL-V12-CONSUMER-FIXTURES-001.md
7038044235a57e163f4cdd71cc56881ca81def3eb58c43fcdb42e1e1acadf5be  docs/operations/canonical-v12-consumer-fixtures-gate1-rereview-2026-09-05.md
ca22acc2980d7505c215cc20019fac60307c36ad21d04b11c7879f41a83bf5a4  docs/operations/patches/canonical-v12-consumer-fixtures-v1.patch
41be1ad7756bc3403823f07475639a21d1ceb205e595cc06e04b2d0f09465784  tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
c75ba4b017bae4e6ef2be25dfb1c9f3a859d70d2afdbb4ecf843e836aeb9399e  tests/InstallationProcess/production_migration_runner_001_test.php
a3f00d1e36d9d4bf5c2ff5c61734a79bf42c2e3d19df57c23e3ff43b01690da6  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

Every other patch input remains at the exact hash pinned by the approved Gate 1
rereview. Gate 3 must independently inspect the complete patch, this evidence,
the spec, and all eleven current input hashes before application.

## Disposition

The exact patch and qualifying RED are ready for independent Gate 3. The patch
must not be applied and no GREEN may begin before explicit Gate 3 approval.
This record is not a review or approval.
