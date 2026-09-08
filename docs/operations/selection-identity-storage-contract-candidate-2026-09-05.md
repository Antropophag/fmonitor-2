# Selection identity/storage contract candidate

Date: 2026-09-05. Status: technical **DRAFT** for the future Gate 1 batch.
This appendix proposes an exact storage and cutover contract. It is not product
approval, Gate 1 approval, a migration manifest, a reserved migration version,
RED evidence, or permission to implement. `REPLACE_PENDING` remains owner
pending and is not enabled by this draft.

## Evidence and boundary

This candidate follows `AGENTS.md`, `docs/development-process.md`, the selected
direction appended at commit `5dd3e9f` to the OpenSpec design, the P0/P1
resolution audit dated 2026-09-05, and the current
`ProductionProcessSchemaMigration` and `MariaDbInstallationProcessEnvironment`.

The existing physical contract matters:

- `fm2_assignment_orders.id` is independently `AUTO_INCREMENT` and
  `(installation_case_id, version_no)` is unique;
- preparation reconstructs orders only from `fm2_assignment_orders`, takes the
  aggregate-produced version, and inserts without an explicit ID;
- for version `N > 1`, persistence requires a **registered physical order** at
  exactly `N - 1` and stores its ID as `previous_assignment_order_id`.

Consequently, changing only numeric ID allocation is unsafe. A selection may
own case version `N` without a physical order row. An unchanged legacy prepare
then either proposes the same `N` or proposes `N + 1` but cannot satisfy its
registered-physical-`N` predecessor rule. The compatibility rule below blocks
that path rather than pretending that globally unique IDs solve case-version
continuity.

## Candidate schema

All tables are InnoDB and use the deployment's canonical `utf8mb4` collation.
Constraint names are migration-generated with the repository's prefix-safe
naming convention. The eventual migration must fingerprint columns, defaults,
indexes, foreign keys, checks, engine and collation exactly. The literal
migration version is deliberately unspecified.

```sql
CREATE TABLE fm2_assignment_order_identities (
  assignment_order_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  installation_case_id BIGINT UNSIGNED NOT NULL,
  order_version SMALLINT UNSIGNED NOT NULL,
  source_kind VARCHAR(24) NOT NULL,
  allocated_at_utc DATETIME(6) NOT NULL,
  PRIMARY KEY (assignment_order_id),
  UNIQUE KEY uq_identity_case_version (installation_case_id, order_version),
  UNIQUE KEY uq_identity_id_case (assignment_order_id, installation_case_id),
  KEY ix_identity_case_source_version
    (installation_case_id, source_kind, order_version),
  CHECK (assignment_order_id BETWEEN 1 AND 9223372036854775807),
  CHECK (order_version BETWEEN 1 AND 65535),
  CHECK (
    BINARY source_kind IN (BINARY 'legacy_order', BINARY 'selection')
  ),
  FOREIGN KEY (installation_case_id)
    REFERENCES fm2_installation_cases (id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
);
```

The signed-64 ceiling is intentional even though MariaDB stores an unsigned
value: all current public PHP seams carry IDs as `int`. Allocation at or above
`9223372036854775808` is therefore unsupported.

```sql
CREATE TABLE fm2_assignment_order_selections (
  assignment_order_id BIGINT UNSIGNED NOT NULL,
  installation_case_id BIGINT UNSIGNED NOT NULL,
  order_version SMALLINT UNSIGNED NOT NULL,
  selection_revision INT UNSIGNED NOT NULL,
  mode VARCHAR(24) NOT NULL,
  previous_selection_order_id BIGINT UNSIGNED NULL,
  replaces_selection_order_id BIGINT UNSIGNED NULL,
  composition_identity VARCHAR(160) NOT NULL,
  composition_sha256 CHAR(64) NOT NULL,
  control_engineer_user_id BIGINT UNSIGNED NOT NULL,
  control_engineer_fio_snapshot VARCHAR(300) NOT NULL,
  control_engineer_position_snapshot VARCHAR(300) NOT NULL,
  selection_date DATE NOT NULL,
  selected_at_utc DATETIME(6) NOT NULL,
  selected_by_user_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (assignment_order_id),
  UNIQUE KEY uq_selection_case_version (installation_case_id, order_version),
  UNIQUE KEY uq_selection_case_revision
    (installation_case_id, selection_revision),
  UNIQUE KEY uq_selection_composition_identity (composition_identity),
  KEY ix_selection_previous (previous_selection_order_id),
  KEY ix_selection_replaces (replaces_selection_order_id),
  CHECK (assignment_order_id BETWEEN 1 AND 9223372036854775807),
  CHECK (order_version BETWEEN 1 AND 65535),
  CHECK (selection_revision >= 1),
  CHECK (
    BINARY mode IN (BINARY 'new_order', BINARY 'replace_pending')
  ),
  CHECK (
    BINARY composition_sha256 REGEXP BINARY '^[0-9a-f]{64}$'
  ),
  CHECK (
    (mode = 'new_order' AND replaces_selection_order_id IS NULL) OR
    (mode = 'replace_pending' AND replaces_selection_order_id IS NOT NULL)
  ),
  FOREIGN KEY (assignment_order_id, installation_case_id)
    REFERENCES fm2_assignment_order_identities
      (assignment_order_id, installation_case_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  FOREIGN KEY (previous_selection_order_id)
    REFERENCES fm2_assignment_order_selections (assignment_order_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT,
  FOREIGN KEY (replaces_selection_order_id)
    REFERENCES fm2_assignment_order_selections (assignment_order_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
);

CREATE TABLE fm2_assignment_order_selection_installers (
  assignment_order_id BIGINT UNSIGNED NOT NULL,
  installer_tab_id BIGINT UNSIGNED NOT NULL,
  fio_snapshot VARCHAR(300) NOT NULL,
  position_snapshot VARCHAR(300) NOT NULL,
  employment_status_snapshot VARCHAR(40) NOT NULL,
  employed_from_snapshot DATE NOT NULL,
  employed_to_snapshot DATE NULL,
  workforce_source_snapshot VARCHAR(80) NOT NULL,
  workforce_source_updated_at_snapshot VARCHAR(40) NOT NULL,
  PRIMARY KEY (assignment_order_id, installer_tab_id),
  CHECK (installer_tab_id >= 1),
  FOREIGN KEY (assignment_order_id)
    REFERENCES fm2_assignment_order_selections (assignment_order_id)
    ON UPDATE RESTRICT ON DELETE RESTRICT
);
```

The selection family is deliberately dateless with respect to applicability:
it contains no `order_date`, `valid_from`, `valid_to`, `change_action`,
registration status, opening status, or effective flag. `selection_date` is the
Moscow calendar date derived from `selected_at_utc`; it is eligibility and
audit context only. It is never an original `documentDate`, template date, or
effective interval boundary.

`previous_selection_order_id` links to the preceding selection revision, if
one exists. It does not assert that the preceding composition was effective.
`replaces_selection_order_id` is populated only if the owner later approves
append-only pre-original replacement. Until then, the accepted runtime modes
and schema readiness must allow only `new_order`; no caller may write the
`replace_pending` literal merely because the candidate column/check exists.

Requests, success events and safe audits use the exact typed command/fingerprint
contract maintained by the companion planning work. Their storage belongs in
the same migration family, but this appendix does not duplicate or silently
vary that contract. A successful request must reference the selected
`assignment_order_id`; one accepted event and one accepted audit are committed
with it. Terminal rejection/conflict has no identity allocation and no
selection/member/event row.

## Identity and version allocation transaction

The serialization anchor is the existing `fm2_installation_cases` row. One
selection or compatible legacy preparation transaction performs these steps:

1. Begin a transaction and lock the exact case row by primary key with
   `SELECT ... FOR UPDATE`. Missing or ambiguous object-to-case resolution is a
   typed dependency outcome before allocation.
2. Recheck terminal request replay and command preconditions inside the locked
   transaction where required by the command contract.
3. Read the greatest registry `order_version` for that case. The next version
   is `1` when absent, otherwise `greatest + 1`. Do not derive it from either
   source table and do not use `MAX()+1` without the case lock.
4. If the greatest version is `65535`, roll back. No source fact, terminal
   success, event, or accepted audit is written. The public result mapping is
   an explicit Gate 1 blocker: the typed result contract needs one exact,
   non-retryable exhaustion reason before implementation; this draft does not
   reuse an unrelated existing reason or invent its final enum literal.
5. Insert one identity row with the resolved case/version and immutable
   `source_kind`. MariaDB assigns the global ID. Treat an allocated value above
   `9223372036854775807`, an exhausted sequence, or conversion failure by
   rolling back the whole transaction. This has the same unresolved typed
   non-retryable exhaustion mapping as the version frontier and cannot be
   reported as a retryable dependency outage.
6. Insert exactly one matching source header and all source-owned rows. For a
   selection, also insert terminal success, the success event and accepted
   audit according to the companion contract.
7. Commit once. A known rollback leaves neither an acknowledged identity nor a
   source fact. An unknown commit outcome is resolved only by the same-request
   terminal lookup; the allocator is never called again until that lookup
   proves absence.

The database may consume an `AUTO_INCREMENT` number on rollback. That gap is
valid allocator history and is never filled deliberately. There is no promise
that successful IDs are consecutive. Uniqueness of the registry primary key
and `(installation_case_id, order_version)`, plus the case lock, is the final
concurrency authority. Duplicate-key or deadlock results are conflicts/retryable
failures according to the typed commit contract; callers never repair them by
choosing their own ID or version.

For `source_kind='legacy_order'`, the revised legacy repository inserts
`fm2_assignment_orders.id` explicitly from the registry. The physical table's
`AUTO_INCREMENT` attribute may remain during the compatibility release, but no
ready writer may invoke it implicitly. For `source_kind='selection'`, the same
transaction inserts the dateless selection row and never inserts into
`fm2_assignment_orders`, `fm2_order_installers`, or effective projections.

## Legacy preparation compatibility rule

The compatible legacy path is intentionally narrow:

- it allocates ID and version through the registry in the same transaction;
- it may run only when the latest registry identity for the case is absent, or
  is a `legacy_order` whose matching physical row is `registered`;
- for version `N > 1`, that latest registered physical row must be version
  `N - 1` and becomes `previous_assignment_order_id`;
- if the latest registry identity is a `selection`, or registry and physical
  history do not meet that exact predecessor condition, legacy preparation
  fails closed before allocation with `SELECTION_OWNED_CASE` or
  `IDENTITY_HISTORY_UNAVAILABLE` and directs the workflow to the new selection
  owner. It must not skip the selection version, reuse it, synthesize a physical
  predecessor, or treat an accepted original as a registered legacy row.

This rule preserves existing all-legacy cases while preventing split version
ownership. It does not establish how the old aggregate should project a
selection-backed order. If continued legacy prepare on a case after any
selection is a release requirement, this candidate has a concrete blocker:
the aggregate and predecessor semantics need a separately approved compatibility
design before Gate 1. Numeric-ID sharing alone cannot close it.

## Historical backfill and allocator frontier

Backfill is metadata preservation, not creation of new domain events. It runs
with all assignment-order writers stopped and records these pre-DDL values:

- `legacy_auto_increment`: the exact `information_schema.TABLES.AUTO_INCREMENT`
  for `fm2_assignment_orders`, including gaps caused by deleted/rolled-back
  inserts;
- `legacy_max_id`: `MAX(id)`, using zero for an empty table;
- counts and deterministic hashes of `(id, installation_case_id, version_no)`;
- duplicate, zero/out-of-range and orphan-case diagnostics.

Preflight fails without mutation if any ID exceeds signed PHP range, any
version is outside `1..65535`, `(case,version)` is duplicated, a case is
missing, or the legacy table shape is not the exact expected predecessor.

Create the registry with no application writers, then insert one
`legacy_order` identity for every physical order, preserving all three values
exactly. `allocated_at_utc` for historical entries is the parseable UTC value
of `prepared_at`; an invalid/ambiguous historical timestamp is a preflight
blocker, not replaced with migration time. Backfill emits no selection,
process, or audit event.

Set the registry next frontier to:

```text
max(legacy_auto_increment, legacy_max_id + 1, registry_max_id + 1)
```

All arithmetic is decimal/unsigned during migration inspection. If the result
is greater than `9223372036854775807`, cutover fails as allocator exhaustion.
The frontier must preserve an empty table's configured `AUTO_INCREMENT` and
all historical gaps; setting it merely to `MAX(id)+1` is forbidden. Explicitly
backfilled IDs must never renumber rows. Keep the legacy table's own frontier
at least its captured value; compatible writers use explicit registry IDs, so
it ceases to be an allocation authority.

The migration receipt freezes evidence only for the migration-origin subset. It
stores `legacy_max_id`, the captured legacy frontier, the historical row count,
and the deterministic hash of historical tuples. Because every later identity
is allocated at or above the preserved frontier and that frontier is greater
than `legacy_max_id`, the historical subset is exactly the physical and
registry rows with `assignment_order_id <= legacy_max_id`. Receipt validation
recomputes count/hash only over that subset. It must not compare a frozen
whole-database count/hash with a database that has legitimately gained later
legacy orders or selections.

Backfill is restartable by exact equality: an existing registry row is accepted
only when ID, case, version and `legacy_order` all match. Any mismatch or a row
with the same `(case,version)` under another ID/source aborts. After each batch,
persist migration bookkeeping outside domain/audit tables. Cutover and ongoing
dynamic verification separately require bidirectional equality over the live
database: every physical order has one matching legacy identity; every legacy
identity has one matching physical order; every selection identity has one
matching selection header; no ID exists in both source tables. These dynamic
invariants admit valid new rows while the immutable receipt continues to prove
that the migration-origin subset was preserved exactly.

## Original composition reader

`AssignmentOrderCompositionReader::find(caseId, assignmentOrderId)` reads in
one consistent read-only transaction/snapshot:

1. Read the exact registry row by numeric ID. If it exists for another case,
   return `NOT_FOUND` without probing or disclosing that case's source. If no
   registry row exists, probe both source headers constrained to the requested
   case and ID: neither source is `NOT_FOUND`; either source is an orphan and
   therefore `UNAVAILABLE`. There is no source fallback.
2. For `legacy_order`, require exactly one matching physical order and no
   selection header. Apply the existing strict `order_date`, member action and
   validity rules unchanged; derive the existing canonical composition JSON,
   identity and SHA-256 exactly as before.
3. For `selection`, require exactly one matching selection header and no
   physical order. Require header case/version to equal the registry, at least
   one unique positive installer row, one positive engineer ID, canonical
   snapshot fields, lowercase 64-hex stored hash, and recomputation of the
   approved canonical composition JSON/hash. Read every installer row for that
   ID ordered numerically, with no temporal or effective predicate.

Missing registered source, contradictory dual source, byte-noncanonical
source-kind/hash, source-kind mismatch, orphan source (including a same-case
source with no registry identity), duplicate case/version, inconsistent header,
query failure or unavailable inspection yields `UNAVAILABLE`; there is no source precedence or
fallback. An ID registered to another case remains `NOT_FOUND` for the requested
case and is not source-probed, preserving nondisclosure. A structurally found composition that fails the existing original
command's content validation may remain `FOUND` with an invalid snapshot only
if the executable original-reader amendment specifies its deterministic
`INVALID_COMPOSITION` mapping. Otherwise malformed found data is
`UNAVAILABLE`. Absence from the registry is the only ordinary `NOT_FOUND`.

This is an additive registered-source branch for original upload. It neither
changes original root/revision persistence nor authorizes upload, and it does
not make the selection visible to installer-directory, inspection, opening,
checklist, assignment, or other effective readers.

## Readiness and cutover

Readiness fails closed unless all of the following are true:

- the exact registry and enabled selection-family fingerprints match;
- the immutable migration receipt matches the migration-origin subset through
  its captured `legacy_max_id`, count and tuple hash, and the stored captured
  legacy frontier; it is not compared with current whole-database totals;
- registry `AUTO_INCREMENT` is at least the computed preserved frontier and is
  within signed PHP range;
- dynamic whole-database ownership checks find no missing source, dual source,
  byte-noncanonical discriminator/hash, source mismatch, duplicate
  case/version, or orphan;
- the deployed selection writer, original reader and every legacy preparation
  writer are from the compatibility release and use the registry contract;
- no enabled binary can implicitly allocate an `fm2_assignment_orders.id`;
- the executable contract's disposition of `REPLACE_PENDING` matches runtime
  mode checks (currently disabled pending owner decision).

There is no runtime DDL, lazy backfill, best-effort repair, lookup precedence,
or mixed-writer grace period. The cutover sequence is: stop all writers;
preflight and capture; create/backfill/validate; deploy all compatible readers
and writers; run readiness; then start writers. Reads may resume only when their
binary's readiness contract is satisfied.

Rollback of application code is allowed only to a build that understands the
registry and explicit-ID allocation. Rollback to an older implicit-ID writer is
blocked after registry creation, even if no selection has yet been written,
because the two allocator frontiers can diverge. Schema rollback does not
delete registry/selection history or lower either frontier. A forward repair
release is required for any post-cutover invariant failure.

The combined release must also expose optional rendering as a separate public
operation over the already selected `assignment_order_id`. Rendering reads the
exact immutable selection source, records only its template/date/artifact facts,
and never invokes legacy preparation or allocates another identity/version.
Blocking legacy preparation after selection ownership is only a compatibility
guard; it is not evidence that the approved direct-upload and optional-template
paths have reached parity. Readiness must fail if the release routes optional
rendering through the blocked legacy prepare path.

## Worked synthetic cases

**Preserved global frontier.** Physical legacy rows have IDs `7` and `11`, but
`information_schema.TABLES.AUTO_INCREMENT` is `20` because IDs `12..19` were
consumed or reserved historically. Backfill writes registry IDs `7` and `11`
and sets its next value to `20`, not `12`. A rolled-back selection may consume
`20`; the next committed selection may therefore be ID `21`. This is valid.

**Case version serialization.** Case `44` has registered legacy identity
`(id=11, version=2)`. Selection locks case `44`, creates registry
`(id=21, version=3, source=selection)` and its dateless source in one commit.
The legacy prepare endpoint then fails `SELECTION_OWNED_CASE`; it cannot create
version `3`, cannot jump to `4` with physical predecessor `2`, and cannot point
`previous_assignment_order_id` at selection ID `21`.

**Original lookup.** `find(44, 21)` resolves registry source `selection`, reads
engineer `501` and installer IDs `[104, 109]`, and hashes the approved canonical
composition bytes. It does not inspect `order_date` or member validity. If a
stray physical order row also has ID `21`, the result is `UNAVAILABLE`, never a
legacy-first or selection-first answer.

**Exhaustion.** A case whose greatest registry version is `65535` cannot select
or prepare another order. A global next identity value of
`9223372036854775808` cannot cross the PHP `int` seam. Both failures happen
before an acknowledged domain fact. Gate 1 must add their exact non-retryable
typed result mapping; wrapping, reuse, negative conversion and classification
as a retryable dependency outage are forbidden.

## Gate boundary and open blocker

The eventual executable Gate 1 batch must adopt or revise this schema together
with the original-reader amendment, typed command/fingerprint contract,
existing-writer handoff and deployment checks. It must use the actual migration
catalogue frontier at implementation time. This draft creates no effective
facts and settles no user-visible pending-selection replacement policy.

The only compatibility claim made here is fail-closed coexistence: legacy
prepare remains usable for all-legacy cases and becomes unavailable after a
case enters selection ownership. Seamless legacy prepare after a selection
remains blocked until aggregate version and predecessor semantics are designed
and approved explicitly.
