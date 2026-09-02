## Purpose

Даёт executable PILOT_ONLY oracle первого post-open inspection mutation и явно
отделяет current replay/revision/concurrency behavior от будущего target command
contract.

## ADDED Requirements

### Requirement: Characterization exercises the real public HTTP seam

Characterization SHALL выполнить production GET/session/CSRF exchange и real
same-origin POST `/pilot/objects/{id}/checklist/operations` с authenticated
synthetic current engineer против isolated private MariaDB facts. Expected rows
and projection MUST независимо происходить из literal Gate 1 fixture; direct
behavior-substitute SQL, downstream object call или printed transcript не
являются execution evidence.

#### Scenario: One item completion is accepted
- **WHEN** working case, valid template association, current registered crew и
  revision zero получают valid `item_completed` JSON через HTTP seam
- **THEN** HTTP/result сообщает accepted revision one
- **AND** independent audit доказывает exact operation, installer snapshots,
  revision and template facts plus matching projection

### Requirement: Accepted history and crew snapshot are observable

Characterization SHALL проверять actor/device/server time, normalized installer
payload, template id/version/hash и every selected crew snapshot. Later current
crew change MUST не переписывать stored completion rows.

#### Scenario: Crew changes after accepted completion
- **WHEN** registered order changes after the completion fact
- **THEN** stored installer snapshot remains byte-identical
- **AND** projection distinguishes historical installer from current crew

### Requirement: Current duplicate precedence is characterized without promotion

Characterization SHALL зафиксировать exact replay и same-id changed-payload
result as PILOT_ONLY. It MUST сравнивать full before/after state and MUST NOT
describe payload-unaware duplicate as target idempotency.

#### Scenario: Exact serial replay is a no-op
- **WHEN** exact accepted envelope is repeated serially
- **THEN** current seam reports duplicate at original revision
- **AND** operation, installer, revision and projection facts are byte-identical

#### Scenario: Same id with changed payload is also duplicate
- **WHEN** accepted client operation id is reused on the same object with another
  item or installer while envelope shape/session remain valid
- **THEN** current seam reports duplicate at original revision before those
  changed semantics are validated
- **AND** no fact changes; target payload-conflict policy remains separate

### Requirement: Current revision and concurrency behavior is executable

Characterization SHALL distinguish lower stale base from ahead base through HTTP
and SHALL use two independent loopback server/client processes, DB connections
and a parent start barrier for two different same-base commands. Final evidence
MUST be winner-neutral where order can vary.

#### Scenario: Lower stale base is accepted
- **WHEN** current revision is one and a new valid operation submits base zero
- **THEN** pilot accepts it as revision two
- **AND** both append-only operation facts remain

#### Scenario: Ahead base conflicts without mutation
- **WHEN** current revision is two and a new valid operation submits base three
- **THEN** pilot reports conflict with current revision two
- **AND** all relevant rows remain byte-identical

#### Scenario: Two same-base commands serialize
- **WHEN** two distinct valid operations concurrently submit the same current
  base through independent connections
- **THEN** current pilot accepts both as consecutive revisions in either order
- **AND** no partial installer/operation/revision state exists

### Requirement: Rejection mutation boundaries are exact

Characterization SHALL exercise one independently isolated example for
non-working case, invalid template association, item outside section and
installer outside current crew. Exact translated messages MUST NOT be promoted;
stable HTTP result category and exact before/after rows are observable. Required
session-acquisition GET currently initializes revision row zero through
projection for every scenario. The rejected POST SHALL create no operation or
installer fact and SHALL not advance that revision. This read/request-side
initialization is PILOT_ONLY, not target-approved audit behavior.

#### Scenario: Four current validation boundaries reject
- **WHEN** each otherwise-valid operation violates exactly one covered
  case/template/item/crew precondition
- **THEN** current HTTP seam reports 403 for non-working admission and 422 for
  template, item and crew domain rejections
- **AND** operation/installer facts remain absent, case/template/workforce facts
  remain byte-identical, and the GET-created revision remains exactly zero in
  every isolated scenario

### Requirement: Harness is deterministic and bounded

Verifier SHALL use collision-resistant caller-supplied namespaces, refuse every
occupied owned name before mutation, preserve ambient decoys and clean only an
explicit owned-name set on success/failure. Setup failure MUST be distinct from
RED assertion and regression failure; secrets and generated identifiers MUST
NOT enter normalized transcript.

#### Scenario: Two clean runs produce the same evidence
- **WHEN** complete verifier runs twice with distinct unoccupied tokens
- **THEN** normalized transcript is byte-identical and independent DB/process
  evidence proves every behavioral action
- **AND** no owned artifact survives while ambient decoys remain unchanged

### Requirement: Legacy projection backfill is characterized as a read mutation

Characterization SHALL isolate one pre-attribution completion row with no
installer snapshot and SHALL prove that the current HTTP projection fills it
from current crew using `pilot_backfill_current_order`. This MUST be labelled
PILOT_ONLY and MUST NOT satisfy target append-only/read-only projection rules.

#### Scenario: Reading a legacy completion manufactures attribution
- **WHEN** authenticated admitted actor requests projection for a legacy
  completion lacking installer rows
- **THEN** current pilot inserts current-crew installer snapshots with the
  backfill source and returns them in projection
- **AND** operation row remains unchanged; target migration receives an explicit
  follow-up requirement to remove read-side history mutation

### Requirement: Authorization and integrity exclusions remain explicit

This characterization MUST exercise only one fixed current-engineer admission
path and MUST NOT approve current broad HTTP authorization or choose target
capability/object scope. Projection-side legacy attribution backfill, runtime
DDL and hard-coded item catalogue remain PILOT_ONLY. GRILL-003 continues to
block target authorization only.

#### Scenario: Target migration consumes the oracle
- **WHEN** `migrate-inspection-item-completion` compares target behavior with the
  characterization
- **THEN** accepted fact/history shape may be preserved where approved
- **AND** payload conflict, strict expected revision, one-winner concurrency,
  exact authorization and read-only projection MUST be added/retained as target
  normative requirements and RED expectations rather than characterization
  regressions
