# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 5 setup rereview v2

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate5_v2`
- Reviewed implementation commit: `edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5`
- Original implementation commit: `32dd3151941f1198e0fdd6ce5ee8ee9e1b851abd`
- Prior Gate 5: `c40c010` / setup v1 `CHANGES_REQUESTED`
- Replacement RED: `f3cce30d963ac655fadabef9f622a448d4c44108`
- Fresh Gate 3: `2ab6e511ff7a704cb54ebfdfa74ff7bf0f33d51d` / setup v20 `APPROVED`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Verdict: **CHANGES_REQUESTED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production implementation. This append-only
review record is the only authored artifact.

## Blocking finding

### G5-SETUP-V2-1 — absent prerequisite capability schema is accepted and mutated

The v14 contract permits exactly two states for the prerequisite
`fm2_process_user_capabilities` capability-enum CHECK: exact V4 or exact V5.
Migration inspection must include that prerequisite before any DDL and return
all conflicts without mutation. The implementation classifier does return an
`ABSENT` state when the prerequisite table is absent, but `apply()` neither
classifies it as conflict nor throws unavailable. It proceeds to create all
seven original tables and returns `APPLIED` without publishing or proving V5.

This creates an unusable original schema against a missing authorization owner,
violates the exact-V4/V5 classifier and the inspect-before-DDL order, and makes
the claimed affected set omit the missing prerequisite. It is independently
reproduced against the real test MariaDB from an empty task-owned database:

```text
$ php -r '<create empty bounded database; call AssignmentOrderOriginalSchemaMigration::apply($db,"r_"); inspect result/catalog; drop database>'
applied ["fm2_assignment_order_original_roots","fm2_assignment_order_original_revisions","fm2_assignment_order_original_requests","fm2_assignment_order_original_events","fm2_assignment_order_original_audits","fm2_assignment_order_original_maintenance_requests","fm2_assignment_order_original_maintenance_audits"]
7 tables
```

The database was dropped in `finally`. The approved capability test always runs
the three prerequisite process migrations before invoking this seam, so it has
no missing-table axis and would not catch this regression. Add an independently
derived absent-prerequisite case that requires a fail-closed, zero-DDL outcome;
then correct production behavior. Because test sensitivity changes, this returns
to Gate 2 and requires a fresh independent Gate 3 before another Gate 5.

## Prior Gate 5 findings

- **G5-SETUP-1 is closed for the approved fixture matrix.** Seed and cleanup now
  validate the complete owned identity family before mutation, compare every
  non-identity value including nullable fields, reject partial seed occupancy,
  allow partially absent cleanup, preserve unrelated rows, make exact repeats
  no-ops, and execute mutation only after the full preflight inside one
  `SERIALIZABLE` transaction. The generated per-field drift matrix, explicit
  partial-family cases and real lock observers are sensitive to these paths.
- **G5-SETUP-2 is closed for present prerequisite schemas.** The classifier
  distinguishes the engineer-position CHECK, requires one safe-named top-level
  candidate, accepts only exact sorted V4/V5 members, and rejects upload-only,
  correct-only, subset, superset and multiple candidates with the exact affected
  union and no DDL. A real-MariaDB probe of a logically permissive
  `V5 OR 1=1` expression also returned `CONFLICT`.
- **G5-SETUP-3 is closed for every reviewed injected phase.** Each durable
  create is observed, the seven-table family is revalidated before publication,
  V5 is last, and post-ALTER V5/V4/conflict/connection-unavailable outcomes are
  freshly resolved as specified. Exact leading-partial retries converge without
  data loss and affected tables preserve manifest order plus capability last.

These corrections do not close the newly exposed absent-prerequisite path.

## Boundaries, security and maintainability

Searches found no application, HTTP, worker, cron or `rapid-pilot/` caller of
the migration/fixture/verification factory. DDL remains in named schema setup
owners and fixture DML remains verification-only; no original/domain fact is
seeded. Prefix validation bounds identifiers, unsafe constraint names conflict,
SQL/database diagnostics are normalized to fixed unavailable exceptions, and
the fixture contains fictional identities without credentials. The compressed
implementation and retained unused legacy helper methods are unnecessarily hard
to audit, but are not a separate correctness blocker for this minimal setup
slice.

## Reproduced verification

```text
$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ git diff --check
PASS (no output before this review record)
```

An attempted `migration_process_001_test.php` without its required configured
database credentials failed during connection setup and is not reclassified as
a behavioral result. The focused real-MariaDB suites and canonical migration
runner above completed successfully and removed their task-owned databases and
workers.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
8cf958ab6f37107a9571bfcf4fdb7c80275b8e24887bd3fbbb9c5ad12bfafec5  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
ee16b431fd28fc0897f5e3755321408f76b53deeb52df0fc64a39c7929a3f67d  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
865513ce563cac157d64324c51f597024ffd1cc19456bce9ea5456d3e96c714c  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
8b34d11c5d204d68cafa3bf74322c046fa393f89d701863808d8a0d9ad60f72c  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
7da792b11c91f1d598e293a0fa3604c465376f3d0b9e3fef0fa128c286522719  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
d729e13a1fff56ba92acaedfc31b668c1c0f23db598584fd838da18748f73931  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
625beca62d5218585f7a488a3c2e3f0aea7dcaf841ec458599c52b43392bd088  app/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigrationVerificationFactory.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
89f6aca8d18cd521a87fa88a3d1e2d96cfadef35c89e47c154b526317b03da5e  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v20.md
6d1649754b8c53c5589887b68319e4908a8c5dfa3ef4ee9eb8cea432c3da8184  docs/operations/assignment-order-original-database-setup-green-v2-2026-09-05.md
```

The review omits its own circular hash. Gate 5 remains **CHANGES_REQUESTED** and
OpenSpec task 3.2 must remain unchecked.
