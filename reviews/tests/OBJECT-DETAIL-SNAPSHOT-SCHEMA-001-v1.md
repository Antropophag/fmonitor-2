# Test review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4

- Reviewer: `/root/object_detail_schema_gate3` (fresh independent Gate 3 reviewer)
- Test author: `/root/object_detail_schema_red`
- Reviewed commit: `77186fe02b5ac0fc37c1968a96bb17b427ea89c7`
- Authorized base / owner approval commit: `e8f17b63a3c93e8f4be5664c309b435fde3318e9`
- Independent Gate 1 commit: `eaef9ebbd14cd34e1fe7faf85bde0a6968c0d0db`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Test: `tests/InstallationProcess/object_detail_snapshot_schema_001_test.php`, SHA-256 `b6e06737cd7f6d0f41748df0893512e9afbaf6502b2b1730550c0a453cc63da6`
- RED evidence: `docs/operations/object-detail-snapshot-schema-red-evidence-2026-09-05.md`, SHA-256 `70e3a5ac13b153e38b8348fe199914dcf02511f110ce255fc7256c9877bc1a1e`
- Public seam: `ObjectDetailSnapshotSchemaMigration::apply()`, `ObjectDetailSnapshotSchemaMigration::isCompleteCompatible()`, and `bin/fmonitor2-migrate.php`
- Verdict: **CHANGES_REQUESTED**

## Findings

The isolated command was rerun exactly:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
```

It printed the writable/observable MariaDB prerequisite, then failed at the
explicit missing-class assertion with exit 255. A fresh administrative query
afterward found no `fm2_ods_red_%` or `fm2_ods_runner_%` databases. Thus the
demonstrated failure is the intended missing-public-seam RED and cleanup owns
only randomized test databases. `php -l` also passed.

The corpus cannot yet authorize even the bounded clean/repeat/partial/conflict/
prefix/collation/runner implementation slice, because code hidden behind the
early RED guard contains blocking validity and sensitivity gaps:

1. `odsTableState()` unconditionally orders row reads by `object_id`, while both
   decoy tables are deliberately declared with only `id` and `payload`. The
   first pre-apply decoy snapshot will therefore fail with an unknown-column SQL
   error once the public class exists. This is a fixture/harness failure, not a
   product assertion, and prevents a qualifying GREEN.
2. The exact manifest query collects `COLUMN_TYPE`, `CHARACTER_SET_NAME`, and
   `COLLATION_NAME`, but `odsAssertExact()` never asserts any of them. An
   implementation with wrong widths/types (including signed `object_id`) or
   wrong per-column charset/collation can pass the asserted column names,
   nullability, defaults, engine, table collation, and index shape. The central
   exact manifest is therefore not independently sensitive to the approved
   contract.
3. Database-default collation behavior is exercised only with a database whose
   default is hard-coded `utf8mb4_unicode_ci`, and the expected table collation
   is the same hard-coded literal. This does not distinguish deriving/emitting
   the validated database default from always choosing that one collation.
4. The 25-byte prefix case checks only the return value. It does not inspect the
   resulting tables with the exact-manifest oracle, check they are empty, or
   check compatibility. The invalid-prefix checks likewise do not prove absence
   of composed-family mutation after direct calls.
5. The production-runner assertions inspect only exit/stdout/stderr and drop the
   runner database without proving that v12 created the exact empty two-table
   family. A registered no-op/dummy v12 can satisfy those runner expectations.
   The runner path therefore is not sensitive to its required schema effect.
6. `isCompleteCompatible()` is asserted for complete and conflicting families,
   but not for absent or either exact-partial family before repair. The approved
   read-only false outcomes for those states are untested.
7. `odsRunCli()` has no bounded deadline and no `finally` cleanup/termination of
   a child process. A hung or abnormal runner can block the suite and leave a
   process behind, contrary to the repository's deterministic harness standard.

The assertions for exact API result key order, sorted table names, fictional
row preservation, same-object coexistence, both partial recovery directions,
one family-wide conflict direction, invalid prefix diagnostics/pre-DB CLI
mapping, and randomized database cleanup are otherwise independently derived
and useful within this bounded corpus.

Deterministic interruption, connection-loss/final-verification behavior,
table-scoped DDL denial/retry, named-lock timeout, concurrent creator
serialization, and composed concurrency fixtures remain explicitly outside
this review and unapproved. This verdict does not request their addition to the
current bounded corpus and does not authorize importer DML or source fixtures.

## Required changes

- Make decoy snapshots valid for their declared schema while retaining a
  byte-sensitive before/after comparison.
- Assert the complete approved column type, unsigned/width allowance,
  per-column character set/collation, and numeric no-collation manifest.
- Exercise a non-hard-coded supported utf8mb4 database-default collation so the
  test detects a fixed-collation implementation.
- Inspect the 25-byte-prefix family and the runner-created v12 family as exact,
  empty, compatible tables; prove invalid direct prefixes create no family.
- Assert `isCompleteCompatible()` is false for absent and both partial states
  before repair.
- Bound runner subprocess execution and guarantee close/terminate/reap cleanup
  on all paths.

After these corrections receive a fresh RED and independent Gate 3 approval,
that bounded corpus may authorize a minimal implementation slice. It still
cannot claim full v12 completion without the separately approved interruption,
failure-retry, named-lock/concurrency, composed-fixture, regression,
architecture, Gate 5, and integration evidence required by the specification.
