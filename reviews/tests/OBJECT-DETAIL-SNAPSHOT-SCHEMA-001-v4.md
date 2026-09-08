# Test rereview: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 empty-prefix harness amendment

- Reviewer: `/root/seed_original_planning_review` (independent of this amendment)
- Amendment author: `/root`
- Reviewed commit: `13f4eef13891b03b325ca56097218666ad5f25cf`
- Previously approved bounded Gate 3 review: `reviews/tests/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001-v3.md`
- Exact preimplementation RED base: `26bed9af6c70aefc690e89b5965246ea26628e1c`
- Exact production candidate used by the evidence: `877f52993d8ddf4570990874800306241581a120`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Corrected test: `tests/InstallationProcess/object_detail_snapshot_schema_001_test.php`, SHA-256 `b0dc9cf7d87c275c201409f6933924c7d8a275b6d43da137f84c025bac884ed6`
- Amendment evidence: `docs/operations/object-detail-schema-empty-prefix-harness-correction-2026-09-05.md`, SHA-256 `cfe512430ed807d66484bb5f203a2450a447b93a5ff291868a8515cbf2c8194e`
- Verdict: **APPROVED** for the empty-prefix harness amendment only

## Findings

The amendment correctly addresses the host behavior where `proc_open` can omit
an explicit empty environment value. It adds `/usr/bin/env` arguments only for
values that are exactly empty, so the empty
`FMONITOR_PROCESS_TABLE_PREFIX=` assignment survives while nonempty database
credentials remain solely in the child environment and do not enter argv.
Environment names are fixed by the test harness, not supplied by an external
actor.

The new pre-guard calibration is discriminating: with an empty prefix and a
deliberately unreachable database it requires exit 69 and exact
`DATABASE_UNAVAILABLE` output. Exit 64 would reveal the original dropped-empty-
value fault. It executes before the production-seam assertions, so the detached
preimplementation run still proves a qualifying missing-seam RED after this
harness assumption passed.

The evidence records byte-identical corrected test SHA-256 on detached exact
preimplementation base `26bed9af...`, where the test reached the prerequisite
message and failed on the missing public v12 seam with exit 255. The same bytes
passed on candidate `877f529...`. The amendment does not weaken or alter the
previously approved oracle, subprocess deadline/output bounds, termination/reap
handling, database isolation, or owned cleanup.

## Independent verification

At exact reviewed commit `13f4eef13891b03b325ca56097218666ad5f25cf`:

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_001_test.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract
exit 0
```

A read-only post-run inventory for `fm2_ods_red_%`, `fm2_ods_runner_%`, and
`fm2_ods_collation_%` returned `[]`. `git diff 13f4eef^ 13f4eef --check` also
passed.

## Authority boundary

This approval covers only the empty-prefix process-launcher amendment to the
already approved bounded first-tranche test. It does not approve or complete
deterministic interruption, connection-loss/final-verification behavior,
table-scoped DDL denial/retry, named-lock or creator concurrency, composed
fixtures, importer DML/source behavior, full migration integration, Gate 5, or
Done.

Required changes: none for this bounded harness amendment.
