# Object-detail schema — owner approval and v12 frontier

Date: 2026-09-05. Recorded by `/root`.
Approval source: owner explicitly answered
«подтверждаю перенос создания таблиц в штатные миграции» to the described
two-table, data-free, preservation/retry/concurrency migration.

## Approved batch and limits

Technical Gate 1 approval:
`eaef9ebbd14cd34e1fe7faf85bde0a6968c0d0db`, reviewed spec at
`65fd86fd00b5b7d4df7f0537bd2699b991fc50e6`.

The owner approves `OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` v0.4's data-free
canonical ownership contract. This dated record supersedes the historical
DRAFT/owner-pending status in the exact reviewed bytes; their behavioral
content is unchanged. It permits Gate 2 for clean/repeat/partial/conflict/
prefix/collation/preservation/interruption/concurrent migration cases.

The imported serial DML behavior remains separately gated. No real source
data, production import, fixture population, quarantine lifecycle redesign,
or unreviewed runtime code is authorized. Gate 3 is still required before
minimal production implementation; Gates 4/5 and full integration are not
claimed by this decision.

## Fresh frontier confirmation

Exact execution base: `ca5f89a4f7042da359889aaa9c0a8c99ed2c236a`.
`bin/fmonitor2-migrate.php` still registers the contiguous 1–11 catalogue;
SHA-256 `e9caa610a952ba9bcbef28dd6e17996e3c83cc5a51c82f527e7b44a99625acf9`.
No v12 migration is present. This record selects literal v12 for the new
data-free object-detail family; revalidate the frontier before registration.

Fresh command on that SHA:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
exit 0
```

This proves the current runner regression baseline, not future v12 GREEN.

## Reviewed behavioral artifact SHA-256

```text
be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40  specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md
765f5c1980d2be241ee1732b93f260196b35f5be24ad6a21543197ed6ad303f3  openspec/changes/canonicalize-object-detail-snapshot-schema/proposal.md
71e4dd6bd50270c71eff2459bb45019e67b9cd206944bc5c4d2648c6f838e799  openspec/changes/canonicalize-object-detail-snapshot-schema/design.md
6790151d50dc9425d12e9d7e7dbf693827d6688c7a2ad052e08d5a93e784665a  openspec/changes/canonicalize-object-detail-snapshot-schema/specs/deployment/canonical-object-detail-snapshot-schema/spec.md
0c9321443e825bc888eb94bf316160e679f6e4f14070b4acd8544c96a526ab5f  openspec/changes/canonicalize-object-detail-snapshot-schema/tasks.md (before completion accounting)
```

Tasks 1.1–1.3 can now record their completed preparation/approval/source-policy
evidence. They do not assert any RED or implementation task is complete.
