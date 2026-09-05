# Object-detail schema — causal two-creator coverage

Date: 2026-09-05. Test author: `/root`.
Current base: `0872b56d217487dfdf8d3fc24bf5f42a166b8a9d`.
Retrospective pre-v12 sensitivity base:
`26bed9af6c70aefc690e89b5965246ea26628e1c`.

Three real PHP workers connect to one uniquely owned fictional database.
A holds the approved LOCK_ACQUIRED observer phase and announces its actual
connection ID. The parent independently verifies named-lock ownership.
C creates a different prefix while A still holds its lock. Only then B starts
for A's prefix, avoiding consumption of B's five-second wait by unrelated work.
The parent observes B in PROCESSLIST waiting on GET_LOCK for the exact lock
name and verifies the race family is still absent before releasing A.
A returns applied/two tables; B returns exact repeat/no tables. Final physical
inventory is exactly four empty tables; the lock is free and workers exit 0
with no extra stdout/stderr.

The test uses bounded lines/output, monotonic deadlines, explicit protocol
tokens, and termination/reap before owned DB cleanup. Cleanup attempts all
children even if one cleanup reports failure. Worker error exits occur after
its finally block closes the connection. No production selector was added.

Current command passes:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php
PREREQUISITE PASS: isolated schema database
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 causal two-creator serialization and namespace independence
exit 0
```

Both byte-identical new files were also placed in a detached worktree at the
pre-v12 base. The fixture DB prerequisite passed, then the missing required
verification seam assertion failed (Expected true / Actual false), exit 255.
This is retrospective sensitivity evidence, not a claim the new coverage was
written before existing locking implementation. No production mutation was
used to manufacture failure.

Exact SHA-256:

```text
92f241e3f89c525eb07af07a84ede4a15cf62dbfbc4b353bbbc1737108075955  tests/InstallationProcess/object_detail_snapshot_schema_concurrency_001_test.php
0caebf566be8f3323cd2a31c90281f6c6a3a2612e24baf2d3bc4f9b0262dfb9f  tests/Support/object_detail_schema_concurrency_worker.php
```

Lint/diff checks pass. Read-only DB residue query returned `[]`; process scan
found no worker left. The detached worktree contained only these known files
with identical hashes before removal. Fresh independent coverage review is
required; importer and full migration integration remain open.
