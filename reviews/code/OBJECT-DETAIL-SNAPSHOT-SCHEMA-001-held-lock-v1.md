# Code review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 held-lock tranche

- Reviewer: `/root/object_detail_schema_gate3` (independent Gate 5 reviewer; did not author tests or production)
- Exact reviewed commit: `145770a37019c4436c8cb9755222b1282ebf079f`
- RED commit: `30657cd34b40041e78bcd0ce42622e9aff52a144`
- Independent Gate 3 commit: `b60216bfe4a8ccbc827977c5ea00ac6e6fde630b`
- Initial lock implementation: `5d47fde9112dcde059f592d4ce72c5965843cfa7`
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Migration SHA-256: `a2ccd73ea0215b40c4667ec192f508336d1027ff4b849d2062386e530e97e28f`
- Extracted fingerprint SHA-256: `2483e6458819fcc8b61d9f272730494aa15222a2a64d5732b4ed621a90084317`
- Held-lock test SHA-256: `b0f0bbc6b830bdcf37dddef8c07d72ce1e1016991586f29dbc86415c78b8c1f2`
- Base corpus SHA-256: `b0dc9cf7d87c275c201409f6933924c7d8a275b6d43da137f84c025bac884ed6`
- Runner SHA-256: `6d4e6d6e89bf462524a8793e12d3ab69bd1d15125a5956ac52412fa2ad5f36e6`
- Gate 3 review SHA-256: `7aed42c29d1544bcbafe130932f20b94b7818e9d3aed7db8180452ed500a303c`
- Verdict: **APPROVED_FOR_BOUNDED_TRANCHE**

## Scope and findings

No defect was found within the held-lock rejection, no-mutation, preservation,
release, and ordinary-retry tranche.

Prefix validation occurs before any database operation. `apply()` then reads
the exact current database identity and derives the lowercase SHA-256 lock name
from `object-detail-schema-v1`, NUL, database name, NUL, and prefix. The hash is
safe for the fixed SQL literal. `GET_LOCK` uses the required five-second timeout
and only string result `1` establishes ownership; timeout, NULL, query failure,
or invalid database identity maps to `DatabaseUnavailable` before metadata
inspection or family mutation.

After acquisition, the lock surrounds complete family preflight, all CREATEs,
post-create verification, result formation, and conflict return. The `locked`
flag is set only after successful acquisition, so `finally` never releases an
unowned lock. For an owned lock it makes exactly one RELEASE_LOCK query. A NULL,
non-1 result, or throwable is mapped to `DatabaseUnavailable` and overrides a
pending success/conflict result, so release failure cannot publish success.

The architecture correction did not create another mutation owner. It extracts
only read-only metadata classification into
`MariaDbObjectDetailSnapshotSchemaFingerprint`; CREATE sequencing and public
results remain in the migration. Its queries and comparisons preserve the
previous exact engine/table type/collation, ordered columns, types, unsigned
identity, charset/collation, null/default/extra/generated metadata, primary-key,
and no-FK/CHECK semantics. The passing base corpus independently confirms clean,
repeat, partial, conflict and opaque-row preservation after extraction.

## Independent verification

Commands were run at the exact reviewed commit:

```text
php -l app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
No syntax errors detected in app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php

php -l app/InstallationProcess/MariaDbObjectDetailSnapshotSchemaFingerprint.php
No syntax errors detected in app/InstallationProcess/MariaDbObjectDetailSnapshotSchemaFingerprint.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_lock_001_test.php
PREREQUISITE PASS: real distinct connection holds the exact migration lock
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 real held-lock rejection and retry
exit 0

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract
exit 0

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
PASS: PRODUCTION-MIGRATION-RUNNER-001 CLI contract
exit 0

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
exit 0
```

Both `git diff --check` and a post-run read-only database inventory passed; the
inventory returned `[]` for the owned lock, base, runner, and collation database
prefixes.

## Authority boundary and remaining blockers

This is not full migration Gate 5, integration approval, or Done. It authorizes
only the independently held-lock timeout/no-mutation and ordinary-retry tranche.
Still required are:

- public verification enum, observer interface, and verification composition;
- deterministic observer/connection failure after CREATE and final-verification
  failure behavior, including release-error paths;
- first-CREATE/second-CREATE DDL denial, durable partial state, and retry;
- two-creator same-family serialization and timeout behavior plus
  different-database/prefix independence;
- bounded independently reviewed worker/IPC and composed fixtures for those
  interruption and concurrency cases;
- separately gated importer characterization, runtime no-DDL enforcement, and
  fail-closed absent/incompatible schema behavior;
- remaining regression/composed-consumer evidence, full `make verify`, and
  architecture verification on the eventual integration SHA;
- RED, independent Gate 3, minimal GREEN, and independent Gate 5 for every
  remaining tranche, followed by OpenSpec completion accounting and final
  integration review.

## Required changes

None within this bounded held-lock tranche.
