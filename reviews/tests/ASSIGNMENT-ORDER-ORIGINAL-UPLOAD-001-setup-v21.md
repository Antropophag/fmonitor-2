# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v21

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v21`
- Reviewed correction commit: `c585cf016fbca044745ed192f356a02547089d72`
- Production gap base: `edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5`
- Triggering Gate 5: `f4bdb56aa446148a69b3044bf565228bbdef0f3d`
- Prior Gate 3: `2ab6e511ff7a704cb54ebfdfa74ff7bf0f33d51d` / setup v20 `APPROVED`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Scope: missing capability-prerequisite correction to setup tests only
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production implementation. This append-only
review record is the only authored artifact.

## Independent assessment

The correction closes exactly the Gate 5 v2 sensitivity gap and preserves the
previously approved setup matrix.

- Both public setup verifiers create a separately isolated empty database and
  call `AssignmentOrderOriginalSchemaMigration::apply()` without creating the
  prerequisite capability table. Each requires exact status `CONFLICT`, exact
  affected list `['fm2_process_user_capabilities']`, and equality of a complete
  before/after schema snapshot. The snapshots cover every table definition and
  every row, so creating any of the seven original tables, the prerequisite, or
  any other DDL is observable.
- The intended failure occurs before either new zero-DDL assertion on production
  `edbae87`: current production instead returns `APPLIED` and reports all seven
  original tables. Thus the RED is caused by the missing fail-closed behavior,
  not database setup, an unrelated old assertion, or cleanup.
- The database-setup verifier now establishes the exact V4 capability schema for
  clean, leading-partial, populated and conflict axes before the original setup
  seam. Its `near_` and `check_` alternate prefixes independently establish the
  same V4 predecessor before creating their exact original families. The fixture
  axis already applies the complete process/identity/user-capability/command-
  capability predecessor chain, ending at exact V4, before original setup.
- The capability verifier retains exact V4-to-V5, exact V5 repeat, upload-only,
  correct-only, subset, superset, multiple/unsafe candidates, combined conflict,
  every durable-create recovery position, pre/post publication outcomes and
  fixed unavailable behavior. The correction adds only the missing-prerequisite
  axis; it changes no prior expectation.
- The database verifier retains exact seven-table columns, keys, foreign keys,
  CHECKs, engine/collation, repeat/populated preservation, binary conflict
  ordering, near-equivalent sensitivity, complete fictional fixture literals,
  per-field and partial-family conflict matrices, seed/cleanup contention,
  prefix/projection isolation, bounded workers and connection-health checks.
  No assertion, timeout, cleanup path or support oracle was weakened.
- Both tests retain bounded `finally` cleanup of every task-owned schema and
  worker. Independent post-run catalog checks found zero `t_aoou_%` schemas and
  zero connections using one.

The change is traceable to the v14 inspect-before-DDL prerequisite rule and the
specific Gate 5 v2 finding. Expected values are literal and independently
derived from the contract. No production, specification or support code is
part of the reviewed correction.

## Reproduced RED and cleanup evidence

The two correction test files from `c585cf0` were run in an isolated detached
worktree whose production and all other files were exactly `edbae87`.

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Missing prerequisite capability table conflicts before DDL.
Expected: CONFLICT ['fm2_process_user_capabilities']
Actual: APPLIED [all seven original tables]
RED_ASSERTION: expected failing behavior observed
exit 0

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Missing prerequisite capability table conflicts.
Expected: CONFLICT ['fm2_process_user_capabilities']
Actual: APPLIED [all seven original tables]
RED_ASSERTION: expected failing behavior observed
exit 0

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php

$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php

$ docker exec fmonitor2-test-test-db-1 mariadb ...
SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 't\\_aoou\\_%';
0
SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE DB LIKE 't\\_aoou\\_%';
0

$ git diff --check edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5..c585cf016fbca044745ed192f356a02547089d72
PASS (no output)
```

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
58f791f67a4d3f207ff4b0aa4f6aa260b18f5a64ddd97bffacf8c190c8f513bf  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
6e8d35d624018a344ef923eba64a7cd0f1927e2205d924a5cac301dd7e3ff4ec  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
d364f820d32b21c57b832557f53b7d0334e77f641556060b4b7434e8a5669f50  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
e302d1cb47af23dada9ab6e09b14aa6d57c29a5ef4f330994362f7999ee0964d  docs/operations/assignment-order-original-database-setup-missing-prerequisite-red-2026-09-05.md
```

This record omits its own circular hash. Fresh Gate 3 is **APPROVED**. Task 3.1
may resume with only the minimal production correction required by these exact
reviewed tests; a fresh independent Gate 5 remains mandatory.
