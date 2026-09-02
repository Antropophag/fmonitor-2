## Why

Native operational import depends on classification provenance, but its table is
still created lazily after output creation. The previous backlog incorrectly
grouped this release-supporting proof with optional legacy-active cutover tables,
so a clean native-only TEST-USER contour cannot prove schema readiness or safe
prefix bounds independently.

## What Changes

- Introduce one additive canonical migration for the exact populated-preserving
  `fm2_migration_classification_provenance` table.
- Register it after its actual process-schema predecessors and before native
  case import/bootstrap.
- Preflight exact semantic compatibility before DDL and fail closed without
  changing tables, rows, counters or decoys on conflict; runner exposes only
  per-run version results and has no durable migration ledger.
- Make the bounded absent-table race deterministic only in verifier-composed
  subprocesses through an injected barrier after both absent-v11 preflights and
  before plain `CREATE`; the production CLI/factory has no argv, environment or
  configuration path that can enable the barrier and keeps plain
  preflight-to-`CREATE` behavior without `GET_LOCK`, `SLEEP`, a ledger or other
  serialization.
- Enforce the newly discovered full-catalogue maximum process prefix of 25
  bytes, rejecting 26-byte/invalid input before DB connection/access.
- Remove runtime schema creation from classification reconciliation only after
  clean/existing/conflict and DDL-denied consumer verification is GREEN.
- Keep all three observed output kinds (`operational_case`,
  `historical_snapshot`, `active_baseline`), categories, reason codes, hashes
  and import ordering behavior PILOT_ONLY and unchanged.
- Fix exact missing/drift CLI outcomes and independent no-source-access
  sentinels for native, historical and active batches; characterize the
  output-without-provenance window on one mandatory native operational case.
- Split optional baseline/active-case tables into the separate
  `canonicalize-active-baseline-provenance-schema`, still conditional on
  GRILL-004.
- **NEEDS_GRILL:** legacy-active cutover, classification taxonomy and the
  existing output-without-provenance failure window remain separate behavior
  decisions; they do not block this storage-ownership plan.

Behavior slice: `CANONICALIZE-CLASSIFICATION-PROVENANCE-SCHEMA-001`. Actor —
production migration operator/runner. Source oracle — exact runtime DDL owned by
`MigrationClassificationProvenanceTarget`, confirmed on disposable MariaDB and
through current native/historical consumers. Target public seam —
`bin/fmonitor2-migrate.php`, delegating a canonical persistence migration in
`app/InstallationProcess`. Release value — clean import-backed native-only
startup with a pre-existing exact provenance store and no runtime DDL. Non-goals
— legacy-active baseline/case storage, taxonomy changes, import transaction
redesign, data backfill/reconciliation and new domain logic in rapid-pilot.

## Capabilities

### New Capabilities

- `persistence/classification-provenance-schema`: Каноническое аддитивное
  владение exact classification-provenance storage для native/historical import
  proofs без утверждения их доменной семантики.

### Modified Capabilities

Нет.

## Impact

Затрагиваются production migration runner/catalogue, новый persistence owner в
`app/InstallationProcess`, `MigrationClassificationProvenanceTarget`, native и
historical import schema preconditions, bootstrap ordering, prefix validation,
focused verifiers и architecture ratchet. Optional legacy-active tables и
target behavior не входят в change.
