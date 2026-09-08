# Code review: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 bounded first tranche

- Reviewer: `/root/object_detail_schema_gate3` (independent Gate 5 role; did not author production or corrected tests)
- Exact combined reviewed commit: `907e76ee87331105d603852e7b1bcb1de5e1c39d`
- Production commit: `877f52993d8ddf4570990874800306241581a120`
- Approved bounded Gate 3: `26bed9af6c70aefc690e89b5965246ea26628e1c`
- Approved empty-prefix amendment: `907e76ee87331105d603852e7b1bcb1de5e1c39d` (review record added at this combined commit)
- Specification: `specs/OBJECT-DETAIL-SNAPSHOT-SCHEMA-001.md` v0.4, SHA-256 `be41d31fdc7bfa14e3c963e0d1498ea93d74c8c363baedbc7e7705c1f71c5f40`
- Production owner SHA-256: `e7518b3d97ad2c5ccf811625832aac4479a54a90d498cc1f1865030c60ea6fb9`
- Canonical runner SHA-256: `6d4e6d6e89bf462524a8793e12d3ab69bd1d15125a5956ac52412fa2ad5f36e6`
- Corrected bounded test SHA-256: `b0dc9cf7d87c275c201409f6933924c7d8a275b6d43da137f84c025bac884ed6`
- Gate 3 v3 review SHA-256: `c15201fa346f3df9fc2e5e790696617c603d5f7b9bccbe725f7132fd05bbadbb`
- Empty-prefix Gate 3 v4 review SHA-256: `d9700e9d1715854acc308daebbb2e03aba6f2877489606ed537ecad9a1a22b46`
- Verdict: **APPROVED_FOR_BOUNDED_TRANCHE**

## Reviewed scope

This Gate 5 disposition covers only the admitted first implementation tranche:
clean and repeat application, both exact partial recoveries, exact metadata and
database-default utf8mb4 collation, family-wide conflicts, prefix validation
and namespace/decoy isolation, data-free creation and preservation, read-only
compatibility, and canonical runner v12 registration/table creation.

## Verification evidence

Commands were rerun at the exact combined commit:

```text
php -l app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php
No syntax errors detected in app/InstallationProcess/ObjectDetailSnapshotSchemaMigration.php

php -l bin/fmonitor2-migrate.php
No syntax errors detected in bin/fmonitor2-migrate.php

FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/object_detail_snapshot_schema_001_test.php
PREREQUISITE PASS: isolated MariaDB fixture is writable and observable
PASS: OBJECT-DETAIL-SNAPSHOT-SCHEMA-001 v0.4 canonical migration contract
exit 0

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
```

`git diff --check` exited 0. A fresh administrative inventory after the test
returned `[]` for all `fm2_ods_red_%`, `fm2_ods_runner_%`, and
`fm2_ods_collation_%` databases.

The main reviewer also ran the existing literal catalogue fixture at this SHA:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local php tests/InstallationProcess/production_migration_runner_001_test.php
Expected terminal version 11 / applied versions 1..11;
actual terminal version 12 / applied versions 1..12
exit 255
```

That expected registry-frontier mismatch is an acknowledged, still-required
composed-fixture update, not an accepted regression or evidence of integration.
The existing test was not changed in this tranche. Consequently this review
cannot be represented as full Gate 5 or integration approval.

## Findings

No defect was found within the bounded tranche.

The migration validates the exact 0–25-byte ASCII prefix before database use,
derives the validated database collation through the existing canonical helper,
and uses only fixed basenames plus the validated prefix for identifiers. It
performs a read-only inspection of both members before its first CREATE, returns
sorted exact conflicts without mutation, creates only absent members in the
specified details-then-quarantine order, and re-inspects the complete family
before publishing success. Exact and partial existing members are not altered;
the implementation contains no INSERT, UPDATE, DELETE, REPLACE, import, source
read, or domain-event behavior.

The metadata fingerprint checks base-table identity, InnoDB, database-default
collation, ordered exact types including unsigned object identity, per-column
charset/collation, nullability/default/extra/generated state, the sole exact
primary index, and absence of FK/CHECK constraints. Inspection and DDL failures
are mapped to `DatabaseUnavailable`; invalid compatibility requests fail closed.
The runner registers the owner as contiguous version 12 through the established
canonical composition. The approved test independently demonstrates clean,
repeat, preservation, partial, conflict-order/drift, alternate-collation,
25/26-byte and invalid-prefix, decoy, empty-table, compatibility, and actual CLI
table effects.

## Full-completion blockers and authority boundary

This is deliberately not full Gate 5, integration approval, or completion of
canonical v12. The following approved-spec work remains required:

- public verification enum, observer interface, and verification composition;
- connection-scoped named-lock derivation, five-second acquisition contract,
  lock lifetime through final result, and exactly-once release/error handling;
- deterministic first-CREATE/second-CREATE denial with durable partial state and
  privileged retry;
- deterministic connection loss or observer failure after both CREATEs, failed
  final verification, fresh inspection, and exact-repeat recovery;
- same-database/prefix concurrent creator serialization, timeout/no-mutation,
  retry, and different-namespace independence;
- independently reviewed bounded IPC/support and composed fixtures for those
  failure and concurrency cases;
- the separately gated importer characterization and proof that runtime/importer
  paths perform no family DDL and fail closed on absent/incompatible schema;
- remaining regression and canonical-runner catalogue coverage, full
  `make verify`, and architecture verification on the eventual integration SHA;
- demonstrated RED, independent Gate 3, minimal GREEN, and independent Gate 5
  for each remaining tranche, followed by coherent OpenSpec completion
  accounting and final integration review.

No source import, fixture population, quarantine redesign, importer DML, full
v12 completion, OpenSpec archive, integration, or Done is authorized by this
review.

## Required changes

None within the bounded first tranche.
