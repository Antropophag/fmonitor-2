# INSPECTION-ITEM-COMPLETE-001 — independent Gate 1 composition amendment review

Date: 2026-09-01  
Reviewer: `/root/item_gate1_rereview` (independently tasked; did not author the
reviewed amendment, specification, OpenSpec artifacts or production code)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Exact reviewed baseline

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Amended executable spec, `specs/INSPECTION-ITEM-COMPLETE-001.md`:
  SHA-256
  `9767d83c199d3b83e663d9c10ac479884efaeed00fe3a4007908ee1c9664fbe9`.
- `openspec/changes/migrate-inspection-item-completion/README.md`: SHA-256
  `b483aee923918fa973fb33d1dfe4391dd3326af436a12a09768f8d45e2e3d53a`.
- `openspec/changes/migrate-inspection-item-completion/proposal.md`: SHA-256
  `ee6f28e2dec1ec8012eff431712412372e259e1795bfbfd0f78d4dc3730cd777`.
- Amended `openspec/changes/migrate-inspection-item-completion/design.md`:
  SHA-256
  `61f609cac871a0b67b34004c15902e0f2bc9955c8b910253191050edb5ac6fd0`.
- Amended `openspec/changes/migrate-inspection-item-completion/tasks.md`:
  SHA-256
  `e03b3e65e306fe3ac8aa27b9265afdff2a43e182b120859d06594722fe1afc45`.
- Amended delta spec,
  `openspec/changes/migrate-inspection-item-completion/specs/inspection-evidence/item-completion/spec.md`:
  SHA-256
  `bf83a14c89c5cf807a7904c6dfa2d56c9e08072f5720d7e22e4950bee4138d00`.
- Prior independent rereview,
  `docs/operations/inspection-item-completion-gate1-rereview.md`: SHA-256
  `8f68744fc4d0409ef27508fb3943328ea48ef5d75c62eaf4658f86b8c758bd86`.
- Gate 2 incremental evidence identifying the real-MariaDB concurrency need,
  `docs/operations/inspection-item-completion-red-evidence-v2.md`: SHA-256
  `4e6a33240ce3cf4a6eeee081315b690a485407636da37cc63abf43c2c6b184dd`.
- Existing repository composition precedent,
  `specs/PRODUCTION-COMPOSITION-001.md`: SHA-256
  `1bd6aaa1195b9622d6c1d80b970a0aefed1d8dced8cb5d4964a6659dfbb1e1d5`.
- Existing factory/config implementations were read only as pattern evidence:
  `app/InstallationProcess/ProductionInstallationProcessFactory.php` SHA-256
  `2fcb40a05be9d0a514f77d169a617ee0a9d6f95e245d020c125b20c96e3a5350`;
  `app/InstallationProcess/ProductionInstallationProcessConfig.php` SHA-256
  `e3f32fe084a9893de61454f97def9d329522e53a552c099e1adae20de21a046c`.

`openspec validate migrate-inspection-item-completion --strict` reports the
change valid. Structural validity does not close the interface blocker below.

## Finding

### CA-01 — BLOCKER: the injected clock has a type name but no callable contract

The amended factory signature names optional `InspectionEvidenceClock`, and the
prose says that a fixed clock makes server receipt time deterministic. Neither
the executable spec nor synchronized OpenSpec defines the interface method,
its return type, or its timestamp format. The only occurrences in the reviewed
change are the factory parameter type itself.

Consequently an independent production-seam RED cannot know whether the
approved contract is, for example, `now(): string`,
`now(): DateTimeImmutable`, or another value type, and cannot derive the exact
server-receipt evidence expected from a fixed clock. This is materially less
exact than the existing repository composition precedent, which defines
`Clock::now(): string` and its RFC3339-with-offset production format.

Required amendment: define the exact `InspectionEvidenceClock` public method
signature and authoritative output contract, including the accepted timestamp
shape/precision/offset rule and the system-clock default. Synchronize the
executable spec, design/delta requirement and production-composition acceptance
scenario. The choice may reuse an existing clock interface if that is the
intended dependency, but the approved type and method must be unambiguous.

## Checks that otherwise pass

- The factory/config shape is appropriately narrow:
  `ProductionInspectionEvidenceFactory::create(mysqli,
  ProductionInspectionEvidenceConfig, ?InspectionEvidenceClock)` returns one
  `InspectionEvidenceApplication` implementing the unchanged
  `InspectionRecording` and `InspectionEvidenceView` interfaces. Config contains
  only `processTablePrefix`; no legacy prefix, artifact root, credentials or
  unrelated application configuration leaked into this module.
- Caller ownership of connection open/close is separated from application
  ownership of command transactions. One application instance has exclusive
  transaction use of one connection; concurrent workers must use distinct
  caller-owned connections and application instances. This supports the real
  overlap test identified by the test author without exposing repositories.
- Prefix validation inherits the canonical ASCII `[A-Za-z0-9_]*`, 0..25-byte
  process-prefix boundary and is required before any connection access. Empty
  and exact 25-byte prefixes are admitted; 26-byte, non-ASCII and invalid
  characters fail as configuration before DB access.
- After configuration validation the factory initializes the supplied
  connection to `utf8mb4`. Factory creation performs no DDL, schema repair,
  migration, ledger write or business mutation. Missing/incompatible canonical
  v8 remains a fail-closed operation precondition, and no v9 is introduced.
- The composition amendment does not change command/query semantics,
  authorization, replay/rejection precedence, evidence shape or v8 ownership.
  HTTP and MariaDB tests use the same application interfaces and cannot wire a
  concrete repository as a test-only seam.

## Verdict

The composition direction is deep, narrow and consistent with repository/v8
patterns, but its clock dependency is not yet an exact executable public
contract. The amended artifact is `CHANGES_REQUESTED`; owner reapproval and the
production-composition RED must wait for reconciliation and fresh independent
review of the new exact hashes.
