# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 5 setup rereview v4

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate5_v4`
- Reviewed implementation commit: `77c3e5f7d0f29225dcef474093edfeb9953bc585`
- Included recovery implementation: `6e19bd4191b659f8485d6543df303db60af98575`
- Prior Gate 5 finding authority: `12fca973ab0a9eb997607a63259df783aaf62c4f`
- Replacement RED: `73ef81e08ae77dd370625128552ae04e9fd7bb25`
- Fresh Gate 3: `8947ccd`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production implementation. This append-only
review record is the only authored artifact.

## Assessment

No blocking findings.

The minimal correction closes Gate 5 v3's byte-identity defect without changing
schema collation or fixture ownership. Before either seed or cleanup DML,
`validate()` now rewrites every fixed quoted field comparison in all eight
fixture families to `BINARY column = BINARY literal`. An independent reflection
audit counted 53 quoted comparisons, 53 binary comparisons after transformation
and zero residual ordinary quoted equalities. This includes the two explicit
capability alternatives `assignment_order.original.correct` and
`assignment_order.original.upload` after the former `IN` expression was split
into equality branches.

The approved real-MariaDB matrix independently proves rejection, zero DML and
exact restoration for trailing-space user/order/installer values, case drift in
role/task values, composed and decomposed accent drift, plus every non-identity
field in every fixture-owned row. Both public calls are exercised. Thus a
collation-equivalent but byte-distinct occupied row can neither be accepted by
seed nor deleted by cleanup.

No SQL injection surface was introduced. The only interpolated prefix remains
bounded to 25 ASCII alphanumeric/underscore bytes before transaction work; the
predicate grammar and every rewritten literal are fixed implementation
constants, not request, database or environment input. Current literals contain
no quote escape edge, and the transformation does not interpret stored values.

All earlier findings remain closed:

- fixture validation covers complete rows and refuses partial/foreign state
  before DML; cleanup remains reverse, bounded and byte-validated;
- the capability classifier accepts exactly one safe-named exact V4 or V5
  constraint, distinguishes the engineer-position constraint and rejects
  absent, subset, superset or ambiguous candidates;
- the full seven-table family is revalidated before V5 publication, capability
  ALTER remains last, every durable phase is observed, and fresh post-ALTER
  classification keeps V4/V5 recovery fail closed;
- a missing capability prerequisite remains a zero-DDL conflict with the exact
  affected logical name; repeat, populated, leading-partial, contention and
  observer-failure recovery remain covered;
- searches and architecture checks show no runtime application, HTTP, worker,
  cron or `rapid-pilot/` invocation. DDL stays in the canonical setup seam and
  the fictional verification fixture creates no original/domain facts or
  credentials.

The implementation is unusually compressed, which raises ordinary readability
cost, but the reviewed two-line behavioral correction is local, deterministic
and adequately guarded by the complete literal/field matrix. It is not a Gate 5
blocker for this minimal setup slice.

## Reproduced verification

```text
$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK

$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK

$ make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/process_command_authorization_001_test.php
PASS: PROCESS-COMMAND-AUTHORIZATION-001

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract

$ openspec validate replace-pilot-registration-with-original-upload --strict
Change 'replace-pilot-registration-with-original-upload' is valid

$ independent fixed-predicate reflection audit
8 families; quoted=53; binary=53; residual=0

$ git diff --check
PASS (no output before this review record)
```

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
8cf958ab6f37107a9571bfcf4fdb7c80275b8e24887bd3fbbb9c5ad12bfafec5  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
c439aabf1c1f9ec57b945290661d50900c567b738e34f10cdfd80852ce8c36d2  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
6e8d35d624018a344ef923eba64a7cd0f1927e2205d924a5cac301dd7e3ff4ec  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
8b34d11c5d204d68cafa3bf74322c046fa393f89d701863808d8a0d9ad60f72c  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
8f7364134463dffae94b481a3d84ff0b0515e0c1ef6d178d3d8101b1eb97a581  app/InstallationProcess/AssignmentOrderOriginalSchemaMigrationEngineSchemaMigration.php
9abe08bde8cb170718d784f674f64dcfb72ef5f57f38eed4d60e716ac01512ff  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
625beca62d5218585f7a488a3c2e3f0aea7dcaf841ec458599c52b43392bd088  app/AssignmentOrderOriginal/AssignmentOrderOriginalSchemaMigrationVerificationFactory.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
d364f820d32b21c57b832557f53b7d0334e77f641556060b4b7434e8a5669f50  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
79798a1a79aae370daa4154582e1ca5c034840558376766b5b71ac01cde26ce5  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v3.md
3a10c860721ccaf4fd91b5d6c5016c51757a3d3889b3cf0126244b2e7dc3f1ee  reviews/tests/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v23.md
370f801ad88ad2a0f6796f11e492f3394fe297873b93d80446e6ae05c9a4bae5  docs/operations/assignment-order-original-database-setup-green-v4-2026-09-05.md
```

This record omits its own circular hash. Gate 5 setup rereview v4 is
**APPROVED**. OpenSpec task 3.2 may be checked by the integrator; command-matrix
RED and its fresh independent Gate 3 remain mandatory before command GREEN.
