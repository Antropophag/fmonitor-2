# Code review: ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 schema v2

- Date: `2026-09-05`
- Reviewer: separately tasked agent `/root/schema_v2_gate5`
- Reviewed implementation: `4beb1b8bdffa11d6f945bbce6fb234fe24637104`
- Specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v54
- Gate 1: `390f5629cd1a39da7d39b414b0ca6ad0419d3234` (approval record `54e7cb2`)
- Fresh Gate 3: `d939546e54e86dcc559f80dd97c72e63c4a9992a`
- Verdict: **CHANGES_REQUESTED**

The reviewer authored neither the specification, executable tests nor reviewed
production implementation. Untracked partial command-slice files were excluded
and untouched.

## Blocking findings

### 1. Capability classifier regression accepts non-exact CHECK and performs DDL

`AssignmentOrderOriginalSchemaMigrationEngine::capability()` was rewritten as
part of the schema-v2 implementation even though v54 does not change capability
classification. The new implementation removes all parentheses, checks only a
`capabilityin` prefix, and extracts every quoted token without proving the whole
CHECK grammar. Consequently an existing non-exact constraint such as exact V4
values followed by `AND 1=1` is classified as V4.

This violates the inherited capability-last contract: an ambiguous/non-exact
capability CHECK must conflict before any original-schema DDL. A live isolated
MariaDB probe at the reviewed SHA reproduced `status=applied`, all seven original
tables created, and V5 capability publication from that non-exact predecessor:

```text
{"status":"applied","affected":["fm2_assignment_order_original_roots","fm2_assignment_order_original_revisions","fm2_assignment_order_original_requests","fm2_assignment_order_original_events","fm2_assignment_order_original_audits","fm2_assignment_order_original_maintenance_requests","fm2_assignment_order_original_maintenance_audits","fm2_process_user_capabilities"],"tablesBefore":0,"tablesAfter":7}
```

Required correction: preserve or restore the previously exact whole-expression
classifier (including quote-aware folding and anchored grammar), so every
non-exact capability expression returns `CONFLICT` with zero DDL. Add an
executable regression for a plausible trailing-clause expression; because that
is a test change discovered at Gate 5, it must restart at Gate 2 and receive a
fresh independent Gate 3 before the corrected Gate 4/Gate 5.

### 2. Clean revisions creation is two DDL statements, not direct exact v2

The shared `ddl()` emits an unnamed `KEY(private_content_identity)` for the
revisions table. The engine then calls `nameV2()`, which executes a separate
`ALTER TABLE ... RENAME INDEX` before the created-table observer. The v54
contract requires a missing revisions table to be created directly as v2 and
uses per-created-table observation to make implicit-commit recovery
deterministic.

The current two-statement path creates a durable intermediate table whose
content index has MariaDB's auto-generated non-normative name. A failure after
CREATE and before/during RENAME cannot be observed at the specified phase; on
retry `revisionState()` classifies that durable table as `CONFLICT`, so ordinary
v1-or-v2 recovery is unavailable. It also introduces an extra schema-v2 DDL not
authorized by the minimal amendment.

Required correction: make the revisions CREATE statement name the non-unique
index `idx_aoou_revision_content` directly and remove the clean-path rename.
Add sensitivity proving the exact CREATE/observer recovery boundary if the
existing executable suite cannot distinguish the two-statement implementation;
any changed test must receive fresh Gate 2/Gate 3 review.

## Non-blocking observations

The reviewed focused behavior otherwise demonstrates the intended populated-v1
single atomic DROP+ADD ALTER, exact v2 repeat, same-content two-revision insert,
v1/v2 observer retry states, binary conflict reporting, row preservation and
capability-last affected ordering. SQL index identifiers discovered from the
catalog are bounded by the approved safe-name grammar before interpolation.

## Verification evidence

```text
$ git rev-parse HEAD
4beb1b8bdffa11d6f945bbce6fb234fe24637104

$ php -l app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
No syntax errors detected in app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php

$ php tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_SCHEMA_V2_001_OK

$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ adversarial isolated MariaDB public-apply probe
CHECK (capability IN (<exact V4 values>) AND 1=1)
=> status=applied, tablesBefore=0, tablesAfter=7
```

The three green suites do not override the reproduced fail-open behavior or the
unobservable two-DDL clean creation boundary. Gate 5 remains closed.

## Exact reviewed hashes

```text
34b4a20be0005e4236ee3cdb04ca6fd41537ca3608ecef41ac5e9d91f151ddf5  app/InstallationProcess/AssignmentOrderOriginalDefinitionSchemaMigration.php
bf52fd70fab01df9ad77fca25ea2b08187c072c62043a2b908cc090ed522bf24  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
88097290f5bd67ce812c4a6bdebf8f00013469adc82e5c07b253219bad3f184f  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
445645987816fcaf3dd82c1798b29dff0daa3988ef5e252fc8a28085ee8598dd  app/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigrationVerificationFactory.php
81de0cb0529f8f5885cf1475aaf2e5f6f0f35afb4b22e4968cda87a4d3a42f2d  tests/InstallationProcess/assignment_order_original_schema_v2_001_test.php
a87f01615c796c602af622091a7b2cbfdfd99f6f7fe2db0c9a03578c02244cc6  docs/operations/assignment-order-original-schema-v2-green-evidence-2026-09-05.md
```

This append-only review omits its own circular hash.
