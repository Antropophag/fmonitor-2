# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001 — independent Gate 3 setup rereview v17

- Date: `2026-09-05`
- Reviewer: separately tasked fresh agent `/root/assignment_dbsetup_gate3_v17`
- Reviewed RED commit: `b30ba2d5809e2f628c63852a6e4d9de6f0cad7dd`
- Production baseline under RED: `32dd3151941f1198e0fdd6ce5ee8ee9e1b851abd`
- Approved executable specification: `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001` v14
- Gate 1 authority: `3a1be3cef02e2826b0afc1e80cf0da5e531ba7d9`
- Triggering Gate 5 review: `c40c0101f46cbbee0187ea48f569999c3a0c49f1`
- Scope: replacement setup tests for tasks 2.2/2.3 only
- Verdict: **CHANGES_REQUESTED**

The reviewer did not author the specification, OpenSpec artifacts, tests,
support oracle, RED evidence or production implementation. This append-only
review record is the only authored artifact.

## Blocking findings

### G3-V17-1 — partial cleanup expectation contradicts the approved v14 contract

The normative fixture contract says that cleanup first validates occupied
owned rows, deletes only byte-identical rows in reverse dependency order, and
that **absent rows are a no-op**. The new test deletes task `9001`, then requires
`cleanupExampleA()` to throw a fixture conflict and preserve every remaining
exact row. Absence is not drift, and the approved exception contract reserves
`AssignmentOrderOriginalVerificationFixtureConflict` for an occupied identity
with different values.

This expectation would force production to treat an ordinary bounded/idempotent
cleanup state as a conflict and is therefore not traceable to v14. Correct the
test so a partially absent exact fixture family is safely reconciled: missing
rows remain absent, every present byte-identical owned row is deleted, foreign
rows are preserved, and repeat cleanup is a no-op. Keep the existing drift
cases as zero-DML conflicts.

### G3-V17-2 — observer tests do not prove that faults occur after the named durable boundary

For every `AFTER_SCHEMA_TABLE_CREATED` axis, the test checks only a fixed
exception, V4 capability text and that a later static retry reports `APPLIED`.
It never asserts the spy call trace, never inspects which original tables are
durably present at the fault, and never checks the retry's exact trailing
`affectedTables()`. An implementation can call the observer before each CREATE
while passing the future logical name; the test still passes because retry then
creates the full schema. Likewise, the post-ALTER case never asserts that its
throwing spy was invoked, so a verification factory that ignores that observer
can pass by returning an ordinary successful migration.

For each manifest position, require the exact ordered observer transcript and
the exact durable leading prefix immediately after the fault, then require the
retry to report only the missing suffix plus the capability table. At the
pre-ALTER boundary prove all seven tables are already exact and the exact V4
candidate remains. At the post-ALTER boundary assert the observer invocation
and fresh exact V5 resolution. These observations are available through the
approved public verification application and independent schema metadata; no
private migration method is needed.

### G3-V17-3 — the capability classifier and zero-DDL oracle are materially under-sensitive

The successful V4→V5 path only searches for the substring
`assignment_order.original.correct`; it does not compare the normalized
candidate to the exact six-member V5 set and does not prove that the separate
engineer-position CHECK survives publication. A migration that drops that
constraint can pass. The conflict snapshot contains only table
name/engine/collation and CHECK rows, so an implementation may add/drop a
column, key or FK on a conflict path while the asserted "zero DDL" snapshot
remains equal.

Use an independent exact candidate classifier in the test, explicitly assert
one exact V5 candidate plus the unchanged engineer-position predicate, and use
a complete structural schema snapshot for every zero-DDL conflict assertion.
The expected values must remain test-owned rather than imported from production
migration constants.

### G3-V17-4 — combined conflict `affectedTables()` is not exercised

V14 requires one binary-sorted union containing every incompatible original
logical table and `fm2_process_user_capabilities` if and only if capability
classification conflicts, with zero DDL. The original setup test exercises two
incompatible original tables while its capability prerequisite is not part of
that axis; the new capability test exercises capability-only conflicts. No case
combines the two classifications. An implementation that returns only one side
of the union would pass.

Add a mixed conflict containing at least two incompatible original tables and
one non-exact capability candidate, require the complete binary-sorted union,
and prove the complete schema is unchanged.

### G3-V17-5 — required post-ALTER recovery branches are not sensitive

The only post-ALTER observer case throws after a normal successful ALTER and
expects the fresh V5 reread to resolve `APPLIED`. V14 separately specifies that
the one mandatory fresh classification may observe exact V4, a conflict, or
an unavailable reread: each must throw the fixed unavailable exception without
further DDL, and a subsequent retry must classify/recover safely. None of these
branches is induced. The current spy can only throw and cannot arrange the
approved post-ALTER states, so plausible regressions that blindly return
`APPLIED`, retry ALTER in the same invocation, or leak a diagnostic exception
would pass.

Extend the verification support with deterministic observer actions that,
before throwing at the post-ALTER phase, restore exact V4, replace the candidate
with a conflict, or make the fresh reread unavailable. Assert the fixed
exception shape, no second DDL in that invocation, the allowed durable state,
and safe retry behavior. This uses the approved injected observer seam and
public schema metadata, not private implementation coupling.

### G3-V17-6 — fixture full-row validation can be faked by comparing only sampled columns

The replacement matrix reaches every owned row identity/family, including both
users, both roles, both capabilities, both cases and both installers, which is
a useful improvement. However, it mutates only one selected column per row.
The v14 contract defines all listed values as the repeat/conflict identity,
including nullable values. An implementation that compares only the thirteen
sampled columns and ignores all remaining columns would pass while still
deleting or accepting drifted owned data.

Generate the drift matrix from the complete literal row oracle and perturb
every contract column with a type-valid distinguishable value (including
null↔non-null where allowed), for both seed and cleanup. Retain the full-state
snapshot around every expected conflict. This is needed to establish
byte-for-byte validation rather than representative-family validation.

## Positive observations

- Both focused commands reproduce qualifying current-production REDs after a
  live isolated MariaDB preflight. The fixture RED reaches the intended
  non-case drift assertion; the migration RED reaches the absent approved
  verification factory. Neither is a bootstrap/setup failure.
- The replacement fixture test covers every owned row identity/family at least
  once, exact seed repeat, conflict rollback, fictional/no-credential values,
  original-table zero facts, serialized worker contention and bounded database
  cleanup.
- Capability variants cover upload-only, correct-only, unexpected superset and
  subset, duplicate candidates and an unsafe candidate name. Their current
  expected `CONFLICT` outcomes and capability-only affected name agree with
  v14.
- Tests call only the public migration/factory/fixture seams. Metadata reads and
  deterministic fixture DML are test-side observations/setup; there is no
  coupling to a private production method, runtime DDL consumer, HTTP path or
  `rapid-pilot/` domain owner.
- Independent post-run inspection found zero `t_aoou_%` schemas and zero live
  connections using such a schema.

## Reproduced RED evidence

```text
$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: actor user drift was accepted by seedExampleA.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
exit 0

$ tools/verification/run.sh red tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
Fatal error: Uncaught TestFailure: INTENDED_RED: approved AssignmentOrderOriginalSchemaMigrationVerificationFactory seam is absent.
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
exit 0

$ docker exec fmonitor2-test-test-db-1 mariadb ...
0

$ php -l tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
$ php -l tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
No syntax errors detected in tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
$ php -l tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
No syntax errors detected in tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
```

The direct Compose command was unavailable because repository interpolation
requires an unrelated bootstrap secret; direct read-only inspection of the
already-running disposable MariaDB container was used instead. This did not
alter repository or fixture state.

## Exact reviewed hashes

```text
f19bca46b2334e482e079c95fd856754b45f4151fba1636ee82041c0356b9d26  specs/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001.md
7007b1c96792f0e2fe151c6d2f2910e0f32fbfe6021e1cb72dd615527e56808a  openspec/changes/replace-pilot-registration-with-original-upload/proposal.md
20307b87e81cf11c29d42b3ca69f0a6bed28828a9724adda667ecf74bc603f58  openspec/changes/replace-pilot-registration-with-original-upload/design.md
bc7c6a43f558574e73888e2e14ec504001360dbcb5eb4cd98ddb4c4372122856  openspec/changes/replace-pilot-registration-with-original-upload/tasks.md
97bba61e2ba7fdbbf53abb160f802d491cc284c9fce06b537e85880d40838864  openspec/changes/replace-pilot-registration-with-original-upload/specs/pilot/assignment-order-original/spec.md
5069ae841105f2146923ac950941da7877bd0ea2ba7ba8ce4cc3f6f2b550514b  tests/InstallationProcess/assignment_order_original_database_setup_001_test.php
677c44c98a382a115783aa0114ad1a8706d6db8d201afc4107c4a865141fad2a  tests/InstallationProcess/assignment_order_original_capability_migration_001_test.php
adb816d5819cbd00d8593b161b35e4c1e6920e582e9b3c246a78139bb748929e  tests/Support/AssignmentOrderOriginalSchemaMigrationObserverSpy.php
2d03ad4d6d5d950e4a92592922193e683ee2b7f96451e3634c50efccd0a65a0d  tests/Support/AssignmentOrderOriginalDatabaseSetupV1.php
2e6f2e7d4cbe955acfe278cf54efd3b3eb293bc26a63052555e14f1e953f5107  reviews/code/ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001-setup-v1.md
bae34d570085beaadb2f3af525523f60d6503ccb1b17e1a83343c071d4936520  docs/operations/assignment-order-original-database-setup-red-v14-restart-2026-09-05.md
d5ef6800ec11450571d5093796431be41f7ac7e62137f899e36172de1440fddd  docs/operations/pilot-assignment-order-original-capability-migration-gate1-rereview-v14-2026-09-05.md
75d1cc01f2e6d94504e9a266098de44ebcd5b2aa6d4ac743537cae75c519b016  app/InstallationProcess/AssignmentOrderOriginalSchemaMigration.php
7dcf210a033ed0c1a4723ab6b399eb22a2bfa33d8d921c53654c55824901fc5f  app/InstallationProcess/MariaDbAssignmentOrderOriginalVerificationFixture.php
```

The review record omits its own circular hash. Gate 3 remains closed; task 2.3
and production task 3.1 must stay unchecked until a corrected RED receives a
fresh independent approval.
