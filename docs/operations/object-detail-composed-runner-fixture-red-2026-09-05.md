# Object-detail v12 — composed runner fixture RED amendment

Date: 2026-09-05. Test author: `/root`.
Production candidate: `877f52993d8ddf4570990874800306241581a120`.
Current review base: `294664292632cc4d1c044add5a21b6455ba7b71a`.
Exact pre-implementation RED base: `26bed9af6c70aefc690e89b5965246ea26628e1c`.

## Scoped correction

The composed runner verifier now expects terminal v12 and the exact applied
version suffix 12 in its existing success/recovery cases. Its catalogue uses
explicit `columnsV12()`/`indexesV12()` methods adding only the two normative
PK-only object-detail tables from the owner-approved v0.4 manifest.

The existing default columns/indexes/FKs/CHECKs methods retain their output
byte-for-byte. SHA-256 of their combined JSON before and after:
`380b1de99c8a1b8d3c204126c2b6805bfbaaeab9c9b59a63862b1b8899e87c4f`.
Only this verifier opts into v12. Protected legacy E2E and other caller files
are unchanged; this is not a hidden update of their expected catalogue.

## RED and candidate regression

Only the two-file test/support patch was applied in a detached worktree at
`26bed9af...`; hashes matched the main worktree exactly:

```text
c75ba4b017bae4e6ef2be25dfb1c9f3a859d70d2afdbb4ecf843e836aeb9399e  tests/InstallationProcess/production_migration_runner_001_test.php
dddec91ba654b1503e4051cd732325a9fed7ff166a1d3ff2cb101b9593f6c0b3  tests/Support/ProductionMigrationRunnerCatalogContract.php
```

Command:
`FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php`

RED base returned successful runner JSON for v11, failing the expected v12
assertion (exit 255). This is missing successor behavior, not setup failure.
Current production returned `PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract`
and exit 0, including physical catalogue and preservation assertions.

The temporary worktree had only these two known modified files before removal;
after cleanup only the integration worktree remained. Lint/diff checks passed.
No production/spec/config/importer/protected E2E changed. Fresh independent
Gate 3 is required; candidate GREEN is not test approval or full integration.
