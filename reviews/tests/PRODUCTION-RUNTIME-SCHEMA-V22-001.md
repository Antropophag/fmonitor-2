# Independent Gate 3 review — PRODUCTION-RUNTIME-SCHEMA-V22-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Test author: separately tasked agent `/root/runtime_tests`; this reviewer did
  not author the specification, tests, production migration, or RED evidence
- Public seam: canonical migration catalogue plus its registered v22 migration
- Verdict: **APPROVED**

This approval is bounded to registration and behavior of canonical schema v22.
It does not approve the remaining production HTTP runtime.

## Exact reviewed artifacts

```text
5a224fc9e68742bc8c994c0fe6bea5c4f52fdbf6098623e58765c65177132645  openspec/changes/production-http-runtime/specs/operations/locked-schema-migrations/spec.md
2f3293f3ce4b7b5fbb465b15d7a83bf4d59258775a8fd58322726127ed7dcc2c  tests/Runtime/production_schema_frontier_001_test.php
e831307137c7a98f23818470efccca29456a411b99f5e4931fe75edd64e36358  tests/InstallationProcess/production_migration_runner_001_test.php
878460b8857b2bbbc08201e93f084c2974b3de7870f86e0198d109b4290eb189  tests/Otiz/runtime_schema_001_test.php
b882cfadd7807fbf15cfcfb63445f5baf585aa1f0f2ce6112133ec36da041664  tests/InstallationProcess/pilot_e2e_flow_001_test.php
```

## Traceability and sensitivity

The focused executable first requires a contiguous canonical `1..22` catalogue,
then obtains v22 from that public catalogue. Once registration is implemented it
will exercise all newly specified shapes against real MariaDB:

- a populated exact ten-column predecessor must add exactly seven columns and
  preserve every predecessor value;
- the resulting compatible populated table must be a no-op on repeat;
- an incompatible populated table must return `SCHEMA_MIGRATION_CONFLICT` while
  preserving byte-equivalent `SHOW CREATE TABLE` output and all rows;
- a clean full catalogue must reach v22, satisfy production legacy-object
  readiness, and preserve an inserted object fact across catalogue replay.

The expectations come from the delta specification and the established read-only
schema readiness contract. They do not copy a proposed v22 registration. The
three existing suites are changed only at the exact catalogue frontier assertions;
they retain their broader migration, OTIZ, and protected E2E barriers.

## Demonstrated baseline RED

```text
$ FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
    php tests/Runtime/production_schema_frontier_001_test.php

Fatal error: Uncaught TestFailure: INTENTIONAL_RED: canonical production catalogue has a contiguous v22 frontier
Expected: array (0 => 1, ..., 21 => 22)
Actual:   array (0 => 1, ..., 20 => 21)
exit 255
```

The database connection and isolated database creation succeeded before this
assertion. The failure is the intended missing catalogue registration, not setup.
The later predecessor/conflict/replay assertions form independent barriers after
the first RED is corrected.

## Gate 3 checklist

- Traceability: PASS — every v22 shape and catalogue outcome maps to the added
  requirements.
- Public seam: PASS — real catalogue registration, migration callable, readiness,
  and MariaDB schema/data.
- RED specificity: PASS — current production ends contiguously at v21.
- Expected-value independence: PASS — expected shapes and preservation outcomes
  are specified independently of registration code.
- Rejected cases: PASS — incompatible populated schema is snapshot before/after.
- Determinism/isolation: PASS — unique database and unconditional teardown.
- Sensitivity: PASS — registration alone advances into predecessor, conflict,
  clean schema, readiness, replay, and existing-suite frontier barriers.

## Gate 4 boundary

**APPROVED.** Gate 4 may register the existing legacy-object migration as exact
canonical v22 and update no production behavior beyond that bounded catalogue
addition. Reviewed expectations must not change without a new Gate 2/3 cycle.
