# Test review: IDENTITY-ACCESS-SCHEMA-001 v0.1

- Date: `2026-09-01`
- Reviewer: fresh independent agent `identity_access_test_review_20260901b`
- Test author: fresh separately tasked agent `identity_access_red_author_20260901a`
- Reviewed state: authoritative dirty worktree at `79658fa`
- Specification: `specs/IDENTITY-ACCESS-SCHEMA-001.md`, approved behavior version `0.1`
- Public seam: `php bin/fmonitor2-migrate.php`
- Red command: `FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/identity_access_schema_001_test.php`
- Intended failure: clean runner returns final v5 and `[1,2,3,4,5]` because literal identity/access v6 is absent
- Verdict: `CHANGES_REQUESTED`

## Independence and reviewed scope

I did not author or modify the test, production code, executable specification,
OpenSpec planning, RED evidence or operations status. I reviewed the approved
specification, all four `canonicalize-identity-access-schema` artifacts, source
evidence, partial-recovery owner decision, the Gate 1 review chain through
`identity-access-gate1-rereview-v3.md`, the new focused test and its RED record.

## Accepted evidence

1. The first executed assertion uses the agreed public canonical CLI seam and
   literal values owned by the specification. It is not derived from the
   migration map or production constants.
2. The reproduced failure is qualifying RED: the process connects to the
   isolated task-owned MariaDB database, successfully applies v1-v5, emits
   valid JSON and fails only because v6 is absent. I reproduced exit `255` with
   expected `schemaVersion=6`, `appliedVersions=[1,2,3,4,5,6]` versus actual
   `schemaVersion=5`, `appliedVersions=[1,2,3,4,5]`.
3. The database name is randomized and dropped in `finally`; the child process
   receives an explicit minimal environment. Prefix 26 and invalid-character
   fixtures use an unresolvable host/database and therefore can demonstrate
   pre-DB rejection once reached.
4. Literal success/repeat/conflict CLI expectations, prefix isolation and
   before/after preservation snapshots provide useful tracer coverage. The test
   does not assert RBAC authority or change authorization semantics.

## Blocking findings

### 1. Required fingerprint conflict matrix is mostly absent

Section 7 item 4 requires independent zero-mutation coverage for ordinal/name/
type, nullability/default/AI, enum, extra column, PK/unique/secondary index, FK
target/columns/delete/update, engine, charset and alternate collation. The test
mutates only one extra column and one FK delete rule. It does not establish that
the future compatibility classifier is sensitive to the remaining categories.

The test also lacks the required multi-table conflict with complete ordered
`conflictingTables` and `missingTables`, exact redacted stdout/stderr/exit, and
proof that no missing member is created or later migration invoked. Merely
checking exit code and an unchanged complete family is insufficient for the
full-family zero-mutation contract.

### 2. Clean and compatible expectations are implementation-coupled and incomplete

Clean creation checks only existence and emptiness of nine tables. It does not
assert the test-owned literal column, default, enum, index, FK, engine, charset,
database-default collation or deterministic-symbol manifests required by
section 7 item 1.

`iaCreateObservedFamily()` calls the current production oracle
`RapidPilotIdentityBootstrap::apply()` to manufacture the expected compatible
eight-table shape, then uses another ad-hoc CREATE for the ninth. Consequently
the populated/recovery fixture can drift in the same direction as production
and still be accepted. The approved contract explicitly requires test-owned
literal tuples independent from production DDL/constants. It also seeds only a
subset of tables and does not cover the example's nullable invitation values,
all-table sentinels and non-default counters.

### 3. Partial recovery matrix is incomplete

Only the easy 8/9 case with the dependency-free final status-event table absent
is covered. The required missing dependency subset (for example roles together
with dependent members), dependency-safe creation order, exact created subset,
new member manifest/emptiness and interrupted recovery after a partially
confirmed DDL sequence are not exercised. This does not prove the FK-safe
restartable recovery policy.

### 4. No request-level runtime DDL observer or fail-closed characterization

The final check is a static regex over only three source files. It does not run
login, invitation, role attach/detach or block/unblock public request/application
seams; it cannot observe SQL issued indirectly by helpers and it omits known
identity consumers. It also does not test migrated success, missing
status-events or incompatible-schema fail-closed paths. Therefore it is not the
request-level DDL observer required by section 7 item 9 and OpenSpec task 2.3.

### 5. Other explicit Gate 2 rejection cases are missing

The suite does not cover canonical no-seed/no-rebuild versus separately invoked
destructive bootstrap, exact unexpected-failure classification/redaction,
stop-before-later-migration, cross-prefix FK target verification, identifier
lengths at prefix 25, or exact deterministic FK symbols. These are normative
items 6-11 of the approved matrix, not optional broader regression work.

### 6. The executable approval record is internally inconsistent

The specification header says `APPROVED / GATE 1 COMPLETE`, but section 10 still
records owner date/decision as `PENDING` and says Gate 2 is not authorized. The
owner answer and header establish intent, so this does not invalidate the
captured missing-v6 failure; nevertheless an executable artifact cannot be
simultaneously approved and pending. This must be corrected through the Gate 1
recording workflow before the next Gate 3 review, without rewriting historical
reviews.

## Required changes

1. Complete the test-owned literal clean manifest and deterministic symbol
   assertions without importing expected schema from production owners.
2. Add the category-complete zero-mutation conflict matrix, deterministic
   multi-table/missing lists and exact CLI failure/redaction/short-circuit
   assertions.
3. Add dependency-subset and interrupted partial-recovery scenarios with exact
   subset/order, preservation and repeat checks.
4. Replace the static three-file ratchet as the primary evidence with a DB
   observer around the required public runtime/application paths, including
   migrated success and missing/incompatible fail-closed behavior. A static
   architecture ratchet may remain supplementary.
5. Cover the remaining section 7 items: two-prefix FK isolation and symbols,
   prefix-25 identifier lengths, no-seed/separate destructive seam, unexpected
   failure and stop-before-later-migration.
6. Reconcile the contradictory pending fields in specification section 10 with
   the recorded owner approval, then obtain a fresh append-only independent
   Gate 3 review of the completed tests and new qualifying RED evidence.

OpenSpec tasks 2.1-2.4 must remain unchecked. Production implementation is not
authorized by this review.

## Verification

- `php -l tests/InstallationProcess/identity_access_schema_001_test.php` — PASS.
- Focused RED command above — QUALIFYING RED, exit `255`, reproduced against
  healthy isolated MariaDB.
- Repository search found no other test artifact that supplies the omitted
  identity/access matrix or request-level observer.
