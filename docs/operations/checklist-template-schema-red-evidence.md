# CHECKLIST-TEMPLATE-SCHEMA-001 Gate 2 superseding RED evidence v7

- Date: `2026-09-01`
- Test author: separately tasked agent `/root/checklist_v7_red`
- Executable specification: `CHECKLIST-TEMPLATE-SCHEMA-001 v0.1`, Gate 1 approved
- Public seam: `php bin/fmonitor2-migrate.php`
- Production code changed: no
- Result: `QUALIFYING RED`; independent Gate 3 review remains required
- Supersedes: v6, v5, v4, v3, v2 and the narrow first tracer; each received append-only
  `CHANGES_REQUESTED` verdicts in
  `reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md`

## Scope

The dedicated MariaDB test now transcribes the complete approved section 7
matrix into test-owned literals and fixtures. It covers exact clean schema
fingerprints; populated repeat preservation; both partial directions;
column/default/generated/index/engine/collation/FK/CHECK conflicts;
multi-conflict ordering; conflict plus missing zero mutation; prefix isolation
and boundaries; UCA alias and invalid database defaults; DDL-denied runtime
import/link/replay/conflicts; both consumers' absent/incompatible fail-closed
behavior; and the family-targeted runtime DDL architecture rule.

After the second review, v3 adds zero-family-mutation assertions to FK and
two-table conflicts; schema/DML/row/counter fingerprints around every runtime
success, replay and rejection; snapshot hash conflict, missing/hash-mismatched
snapshot and policy rejection; exact association replay identity/facts; exact
fingerprints for both partial-created siblings and UCA-created tables plus UCA
repeat; explicit SQL `NULL`, string `'NULL'`, and normative-column generated
expression conflicts; runtime `SHOW GRANTS` proof of no DDL privileges; and
MariaDB rejection evidence for syntactically invalid/unknown database defaults.

After rereview v3, v4 asserts every persisted snapshot field and every
persisted association field from independent literals immediately after the
successful writes. The existing replay assertions then prove the identical
identity/facts are returned with no row change. V4 also adds an independent
wrong `template_snapshot_version` rejection with exact
`CHECKLIST_TEMPLATE_SNAPSHOT_MISMATCH` and full before/after equality.

After Gate 4 diagnostic execution, v5 replaces two MariaDB-unsupported test
fixtures: the generated mismatch now recreates normative `created_at` as a
supported virtual expression in the same ordinal position, and the extra CHECK
targets `source_label` instead of an auto-increment expression. A full run
through current minimal production passed those fixtures and reached a genuine
diagnostic: exact association replay advanced `AUTO_INCREMENT` from `2` to
`3`.

Fixture review correctly determined that this was an over-strong runtime
expectation, not a production defect. The approved migration repeat/partial/
conflict rules require allocator preservation, and those assertions remain
unchanged. Runtime rules require unchanged schema and persisted rows/identity,
so v6 excludes allocator state only from runtime before/after fingerprints.
The complete current-production matrix then passed.

After Gate 5 boundary review, v7 adds public `associateActiveBaseline(...)`
coverage for both absent and incompatible family states. It proves the exact
`CHECKLIST_TEMPLATE_SCHEMA_REQUIRED` precondition occurs before baseline or
snapshot lookup and preserves schema/rows. Closed-connection fixtures for both
snapshot import and association seams separately prove unexpected schema-
inspection driver failures are not masked as that compatibility outcome.

The accompanying physical-catalogue oracle correction keeps
`payload_json` as literal MariaDB `LONGTEXT`; it must not normalize that column
to the `JSON` alias, which would introduce an implicit `json_valid` CHECK and
contradict specification section 3. The corrected production migration-runner
regression remains supporting catalogue evidence; this dedicated test's exact
fingerprint independently requires `longtext` and no CHECK.

The first assertion remains the runner tracer on a fresh isolated database. It
requires landed v1–v6 to complete successfully, then expects approved literal
v7. The later fixtures are executable once v7 exists; current production fails
first and exclusively because canonical v7 is not registered.

This artifact does not mark OpenSpec tasks and does not authorize GREEN.

## Artifact identity

```text
9094170947b65fdae21b9136a5438ad26ced37d82fef3cd48e6c959b106063e8  tests/InstallationProcess/checklist_template_schema_001_test.php
2951eff3c1f1c743d0fb5ad797ce2f678568e919e2f8375941dd70c23bc63f23  specs/CHECKLIST-TEMPLATE-SCHEMA-001.md
9338de86cd797521f8ccab26174b8ce91edbfc2825b8ff7636ca65c6de8ccaf1  bin/fmonitor2-migrate.php (current Gate 4 diagnostic production)
8e2883213d3f723d5bd643a56aeaada904aa9e8d7303010b8f8e986637b06267  bin/fmonitor2-migrate.php (reviewed predecessor c57663d)
24e465f285d72b66bec8b7c0bc17b51f48cbab587e96a35cb2fdd78818a0b728  reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md
fc80f4f89d61c94f8a60fef5a96246bfae9bb5d3cf6d9c47d71d590f4855f01b  tests/InstallationProcess/production_migration_runner_001_test.php
```

## Syntax and diff checks

```text
$ php -l tests/InstallationProcess/checklist_template_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/checklist_template_schema_001_test.php

$ git diff --check -- tests/InstallationProcess/checklist_template_schema_001_test.php
[no output; exit 0]
```

## Qualifying public-seam RED

Command:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
```

Relevant exact failure:

```text
PHP Fatal error:  Uncaught TestFailure: Clean canonical runner must apply literal checklist-template migration v7.
Expected: array (
  'ok' => true,
  'schemaVersion' => 7,
  'appliedVersions' =>
  array (
    0 => 1,
    1 => 2,
    2 => 3,
    3 => 4,
    4 => 5,
    5 => 6,
    6 => 7,
  ),
)
Actual: array (
  'ok' => true,
  'schemaVersion' => 6,
  'appliedVersions' =>
  array (
    0 => 1,
    1 => 2,
    2 => 3,
    3 => 4,
    4 => 5,
    5 => 6,
  ),
)
```

Process exit was `255`. The isolated database was created, the canonical CLI
started, and v1–v6 completed with valid success JSON. The mismatch is exactly
the missing approved canonical v7; it is not a connection, configuration,
fixture, parsing, or prerequisite-migration failure.

The v5 hash was re-proved against the reviewed predecessor without modifying
current production:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    FMONITOR_CTS_RUNNER=/home/antropophag/code/fmonitor-2-checklist-red-predecessor/bin/fmonitor2-migrate.php \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
[same schemaVersion 7 / [1..7] expected versus schemaVersion 6 / [1..6] actual]
[exit 255]
```

## Gate 4 fixture diagnostic

Command against the current minimal production:

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/checklist_template_schema_001_test.php
```

The revised generated and CHECK fixtures executed successfully. The run then
reached the runtime matrix and failed on a production behavior rather than a
fixture:

```text
TestFailure: Association replay performs zero schema/DML/counter mutation.
Expected association AUTO_INCREMENT: 2
Actual association AUTO_INCREMENT:   3
[exit 255]
```

This diagnostic did not replace the original qualifying RED. Fixture rereview
found that runtime `AUTO_INCREMENT` was outside the approved preservation
claim; v6 corrected only that runtime expectation. With migration allocator
assertions intact, the current-production command now returns:

```text
CHECKLIST-TEMPLATE-SCHEMA-001 migration runner test passed
[exit 0]
```

## Gate boundary

A fresh independent agent must review the approved specification, test and
this evidence in `reviews/tests/CHECKLIST-TEMPLATE-SCHEMA-001.md`. RED and this
record alone do not permit implementation. Task `2.1` records only the
demonstrated RED; implementation remains prohibited until task `2.2` receives
the required independent Gate 3 `APPROVED` verdict.
