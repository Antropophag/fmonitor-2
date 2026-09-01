# Test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_rereview_20260901k`
- Test authors: separately tasked Gate 2 agents; this reviewer authored no tests, planning or production code
- Supersedes: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001-v2.md`
- Reviewed state: authoritative dirty worktree based on `79658fa`
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved version `0.1`
- Approved amendment: `docs/operations/identity-access-gate1-diagnostic-seam-amendment.md`
- Verdict: `APPROVED`

## Independence and scope

I reviewed the constitution and delivery process, the approved executable
specification and all four strict-valid OpenSpec artifacts, the owner-approved
diagnostic-seam amendment, historical reviews v1/v2, RED evidence v1-v8, the
current canonical/application contract test, first-GREEN contract helper,
runtime SQL observer and its exclusive MariaDB runner. I did not edit tests,
specification, production source or OpenSpec tasks.

## Prior blockers and amendment review

1. The clean-family assertion now compares test-owned literal semantic
   manifests for all nine tables: ordered columns and types, nullability,
   defaults, auto-increment flags, character metadata, table-local indexes,
   five FK tuples, engine, database-default collation and deterministic FK
   symbols. Expected values are independent of production migration constants.
2. Conflict fixtures are category-complete for the approved matrix: ordinal/
   name/type, nullability, default, auto-increment, enum, extra column, primary,
   unique and secondary indexes, engine, latin1 charset, alternate utf8mb4
   collation, and independent FK target, local column, referenced column,
   delete-rule and update-rule defects. Each executed fixture requires exact
   redacted CLI output and byte-observable zero mutation.
3. The owner-approved seam split is represented precisely. The CLI remains
   redacted. The public `IdentityAccessSchemaMigration` result object has exact
   ordered assertions for complete `conflictingTables`, `missingTables` and
   `tablesCreated`; these become reachable when minimal GREEN supplies v6.
   The multi-conflict CLI fixture independently proves no missing member is
   created and no table/catalog detail leaks.
4. Compatible complete/populated, 8/9 and dependency-subset recovery assertions
   preserve rows, schema and counters, require exact applied versions and prove
   restartable no-op behavior. Prefix coexistence, exact prefix-scoped FK
   targets/symbols, the 25-byte identifier boundary and pre-DB 26-byte/invalid
   rejection are covered.
5. The exclusive runtime observer exercises real login, invitation, role
   attach/detach and block/unblock seams. It distinguishes HTTP status from
   child exit, observes SQL on a task-owned MariaDB instance, and verifies
   migrated, missing and incompatible status-event states. Its randomized
   tmpfs container/database and trap provide deterministic cleanup without
   shared logging state.
6. `identity_access_schema_001_green_application_contract.php` fixes the
   first-GREEN expectations at exact exit `70`, one redacted
   `MIGRATION_FAILED` JSON line, empty stderr and zero post-v6 callback
   invocations. Under the explicit amendment these assertions are correctly
   reviewable but not executable before v6 exists. Minimal GREEN must provide
   the public adapters and invoke this helper; changing its expectations would
   invalidate this approval and return the slice to Gates 2-3.
7. Canonical no-seed behavior, separate destructive-bootstrap boundary,
   complete preflight and runtime ownership constraints are asserted without
   selecting or changing the blocked `GRILL-002` RBAC/authorization semantics.

## Reproduced qualifying RED

Canonical runner:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php
exit 255
Expected schemaVersion=6, appliedVersions=[1,2,3,4,5,6]
Actual   schemaVersion=5, appliedVersions=[1,2,3,4,5]
```

Exclusive runtime observer:

```text
tools/verification/run-identity-access-isolated-red.sh
exit 255
```

It reproduced two migrated-path lazy `CREATE TABLE` statements; the missing
member returned HTTP 303 instead of 400, mutated user state and emitted lazy
DDL. The incompatible-member branch added no failure. No random
`fm2-ia-red-*` container remained after the trap.

Both failures arise from absent approved production behavior rather than test
setup. They are independent qualifying RED causes through the approved public
seams.

## Verification

- PHP lint: all three focused PHP artifacts pass.
- Shell syntax: isolated runner passes `bash -n`.
- Artifact SHA-256 values match RED evidence v8.
- `openspec validate canonicalize-identity-access-schema --strict`: PASS.
- Focused `git diff --check`: PASS.
- Exclusive observer cleanup query: no matching containers.

## Gate decision

Gate 3 is `APPROVED`. OpenSpec task `2.4` is authorized to be checked. Gate 4
minimal GREEN is authorized, limited to canonical identity/access schema
ownership, literal v6 registration, removal of runtime DDL and the separately
invoked bootstrap boundary. The first GREEN must execute the already-authored
unexpected-v6-failure/post-v6-short-circuit contract without weakening it.
Any test expectation or approved seam change requires a fresh append-only Gate
2/3 cycle. `GRILL-002` continues to block RBAC/authorization behavior changes.
