# Test review: BITRIX-WORKFORCE-SCHEMA-001 v0.3

- Gate: 3 — fresh independent review after metadata CHECK fixture correction
- Reviewed at: `2026-08-28T11:34:09Z` (UTC)
- Reviewer: separately tasked Codex agent `/root/bitrix_schema_check_fixture_gate3_fresh`
- Specification: `specs/BITRIX-WORKFORCE-SCHEMA-001.md`, version `0.3`, status `APPROVED`
- Public seam: `BitrixWorkforceHistorySchemaMigration.apply(connection, tablePrefix = '')`
- Verdict: `APPROVED`

This record supersedes the immediately preceding `CHANGES_REQUESTED` Gate 3 review and every earlier Gate 3 record at this path. The verdict applies only to the SHA-256 manifest below.

## Findings

The sole requested Gate 2 correction is present at `tests/InstallationProcess/bitrix_workforce_schema_001_test.php:405`: the invalid metadata adversary `CHECK (singleton_id <> 1)` is now `CHECK (singleton_id >= 1)`. Replacing that one corrected expression back to `<> 1` in a read-only stream reproduces the prior reviewed test SHA-256 exactly (`100ae8cf630109c9741f0c1daced5eeec0a9a32d76a0edf69a34d055ba57b632`); the corrected file contains one `>= 1` occurrence and no `<> 1` occurrence. Therefore no other byte of the reviewed test changed.

The corrected metadata fixture remains valid with the already-seeded `singleton_id = 1`, while its operator and accepted set remain observably different from the normative `singleton_id = 1`. The public migration seam now receives the fixture. Together with the catalog grouping adversary and sync-run quoted-literal adversary, the exact assertion requires all three full target names in binary-sorted order. Omitting the metadata conflict, accepting `>=` as `=`, changing the literal/grouping semantics, or returning a partial/unsorted conflict list fails.

`$checkBefore` is captured only after all three adversarial ALTERs. Exact equality with `bwSchemaState(...)` after `apply()` covers complete `SHOW CREATE TABLE`, rows, and `AUTO_INCREMENT` for every table in the prefix. Thus the three-conflict preflight must perform no DDL or DML; repairing, normalizing, or partly changing any adversarial table cannot pass.

All earlier v0.3 cases remain byte-for-byte intact and execute before or after this fixture in the focused GREEN run: 37-byte prefix acceptance and invalid/38-byte zero-DB rejection; exact prefix-derived symbols; two coexisting populated prefixes; same-named CHECK table qualification; populated repeat preservation; compatible partial recovery; legacy prefix-independent-name conflict; exact database-default collation enforcement; complete four-table conflict; and missing-catalog conflict. Expected schemas and results remain independent literals from the approved specification/support contract.

The test remains deterministic and isolated. It uses one randomized database only as a namespace, fixed schema/data expectations, and an unconditional `finally` drop of that exact database. A post-run independent catalog query found zero databases beginning `t_bw_schema_001_`.

No blocking findings remain. Gate 3 is approved for the manifest below; Gate 5 still requires an independent code review.

## Verification evidence

```text
php -l tests/InstallationProcess/bitrix_workforce_schema_001_test.php
No syntax errors detected in tests/InstallationProcess/bitrix_workforce_schema_001_test.php

php tests/InstallationProcess/bitrix_workforce_schema_001_test.php
BITRIX-WORKFORCE-SCHEMA-001 v0.3 tests passed.
```

Both commands exited `0`. Cleanup observation after the run: `leftover databases: 0`.

## SHA-256 manifest

```text
79fcc5f44e8ad4877a55d26db01f17c521c10d03f5c88596e9ccf1ed8a6513a5  specs/BITRIX-WORKFORCE-SCHEMA-001.md
b4887bbe1defd8ecc9ac9eb8463be06a0e1a27051e1cad49d9d5f0127b8356d2  specs/MIGRATION-PROCESS-001.md
e434c0d18564a08c2ec4238bb645d5cd7458c9a617ad95fb9f6eccdae9440034  specs/WORKFORCE-CATALOG-001.md
40629b6f083dfad29cb414a935eab7128eee10627dfcc3da2f3baad27b139cc0  specs/PROCESS-USER-DIRECTORY-001.md
496bef81d706fc49c10012fe6092b34418048e225c96c1ecb05387e9e0d30a48  specs/PROCESS-COMMAND-AUTHORIZATION-001.md
8313a1705ad3aede69adffc0bde6ca2158305a8ee088c234d41fad9826da985c  tests/InstallationProcess/bitrix_workforce_schema_001_test.php
e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
f903a36e93028e703558b49467034ff3354e8dbc4b7a5da17d8b9b354e9b2b62  app/InstallationProcess/BitrixWorkforceHistorySchemaMigration.php
923ee3d72fbaba6bb717bf236ce62e00d958908ffc422b15feef82e029481a03  app/InstallationProcess/MariaDbSchemaInspector.php
5a91a1ce9f4facac9732dd085e8238f419d019056c5ac1dd170c06f36d031660  app/InstallationProcess/ProductionProcessSchemaMigration.php
d6d837e359105a2e8cd632148e47a1655d7960f1bf49ae5c679adaf6688203a6  app/InstallationProcess/WorkforceCatalogSchemaMigration.php
ce48157d0711466f66579687f8bf659c15675cd3dbcb6480854a9236f9431aba  app/InstallationProcess/ProcessUserCapabilitiesSchemaMigration.php
b8745e170b235e406c29f2118cfe7a38beccceed7a6345fe5b825dd90a531ce4  app/InstallationProcess/ProcessCommandCapabilitiesSchemaMigration.php
15cc2badf6d937265bab19ffd5d63551c9137c3fce05dd7a59ad8dbfb0a2a207  reviews/code/BITRIX-WORKFORCE-SCHEMA-001.md
```

Repository HEAD observed during review: `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd` (working-tree inputs are identified by this manifest, not by HEAD alone).
