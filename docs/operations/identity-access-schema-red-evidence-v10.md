# IDENTITY-ACCESS-SCHEMA-001 superseding RED evidence v10

- Date: `2026-09-01`
- Specification: `IDENTITY-ACCESS-SCHEMA-001 v0.1`, Gate 1 approved
- Supersedes: `identity-access-schema-red-evidence-v9.md` for the Gate 5 return path
- Production changes in this iteration: none
- Result: `QUALIFYING RED / READY FOR FRESH INDEPENDENT TEST REVIEW`

## Gate 5 findings converted into sensitive tests

The populated-family fixture now asks MariaDB to assign legacy/arbitrary names
by using unnamed unique/secondary keys and unnamed foreign keys. Before invoking
the public runner, the test independently queries `information_schema` and
requires both a non-primary index name and a FK constraint symbol to differ from
the canonical explicit-name catalogue. The complete populated state, including
rows and `SHOW CREATE TABLE`, is snapshotted before the runner.

The database-default preflight fixture creates a real `latin1` database and
independently confirms `latin1_swedish_ci` from `information_schema.SCHEMATA`.
It requires exact public classification `DATABASE_UNAVAILABLE`, exit `69`, empty
stderr and exact zero identity-family mutation. The normal utf8mb4 fixture also
independently proves, before first DDL, the safe collation-name grammar and the
collation's membership in `information_schema.COLLATIONS` for `utf8mb4`.

An invalid or unavailable database-default collation cannot be safely installed
as a MariaDB fixture: MariaDB rejects such `CREATE/ALTER DATABASE` statements,
and `information_schema` is not mutable. The real non-utf8mb4 path nevertheless
exercises the required fail-closed metadata preflight. No mock or production
test hook was introduced merely to synthesize impossible server metadata.

## Reproduced RED

With the disposable MariaDB 11.4 test service healthy:

```text
FMONITOR_IA_RED_CASE=generated-names ... identity_access_schema_001_test.php
Expected: ok=true, schemaVersion=6, appliedVersions=[]
Actual:   SCHEMA_MIGRATION_CONFLICT, schemaVersion=6
```

The fixture's prior generated-name assertions passed, so this is the production
fingerprint defect, not setup failure.

```text
FMONITOR_IA_RED_CASE=collation ... identity_access_schema_001_test.php
Expected: exit 69, DATABASE_UNAVAILABLE, unchanged empty identity state
Actual:   exit 70, MIGRATION_FAILED, unchanged empty identity state
```

The database metadata and zero-mutation observation passed; the RED is the
missing charset/collation preflight and wrong failure classification.

## Gate state

OpenSpec tasks `2.1` and `2.2` are checked because their changed test scope is
complete and demonstrates qualifying RED. Task `2.4` remains unchecked pending
a fresh separately tasked independent test review. Gate 4 production work must
not resume before that review records `APPROVED` in a new append-only record.
