# OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 — Gate 2 RED evidence

Date: 2026-09-05. Test author: separately tasked agent
`/root/object_detail_schema_red`.

## Authorization and inspected base

- Authorized executable base:
  `e8f17b63a3c93e8f4be5664c309b435fde3318e9`.
- Independent technical Gate 1 commit:
  `eaef9ebbd14cd34e1fe7faf85bde0a6968c0d0db`.
- Owner approval:
  `docs/operations/object-detail-schema-owner-approval-2026-09-05.md`.
- Executable specification SHA-256:
  `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`.
- Test SHA-256 before this evidence record:
  `b6e06737cd7f6d0f41748df0893512e9afbaf6502b2b1730550c0a453cc63da6`.

The repository `HEAD` and authorized base were independently confirmed equal
before test authoring. The test creates randomly named, task-owned MariaDB
databases and drops those exact names in `finally`. It invokes neither the
importer nor any external source and contains fictional opaque preservation
sentinels only.

## Covered acceptance surface

The new public-seam test encodes:

- writable real-MariaDB prerequisite before the intended RED;
- clean, empty, exact two-table creation and read-only compatibility;
- populated exact repeat, including permitted same-object detail/quarantine
  coexistence and byte-preserved opaque rows;
- exact details-only and quarantine-only partial recovery;
- incompatible existing member plus absent sibling, with family-wide
  zero-mutation behavior and exact conflicting table result;
- unprefixed and other-prefix decoy isolation;
- exact ordered columns, NOT NULL/no-default/no-generated metadata, PK-only
  index shape, InnoDB, utf8mb4 and database-default collation;
- direct 25-byte prefix acceptance and 26-byte/invalid/non-ASCII rejection;
- CLI pre-DB rejection for invalid and 26-byte prefixes using an unreachable
  database endpoint;
- production runner v12 clean and repeat JSON outcomes.

Deterministic observer interruption, named-lock timeout, and two-worker creator
serialization remain for a separately reviewed Gate 2 support/IPC harness.
Existing literal v1-v11 runner/catalogue tests and protected
`PILOT-E2E-FLOW-001` were not changed. Importer DML characterization remains a
separate gate.

## Syntax prerequisite

Command:

```text
php -l tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
```

Output and exit:

```text
No syntax errors detected in tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
exit 0
```

## Demonstrated RED

Command:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
```

Relevant output and exit:

```text
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable

Fatal error: Uncaught TestFailure: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 requires the missing public v12 migration seam.
Expected: true
Actual: false in /Users/antropophag/code/fmonitor-2/tests/bootstrap.php:36
Stack trace:
#0 /Users/antropophag/code/fmonitor-2/tests/InstallationProcess/object_detail_snapshot_schema_001_test.php(136): assertSameValue(true, false, 'OBJECT-DETAIL-S...')
#1 {main}
  thrown in /Users/antropophag/code/fmonitor-2/tests/bootstrap.php on line 36
exit 255
```

This is the intended assertion RED: the isolated database fixture is proven
writable and observable first, then the test reports that the approved public
`ObjectDetailSnapshotSchemaMigration` seam does not exist. It is not an
autoload exception, connection failure, missing fixture, feature skip, or
external-source failure.

## Cleanup proof

After the RED, the following query returned no rows:

```text
mysql -h127.0.0.1 -P23306 -uroot -p... -N -e "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME LIKE 'fm2_ods_red_%' OR SCHEMA_NAME LIKE 'fm2_ods_runner_%' ORDER BY SCHEMA_NAME"
```

The password is redacted in this durable record; the executed local command
used only the documented test-root credential.

## Gate disposition

This record demonstrates RED only. It is not an independent Gate 3 review and
does not claim complete Gate 2 coverage, GREEN, integration, or Done.
