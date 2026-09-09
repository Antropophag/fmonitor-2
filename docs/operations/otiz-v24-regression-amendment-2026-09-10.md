# Canonical v24 regression amendment — root-authored

OTIZ-SETTLEMENT-001 adds the independently reviewed canonical migration24 after
existing migration23. Existing tests that invoke the full current catalogue/CLI
must therefore expect literal schemaVersion24 and the ordered migration list
with24 appended. Tests continue using independent literal expectations, never
`max(catalogue)` as their own oracle.

This amendment changes current-frontier setup/result expectations in26 existing
tests. The two complete table inventories in production-runner/demo tests add only
`fm2_otiz_settlement_locks` and `fm2_otiz_settlement_operations`. Existing row,
column, ordering, replay, error, partial-upgrade, permissions and preservation
assertions remain intact. Historical direct migration23 (Jobs) and the explicit
settlement predecessor-v23 fixture remain unchanged. Recovery v22/v23 contracts
are excluded and require a separate current-v24 integration increment.

Focused checks already run against the approved core migration:

- `php tests/Runtime/production_schema_frontier_001_test.php`: PASS.
- `php tests/Otiz/runtime_schema_001_test.php`: PASS.
- `php tests/Jobs/jobs_schema_001_test.php`: PASS.
- `git diff --check`: PASS.

These are compatibility amendments for an approved migration, not a claim of new
behavioral RED. Independent review must verify they preserve all old guarantees.
All changed registered tests remain required by the regenerated plan and the full
exact-source CI. Remaining focused runs will be recorded before delivery.

Review a38716ff identified one additional complete inventory in the inspection
MariaDB test. Root added the same two settlement tables and changed its diagnostic
to71. The reviewed current-catalogue v23 labels are corrected to24; historical
migration versions remain untouched. Independent review failure was reproduced
by reviewer; corrected focused command
`php tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`
now passes both existing- and missing-revision concurrency cases (exit0).
