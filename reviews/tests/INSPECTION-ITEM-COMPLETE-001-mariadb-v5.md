# INSPECTION-ITEM-COMPLETE-001 — independent MariaDB overlap Gate 3 review v5

Date: 2026-09-01  
Reviewer: `/root/item_test_review` (independently tasked; did not author the
test, specification, production or RED evidence)  
Mission: `TEST-USER-READY`  
Verdict: `APPROVED`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Approved composition-amended spec: SHA-256
  `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.
- Corrected MariaDB test: SHA-256
  `026c4bf84433b6abd534b4aa5a2f2c6dca1b0322ea883b345a03fb7e6e1dccee`.
- Pairing evidence v5: SHA-256
  `72d4dc20e449a43d3555af17a78c0cafd6e6e7b7c09a203ced0c9e0b96bb52fd`.
- Prior v4 review: SHA-256
  `179d895d1960b9f93a2adb240fafefbf63b2147ee3bc8cde40b4cc609c21da1c`.
- RED runner: SHA-256
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

## Independently reproduced lifecycle

With a healthy test Compose environment I ran:

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
FMONITOR_IIC_BREAK_OVERLAP=1 tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

Syntax passed. Controlled break mode produced the intended missing-overlap RED:

```text
SETUP_FAILURE: both exact worker revision-lock queries continuously visible in PROCESSLIST for 300ms.
Expected: true
Actual: false
RED_ASSERTION: expected failing behavior observed
```

The default path no longer failed at the overlap oracle. It released the
coordinator only after sustained observation, then both workers reached the
production command and reported the current production exception:

```text
mysqli_sql_exception: Illegal mix of collations
(utf8mb4_unicode_ci,IMPLICIT) and
(utf8mb4_uca1400_ai_ci,IMPLICIT) for operation '='
```

Both paths self-cleaned. Independent post-run probes reported
`databases=0 users=0`; no owned artifacts or worker processes remained. Compose
down removed container, volume and network and final `ps --all` was empty.

## MDB4-01 closure

MDB4-01 is closed. The predicate admits only these exact command/SQL pairs:

1. `COMMAND=Execute` with the normalized prepared statement
   `SELECT revision_no FROM ${prefix}fm2_checklist_revisions WHERE
   installation_case_id=? FOR UPDATE`;
2. `COMMAND=Query` with the normalized literal statement using exact case
   `installation_case_id=4512`.

It does not independently allow either command or either SQL representation:
`Execute + 4512`, `Query + ?`, another command, table, selected column,
predicate or case all fail. Both exact published worker connection ids must be
present simultaneously with an allowed executing state; every bad/missing
sample resets the sustained interval. The coordinator has already fetched and
asserted revision `0` under its exact private-row lock, and it retains that lock
for the full 300 ms default observation.

Break mode commits before the worker `go` signal. Its reproducible RED therefore
demonstrates that fast executions without the coordinator lock cannot satisfy
the sustained predicate. Default mode reproducibly advanced beyond the oracle,
demonstrating that the corrected `Execute + ?` path accepts MariaDB's actual
prepared-execution metadata rather than always failing.

## Default failure classification

The default collation exception is a genuine production GREEN failure, not a
test/setup/oracle defect:

- the test database is explicitly and canonically created with
  `utf8mb4_unicode_ci`, and migrations/fixtures complete successfully;
- workers authenticate through the reviewed DML-only production factory path;
- the production `installationCase` query compares the canonical association
  string with `CAST(c.id AS CHAR)`;
- on this MariaDB, that cast uses connection collation
  `utf8mb4_uca1400_ai_ci` while the canonical column is
  `utf8mb4_unicode_ci`, producing the reported comparison failure after the
  overlap lock is released.

No fixture, schema expectation or oracle was weakened to accommodate it. Gate 4
must make production use compatible canonical/connection collation semantics;
the test correctly remains non-green until then.

## Reconfirmed unchanged coverage

- Coordinator revision-zero fetch, two distinct published worker ids, exact
  private table/case/`FOR UPDATE`, sustained acquisition deadline and break
  sensitivity remain intact.
- Bounded process/pipe ownership, TERM/KILL/reap, aggregated cleanup and
  database/user/artifact/worker absence verification are unchanged.
- Literal 26-table catalogue, DML-only runtime user, decoy and no-runtime-DDL
  sensitivity remain unchanged.
- Once workers succeed, the unchanged assertions still require unordered exact
  `ACCEPTED(1)`/`STALE_REVISION(1)`, complete literal winner DTO for either
  winner, loser `null`, exact `DUPLICATE(1)` and conflict revision `1`, unchanged
  evidence, and zero query/replay/conflict clock calls.
- Business outcomes remain observable only through the approved command/query
  seams. No sequential concurrency substitute or direct SQL business oracle was
  added.

## Gate decision

The PROCESSLIST correction is deterministic, correlated and demonstrably
sensitive in both directions. MariaDB overlap Gate 3 v5 is `APPROVED` for the
exact hashes above. Gate 4 may fix the exposed production collation failure and
continue until the previously reviewed default assertions turn green; test
expectations must remain unchanged.
