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

Full focused execution found the workforce runner's exact25-byte-prefix inventory
also needed the same two additions. Root updated only those table literals;
`php tests/InstallationProcess/workforce_canonical_runner_001_test.php` now passes
its complete public-runner matrix. Historical data/DDL/prefix rejection assertions
remain intact. Output retained at `/tmp/fm2-root-workforce-frontier.log`.

The isolated legacy OTIZ verifier provisions a private partial schema rather than
the complete catalogue. Its setup now explicitly invokes the approved settlement
migration before requests and cleans both new tables afterward. No runtime DDL or
business assertion changes were made. Faithful full-privilege disposable harness
rerun passed HARNESS-OTIZ-CANONICAL-COMPAT-001 (exit0); databases/users removed.
DML-only runtime evidence remains in the separate owner/browser/image tests.

После удаления server-side UUID fallback синтетический helper валидных financial
POST в verifier передаёт собственные детерминированные v4 IDs, как реальные формы.
Явно переданные невалидные IDs не исправляются. Проверки malformed money, over-close,
истории и экспорта не изменены; missing/invalid HTTP IDs отдельно проверяются через
реальный runtime test. Повторный изолированный harness после финального кода GREEN.
