# Test review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema-v2 Gate 5 findings

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/schema_v2_gate5_red_gate3`
- Test author: not this reviewer
- Reviewed corrective RED commit: `781f543687eaa467f0a0e89d1c17e67f30bbd6f7`
- Reviewed parent / Gate 5 findings: `9a8e288569edd042887a1aed81bde3864aa9300b`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Gate 1: `390f5629cd1a39da7d39b414b0ca6ad0419d3234` (approval record `54e7cb2`)
- Verdict: **APPROVED**

The reviewer authored neither the specification, production implementation nor
the two corrective tests. This review covers only the two test amendments and
their append-only RED evidence. The nine untracked partial command-slice files
were excluded and untouched.

## Findings

No blocking findings remain.

### Direct-v2 creation sensitivity

The clean scenario continues through the public
`AssignmentOrderOriginalSchemaMigration::apply` seam against its isolated
MariaDB database and first proves the exact `APPLIED`, schema version and
capability-last affected list. It then compares the same connection's
session-local `Com_alter_table` value immediately before and after the call.

The independently specified clean path requires the revisions table to be
created directly with the exact named non-unique
`idx_aoou_revision_content` index. Capability V4-to-V5 publication is the one
required `ALTER TABLE`; therefore delta `1` is an implementation-independent
oracle. The current CREATE-plus-RENAME implementation produces delta `2`.
This detects the forbidden intermediate index-name state while tolerating the
required capability ALTER, and it does not rely on a private hook or production
SQL callback.

### Whole-expression capability sensitivity

The added `trailing_clause` fixture is the exact V4 membership expression with
`AND 1=1` appended. Its expected result is inherited from the already-approved
exact whole-expression classifier and capability-last fail-closed contract:
`CONFLICT`, only `fm2_process_user_capabilities` affected, no original tables,
and a byte-identical before/after catalog snapshot.

The assertion is placed after the same fixture construction and public `apply`
call used by the existing adversarial variants. The reproduced actual result is
`APPLIED`, all seven original tables created and capability publication added,
so the test is red solely because production accepts a non-exact CHECK and
performs prohibited DDL. Setup, database access and expected-value derivation
are not responsible for the failure.

### Scope, determinism and independence

Both tests use task-owned isolated databases and existing cleanup. The commit
contains only the two executable-test changes and one evidence document; no
tracked production file changes. Recorded test and production hashes match the
reviewed worktree exactly. Existing untracked command implementation remains
outside this Gate 3 scope.

## Required changes

None. The two corrective tests are approved for Gate 4. Production correction
must not change their expectations and requires a fresh independent Gate 5.

## Verification evidence

```text
$ git rev-parse HEAD
781f543687eaa467f0a0e89d1c17e67f30bbd6f7

$ git diff-tree --no-commit-id --name-status -r 781f543687eaa467f0a0e89d1c17e67f30bbd6f7
A docs/operations/assignment-order-original-schema-v2-gate5-findings-red-evidence-2026-09-05.md
M tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
M tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php

$ git diff --check 781f543^ 781f543
PASS (no output)

$ php -l tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php

$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
Fatal error: Uncaught TestFailure: Clean v2 names idx_aoou_revision_content directly in CREATE; capability publication is the only ALTER.
Expected: 1
Actual: 2
exit 255

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: trailing_clause capability conflicts before original DDL with zero state change.
Expected: [CONFLICT, ['fm2_process_user_capabilities'], [], true]
Actual: [APPLIED, [seven original tables, 'fm2_process_user_capabilities'], [seven original tables], false]
exit 255
```

## Exact reviewed hashes

```text
fe12a6fb38843943433677a13875a83c10f516ccde56a20d026e2f617b7b928b  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
5a10b53462f379841f5f1429e43ca1d730bb901de37cd721b35e7969ab23776c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
edf45bc2cb06e9dc87cb4c57e4f407af46564127fb696dcee6c177671c374e7e  docs/operations/assignment-order-original-schema-v2-gate5-findings-red-evidence-2026-09-05.md
88097290f5bd67ce812c4a6bdebf8f00013469adc82e5c07b253219bad3f184f  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
bf52fd70fab01df9ad77fca25ea2b08187c072c62043a2b908cc090ed522bf24  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
bf008158e451e216f922dfca94f901c936123f50a624685cd249c69ba6f63e5f  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
31e8d1036f99f6e448a0fa23a6027b8a3012946127320a9549d31bfc99649116  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
361b6cf8d8199e11d8c99253721afa1985aeee71212cc43fb8338bd609009d32  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-schema-v2-v1.md
```

This append-only review intentionally omits its own circular hash.
