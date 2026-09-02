# CLASSIFICATION-PROVENANCE-SCHEMA-001 — Gate 2 RED evidence

Date: 2026-09-02  
Author: separately tasked Gate 2 test author `/root/classification_red`  
Approved specification SHA-256: `a044645fac8c347e98ae876f1dfdb98c12944a1c4fde85a098f99b6a84be71ed`

## Verifier

`tests/InstallationProcess/classification_provenance_schema_001_test.php`

Verifier exercises the public migration runner, exact test-owned information-
schema manifest, clean/repeat/populated/conflict preservation, 25/26 prefix
boundary, bounded two-process race, DDL-denied literal reconcile family and
independently runnable pre-source/runtime-DDL ratchets. It does not read private
production migration definitions to derive expected values.

## Intended RED: canonical owner absent

```text
$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: clean public runner reaches v11 canonical owner
Expected: exit 0, schemaVersion 11, appliedVersions [1..11]
Actual:   exit 0, schemaVersion 10, appliedVersions [1..10]
```

Classification: **RED assertion**, not setup failure. MariaDB was reachable,
the disposable database was created, and the same public runner successfully
applied the exact v1–v10 predecessor catalogue. The missing behavior is v11
registration/ownership.

## Independent boundary REDs

```text
$ CPS_SCENARIO=source-order php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: native exact provenance precondition precedes source connection sentinel
Expected: true
Actual: false

$ CPS_SCENARIO=runtime-ddl php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: classification provenance runtime owner contains no DDL
Expected: false
Actual: true
```

Classification: both are **RED assertions**. They independently prove that the
native CLI still opens its source before an exact provenance readiness guard,
and that `MigrationClassificationProvenanceTarget` still owns runtime
`CREATE TABLE`. Historical/active ordering assertions are present in the same
deterministic source-sentinel scenario and become observable after the native
failure is fixed.

## Hygiene

`php -l` passes and `git diff --check` reports no whitespace errors. Each DB
scenario uses a unique disposable database and `finally` cleanup. No production
code was changed. Independent Gate 3 test review remains required.

## Correction cycle after `CHANGES_REQUIRED`

The first independent review at
`reviews/tests/CLASSIFICATION-PROVENANCE-SCHEMA-001.md` correctly found the
initial verifier incomplete. The superseding verifier SHA-256 is:

```text
36a5550f1f7c73d6db1ead7e2c479dc35e27ed6fbde13dd1d13610e389d50ef6  tests/InstallationProcess/classification_provenance_schema_001_test.php
cb83026c22289f56eefb5972da3fdc6c2d4cf4b8f0e528aaf981d18a607cc099  tests/Support/classification_source_sentinel.php
```

Corrections add:

- real native/history/active public CLI missing and drift invocations;
- an independent bounded TCP source sentinel which records any connection
  attempt, exact exit/stdout/empty-stderr checks, redaction and before/after
  schema/data/counter/decoy snapshots;
- a real native eligible-object CLI contrast whose test-owned `AFTER INSERT`
  trigger injects the conflict after readiness and case creation, with exact
  `PILOT_ONLY_OUTPUT_WITHOUT_PROVENANCE` transcript and persisted-state proof;
- exact semantic index assertions independent of index names, plus column,
  default, extra-column, index order/extra/prefix/direction/visibility, engine,
  collation and CHECK conflict mutants;
- exact 25-byte success and 26-byte/non-ASCII/invalid-ASCII pre-access failures;
- a separate race database with a populated v1–v10 process row, preserved
  AUTO_INCREMENT and ambient decoy;
- bounded CLI/race/sentinel waits and unconditional cleanup of every database,
  process, manifest and sentinel artifact.

The corrected primary command remains intentionally RED at the same earliest
missing behavior:

```text
$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: clean public runner reaches v11 canonical owner
Expected: exit 0 / schemaVersion 11 / appliedVersions [1..11]
Actual:   exit 0 / schemaVersion 10 / appliedVersions [1..10]
```

The corrected real-boundary scenario additionally fails against current
production with exact stdout but non-empty mysqli warning stderr while the TCP
sentinel accepts the forbidden source connection:

```text
$ CPS_SCENARIO=runtime-boundaries php tests/InstallationProcess/classification_provenance_schema_001_test.php
TestFailure: batch-import-native-candidates.php missing exact public failure
Expected stderr: empty
Actual stderr: mysqli greeting warning from attempted sentinel connection
```

Both are **RED assertions**, not setup failures. The sentinel readiness timeout
passed and its connection attempt is the missing behavior under test. A fresh
independent reviewer must review these superseding hashes; task 2.2 remains
open.

## Correction v3 after fresh rereview v2

The three remaining blockers from
`reviews/tests/CLASSIFICATION-PROVENANCE-SCHEMA-001-v2.md` are corrected in:

```text
6413248fdd5f74379a91e09d315bd08219b3e46ecefd4dd91fd75bf68e8593f9  tests/InstallationProcess/classification_provenance_schema_001_test.php
cb83026c22289f56eefb5972da3fdc6c2d4cf4b8f0e528aaf981d18a607cc099  tests/Support/classification_source_sentinel.php
```

- Index fingerprints now retain the semantic `PRIMARY` identity while still
  ignoring secondary presentation names. A dedicated mutant replaces
  `PRIMARY KEY(id)` with merely `UNIQUE(id)` and requires conflict/zero repair.
- Every ordinary runner uses a bounded nonblocking collector. Both race
  children are registered immediately and an attempt-all `finally` terminates,
  closes and reaps every sibling not successfully collected.
- Every missing/drift CLI case installs command-specific output sentinels:
  native installation cases, history snapshots/quarantine, and active
  baseline/case/association outputs. Exact table metadata, binary rows and
  counters are snapshotted. The manifest SHA-256 is the concrete ready-
  publication sentinel. Before/after equality therefore directly proves zero
  output/provenance DML and zero ready publication for each command and mode.

The rerun remains the intended behavior RED at the earliest absent v11 owner;
the separately selected real-boundary scenario remains RED on the forbidden
source connection/non-empty stderr. Syntax and diff checks pass. Task 2.2
remains open pending another fresh independent review.

## Fresh amended v2 RED after GRILL-009 exact-hash approval

The owner approved amended executable spec SHA-256
`d6227243dad996c7f67e3b0e8e9fcac0c100567e101ca66220a00946034e4790`.
Previous RED and Gate 3 evidence were not carried forward.

The verifier now starts two real PHP subprocesses through a test-only
composition runner. Each receives an injected callback which can publish only
its own exact arrival token, then blocks on one shared release file. The parent
requires both arrival tokens before publishing that release. The callback is
passed directly to the canonical v11 migration application seam; no production
argv, environment or configuration switch is used. There is no test or
production `GET_LOCK`, `SLEEP`, migration ledger or other serialization.

```text
$ php tests/InstallationProcess/classification_provenance_schema_001_test.php
PHP Fatal error: Uncaught TestFailure: injected verifier barrier is reached
after each absent-v11 preflight before plain CREATE
```

Classification: **intended RED assertion**, not `SETUP_FAILURE`. The same run
successfully created the disposable database, applied the public v1–v11
catalogue, established the exact populated v1–v10 race predecessor and started
both real verifier subprocesses. Current production ignores the injected
before-create callback, so a child completes migration without publishing its
post-preflight arrival. The parent detects that exact missing behavior before
release instead of accepting a scheduler-dependent ordinary repeat.

After failure, attempt-all cleanup reaped both subprocesses, removed the exact
barrier root and dropped every `t_cps_*` database. A direct
`information_schema.SCHEMATA` query returned `[]`, and no
`/tmp/cps-barrier-*` directory remained.

Fresh verifier hashes:

```text
8de39b681a64ef8a74c497c700e15f1a461930214fe2aa8320940b18490061cc  tests/InstallationProcess/classification_provenance_schema_001_test.php
409a00d9d6c0cb929a6a91800d115cc81245e7349e768ef21f66fb798a6a6c56  tests/Support/classification_provenance_barrier_runner.php
cb83026c22289f56eefb5972da3fdc6c2d4cf4b8f0e528aaf981d18a607cc099  tests/Support/classification_source_sentinel.php
```

Both PHP files pass `php -l`; focused `git diff --check` passes. Fresh
independent Gate 3 test review is required before any production change.
