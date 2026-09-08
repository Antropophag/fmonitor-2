# Code review: ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001 engine v1

- Review date: `2026-09-05`
- Reviewer: separately tasked independent agent `/root/registry_engine_gate1`
- Reviewed implementation commit: `b6f619f41e924c6ae2663d66e22de85cad30d6de`
- Current repository HEAD during review: `0696d88c2d5e85ee24c450ff7f52e6164f3bdae4` (documentation/task updates only; reviewed source bytes unchanged)
- Approved specification SHA-256: `31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f`
- Scope: standalone registry migration engine only; no canonical registration, writer enablement, selection-family integration or parent completion
- Verdict: **APPROVED**

The reviewer authored neither the approved tests nor the implementation. No
production source, test or specification was edited during this review. This
review record is the only authored artifact.

## Findings

No blocking correctness, security, architecture or maintainability finding
remains for the reviewed engine-only diff.

The public facade and verification facade use the same engine. The production
facade always supplies an inert observer, and no HTTP, environment, runtime
runner or canonical migration registration path was added. DDL and backfill DML
remain owned by the InstallationProcess migration area. The 19-file diff is
limited to the approved metadata engine and typed observation surface.

Prefix validation precedes database access, enforces the approved ASCII grammar
and 25-byte bound, and all interpolated identifiers are derived only from that
validated prefix or the validated database-default collation. Ordinary values
use prepared statements. Connection/transaction configuration, named-lock
acquisition and release, SQL failures and observer failures converge on the
fixed redacted `DatabaseUnavailable` without retaining a previous exception.
The caller connection remains open.

Whole-family preflight checks the two predecessor tables, all owned target
columns, defaults, charset/collation, indexes, named CHECKs, FK identity/actions,
triggers, source ranges, orphans, duplicate case/version identities, prepared
instants and current counters before mutation. The corrected FK catalog reader
compares visible referenced-column rows with joined referential-rule rows. When
a restricted principal can see `KEY_COLUMN_USAGE` references but cannot see the
matching `REFERENTIAL_CONSTRAINTS` rules, it reports infrastructure
unavailability rather than treating unknown visibility as an absent FK or a
schema conflict. This preserves fail-closed classification without weakening
the exact shape check.

MariaDB implicit-commit DDL is confined to the approved recoverable empty-family
states. Missing compatible tables are created in registry/receipt order, the
frontier is raised monotonically before backfill, and source is then recaptured
under the owned transaction and compared byte-for-byte with the plan. Historical
identity rows and the singleton receipt are inserted and committed once in the
same transaction. Pre-commit failures roll back staged facts; post-commit
observer/release failures report unavailable while leaving the durable receipt
available for a proving repeat. Cleanup attempts transaction release and exactly
one named-lock release after acquisition even when earlier work fails.

Lossless decimal helpers avoid float conversion for IDs, counters and frontiers.
Initial capacity rejects values above signed PHP range before mutation. Completed
history accepts the explicit `9223372036854775808` exhausted frontier sentinel
only for observation after a valid maximum ID. RFC3339 validation rejects unknown
or excessive offsets, malformed calendar/time values and noncanonical surrounding
bytes, then stores the same instant at UTC microsecond precision. Tuple and
prepared hashes are computed from numeric-ID order and the approved canonical
byte streams.

Completion validation freezes the receipt subset at `legacy_max_id`, requires
exact physical/registry tuple and prepared-instant equality, validates all
current identity types/source discriminators, and requires the live registry
frontier to exceed every current identity and preserve the receipt frontier.
Legitimate later rows above the frozen boundary remain stable on repeat. A
nonempty registry without a receipt, a missing target under an existing receipt,
historical mutation, malformed metadata or lowered frontier remains a conflict.
The snapshot is read-only and the completion facade converts absent,
incompatible, incomplete or unreadable state to `false` as specified.

Observer phases occur only after the corresponding lock, DDL, frontier, staging
or commit operation succeeds. The approved tests exercise every interruption
phase, pre-commit process termination, post-commit acknowledgement loss,
same-prefix timeout, independent-prefix progress, release failure, denied DDL
and DML principals, caller transaction cleanliness, exact owned cleanup and
foreign-decoy preservation. The implementation adds no fault selector outside
the typed verification composition.

The initial predecessor commands recorded in primary evidence failed during
environment setup with old demo credentials and were not counted as regression
results. The corrected canonical test environment ran both predecessor suites
successfully without source or test changes. The evidence archive retains both
the failed setup attempts and corrected outcomes.

## Independent verification

The reviewer independently ran the four approved public-seam scripts against
source bytes identical to the implementation commit:

```text
ASSIGNMENT_ORDER_IDENTITY_REGISTRY_001_OK
ASSIGNMENT_ORDER_IDENTITY_REGISTRY_SCHEMA_001_OK
ASSIGNMENT_ORDER_IDENTITY_REGISTRY_RECOVERY_001_OK
ASSIGNMENT_ORDER_IDENTITY_REGISTRY_CONCURRENCY_001_OK
```

Additional independent checks:

```text
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

php -l <each of 19 new production files>
PASS

git diff --check b6f619f^ b6f619f
PASS (no output)

git diff --exit-code b6f619f HEAD -- <19 registry engine source files>
PASS (no output)
```

The primary archive hashes match the GREEN record:

```text
d99bd027224d5ce3877a93a04ce22052a74cbbcd1d3b162c7aea707d7103d531  evidence.json
feed109bcfecd8ec1dd17f99157998105d904fd6430f81349f309823729de01b  predecessor-canonical-env.json
```

## Exact reviewed production hashes

```text
5396024213285a9afab12ba63d5694d76d8c6495e4e25a4223fe7e75f096834d  app/InstallationProcess/AssignmentOrderIdentityRegistryBackfillSchemaMigration.php
49ba6dcb3ea19cab4f774183135ea1cf40b84ca7c81d365497d7dd3e699d2710  app/InstallationProcess/AssignmentOrderIdentityRegistryDefinitionSchemaMigration.php
1fc818f2daa1f93c724d0e86dbd2a3c1ca7aebb07c586059af13ceaa6b85e017  app/InstallationProcess/AssignmentOrderIdentityRegistryEngineSchemaMigration.php
4c0b90e2a273a408ce637bdc7de8ba8c6d895604fb5258715d09703827363064  app/InstallationProcess/AssignmentOrderIdentityRegistryMigration.php
ef03bbe40d2a68ac4f930f98c2842c5248cdc1d1d8410d1a25f91cc55f64bd33  app/InstallationProcess/AssignmentOrderIdentityRegistryMigrationVerification.php
ce4efc12adac16f875103c852c62d7b3b3e3f3ee19508bdd31d913d598c14325  app/InstallationProcess/AssignmentOrderIdentityRegistryObserver.php
97f2aa2edf0750ba5f5b3a5b4de3a14d637d54f7a74c8546ff14c3918711baac  app/InstallationProcess/AssignmentOrderIdentityRegistryPhase.php
6b9614d1fbdb3330a272e443848d749b853ae49f9b1b2e1ec2bf2358d35b95fe  app/InstallationProcess/AssignmentOrderIdentityRegistryPredicate.php
8b3e982ccf31233b865860dc570da78390e1f36c2dd50c7f92fd012a35c4a34d  app/InstallationProcess/AssignmentOrderIdentityRegistryReceipt.php
a3a9a05555094f31221f8d83f2aff337d415351e920f1f89d9436ee36929a1bf  app/InstallationProcess/AssignmentOrderIdentityRegistryRow.php
523c9ef5333502a1bbe9445677203449e80ba998732a73b3bb755bd78e1a6445  app/InstallationProcess/AssignmentOrderIdentityRegistrySnapshot.php
d891602950b3979aec4fa9b6e829611a9582d63f3da1acc9ea08d637c4cd0927  app/InstallationProcess/AssignmentOrderIdentityRegistryValues.php
55e27c415a39b491e4b4c8f4495f5cfcc9520a144f88cc365aeb3ad9783f3601  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistryCatalog.php
d29868b53e8240ad6296f7bba75a93952e2cb74ebb7ab5bfac515bd93ed43074  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistryHistory.php
e82e21269f3f2e28f96c2e1d5fd3ad27d65a9055140eed4541699fb21670ff7d  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistryPlan.php
a353f512c1bbe1cd5e46ad7b995aa4fb7ae6486751b5a005c7e5e50734a8845c  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistrySource.php
36a77cfc3bd4342c74b5e154e620cc592b16afd0eaed67f42ad212226906586d  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistrySourceShape.php
33fb640e771c1f0cd21ad9737738d26654dc735fe3cfd7e043f2b412caf362de  app/InstallationProcess/MariaDbAssignmentOrderIdentityRegistrySql.php
a3d3118b6f3c55b52d2901f1e9639efecc764296141ddfe49bdf40dc851f77be  app/InstallationProcess/NoOpAssignmentOrderIdentityRegistryObserver.php
```

## Exact approved-test and review inputs

```text
e4841961e89b7c7e39c11449579be71a38766c8b20be0023cb52db69b4c74073  tests/InstallationProcess/assignment_order_identity_registry_001_test.php
57f2f45aec8a364467cfe95bd447c83208c1aadeeb38165281cc887ab667e5ae  tests/InstallationProcess/assignment_order_identity_registry_schema_001_test.php
23e1453486ad26ac124edbdb921ae9a702ae5196ae895732d957cb8fc713392b  tests/InstallationProcess/assignment_order_identity_registry_recovery_001_test.php
42e885421aa0f73bce6080e25e9ae41470f4a9cc8c4b2bf644607c5e9c810ca1  tests/InstallationProcess/assignment_order_identity_registry_concurrency_001_test.php
b409eddac3fa05caf1283c7c2dbf96f36f7611f4bd1a86cb8fd4bbf06b89d02c  tests/Support/IdentityRegistryTestDatabase.php
d3c9a7b9584ade1c1d9a18f26f9f891fb08a85194e25aff5501dabce14aac655  tests/Support/assignment_order_identity_registry_worker.php
```

This approval is limited to the exact standalone engine at implementation SHA
`b6f619f41e924c6ae2663d66e22de85cad30d6de`. It does not establish canonical
registration, writer cutover, selection readiness, full `VERIFY_OK`, deployment
readiness or completion of the parent selection change.
