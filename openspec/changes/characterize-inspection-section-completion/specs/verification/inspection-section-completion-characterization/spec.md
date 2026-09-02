## Purpose

Даёт executable PILOT_ONLY oracle текущего завершения checklist-раздела и
отделяет принятые prerequisites/history от pilot replay/invalidation defects.

## ADDED Requirements

### Requirement: Characterization exercises the real public HTTP seam

Characterization SHALL perform production-composed GET/session/CSRF and
same-origin POST exchanges against isolated private facts. One fixed synthetic
current engineer without broad editor capability SHALL submit item completion,
accepted photo and `section_completed`. Response, raw rows and projection MUST
come from real exchanges; direct calls, SQL behavior substitutes or printed
transcripts are not evidence.

#### Scenario: Ready section is completed
- **WHEN** a working section has every required item fact and one active accepted
  photo, then receives valid `section_completed`
- **THEN** HTTP accepts one section-completion operation at the next revision
- **AND** independent audit proves exact history/template/actor/times/projection
  and no additional item progress or unrelated fact

### Requirement: Readiness ingredients and no-weight behavior are observable

Characterization SHALL use the smallest literal section fixture and SHALL prove
the current server requires every hard-coded item id plus one non-revoked photo.
It MUST show that section completion itself changes no item count or progress
operand. Hard-coded readiness remains PILOT_ONLY rather than template-native
target behavior.

#### Scenario: Completion adds no progress weight
- **WHEN** a ready section completion is accepted
- **THEN** item and active-photo facts remain byte-identical
- **AND** only completion history/revision/projection changes

#### Scenario: Missing item or active photo rejects
- **WHEN** isolated otherwise-valid cases omit the required item or active photo
- **THEN** current HTTP seam rejects without a completion fact or revision advance

### Requirement: Duplicate and revision behavior is characterized without promotion

Characterization SHALL prove exact replay, same-id changed semantics,
lower-stale and ahead behavior with full before/after evidence. Payload-blind
duplicate and stale acceptance MUST remain PILOT_ONLY.

#### Scenario: Exact and changed-payload replay are duplicate
- **WHEN** an accepted completion id is repeated exactly and then reused with a
  changed section/object/body
- **THEN** pilot reports duplicate at the original revision for both
- **AND** no fact changes

#### Scenario: Lower stale appends and ahead conflicts
- **WHEN** distinct completions submit respectively lower and higher bases than
  current revision while readiness remains true
- **THEN** lower stale appends at the next revision and ahead conflicts
- **AND** the conflict has zero mutation

### Requirement: Repeated and concurrent completion facts are executable hazards

Characterization SHALL prove that current server has no already-completed guard.
It SHALL use two independent loopback server/client processes, connections,
sessions/tokens and a parent barrier for distinct same-base commands. Ordering
MUST be winner-neutral.

#### Scenario: Distinct repeated completion appends
- **WHEN** an already completed section receives a new valid operation id
- **THEN** pilot appends another completion/revision
- **AND** projection exposes the later operation while raw prior history remains

#### Scenario: Two same-base completions both succeed
- **WHEN** two distinct valid completion commands race at one current base
- **THEN** both are accepted at consecutive revisions in either order
- **AND** final projection exposes the operation with the later revision and no
  partial fact exists

### Requirement: Post-completion photo revoke inconsistency is isolated

Characterization SHALL prove the current result after the last active photo is
revoked through the real public seam. Persistent completed projection after
readiness becomes false MUST be labelled PILOT_ONLY and MUST NOT choose target
invalidation or photo-reuse semantics.

#### Scenario: Revoking last photo leaves completion visible
- **WHEN** the only active section photo is revoked after accepted completion
- **THEN** photo becomes inactive and a new completion would fail readiness
- **AND** current completed-section projection still exposes the old completion

### Requirement: Admission and domain rejection boundaries are exact

Characterization SHALL isolate non-working/not-open, unauthorized actor,
invalid template, missing item, missing photo and revoked-only photo. Required
GET revision initialization or legacy backfill MUST be captured before POST. A
rejected completion SHALL create no operation and SHALL not advance revision.

#### Scenario: Independent invalid fixtures reject without completion mutation
- **WHEN** each otherwise-valid fixture violates exactly one covered boundary
- **THEN** current stable HTTP category is observed
- **AND** raw before/after evidence proves zero completion mutation without
  promoting message text or broad authorization

### Requirement: Harness is deterministic, private and bounded

Verifier SHALL use caller-supplied collision-resistant namespaces, reject every
occupied owned name before mutation, preserve ambient decoys, own/reap each
child process and clean only a closed owned set on every exit. Setup failure,
intended RED and regression failure MUST be distinct; transcript MUST exclude
secrets and generated ids.

#### Scenario: Two clean runs yield the same normalized evidence
- **WHEN** complete verifier runs twice with different clean tokens
- **THEN** normalized output is byte-identical and backed by raw evidence
- **AND** owned state is absent while decoys/unrelated state remain unchanged

### Requirement: Target semantics and browser automation remain excluded

Characterization MUST NOT approve the current automatic browser enqueue, broad
editor admission, hard-coded readiness, repeated completion, payload/revision
policy, correction/photo invalidation or target public seam. Browser source
evidence SHALL be recorded separately from server execution evidence.

#### Scenario: Future target work consumes only explicit contrast
- **WHEN** a future `InspectionRecording::completeSection` change cites this
  characterization
- **THEN** approved prerequisite/history properties can be preserved
- **AND** deliberate UX, exact authorization, template-native readiness,
  idempotency/concurrency and invalidation receive owner-approved specs and REDs
