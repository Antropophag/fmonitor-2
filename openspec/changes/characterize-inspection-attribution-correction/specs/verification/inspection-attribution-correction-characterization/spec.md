## Purpose

Даёт executable PILOT_ONLY oracle текущей смены отображаемой атрибуции пункта и
не смешивает её с утверждённой продуктовой коррекцией `completion_retracted`.

## ADDED Requirements

### Requirement: Characterization exercises the real public HTTP seam

Characterization SHALL выполнить production-composed GET/session/CSRF exchange
и same-origin POST `item_installers_changed` от фиксированного synthetic current
engineer против isolated private MariaDB facts. Response, raw rows и projection
MUST происходить из одного real exchange; direct call, SQL substitute или
напечатанный transcript не являются evidence.

#### Scenario: Existing completion receives current pilot attribution change
- **WHEN** working case с valid template, crew A/B, revision one и ранее
  attributed `item_completed` получает valid correction selecting B
- **THEN** HTTP seam сообщает accepted revision two
- **AND** independent audit доказывает новую operation и B snapshot с source
  `correction`, неизменные исходные completion rows и projection на B

### Requirement: Pilot attribution change does not masquerade as product correction

Characterization SHALL доказать, что current command не содержит reason или
reference, не создаёт `completion_retracted`, не снимает completed state и не
reopens section. Эти результаты MUST быть помечены `PILOT_ONLY`; change не SHALL
утверждать target seam или correction semantics.

#### Scenario: Original completion continues to drive progress
- **WHEN** attribution change accepted after an item completion
- **THEN** original completion remains byte-identical and continues to count as
  completed
- **AND** only latest visible attribution changes; target retraction remains an
  independent owner-approved slice

### Requirement: Stored correction snapshots and crew drift are observable

Characterization SHALL фиксировать actor/device/server times, submitted-order
installer payload, template triple и every correction installer snapshot. Later
registered crew/workforce change MUST не переписывать stored rows.

#### Scenario: Crew changes after attribution change
- **WHEN** latest registered order and current workforce change after acceptance
- **THEN** stored completion and correction snapshots remain byte-identical
- **AND** projection exposes historical correction participants separately from
  current crew according to current pilot behavior

### Requirement: Duplicate precedence is characterized without promotion

Characterization SHALL проверять exact replay и same-id changed-payload replay
against complete before/after state. It MUST NOT call payload-unaware duplicate
target idempotency.

#### Scenario: Exact replay is a no-op
- **WHEN** the accepted correction is delivered again unchanged
- **THEN** pilot reports duplicate at revision two
- **AND** no operation, installer, revision or projection fact changes

#### Scenario: Changed payload with the same id is also duplicate
- **WHEN** the accepted id is reused with another installer or item
- **THEN** pilot reports duplicate before changed semantics are validated
- **AND** no fact changes and target payload-conflict policy remains excluded

### Requirement: Current revision and concurrency behavior is executable

Characterization SHALL distinguish lower-stale from ahead base and SHALL use two
independent loopback server/client processes, DB connections and a parent start
barrier for two different same-base corrections. Variable ordering MUST be
expressed winner-neutrally.

#### Scenario: Lower stale correction is accepted
- **WHEN** a valid distinct correction submits a base below current revision
- **THEN** pilot appends it at the next revision
- **AND** all earlier operations remain immutable

#### Scenario: Ahead correction conflicts without mutation
- **WHEN** a valid distinct correction submits a base above current revision
- **THEN** HTTP reports conflict with current revision
- **AND** correction-owned rows and revision remain byte-identical

#### Scenario: Two same-base corrections both serialize successfully
- **WHEN** two distinct corrections concurrently submit the same current base
- **THEN** pilot accepts both at consecutive revisions in either order
- **AND** latest inserted correction owns visible attribution with no partial row

### Requirement: Rejection mutation boundaries are exact

Characterization SHALL isolate at least: missing earlier completion, wrong
item/section, empty or duplicate installer list, installer outside current crew,
non-working case, invalid template and unauthorized actor. Required GET-created
revision zero and any explicit legacy backfill MUST be separated from POST
effects. A rejected POST SHALL add no correction operation/installer and SHALL
not advance revision.

#### Scenario: Domain and admission violations reject independently
- **WHEN** each otherwise-valid fixture violates exactly one covered boundary
- **THEN** current seam returns its stable HTTP category
- **AND** raw before/after evidence proves the exact no-correction mutation
  boundary without promoting message text or current authorization

### Requirement: Read-time legacy backfill remains isolated

Characterization SHALL separately prove that current projection may insert
current-crew installer snapshots for a legacy completion lacking attribution.
This behavior MUST remain `PILOT_ONLY` and MUST NOT satisfy target append-only
or read-only projection requirements.

#### Scenario: Projection manufactures missing legacy attribution
- **WHEN** a real admitted GET reads a legacy completion without installer rows
- **THEN** pilot inserts `pilot_backfill_current_order` rows from current crew
- **AND** transcript distinguishes this read mutation from correction facts

### Requirement: Harness is deterministic, private and bounded

Verifier SHALL use caller-supplied collision-resistant namespaces, reject every
occupied owned name before mutation, preserve ambient decoys, own/reap every
child process and clean only a closed owned set on all exits. Setup failure,
intended RED and regression failure MUST remain distinct; normalized transcript
MUST exclude secrets and generated ids.

#### Scenario: Two clean runs yield equal normalized evidence
- **WHEN** verifier runs twice with distinct clean tokens
- **THEN** normalized transcript is byte-identical and backed by raw evidence
- **AND** no owned SQL/process/artifact state survives while decoys are unchanged

### Requirement: Target authorization and correction semantics remain excluded

Characterization MUST exercise only one current-engineer admission branch and
MUST NOT approve broad `checklist.edit`, an exact target capability, assignment
scope, actual-installer editing, reason/reference rules, supersession,
retraction, payload conflict or one-winner concurrency.

#### Scenario: Target migration consumes only explicit contrast
- **WHEN** future `INSPECTION-ATTRIBUTION-CORRECT-001` uses this oracle
- **THEN** it can preserve independently approved history/snapshot properties
- **AND** every unresolved target rule receives its own approved executable spec
  and RED rather than becoming a characterization regression
