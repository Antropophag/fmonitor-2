## Why

`INSPECTION-ITEM-COMPLETE-001` is the calibration slice for the autonomous delivery system. Today an engineer's checklist completion is accepted by `ChecklistSync`, which mixes HTTP synchronization, rules, SQL, and runtime DDL; moving one operation behind `InspectionRecording::completeItem` proves a real append-only application seam before broader migration.

## What Changes

- An engineer records one completed checklist item through `InspectionRecording::completeItem` with actor, case, immutable template/item identity, installer attribution, client operation id, and expected revision.
- The application command seam owns authorization, case/template validation,
  idempotency, optimistic concurrency, append-only facts, and the returned
  accepted revision; a public read seam exposes immutable accepted evidence
  without SQL/repository access or projection-side backfill.
- Landed canonical migration v8 owns the required inspection-evidence tables;
  this slice adds no migration version. The rapid-pilot endpoint becomes a
  translation adapter to the seam and fails closed when v8 schema is absent or
  incompatible.
- Existing offline/current-crew behavior is characterized before RED and remains observable after rewiring.
- Non-goals: photo upload/revocation, section completion, inspection scheduling, completion percentages, and premium semantics.
- GRILL-003 is owner-resolved: any active engineer with exact capability
  `inspection.item.complete` may complete an item on any installation object;
  current control-engineer assignment is routing/audit context and is not an
  authorization condition. Offline commands re-check active status and the
  exact capability at server receipt; `deviceTime` cannot preserve revoked
  authority. Current pilot's `checklist.edit OR current engineer` remains
  characterization evidence only. Scheduling/completion/financial slices remain
  separately blocked by `GRILL-001`.

## Capabilities

### New Capabilities

- `inspection-evidence/item-completion`: append-only, offline-safe acceptance of one checklist item completion through the `InspectionRecording::completeItem` public seam.

### Modified Capabilities

None.

## Impact

- New Inspection Evidence application contract and MariaDB adapter outside HTTP/UI.
- Consumption of landed canonical inspection-evidence schema v8 without a new
  migration or runner version.
- `app/PilotHttp/ChecklistSync.php` delegates the selected operation instead of owning its business SQL/DDL.
- Characterization and regression surface includes offline replay, current-crew history, authorization, concurrency, canonical migration, and `make architecture-check`.
