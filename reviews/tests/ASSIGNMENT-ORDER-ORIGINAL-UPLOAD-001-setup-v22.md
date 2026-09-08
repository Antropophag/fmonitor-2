# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v22

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v22`
- Reviewed correction commit: `5c97abcd270b34aa4ab9d08f0583264ece653b71`
- Correction base: `ccf9643072da9b7429346f76145da40211efdec4`
- Production RED base: `edbae87a46ff9d9abf0bda98dd411a6f4ba28aa5`
- Prior Gate 3: setup v21 `APPROVED`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Scope: V4 first-apply affected-list correction in the database-setup verifier
- Verdict: **APPROVED**

The reviewer authored none of the specification, OpenSpec artifacts, tests,
RED evidence, support oracle or production implementation. This append-only
review record is the only authored artifact.

## Independent assessment

The correction closes the inconsistent affected-list expectations identified
after setup v21 without weakening or replacing its approved coverage.

- The v14 contract requires migration from exact V4 to publish the seven-table
  original manifest first and the capability table last. Clean, populated,
  near-collation, near-CHECK and fixture first applies now require exactly that
  ordered list. The leading-partial axis requires exactly the missing six-table
  suffix followed by `fm2_process_user_capabilities`.
- Exact V5 repeat expectations remain `UNCHANGED` with an empty affected list.
  The dedicated capability verifier retains its corresponding exact V4/V5,
  conflict, publication-phase and recovery expectations unchanged.
- Original-owned inventory now filters only the seven
  `fm2_assignment_order_original_*` names. It therefore independently proves
  that the exact original manifest exists while correctly excluding the
  prerequisite process capability table from that inventory.
- The correction changes seven stale assertions plus that inventory selector;
  it does not change fixture literals, structural manifests, conflict matrices,
  timeouts, worker behavior, cleanup, support code, production or specification.
- All earlier replacement coverage remains: missing prerequisite fail-closed
  and zero-DDL, clean/repeat/leading-partial/populated/conflict, near-equivalent
  collation/default and CHECK sensitivity, exact columns/keys/FKs/CHECKs,
  deterministic seed/repeat/conflict/cleanup contention, prefix/projection
  isolation, durable-create recovery, exact V4/V5 publication and unavailable
  outcomes.
- Expected affected lists are literal consequences of the normative manifest
  and migration order rather than values obtained from the implementation.
  The assertions would fail if capability publication were omitted, reordered,
  reported on V5 repeat, or included in the original-owned inventory.

No finding requires returning to Gate 1. The test remains deterministic,
isolated from production systems and exercises the named public migration seam.

## Reproduced historical RED

The corrected database-setup test from `5c97abc` was placed over an isolated
detached worktree whose production and all other files were exactly `edbae87`.
It failed at the intended missing-prerequisite behavior before reaching the
affected-list correction:

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Missing prerequisite capability table conflicts before DDL.
Expected: CONFLICT ['fm2_process_user_capabilities']
Actual: APPLIED [all seven original tables]
RED_ASSERTION: expected failing behavior observed
exit 0

$ independent SCHEMATA/PROCESSLIST query
0
0
```

This proves sensitivity to the missing production prerequisite rule rather than
to database setup, cleanup, or the corrected normal-axis expectations.

## Reproduced current-worktree GREEN

The shared worktree contained an unstaged parent-owned production correction.
The reviewer neither edited nor staged it. With that correction present:

```text
$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
$ php tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_DATABASE_SETUP_001_OK
$ php tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
ASSIGNMENT_ORDER_ORIGINAL_CAPABILITY_MIGRATION_001_OK
$ independent SCHEMATA/PROCESSLIST query
0
0
$ git diff --check ccf9643072da9b7429346f76145da40211efdec4..5c97abcd270b34aa4ab9d08f0583264ece653b71
PASS (no output)
```

This GREEN is corroborating review evidence, not Gate 4 authorship or approval
of the still-uncommitted production correction. A fresh independent Gate 5 is
still mandatory after the production commit.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
19b7d819b4897ad28e1bcf7eee6e556b3c774a9cad65a46e838534786cc1af04  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
8557ddec86169836d30b0d43236b9f9cbd8e0214d712147f54d11ac150ba9f01  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
6e8d35d624018a344ef923eba64a7cd0f1927e2205d924a5cac301dd7e3ff4ec  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
78b911c27665a6d01ebf2498c48a1d3b0d0952967557b4190a7877e093aaaa4b  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
3480ea78ee679fd7ba9cd667bebaf5ddb7a71925eac318b42dbb22984753cd37  tests/Support/assignment_order_original_fixture_worker.php
50902ba667428e5e1fa8fdf7c863d38e48d34f3633f7c336e744bf86926bb8b0  docs/operations/assignment-order-original-database-setup-v4-affected-list-red-correction-2026-09-05.md
9d88d856cca5d787d377a56df18f47b6a45d0b1bab9d7b898a74de75bec8624b  docs/operations/assignment-order-original-database-setup-v4-affected-list-test-gap-2026-09-05.md
e302d1cb47af23dada9ab6e09b14aa6d57c29a5ef4f330994362f7999ee0964d  docs/operations/assignment-order-original-database-setup-missing-prerequisite-red-2026-09-05.md
d364f820d32b21c57b832557f53b7d0334e77f641556060b4b7434e8a5669f50  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v2.md
```

This record omits its own circular hash. Fresh Gate 3 is **APPROVED**. Task 3.1
may resume against these exact reviewed tests; task 3.2 requires a fresh
independent Gate 5 after the minimal production correction is committed.
