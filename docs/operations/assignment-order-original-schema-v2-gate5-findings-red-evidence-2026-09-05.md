# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 v54 — schema-v2 Gate 5 findings RED evidence

- Date: `2026-09-05`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Reviewed implementation: `4beb1b8bdffa11d6f945bbce6fb234fe24637104`
- Gate 5 finding record: `9a8e288569edd042887a1aed81bde3864aa9300b`
- Scope: Gate 2 regressions only; production and partial command-slice files are unchanged.

The two regressions use the existing public
`AssignmentOrderOriginalSchemaMigration::apply` seam against isolated MariaDB
databases. Expected values come from the approved v54 contract, not from the
reviewed implementation.

## Exact test hashes

```text
fe12a6fb38843943433677a13875a83c10f516ccde56a20d026e2f617b7b928b  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
5a10b53462f379841f5f1429e43ca1d730bb901de37cd721b35e7969ab23776c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
```

The production hashes under test remain those reviewed at Gate 5:

```text
88097290f5bd67ce812c4a6bdebf8f00013469adc82e5c07b253219bad3f184f  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
bf52fd70fab01df9ad77fca25ea2b08187c072c62043a2b908cc090ed522bf24  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
```

## RED 1 — clean v2 must use direct named CREATE

The session-local MariaDB `Com_alter_table` counter makes the approved clean
boundary observable without a new production hook. A clean V4-to-v2 run must
execute exactly one ALTER: capability publication. The revisions table must be
created directly with `idx_aoou_revision_content`; a CREATE followed by index
rename increments the counter a second time.

```text
$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
PHP Fatal error:  Uncaught TestFailure: Clean v2 names idx_aoou_revision_content directly in CREATE; capability publication is the only ALTER.
Expected: 1
Actual: 2 in /Users/antropophag/code/fmonitor-2/tests/bootstrap.php:36
exit 255
```

Classification: intended RED. Setup reached live isolated MariaDB and the
migration returned its otherwise expected clean result. The sole mismatch is
the forbidden second ALTER performed by the current clean revisions path.

## RED 2 — capability CHECK is an exact whole expression

The fixture replaces the exact V4 capability constraint with
`CHECK (capability IN (<exact V4>) AND 1=1)`. The test jointly asserts conflict,
the exact affected table, absence of every original table and a byte-identical
database snapshot.

```text
$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
PHP Fatal error:  Uncaught TestFailure: trailing_clause capability conflicts before original DDL with zero state change.
Expected: array (
  0 => \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationStatus::CONFLICT,
  1 => array (
    0 => 'fm2_process_user_capabilities',
  ),
  2 => array (
  ),
  3 => true,
)
Actual: array (
  0 => \FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalSchemaMigrationStatus::APPLIED,
  1 => array (
    0 => 'fm2_assignment_order_original_roots',
    1 => 'fm2_assignment_order_original_revisions',
    2 => 'fm2_assignment_order_original_requests',
    3 => 'fm2_assignment_order_original_events',
    4 => 'fm2_assignment_order_original_audits',
    5 => 'fm2_assignment_order_original_maintenance_requests',
    6 => 'fm2_assignment_order_original_maintenance_audits',
    7 => 'fm2_process_user_capabilities',
  ),
  2 => array (
    0 => 'fm2_assignment_order_original_roots',
    1 => 'fm2_assignment_order_original_revisions',
    2 => 'fm2_assignment_order_original_requests',
    3 => 'fm2_assignment_order_original_events',
    4 => 'fm2_assignment_order_original_audits',
    5 => 'fm2_assignment_order_original_maintenance_requests',
    6 => 'fm2_assignment_order_original_maintenance_audits',
  ),
  3 => false,
) in /Users/antropophag/code/fmonitor-2/tests/bootstrap.php:36
exit 255
```

Classification: intended RED. The current classifier accepts the trailing
clause, creates all seven original tables and publishes V5. This proves both
fail-open classification and non-zero state change; no setup failure is
involved.

Both test amendments therefore restart at Gate 2 and require fresh independent
Gate 3 approval before any correction to production.
