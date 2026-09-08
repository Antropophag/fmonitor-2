# BATCHED-SCHEMA-SNAPSHOT-001 — independent Gate 3 test review

- Date: `2026-09-08`
- Reviewer: Codex agent `/root/issue55_fixture_review`
- Test author: separate agent `/root/issue55_snapshot_analysis`
- Reviewed worktree HEAD: `102e6056d7e59f9ff32506cb89d9647234d4c588`
- Change: `optimize-integration-fixture-prerequisites`
- Proposed seam: `BatchedSchemaSnapshot::read(mysqli, callable): array`
- Verdict: `CHANGES_REQUESTED`

This review is limited to the database-wide schema snapshot test and its equivalence
to the existing observer in
`tests/InstallationProcess/assignment_order_original_database_setup_001_test.php:33-101`.
It does not reopen PDF caching, production behavior, CI sharding, or unrelated
schema matrices. The reviewer did not author the test or implementation.

## Blocking finding

### MEDIUM — freshness does not cover the `TABLES` property values

The literal first snapshot correctly fixes both values returned by the old
`$tableProperties` observer: `ENGINE` and `TABLE_COLLATION`. Later reads prove that
the table-name inventory itself is fresh by adding and dropping a table. They do
not mutate either property of an already observed table.

Consequently, an implementation may issue a fresh `TABLES` query only for names
while retaining the first-read `ENGINE`/`TABLE_COLLATION` values, and still pass the
test. That violates the design decision that each call freshly reads all five
metadata families and leaves one exact dimension of the old snapshot unprotected.

Required correction: after the first snapshot, alter a retained table's collation
to a distinct literal value and assert the later snapshot's `table` tuple changes.
Also assert the corresponding character column collation in `columns`, since the
same deterministic ALTER provides sensitivity for both related metadata values.
Restore is unnecessary because the database is disposable. No additional engine,
charset, table-kind, or platform matrix is requested.

After this correction, obtain a fresh independent Gate 3 rereview before adding the
helper.

## Confirmed coverage

- The expected dictionary preserves binary table-name ordering and the exact keys
  `table`, `columns`, `keys`, `foreignKeys`, and `checks`.
- `columns` fixes ordinal order, lower-case MariaDB `COLUMN_TYPE`, nullability,
  character set/collation, `EXTRA`, PHP `null` normalization for nullable SQL NULL,
  and a distinct quoted literal `'NULL'` default.
- `keys` distinguishes primary, unique, and ordinary indexes, covers composite
  column aggregation, and fixes the old PHP-sorted output.
- `foreignKeys` covers a composite relationship, referenced table/column names,
  update/delete rules, and the old sorted row shape.
- Two CHECK constraints prove that every clause passes through the caller-owned
  normalizer and that normalized results are sorted. The later CHECK removal proves
  a new CHECK metadata read.
- Later mutations independently expose fresh table inventory, columns, indexes,
  foreign keys, and CHECKs. There is no snapshot cache in the proposed contract.
- The expected values are literal and do not derive from the future helper. The
  custom normalizer is independent of the database-setup boolean normalizer while
  still proving callable ownership and invocation.
- The test uses a collision-resistant database name, quotes it, validates the exact
  cleanup namespace before creation, and drops only that database in `finally`.
  An open `$db` handle on an assertion failure is released at PHP shutdown; closing
  it in `finally` would improve hygiene but is not a Gate 3 blocker because MariaDB
  permits the separately connected admin to drop the isolated database.
- Registration is coherent: the verifier is in the `db` suite and the `integration`
  category.

## Independent no-database verification

```text
$ php -l tests/Verification/batched_schema_snapshot_001_test.php
No syntax errors detected in tests/Verification/batched_schema_snapshot_001_test.php

$ php tests/Verification/batched_schema_snapshot_001_test.php
exit 255
TestFailure: INTENDED_RED: batched schema snapshot test-support helper is absent.
```

The failure occurs at the absent helper guard before opening MariaDB, so it
reproduces the parent's qualifying RED without using the active baseline database.

## Reviewed identities

```text
0694f37d7063d706914c9cd918a6fe6b94f463c4297bc6d0aec3a1599df2a98a  tests/Verification/batched_schema_snapshot_001_test.php
28540384cb939484c176c2dabc2b1a256e9188450e2b36a93cbd07d9c92d0940  openspec/changes/optimize-integration-fixture-prerequisites/proposal.md
3f625f729f14f29cdef6921e7f0ad4ac5673276b807716fd24cfc23da17e72ec  openspec/changes/optimize-integration-fixture-prerequisites/design.md
fcf261aaca6b907b50fdece358e7dbfbac3d9da545eeb57b285ed3181ff9423d  openspec/changes/optimize-integration-fixture-prerequisites/tasks.md
```
