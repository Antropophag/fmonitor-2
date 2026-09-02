# INSPECTION-PLANNING-SCHEMA-001 Gate 2 test-correction evidence

Date: 2026-09-01  
Role: fresh Gate 2 test-correction author after v9 GREEN setup drift  
Scope: verifier fixtures and mechanical terminal-catalogue compatibility only;
no production or specification edits

## Fixed specification and reviewed assertions

The owner-approved executable specification remains byte-identical:

```text
464df8d8cdccea4aeb0997d2e397a3d22958f7c8d04a98e556b59d2c055c888c  specs/INSPECTION-PLANNING-SCHEMA-001.md
```

The direct migration matrix remains byte-identical:

```text
354f761918a276d1a9f6c7c2da9ed628d539a038818695207e063d3181498602  tests/InstallationProcess/inspection_planning_schema_001_test.php
```

No approved HTTP/bootstrap behavior assertion was removed or weakened.

## Reproduced stale-fixture setup failure

After canonical v9 landed, the runtime verifier called the public canonical
runner and then unconditionally issued a second `CREATE TABLE` for both
inspection-planning tables. The verifier therefore failed before exercising a
runtime route:

```text
$ php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PHP Fatal error: Uncaught mysqli_sql_exception:
Table 'healthy_fm2_pilot_inspection_schedules' already exists
...
Next TestFailure: SETUP_FAILURE: MariaDB runtime fixture:
Table 'healthy_fm2_pilot_inspection_schedules' already exists
```

This is setup drift, not evidence about the approved runtime behavior.

## Test-only correction

The fixture now obtains the healthy family from terminal canonical v9 and then
constructs the deliberately hostile states inside its random, test-owned
prefix:

- `healthy`: leave the canonical v9 family unchanged;
- `missing`: drop only `fm2_pilot_inspection_schedule_events`;
- `incompatible`: add the existing hostile column to the canonical schedules
  table;
- bootstrap conflict: add the existing hostile column to the canonical events
  table.

The obsolete duplicate `iprCreatePlanning()` helper and its calls were removed.
All state snapshots, DML-only credentials, HTTP expectations, safe failure
messages, no-repair assertions, byte-preservation assertions, bootstrap
readiness checks and disclosure checks remain.

The OTIZ canonical-compatibility harness was mechanically advanced from the
landed terminal v8 catalogue to v9. Its allowed version range, terminal
`schemaVersion`, messages and transcript now say v1-v9; both v9 planning tables
were added to the byte-preservation catalogue. No financial expectation changed.

Corrected hashes:

```text
79539c76bb4d95aca5f13b558050926f80755fb8ed5a4b09e47af5ebe99b117c  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
117b9dc27f8533b967667f0cf074acc8143f31380bcc556971de3396a9289f2f  tests/Verification/harness_otiz_canonical_compat_001_test.php
```

## Corrected execution evidence

The full runtime verifier now passes setup and reaches an approved behavior
assertion:

```text
$ php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PHP Fatal error: Uncaught TestFailure: DML-only healthy Calendar
Expected: 200
Actual: 503
```

This is no longer `SETUP_FAILURE`; it is a behavior regression/failure surfaced
at the real HTTP seam. This correction role does not diagnose or change
production behavior.

The independently useful bootstrap branch and direct migration matrix remain
green:

```text
$ FMONITOR_IPR_BOOTSTRAP_ONLY=1 php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract

$ php tests/InstallationProcess/inspection_planning_schema_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix
```

The mechanical OTIZ terminal-catalogue update is green:

```text
$ php tests/Verification/harness_otiz_canonical_compat_001_test.php
ok - HARNESS-OTIZ-CANONICAL-COMPAT-001 preserves canonical v1-v9 across repeated isolated OTIZ characterization
```

PHP lint for both edited verifiers and targeted `git diff --check` passed.

## Gate disposition

## Fresh post-GREEN fixture-alignment cycle

Three further test-only integration defects were reproduced before correction:

```text
$ php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
DML-only healthy Calendar
Expected: 200
Actual: 503

$ php rapid-pilot/verify-calendar-projections.php
Access denied for user 'root' ...

$ php tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
final-state fabricator fails on pre-request fixture state
Expected: true
Actual: false
```

The runtime verifier had coupled the planning contract to the mutable external
`../shlz-ui` checkout. It now creates and removes a random test-owned minimal
public export containing the exact CSS/behavior files required by the existing
Calendar guard. `PILOT-CALENDAR-SHLZ-ASSET-001` remains the separate contract
against the real public checkout. The fixture also supplies the pre-existing
non-planning projection tables needed to reach the planning assertions under
DML-only credentials; it does not grant runtime DDL.

The Calendar characterization now runs the public canonical migration runner
inside its random prefix, requires successful terminal `schemaVersion: 9`,
seeds canonical tables by named columns, and removes every table in only that
owned namespace with bounded cleanup.

The schedule-duplicate Gate 2 fixture now obtains its initially empty planning
family from `InspectionPlanningSchemaMigration::apply()` and pins terminal v9.
Its six-request oracle, exact transcript, history snapshots, and final-state
fabricator sensitivity remain unchanged.

Current hashes:

```text
da984ba1d7652fdd24117a22ba0b059dead58885265e2b83b9e826a749da84dd  tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
1d3bfe7b050e98a4903b71bf7e192c250b94bed57233a37ad9e778e73f753092  rapid-pilot/verify-calendar-projections.php
7ecf1cd18fcae798d7ef163f3bb9fa89b6d596ac2fb1448776f28fd5dc6a5e24  tests/Verification/characterize_inspection_schedule_duplicate_001_test.php
```

Focused corrected outputs:

```text
$ php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
DML-only healthy queue
Expected: 200
Actual: 503

$ FMONITOR_IPR_BOOTSTRAP_ONLY=1 php tests/InstallationProcess/inspection_planning_runtime_ddl_001_test.php
PASS: INSPECTION-PLANNING-SCHEMA-001 real HTTP/Compose DML-only contract

PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix
PASS calendar bounded projections, deterministic DOM and fail-closed overflow
INSPECTION_SCHEDULE created responses=1 schedules=1 events=1 history=exact
INSPECTION_SCHEDULE sequential-duplicate responses=2 schedules=1 events=1 mutations=0
INSPECTION_SCHEDULE rejections csrf=403 capability=403 invalid-date=422 ineligible-case=409 mutations=0
CHARACTERIZATION_OK CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001
```

PHP lint and targeted `git diff --check` pass. The owner-approved spec and
direct migration-matrix hashes remain unchanged. The full runtime verifier now
passes the Calendar guard and exposes the next pre-existing RED: ObjectQueue's
completion adapter still attempts runtime `CREATE TABLE IF NOT EXISTS` under
DML-only credentials. That production behavior is outside this test-author
correction and was neither hidden nor granted DDL.

`AWAITING_INDEPENDENT_TEST_REREVIEW`.

Because Gate 5 exposed a test change, implementation must not continue on this
corrected runtime verifier until a separately tasked reviewer checks traceability,
sensitivity, retained assertions, deterministic fixture construction and the
captured setup-failure-to-assertion transition.
