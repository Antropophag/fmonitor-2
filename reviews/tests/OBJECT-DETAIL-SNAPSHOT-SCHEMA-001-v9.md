# Added-coverage review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 two creators

- Reviewer: `/root/seed_original_planning_review` (independent of these fixtures)
- Test author: `/root`
- Reviewed commit: `7f3536f56241f4a409fe35d267ceb451c4e2f395`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Test: `tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php`, SHA-256 `92f241e3f89c525eb07af07a84ede4a15cf62dbfbc4b353bbbc1737108075955`
- Worker: `tests/Support/object_detail_schema_concurrency_worker.php`, SHA-256 `0caebf566be8f3323cd2a31c90281f6c6a3a2612e24baf2d3bc4f9b0262dfb9f`
- Evidence: `docs/operations/object-detail-schema-two-creator-evidence-2026-09-05.md`, SHA-256 `73e4593b216255570aeeec6d2886fc1205fbae4675b938560a6bac62b7a99f9d`
- Verdict: **APPROVED** as bounded added concurrency coverage

## Findings

The test establishes the required causal sequence with three real child PHP
processes and independent parent observations:

- Worker A reports its actual mysqli connection ID, then emits `READY` only
  from the verification observer's `LOCK_ACQUIRED` phase while it is blocked on
  the tokenized release protocol. The parent independently proves that exact
  connection owns the normative SHA-256 lock for the randomized database and
  `race_` prefix.
- Worker C uses `other_` and finishes its exact two-table creation while A still
  owns the `race_` lock. C is stopped and reaped successfully before B starts,
  so unrelated namespace work does not consume B's five-second lock wait.
- Worker B has a distinct real connection ID. Before A is released, the parent
  observes B in `information_schema.PROCESSLIST` waiting on `GET_LOCK` with the
  exact normative lock name and independently proves both race tables remain
  absent.
- Only after the exact tokenized `RELEASE` does A return applied/two sorted
  tables; B then returns the exact compatible repeat with no created tables.
  The final physical inventory is exactly the two `race_` and two `other_`
  tables, every table is empty, the race lock is free, and all three workers
  exit zero with no unconsumed stdout or stderr.

IPC and lifecycle controls are bounded: protocol lines and aggregate output
have explicit limits, reads and process shutdown use monotonic deadlines,
tokens/database/prefix/mode are validated, workers close their database
connection in `finally`, and parent cleanup attempts every child even after a
cleanup error. The parent drops only the randomized database whose CREATE
succeeded, in an outer `finally` after child cleanup/reap attempts.

The detached pre-v12 missing-verification-seam failure is useful retrospective
sensitivity evidence: the byte-identical fixtures pass their real database
prerequisite and fail on the absent required public seam. It is explicitly not
represented as historical RED-before-implementation TDD evidence and is not
needed to rewrite the already completed earlier gate history.

## Independent verification

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php

php -l tests/Support/object_detail_schema_concurrency_worker.php
No syntax errors detected in tests/Support/object_detail_schema_concurrency_worker.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php
PREREQUISITE PASS: isolated schema database
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 causal two-creator serialization and namespace independence
exit 0
```

A read-only residue query returned `[]` for `fm2_ods_concurrent_%`; a subsequent
process scan found no concurrency worker. `git diff 7f3536f^ 7f3536f --check`
passed.

## Authority boundary

This verdict approves only the added causal two-creator serialization and
different-prefix independence coverage. It does not itself approve production
code, replace an implementation Gate 5 review, approve importer behavior, or
establish full migration regression/integration or Done. Those remaining gates
stay open.

Required changes: none for this bounded coverage amendment.
