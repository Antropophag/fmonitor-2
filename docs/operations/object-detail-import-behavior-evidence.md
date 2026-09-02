# Object-detail snapshot/quarantine import behavior evidence

Evidence cut: 2026-09-01. This document records observed rapid-pilot behavior. It
does not approve a legacy cutover, financial semantics, personal-data use, or a
new product requirement.

## Capability and boundary

The capability is an operator-run, one-way projection from the read-only legacy
FMonitor database into the active local pilot generation. Its current entrypoint
is `rapid-pilot/import-production-object-details.php`; it is also invoked by
`rapid-pilot/initialize-native-only.php` after native candidate cases have been
created.

- **Actor:** an infrastructure/migration operator with both source read
  credentials and target write credentials. There is no FMonitor user actor,
  application capability, HTTP route, CSRF boundary, or audit event.
- **Selection boundary:** the target generation supplies the authoritative input
  set. Every `legacy_installation_object_id` currently in
  `fm2_installation_cases` is paged in ascending order; the importer does not
  independently choose arbitrary legacy rows.
- **Source trust boundary:** source `fm_fields`, `fm_view_fields`,
  `fm_fields_values`, and `fm_maintable` are read inside one repeatable-read,
  consistent, read-only transaction. Source host/name have defaults; source user
  and password are required environment inputs.
- **Target trust boundary:** the active manifest selects the local endpoint and
  process prefix. `WorkforceCatalogReconciliationCandidate::assertGeneration`
  is checked before extraction and again under the target write transaction.
  Apply additionally requires the literal local-pilot acknowledgement. The
  acknowledgement is an environment guard, not authorization or an audit trail.
- **Time input:** `captured_at` is supplied by the operator in exact
  `Y-m-dTH:i:s+HH:MM` form. The importer does not obtain a trusted clock value or
  record who supplied it.

The source is read-only, but the current apply path owns target DDL and DML. Its
two `CREATE TABLE IF NOT EXISTS` statements execute before the target
transaction, so they are not rolled back with a later import failure.

## Extracted semantics

Schema version `technical-object-detail-v1` extracts exactly six legacy
technical fields:

`floors`, `weight`, `speed`, `pittype`, `pitmaterial`, `paired`.

For every field the payload retains trimmed `raw`, resolved `display`, and
provenance (`fm_maintable`, source column, legacy field id/type, and optional
dictionary table/id). Type-4 values are resolved through `fm_fields_values`.
The hash is SHA-256 over canonical JSON containing only schema version, object
id, and the six field structures. `capturedAt` and the hash itself are added to
the stored JSON after that material hash is computed.

Despite `RapidPilotObjectDetails` rendering many additional legacy-shaped
fields, this importer does not extract those fields. Their empty display in that
view must not be interpreted as imported evidence. Of the six imported values,
the premium reader uses only `floors.raw`, `weight.raw`, and
`pitmaterial.display`; it also looks for `lift_type.display`, which this importer
never supplies. Current norm lookup can still proceed with a null lift type for
some floor/capacity combinations, but no evidence supports calling lift type an
imported operand.

## Accepted, quarantined, and rejected outcomes

### Accepted projection

A target case whose legacy `fm_maintable` row exists produces one immutable
`fm2_pilot_object_details` row. Blank or semantically unusual scalar values are
accepted as strings; the importer validates neither numeric ranges nor business
meaning. A type-4 nonblank dictionary id must resolve.

On an exact hash repeat the existing row is counted as `alreadyPresent`; its
payload and original `captured_at` are not updated. Therefore repeat execution
with a different capture time is idempotent with respect to stored state.

### Quarantined projection

Only an entirely absent source `fm_maintable` row is quarantined. The importer
creates one `fm2_pilot_object_detail_quarantine` row with
`SOURCE_OBJECT_NOT_FOUND`; its hash covers schema version, object id, and code,
not capture time. An exact repeat is counted as `quarantinePresent`.

This is a diagnostic marker, not an adjudication workflow: it has no reason
payload, observation history, resolution state, actor, retention rule, or
consumer UI. Consumers do not read this table. A later present source row may
gain a detail row while its old quarantine row remains, and a formerly present
row that disappears may gain a quarantine row while the old detail row remains.
The current code neither reconciles nor declares precedence for such coexistence.

### Rejected run

The run fails rather than quarantining a row when:

- required source metadata for any of the six fields is absent;
- any nonblank type-4 value is unknown to the dictionary;
- an existing detail row has a different content hash
  (`DETAIL_PROJECTION_CONFLICT`);
- an existing quarantine row has a different hash
  (`DETAIL_QUARANTINE_CONFLICT`);
- arguments, manifest/generation guard, credentials, SQL, or JSON processing are
  invalid.

Metadata/dictionary failures happen before target DML. Detail and quarantine
DML share one target transaction, so a conflict rolls back all DML in that run;
the preceding DDL remains committed. Dry-run performs source and target reads
and reports counts but writes no target rows.

## Idempotency and concurrency evidence

- Stable identity is one row per `object_id`; there is no snapshot history.
- The same material hash is a no-op even when requested `captured_at` differs.
- Changed source material is fail-closed, not a refresh/upsert. Thus the name
  “snapshot” means first accepted immutable projection, not latest source state.
- The whole batch is one target DML transaction.
- Existing rows are locked with `SELECT ... FOR UPDATE`. There is no operation
  id, run ledger, advisory lock, or characterized two-run concurrency contract.
  The absent-row race is not proven safe by current tests and may surface as a
  duplicate/deadlock rather than an idempotent result.
- The static verifier checks source tokens only; it does not execute clean,
  repeat, conflict, transition, rollback, or concurrent scenarios.

These observations support characterizing serial clean/repeat/conflict behavior
before ownership migration. Concurrent behavior and missing/present transitions
remain `UNKNOWN`, not accepted requirements.

## Tables and consumers

| Surface | Relationship |
|---|---|
| `fm2_installation_cases` | Read-only source of the exact object-id set. |
| `fm2_pilot_object_details` | Importer-owned immutable first projection; no FK to the case/object tables. |
| `fm2_pilot_object_detail_quarantine` | Importer-owned missing-row marker; no FK or consumer. |
| `RapidPilotObjectDetails` | Reads payload/capture time after an already-authorized 200 object-card response; fails closed to an unavailable notice. It does not validate the stored hash. |
| `NativeOperationalPremiumInputs` | Reads technical operands and blocks calculation when evidence/norms are absent. It validates only hash syntax, not hash-to-payload equality. |
| `RapidPilotOtiz::objects` | Reads payload for an economics projection and derives premium/shaft values. |
| `initialize-native-only.php` | Unconditionally invokes the production detail importer after native candidate import. Here “native-only” excludes legacy history/replay; it does **not** mean synthetic or free of production object identifiers/data. |
| `verify-native-only-generation.php` | Requires quarantine to be empty, but does not require a detail row for every case. |

The current focused verifier is
`rapid-pilot/verify-object-detail-projection-import.php`; premium consumers are
partly exercised by `verify-native-operational-otiz-inputs.php`,
`verify-native-operational-live-scenario.php`, and `verify-otiz-workflow.php`.
Several consumer fixtures use a reduced four-column table, so they do not prove
compatibility with the importer's exact five-column canonical candidate.

## Authorization, audit, and personal-data risk

The import is operational tooling, not a user command. Database credentials,
manifest generation checks, and the local acknowledgement limit accidental
execution but do not answer who is allowed to import, who did so, or why. No run
id, actor, source cutoff, source transaction identity, or append-only audit fact
is stored. The public result emits aggregate counts and no identifiers.

The six payload fields are technical characteristics and contain no intended
person names, phones, email addresses, or workforce records. Nevertheless,
`object_id` is a real production identifier and the selected cases can be
correlated with the local legacy mirror, whose object card contains address and
registration data. The rendered card also joins workforce names and employment
status from other tables. Therefore the family is not a sufficient privacy
boundary merely because its JSON is technical. Copying it into a test contour
still constitutes use of production-linked object data and remains subject to
GRILL-004.

## Product-status assessment

- **ACCEPTED_WITH_CHANGES:** an immutable, provenance-bearing technical evidence
  snapshot used fail-closed by premium calculation is consistent with product
  reproducibility principles. Canonical migration ownership, content-hash
  verification, explicit application/read-model ownership, and a real run audit
  are missing.
- **DEMO_ONLY for the first synthetic test-user contour:** importing production
  details and maintaining the missing-source quarantine is unnecessary if the
  owner accepts the recommended deterministic synthetic/native fixture. The
  fixture still needs deterministic technical operands so the golden journey can
  show or intentionally block premium preview.
- **UNKNOWN:** refresh policy after legitimate legacy changes, precedence when
  detail and quarantine coexist, quarantine resolution/retention, concurrent-run
  result, and whether any real production-linked object data may enter the test
  contour.

## Release-scope conclusion

The native synthetic test contour needs the **technical evidence contract** and
the detail table only if premium preview/object technical display is in its
golden scope. It does not need this production importer or populated quarantine
family. A versioned synthetic fixture can create a hash-valid detail payload;
alternatively the premium path must visibly fail closed with
`OBJECT_CARD_EVIDENCE_ABSENT`. It must not silently invent operands.

Canonical schema ownership can proceed without cutover approval because it can
be an additive, data-preserving migration that creates/verifies the two observed
tables and removes importer-owned DDL. That work must not run the importer,
populate production-linked data, approve quarantine semantics, or make legacy
cutover part of deployment. The migration should preserve compatible populated
rows and fail before mutation on incompatible shape. Cutover execution and a
production-data fixture remain blocked by GRILL-004.

## Recommended next slices

1. **`CHARACTERIZE-OBJECT-DETAIL-IMPORT-001` — READY.** Executably cover clean
   accepted + missing quarantine in one run, exact serial repeat preserving the
   original capture time, changed-detail conflict with zero DML mutation, and
   source-metadata/dictionary rejection. Treat transition and concurrency
   behavior as out of scope/UNKNOWN unless separately approved.
2. **`CANONICALIZE-OBJECT-DETAIL-SNAPSHOT-SCHEMA-001` — READY after the
   characterization gate.** Create/verify the exact two-table family through a
   sequential canonical migration, prove populated preservation and incompatible
   zero-mutation, remove both importer DDL statements, and keep import invocation
   outside migration/deployment.
3. **`VERIFY-OBJECT-DETAIL-CONTENT-INTEGRITY-001` — READY security/reliability
   hardening.** Make both object-card and premium consumers recompute the defined
   material hash (or read through one verified seam) and fail closed on mismatch.
   This changes rejection behavior and therefore needs its own executable spec.
4. **`TEST-USER-TECHNICAL-DETAIL-FIXTURE-001` — NEEDS_GRILL.** After GRILL-004,
   either seed explicitly synthetic, hash-valid technical operands with visible
   scenario version/provenance, or assert the deliberate absent-evidence blocker.
   Do not call the production importer in the synthetic reset contract.

## Precise GRILL recommendation

Do not open a new broad questionnaire. Add one high-information clarification to
the existing GRILL-004 package:

> For the first deterministic synthetic test contour, should its golden journey
> include a successful premium **preview** backed by fictional technical object
> details, or should premium remain visibly blocked as out of release scope?
> **Recommendation:** include one fictional hash-valid technical detail snapshot
> so the read-only preview is demonstrable, but keep acceptance/payment actions
> governed by the separate financial GRILL. Consequence of the alternative:
> fewer fixtures and no object-detail dependency, but the test users cannot
> exercise a successful end-to-end premium preview.

Exactly blocked by this answer:
`TEST-USER-TECHNICAL-DETAIL-FIXTURE-001` and the premium-preview branch of the
golden journey. Neither schema ownership nor importer characterization is
blocked.
