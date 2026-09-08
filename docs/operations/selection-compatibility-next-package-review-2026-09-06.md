# Selection compatibility — next coherent contract package review

Date: 2026-09-06. Reviewer: separately tasked agent `/root/admission_oracle_gate3`.
Repository HEAD: `3583ef866be64765017b995f80d4d1c64b8db695`.
Scope: bounded read-only compatibility/design audit. No specification, test,
production code, database, protected E2E or remote system was changed.

## Determination

The registry engine at commit `b6f619f` removes the migration/backfill design
ambiguity, but it remains intentionally absent from the canonical runner and
does not by itself make selection Gate 1 ready. The smallest independently
deliverable next slice is a **disabled selection-family schema migration** over
an already compatible and completed registry. It can complete Gates 1–5 without
enabling either migration or any writer.

That slice is useful but is not the whole release-compatibility contract required
by selection v0.8 section 14. Full selection Gate 1 still needs exact approved
contracts for all-writer cutover, original-reader dispatch and same-identity
optional rendering. These contracts should be prepared in the dependency order
below. Canonical registration must remain the last integration action.

## Package 1 — disabled selection-family schema

Create one executable migration contract for exactly these five v0.8 tables:

1. `fm2_assignment_order_selections`;
2. `fm2_assignment_order_selection_members`;
3. `fm2_assignment_order_selection_requests`;
4. `fm2_assignment_order_selection_events`;
5. `fm2_assignment_order_selection_audits`.

The contract must pin every ordinal column/type/nullability/default/collation,
all key/FK/CHECK definitions and their full prefixed names, table creation order,
exact shape fingerprints, prefix `0..25`, interruption states, denied DDL/DML,
attempt-all cleanup, and event/audit AUTO_INCREMENT capacity behavior. It must
use the current normative basename `fm2_assignment_order_selection_members`;
the older `...selection_installers` candidate name is stale.

Recommended public seams:

```php
final class AssignmentOrderSelectionSchemaMigration
{
    /** @return array{applied:bool,reason?:string} */
    public static function apply(mysqli $connection, string $tablePrefix = ''): array;
    public static function isReady(mysqli $connection, string $tablePrefix = ''): bool;
}

final class AssignmentOrderSelectionSchemaMigrationVerification
{
    public static function apply(mysqli $connection, string $tablePrefix,
        AssignmentOrderSelectionSchemaObserver $observer): array;
    public static function snapshot(mysqli $connection, string $tablePrefix):
        AssignmentOrderSelectionSchemaSnapshot;
}
```

This migration owns only the five empty selection tables. Before its first DDL,
it must require the registry tables to have the exact approved fingerprint and
`AssignmentOrderIdentityRegistryMigration::isBackfillComplete(...) === true`.
Absent, incomplete or incompatible registry is a deterministic conflict; the
selection migration must not create, backfill, alter, repair or advance the
registry. An existing nonempty selection family cannot be adopted without its
own exact compatible shape/data proof.

Implementation is safe while disabled if all of these remain true:

- neither registry nor selection migration is added to the canonical runner;
- no runtime/bootstrap/factory calls the new migration or `isReady` lazily;
- no selection command, legacy writer, reader or route is wired;
- no ready manifest or schema version advertises selection capability;
- tests invoke the public standalone migration only in task-owned databases.

This package closes physical constructibility and gives later adapters stable
tables. It does not prove runtime readiness, exclude an old writer, or authorize
selection RED. Its literal migration number must be selected only at its later
canonical-registration gate against the then-current frontier.

## Package 2 — registered-source reader and optional-render storage contracts

These two contracts should be finalized immediately after the schema contract,
before selection v0.8 receives full Gate 1, because optional rendering currently
has no valid persistence target for a selection-only identity.

### 2A. Original composition reader dispatch

Keep the existing public seam:

```php
AssignmentOrderCompositionReader::find(int $caseId, int $assignmentOrderId)
```

Specify one consistent read snapshot that resolves the registry first, preserves
other-case nondisclosure, and dispatches exactly once to `legacy_order` or
`selection`. The legacy branch retains its current temporal/member semantics.
The selection branch reads the dateless selection header and numerically ordered
members, recomputes the canonical composition and hash, and never falls back to
the physical table. Missing registry, orphan, dual source, discriminator/header/
case/version/hash mismatch and query failure need the exact v0.8 NOT_FOUND versus
UNAVAILABLE outcomes.

The adapter can be implemented and reviewed while unwired. Production wiring
must wait for the selection schema fingerprint/readiness contract; otherwise a
legacy-only database would turn an optional additive branch into a query failure.

### 2B. Same-identity optional render

Define a separate public operation over an existing selected registry identity,
for example:

```php
interface AssignmentOrderSelectionTemplateApplication
{
    public function renderSelectionTemplate(
        RenderAssignmentOrderSelectionTemplateCommand $command,
    ): RenderAssignmentOrderSelectionTemplateResult;
}
```

It must read the immutable selection composition through the registered-source
reader, allocate no order ID/version/revision, and never call legacy
`prepareAssignmentOrder`. Rendering may add only template/date/artifact facts
for that same identity. Direct original upload remains valid without calling it.

The current `fm2_order_artifacts` table cannot store this artifact: its FK points
to `fm2_assignment_orders(id)`, while selection deliberately creates no physical
order row. Therefore the exact compatibility package must choose and specify an
additive artifact owner keyed to the registry/selection identity, including
template revision/idempotency, immutable bytes/hash/date, authorization, replay,
failure cleanup and reader projection. Use a separately ordered disabled migration
for this artifact owner so Package 1's five-table command ledger can proceed and
remain exact. This is a technical storage contract, not a reason to synthesize a
physical order or reuse legacy prepare.

## Package 3 — registry-aware legacy preparation writer

Specify and test the current public `InstallationProcess::prepareAssignmentOrder`
path with an internal registry-aware repository/UoW. It must lock the exact case,
derive the next version only from registry history, allocate the global ID only
from the registry, and insert the physical order with that explicit ID in the
same transaction. `fm2_assignment_orders` may retain AUTO_INCREMENT physically,
but ready code must never use it implicitly.

The exact compatibility outcomes remain internal to this existing seam:

- absent registry history or a matching registered physical predecessor permits
  legacy preparation;
- a latest `selection` identity fails `SELECTION_OWNED_CASE` before allocation;
- missing, dual, orphaned or mismatched registry/physical ownership fails
  `IDENTITY_HISTORY_UNAVAILABLE` before allocation;
- version `N > 1` uses only matching registered physical `N-1` as
  `previous_assignment_order_id`;
- it never skips a selection version, points a physical predecessor at a
  selection, or treats accepted original as legacy registration.

Both coordinator entry paths identified by the writer inventory must reach this
same owner. The two direct signed-original coordinator writers also require an
explicit disposition in the compatibility manifest: they mutate physical status
and artifacts outside `AssignmentOrderOriginalApplication`, so readiness cannot
claim all-writer ownership while leaving them unclassified.

This writer may be implemented behind unwired construction, but it cannot be
enabled independently. Once the registry exists, an implicit-ID legacy writer
and the new allocator can diverge even before the first selection row.

## Package 4 — readiness and deployment cutover

One final executable compatibility contract must compose the preceding approved
hashes. Its public read-only readiness seam should return only ready/not-ready
with a fixed redacted reason surface and no repair, for example:

```php
final class AssignmentOrderCompatibilityReadiness
{
    public static function inspect(mysqli $connection, string $tablePrefix,
        AssignmentOrderCompatibilityBuildManifest $build):
        AssignmentOrderCompatibilityReadinessResult;
}
```

The database portion must verify exact registry and enabled-family fingerprints,
immutable receipt subset/frontier, dynamic bidirectional ownership, absence of
dual/orphan/mismatched identities, and no implicit physical allocator owner. The
build manifest must pin the compatible legacy writer, selection writer, original
reader, optional renderer and every direct physical-status/artifact writer.
Factories and bootstrap may consume this seam only after its own gates.

### N−1 exclusion is an operational prerequisite

A new database marker or readiness row cannot stop an already running N−1
process because that process never reads it. Repository code also cannot prove
the complete external process-manager inventory. The cutover contract therefore
needs evidence from the actual deployment owner that:

1. every assignment-order writer instance is stopped before registry apply;
2. no old process retains a live connection capable of physical implicit insert;
3. registry backfill and selection-family migration run while writers remain
   stopped;
4. only the exact compatible build is installed and admitted;
5. readiness passes before the ready manifest is published and writers start;
6. deployment rollback policy refuses every build lacking registry-aware explicit
   allocation.

Startup admission can prevent a newly launched incompatible-aware build and can
fail the current build closed. It is not evidence that an already running or
externally launched old binary was excluded. If the deployment environment
cannot supply authoritative stop/start and artifact-version evidence, full
selection Gate 1 remains blocked; do not replace that evidence with a schema
marker assumption.

## Package 5 — effective-reader preservation regression

Before enabling selection, pin the current effective-reader manifest from the
inventory and run a single cross-consumer preservation fixture: applicable
physical A followed by pending selection B must leave installer directory,
availability, inspection attribution, schedule, checklist, premium inputs,
object details and queue applicability on A exactly as their approved contracts
require. Selection history/card and original composition lookup may expose B;
effective projections may not.

No reader should be mechanically changed from physical `MAX(version)` to registry
MAX. Existing disagreement among physical readers when a later physical prepared
row exists is a separate lifecycle issue and must not be silently solved inside
selection. The preservation proof works because B has no physical order row.

## Dependency order and cycle resolution

```text
approved disabled registry engine
    -> exact disabled selection-family schema contract/Gates 1–5
    -> registered-source original reader contract/Gates 1–5 (unwired)
    -> optional-render artifact ownership + operation contract/Gates 1–5 (unwired)
    -> registry-aware legacy writer/direct-writer disposition contract/Gates 1–5 (unwired)
    -> exact readiness + deployment/N−1 exclusion contract/Gates 1–5
    -> selection v0.8 consolidated Gate 1
    -> selection RED/Gate 3/GREEN/Gate 5
    -> effective-reader preservation and combined compatibility review
    -> canonical migration/wiring registration and controlled cutover
```

The reader and optional renderer may be developed in parallel after their schema
dependencies are fixed. The legacy writer can also be developed while disabled.
They converge only in readiness and canonical wiring. This breaks the planning
cycle without pretending that independently green components form a safe release.

Selection v0.8 full Gate 1 needs approved exact contract hashes, not necessarily
enabled production code, for Packages 1–4. Enabling remains later and requires
their GREEN/Gate 5 evidence plus deployment exclusion. If the repository process
continues to interpret section 14 as requiring implemented compatibility before
Gate 1, record that explicitly and defer selection RED; do not infer readiness
from the disabled implementations.

## Remaining blockers after the next schema package

1. Exact optional-render artifact storage and lifecycle are still unspecified.
2. Both legacy prepare entrances and both direct signed-original physical writers
   lack one approved registry-aware ownership/disposition contract.
3. The production original reader remains physical-only and is not registry
   dispatched.
4. There is no approved build manifest/readiness seam covering all writer and
   reader generations.
5. There is no repository evidence that the deployment owner can exclude an
   already running N−1 writer; a marker alone is insufficient.
6. Registry and future selection migrations remain intentionally unregistered.
7. Cross-consumer effective-projection preservation has diagnostic evidence but
   not the complete executable regression required by v0.8 section 13.
8. Seamless legacy preparation after selection remains outside scope and must
   stay fail-closed unless separately required and designed.

No new product decision is needed to proceed with the disabled schema contract,
registered-source reader contract, optional-render contract already required by
the approved product direction, or fail-closed legacy writer contract. Any choice
to allow mixed writers, synthesize physical rows for selection, make selection
effective before original application, or support seamless legacy prepare after
selection would be a new behavior decision and remains deferred.

## Reviewed evidence

```text
5cb8a371a43356849b374356212689562a8ccba7db402bb05c46715b1df704b2  specs/ASSIGNMENT-ORDER-COMPOSITION-SELECT-001.md
31ffe9a297af927f030947e00cbbf9629fe2ebf98a312d222cdbf7bff3f1896f  specs/ASSIGNMENT-ORDER-IDENTITY-REGISTRY-001.md
86896f3f16faf5a9f33276b8dc975cbb0d04c013044c80d7b444262927d1f299  docs/operations/selection-writer-reader-cutover-inventory-2026-09-05.md
1946dceb03ce4a231504670e37173a1dba52670910bd321e86029be261aa20f9  docs/operations/selection-v08-counter-outcome-review-2026-09-05.md
4c0b90e2a273a408ce637bdc7de8ba8c6d895604fb5258715d09703827363064  app/InstallationProcess/AssignmentOrderIdentityRegistryMigration.php
5cd931da1ff1bcd356ba2177a3edd0bb56b6aeee1d3f4accb477bd79cbc4a26a  app/InstallationProcess/MariaDbInstallationProcessEnvironment.php
232c56f7009ee4416a35d3942dc2ee46053dc383fb0c11e769f418337f9a158d  app/AssignmentOrderOriginal/MariaDbAssignmentOrderOriginalEvidence.php
4c6252c13e4294401317f2beec3a419a95cd852037c54cfac3a767eedb97ee8c  app/InstallationProcess/AssignmentOrderArtifactService.php
700393249fd0c0982564ede62e75f090810624b5bb82eeea8330590e3c0dc29f  rapid-pilot/docker-bootstrap.php
```

The registry implementation was independently approved and remains disabled;
this audit does not repeat its Gate 5 or authorize canonical registration.
