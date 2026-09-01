# Test rereview: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_rereview_20260901i`
- Test authors: separately tasked Gate 2 agents through `identity_access_red_statusfix_20260901h`
- Supersedes: `reviews/tests/IDENTITY-ACCESS-SCHEMA-001.md`
- Reviewed state: authoritative dirty worktree based on `79658fa`
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, owner-approved version `0.1`
- Public seams: `php bin/fmonitor2-migrate.php`; login and status HTTP handlers; `MariaDbPilotUserDirectory`
- Verdict: `CHANGES_REQUESTED`

## Independence and scope

I did not author or modify the executable specification, tests, production code,
OpenSpec artifacts, RED evidence or historical review. I reviewed the approved
specification, all four `canonicalize-identity-access-schema` artifacts and task
state, the historical Gate 3 review, RED evidence v1-v7, both current focused
tests and the exclusive MariaDB runner. This append-only record changes no test,
specification, production source or OpenSpec task.

## Reproduced qualifying RED

Canonical runner command:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
  php tests/InstallationProcess/identity_access_schema_001_test.php
```

Exit `255`. The runner connected and returned valid JSON, but the first literal
assertion received `schemaVersion=5`, `appliedVersions=[1,2,3,4,5]` instead of
approved `schemaVersion=6`, `appliedVersions=[1,2,3,4,5,6]`. This is qualifying
RED caused by absent production v6, not setup failure.

Exclusive runtime observer command:

```text
tools/verification/run-identity-access-isolated-red.sh
```

Exit `255`. It observed two lazy `CREATE TABLE IF NOT EXISTS ...status_events`
statements on migrated block/unblock. With the table missing it observed HTTP
303 rather than required safe HTTP 400, a user-state mutation, and the same lazy
CREATE. The incompatible-table branch emitted no failure, confirming corrected
HTTP-status observation. The random MariaDB container was removed by the trap.

## Prior blockers that are closed

1. Fixtures no longer call production DDL to construct the canonical expected
   family. Literal CREATE statements, deterministic test data, nullable values
   and non-default counters are owned by the test.
2. Complete/populated repeat, 8/9 recovery and a dependency-subset recovery are
   present. Existing member snapshots are preserved and recovery repeat is
   asserted as a no-op.
3. Independent fixtures cover column/order/type, nullability, default,
   auto-increment, enum, extra column, PK, unique and secondary indexes, engine,
   alternate collation, plus one FK delete-rule defect. Every executed conflict
   expects exact redacted CLI output and zero mutation.
4. Multi-table conflicts coexist with missing members and the snapshot proves
   short-circuit before missing-member creation.
5. Multiple prefixes, cross-prefix target isolation, deterministic canonical FK
   symbols, 25-byte identifier bounds and pre-DB rejection of 26-byte/invalid
   prefixes are exercised.
6. The exclusive observer runs fictional/native login, invitation, role
   attach/detach and block/unblock paths and checks migrated, missing and
   incompatible status-event states without shared logging state.
7. The executable specification approval fields are reconciled and OpenSpec
   tasks 2.1-2.3 reflect the authored test scope.

## Blocking findings

### 1. Exact nine-table manifests are still not asserted against literals

Section 7 item 1 requires exact test-owned manifests for all nine tables. The
test collects full semantic manifests, but on the clean family it directly
asserts only engine/collation for each table, the users column-name order, one
enum type and the five FK symbol names. It never compares the collected columns,
indexes and FKs of all nine tables with a literal expected manifest.

This leaves most column types/nullability/defaults/extra flags, all table-local
index structures and FK column/target/rule tuples able to drift while clean
creation still passes. The populated before/after snapshot proves preservation,
not conformance to the approved fingerprint. This is the central independence
blocker from the first review and is not closed by having literal CREATE fixture
SQL elsewhere in the same test.

Required correction: define literal expected semantic tuples transcribed from
specification section 4 and compare every clean table's columns, indexes, FKs,
engine, charset/database-default collation and deterministic symbols to them.

### 2. The required fingerprint conflict matrix is not category-complete

The matrix lacks independent mutations for FK target, FK local/referenced
columns and FK update rule. It covers only a changed delete rule. It also lacks
an independent character-set defect: converting to `utf8mb4_bin` changes
collation but remains utf8mb4, so it does not demonstrate charset sensitivity.
The approved matrix explicitly names FK target/columns/delete/update and both
charset and non-default collation.

Required correction: add independent zero-mutation fixtures for each missing FK
dimension and a non-utf8mb4 character-set fixture, each with exact redacted
stdout/stderr/exit.

### 3. Complete preflight lists are not actually observed

The multi-conflict test proves zero mutation and exact redacted public CLI, but
does not observe the migration result's ordered `conflictingTables` and
`missingTables`. Therefore it cannot detect an incomplete or incorrectly ordered
full-family classification as required by sections 5 and 7 item 5. The public
CLI intentionally redacts these fields, and no approved observable seam in the
current test supplies them.

Gate 1 and the test seam must be reconciled: either provide a pre-agreed public
deployment result seam that exposes the internal classification without
weakening CLI redaction, or amend the executable requirement with explicit owner
approval. A test must then assert both complete ordered lists and prove no DDL,
DML or later migration invocation.

### 4. Unexpected v6 failure and post-v6 short-circuit remain untested

RED evidence v3-v7 correctly explains why these branches cannot yet be selected,
but section 7 item 11 still requires exact `MIGRATION_FAILED` redaction and
stop-before-later-migration for an unexpected v6 failure. A future post-GREEN
branch is not an independently reviewed Gate 2 test today. Production must not
start while an explicit approved Gate 2 acceptance branch is deferred until
after production editing.

Required correction: establish an approved public test seam/fixture capable of
selecting the failure and observing that later migration is not invoked, then
capture its intended RED without adding identity production behavior.

## Gate decision

The two demonstrated failures are honest and valuable, and the runtime observer
substantially improves sensitivity. However the remaining findings are explicit
normative Gate 2 requirements, not optional regression depth. Gate 3 is not
approved. OpenSpec task `2.4` must remain unchecked, and Gate 4 production edits
are not authorized by this review. After corrections, obtain another fresh
append-only independent test review; do not rewrite this record.
