## Why

`section_completed` is the smallest remaining post-open aggregate mutation after
item and photo facts, but no focused oracle proves its readiness, raw history,
projection or conflict behavior. Before a target
`InspectionRecording::completeSection` seam is designed, current pilot behavior
must be executable and clearly separated from approved product semantics.

## What Changes

- Add a strictly `PILOT_ONLY` production-HTTP/session/CSRF characterization for
  accepted `section_completed` using private synthetic item/photo prerequisites.
- Record exact raw operation/revision/projection facts and prove that completion
  adds no checklist progress weight.
- Characterize exact/changed replay, lower-stale/ahead, distinct repeat and
  two-client same-base behavior as migration contrast.
- Characterize missing-item/photo, revoked-photo, non-working, unauthorized and
  invalid-template mutation boundaries.
- Isolate the current post-completion last-photo revoke inconsistency and
  browser automatic enqueue as PILOT_ONLY hazards, not target requirements.
- Follow Gate 1 → RED → independent test review → GREEN → independent code
  review without modifying production behavior.
- `NEEDS_GRILL`: target correction/revoke invalidation, deliberate UX action,
  repeated/concurrent command result, payload conflict and queued-after-
  reassignment policy block only target migration, not this characterization.

## Capabilities

### New Capabilities

- `verification/inspection-section-completion-characterization`: reproducible
  executable oracle of current section-completion behavior and hazards.

### Modified Capabilities

None.

## Impact

- Source oracle: `app/PilotHttp/ChecklistSync.php`, production checklist HTTP
  routing and current checklist browser client.
- Verification: new private fixture/verifier, registered in canonical
  characterization only after reviewed GREEN.
- Target public seam candidate: `InspectionRecording::completeSection`; it is
  neither approved nor implemented by this change.
- Production code/schema/API and architecture debt baselines do not change.
- Release value: closes the aggregate-transition evidence gap in the engineer
  golden journey while item/photo target seams and canonical schema remain
  separately gated.
