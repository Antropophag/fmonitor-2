# Independent Gate 3 review — PRODUCTION-RUNTIME-MIGRATION-LOCK-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`; this reviewer did
  not author the specification, design, test, production implementation, or RED
  evidence
- Public seam: `CanonicalMigrationApplication::run()` over two independent
  MariaDB connections
- Verdict: **APPROVED**

This is a bounded Gate 3 approval for the whole-catalogue migration lock only.
It does not approve the production HTTP runtime, packaging, route compatibility,
health, persistence, or graceful-stop contracts in the surrounding OpenSpec
change.

## Exact reviewed artifacts

```text
9ed5329d51da1845d97fddb9f4f82a58f88edf8055c907d3984ffd55cdc53830  openspec/changes/production-http-runtime/specs/operations/locked-schema-migrations/spec.md
56238f8a4afa525fcec775c766e27a10a7d5e688db74e45d0ffee2bdf91a31de  openspec/changes/production-http-runtime/design.md
e83375c0d61845708e50dbe2d0802394a6b62143a709f6b5fd767ceb1799dd12  specs/PRODUCTION-HTTP-RUNTIME-001.md
78628a1a19bc48885debbba5978cdfdbbe4114634c9590b183bd741ea25800e3  tests/Runtime/migration_concurrency_lock_001_test.php
```

## Traceability and seam

The test covers the locked-schema-migrations requirements for one advisory lock
before preflight, no catalogue work by a contending runner, bounded contention,
and release after both success and a handled migration failure. It calls the real
canonical migration application with real MariaDB advisory locks; it does not
replace the application with a lock fake or inspect private methods.

The fixture independently acquires the deterministic database/catalogue lock on
a second connection. Its expected busy result (`exitCode 75`,
`MIGRATION_LOCK_UNAVAILABLE`) is an externally observable deployment outcome.
Counters prove that neither preflight nor a migration callback runs under
contention. After release, the same public seam must run preflight and the single
catalogue entry exactly once. Separate probes prove the application connection
does not retain the lock after success or after a synthetic migration failure.

The random database name isolates the run, the fixed table prefix bounds the
catalogue identity, and `finally` closes both connections and drops the database.
An early assertion failure cannot strand the advisory lock because MariaDB
releases connection-scoped locks when the fixture connection closes.

## Demonstrated baseline RED

```text
$ php -l tests/Runtime/migration_concurrency_lock_001_test.php
No syntax errors detected in tests/Runtime/migration_concurrency_lock_001_test.php

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/migration_concurrency_lock_001_test.php

Fatal error: Uncaught TestFailure: concurrent catalogue runner fails closed with a stable operational outcome
Expected: array (
  'exitCode' => 75,
  'result' => array (
    'ok' => false,
    'reason' => 'MIGRATION_LOCK_UNAVAILABLE',
  ),
)
Actual: array (
  'exitCode' => 0,
  'result' => array (
    'ok' => true,
    'schemaVersion' => 1,
    'appliedVersions' => array (0 => 1),
  ),
)
exit 255
```

The database service was healthy before the run. The failure is the intended
missing behavior: current `CanonicalMigrationApplication::run()` executes the
migration callback while the independently held catalogue lock should exclude
it. Setup, connection, database creation, and fixture lock acquisition have all
already succeeded at that point.

## Gate 3 checklist

- Traceability: PASS — contention and release assertions map to the whole-catalogue
  advisory-lock requirements and scenarios.
- Public seam: PASS — real canonical application and MariaDB connections.
- RED specificity: PASS — the observed success and callback execution are exactly
  the absent exclusion behavior.
- Expected-value independence: PASS — busy/success/failure outcomes and callback
  counts come from the specification; the fixture lock is established separately.
- Rejected cases: PASS for this bounded slice — contention does no preflight or
  migration work; migration failure remains the canonical failure result.
- Determinism/isolation: PASS — unique database, bounded immediate acquisition,
  deterministic prefix, connection-scoped cleanup, and database teardown.
- Sensitivity: PASS — status-only handling cannot pass because callbacks and lock
  release are independently checked after the first RED is corrected.

## Gate 4 boundary

**APPROVED.** Gate 4 may add the minimal whole-catalogue advisory-lock ownership
to `CanonicalMigrationApplication`, before preflight and through the final
migration, with the reviewed stable busy outcome and `finally` release behavior.
The reviewed test and expectations must remain unchanged; changes require a new
Gate 2/3 cycle. Full production runtime Gate 3 remains pending.

## Supplemental two-runner evidence

```text
afb272160708221241e88464507fab4a12cf286ec48d0e76564c7404eefd863b  tests/Runtime/migration_parallel_runners_001_test.php
04d6f8e8a2a6e4984098812cc17ade9feaccfdb03511908869cec157900dcc12  tests/Runtime/migration_parallel_runner.php
```

Two real PHP children call the public canonical application. The winner signals
from inside public preflight while holding the whole-catalogue lock. The loser
returns exact exit 75 with zero preflight/migration callbacks, no marker and no DB
effect. After release, the winner executes each callback once and persists exactly
one winner row. This is supplemental regression evidence after the approved lock
RED, rather than a replacement RED.

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/migration_parallel_runners_001_test.php
PASS: PRODUCTION-HTTP-RUNTIME-001 two real canonical migration runners
```
