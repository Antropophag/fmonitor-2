# Object-detail schema — explicit empty prefix harness correction

Date: 2026-09-05. Test author: `/root`.
Implementation candidate: `877f52993d8ddf4570990874800306241581a120`.
Exact pre-implementation RED base: `26bed9af6c70aefc690e89b5965246ea26628e1c`.

The test's process launcher now preserves explicit empty environment values
using `/usr/bin/env KEY=` arguments. Nonempty values (including nonempty test
credentials) remain solely in the environment. A new pre-seam calibration
proves empty prefix survives: valid configuration with deliberately unreachable
DB yields exit 69/DATABASE_UNAVAILABLE, not exit 64/missing configuration.
The existing deadlines/output bounds/finally termination and reap are unchanged.

## Exact RED and candidate GREEN

In a detached task-owned worktree at `26bed9af...`, only this test patch was
applied. Corrected test hash equalled the current worktree test hash exactly:
`b0dc9cf7d87c275c201409f6933924c7d8a275b6d43da137f84c025bac884ed6`.

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
TestFailure: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 requires the missing public v12 migration seam.
Expected true / Actual false
exit 255
```

Against exact production candidate `877f529...`, the same test passed:

```text
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract
exit 0
```

Syntax and diff checks passed. The temporary worktree had only the known test
modification, byte-identical to the current test, before removal. It was removed
and `git worktree list` showed only the integration checkout. No production,
specification, configuration or historical record changed in this correction.

Fresh independent Gate 3 must approve this harness amendment before completed
Gate 4 is claimed. The broader interruption/concurrency/composed-fixture and
importer gates remain open. This candidate GREEN is not a substitute for them.
