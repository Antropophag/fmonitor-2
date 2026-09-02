# INSPECTION-ITEM-COMPLETE-001 — independent composition amendment rereview

Date: 2026-09-01  
Reviewer: `/root/item_gate1_rereview` (independently tasked; did not author the
reviewed amendment, specification, OpenSpec artifacts, tests or production code)  
Mission: `TEST-USER-READY`  
Verdict: `READY_FOR_OWNER_APPROVAL`

## Exact reviewed artifacts

- Repository `HEAD`: `9abe0c42913d0f2598e866d38b9b357327e48b13`.
- Executable spec, `specs/INSPECTION-ITEM-COMPLETE-001.md`: SHA-256
  `ea80f9b469548b7c402dc603a24d8da1bfaef02b858891e923c7ebd9f9ff5750`.
- `openspec/changes/migrate-inspection-item-completion/README.md`: SHA-256
  `b483aee923918fa973fb33d1dfe4391dd3326af436a12a09768f8d45e2e3d53a`.
- `openspec/changes/migrate-inspection-item-completion/proposal.md`: SHA-256
  `ee6f28e2dec1ec8012eff431712412372e259e1795bfbfd0f78d4dc3730cd777`.
- `openspec/changes/migrate-inspection-item-completion/design.md`: SHA-256
  `19d2cde38a3105e1c533039ee43aad7e5266402840d21c859fa43bed55d6167d`.
- `openspec/changes/migrate-inspection-item-completion/tasks.md`: SHA-256
  `e03b3e65e306fe3ac8aa27b9265afdff2a43e182b120859d06594722fe1afc45`.
- Delta spec,
  `openspec/changes/migrate-inspection-item-completion/specs/inspection-evidence/item-completion/spec.md`:
  SHA-256
  `6ba152feb897e1121c5dcf75a0fb2892286e3ba3c8b70d647ed547e5ee867415`.
- Prior composition review,
  `docs/operations/inspection-item-completion-gate1-composition-amendment-review.md`:
  SHA-256
  `b0953a316410659ed55959065f1dff7b8474ed83d0c621a0c550da7a01cbf62e`.

`openspec validate migrate-inspection-item-completion --strict` reports the
change valid.

## Prior finding closure

### CA-01 — CLOSED: clock is an exact executable dependency

The executable spec, design and delta requirement now agree on:

- exact interface method `InspectionEvidenceClock::now(): DateTimeImmutable`;
- application formatting of the returned instant as RFC3339 seconds using
  exact `Y-m-d\TH:i:sP`, which includes an explicit numeric offset;
- a production system-clock default whose `now` is read in `Europe/Moscow`;
- exactly one injected-clock call for a first-time command receipt;
- zero clock calls for replay, which returns the originally persisted
  `server_received_at` value.

This contract lets an independent MariaDB test inject a counting fixed clock,
derive exact receipt evidence without implementation knowledge, and detect both
multiple reads and an erroneous replay-time refresh.

## Preserved composition checks

- The exact creation seam remains
  `ProductionInspectionEvidenceFactory::create(mysqli,
  ProductionInspectionEvidenceConfig, ?InspectionEvidenceClock) ->
  InspectionEvidenceApplication`. The returned application implements the same
  approved `InspectionRecording` command and `InspectionEvidenceView` query
  interfaces; concrete repositories/adapters are not a test seam.
- Config remains deliberately narrow: only `processTablePrefix`. It neither
  discovers credentials nor admits legacy-table routing, artifact storage or
  unrelated InstallationProcess configuration.
- Factory validates the canonical ASCII `[A-Za-z0-9_]*` process prefix at the
  exact 0..25-byte boundary before any DB access. Thus empty and 25-byte valid
  prefixes are admissible, while 26-byte, non-ASCII and invalid-character input
  fails as configuration without connection access or mutation.
- The caller owns opening and closing the supplied `mysqli`; the application
  owns each command transaction on it. One application instance exclusively
  uses one connection for transaction state. Concurrent workers use distinct
  caller-owned connections and application instances, enabling the required
  true-overlap serialization test.
- After configuration validation the factory establishes `utf8mb4`. Creation
  performs no DDL, migration, schema repair, ledger write or business mutation.
  Missing/incompatible canonical v8 fails closed during application use; the
  slice introduces no v9 and no inspection-planning dependency.
- Authorization, replay/rejection precedence, append-only evidence, all-object
  scope, command/query result shapes and transaction ownership remain unchanged
  from the previously approved behavior contract.

## Verdict

No unresolved technical composition ambiguity remains in the exact artifacts
above. CA-01 is closed and all previously passing composition constraints remain
coherent with repository patterns and canonical v8. The amendment is
`READY_FOR_OWNER_APPROVAL`; production-composition Gate 2 work remains gated on
explicit owner approval of the exact executable-spec hash reviewed here.
