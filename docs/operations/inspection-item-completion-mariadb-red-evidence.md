# INSPECTION-ITEM-COMPLETE-001 — MariaDB Gate 2 RED evidence

Date: 2026-09-01  
Mission: `TEST-USER-READY`  
Test author: `/root/item_red_author`

## Approved basis

- Executable specification v0.2:
  `specs/INSPECTION-ITEM-COMPLETE-001.md`, SHA-256
  `cdd85ba009e3bbb6993fd50b26ab199caf5017086d43d43bc474586ff0982e7b`.
- Explicit composition amendment approval:
  `docs/operations/inspection-item-completion-gate1-composition-amendment-approval.md`,
  SHA-256 `638658b9ed5300d0ae3f6dfb32d10cf4600ea1587415c3d50228ed0185de67c7`.
- RED runner SHA-256:
  `edf21e6b4aa282d85f7bc25d8a4db209512b6da5b8c7fb0ec29f54da4c4cb2dd`.

Focused test:
`tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php`,
SHA-256 `8d36dafb9ac35940b00178a716bd6b0b8ae865132c942fb3143d7beded913b01`.

## Scope and sensitivity

Before touching production composition, the test:

1. creates one bounded uniquely named test database and a valid private process
   prefix;
2. runs the canonical production migration runner and requires the exact v1–v8
   result with `appliedVersions=[1,2,3,4,5,6,7,8]`;
3. independently requires all 26 canonical tables;
4. seeds active-user/role/exact-permission, working case, registered order with
   two personnel snapshots, immutable template association/snapshot, revision
   zero and a private-prefix decoy;
5. only then resolves the approved
   `ProductionInspectionEvidenceFactory::create(mysqli,
   ProductionInspectionEvidenceConfig, ?InspectionEvidenceClock)` seam.

After that seam exists, the same test starts two child PHP processes. Each owns
an independent caller-created `mysqli` connection, application instance and
fixed `DateTimeImmutable('2026-09-01T09:05:00+03:00')` clock. A coordinator holds
the case revision row `FOR UPDATE` until both workers report ready, receive a
shared start signal and appear simultaneously in MariaDB's
`INNODB_LOCK_WAITS`. Only after two blocked worker transactions are observable
does the coordinator release the row. Both distinct item commands present
revision zero; this is a measured overlap, not a timing-only approximation.

The public command/query assertions require:

- unordered results exactly `ACCEPTED(1)` and `STALE_REVISION(1)`;
- final public-query checklist revision exactly one;
- complete public evidence only for the winner and `null` for the loser;
- durable exact replay as `DUPLICATE` across application instances;
- changed installer payload under the winner id as
  `OPERATION_PAYLOAD_CONFLICT`;
- the prefix decoy and complete table catalogue unchanged, proving no runtime
  DDL/schema repair;
- loser/replay/conflict paths create no partial evidence.

All behavioral observations use only `completeItem` and `getItemCompletion`.
Direct SQL is restricted to deployment setup, deterministic overlap
coordination and schema/decoy preservation—not business-result observation.
The database and bounded `.test-artifacts` worker directory are removed in a
`finally` block.

## Exact RED run

```sh
php -l tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-up
tools/verification/run.sh red tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
make test-env-down
```

Relevant output:

```text
No syntax errors detected in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
PHP Fatal error: Uncaught TestFailure: Approved production MariaDB composition seam is missing after healthy canonical v1-v8 setup: FMonitor2\InspectionEvidence\ProductionInspectionEvidenceFactory
RED_ASSERTION: expected failing behavior observed in tests/InstallationProcess/inspection_item_complete_001_mariadb_test.php
```

The failure is a qualifying behavior/composition RED, not setup failure: the
MariaDB health check, exact canonical runner result, 26-table assertion and all
fixture inserts completed first. The test-owned database was dropped by
`finally`; Compose was stopped with volumes/orphans removed, and
`docker compose -f compose.test.yaml ps --all` returned no services.

## Gate state

This evidence demonstrates MariaDB Gate 2 RED only. It does not approve the test
or authorize implementation before fresh independent Gate 3 review. Production,
approved specification and OpenSpec task state were not edited.
