# Object-detail schema — real DDL denial coverage

Date: 2026-09-05. Test author: `/root`.
Current base: `a7bc7648a1ef94ea8bcc923b653a028417c27785`.
Exact pre-v12 base: `26bed9af6c70aefc690e89b5965246ea26628e1c`.

This adds a missing required proof to already implemented generic error/retry
behavior; no production code was changed to manufacture a RED.

An isolated principal has SELECT for its uniquely created test database and
table-level CREATE for details only. CURRENT_USER and actual table grants are
asserted before the public seam. The real second CREATE fails; the public
migration returns DatabaseUnavailable, details remains empty/durable,
quarantine remains absent and the lock is free while the caller stays connected.
After a fictional sentinel and the missing CREATE grant, ordinary retry creates
only quarantine, preserves the exact existing row and returns a clean repeat.

Current run:

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_ddl_denial_001_test.php
No syntax errors detected
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_ddl_denial_001_test.php
PREREQUISITE PASS: isolated principal can CREATE only the exact details table
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real DDL-denial partial recovery
exit 0
```

In a detached worktree at the exact pre-v12 base, the byte-identical test passes
the same privilege prerequisite, then reports intended missing public v12
owner (Expected true / Actual false), exit 255. That is retrospective baseline
evidence, not a claim this test preceded the implementation historically.

Test SHA-256:
`19db7462e488b1eb0f17b59425260a40d42204a5363e9e6cf36fce9a41b86b7d`.
Read-only cleanup query returned `{"schemas":[],"users":[]}`. Before removal,
the detached worktree contained only this known untracked test with the same
hash. After removal only the main checkout remained. Diff-check passed.

Fresh independent test review is required. This does not prove two-creator
concurrency, importer no-DDL or full integration and does not approve any gate.
