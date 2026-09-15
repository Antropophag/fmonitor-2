## Why

Native Yii2 checklist operations other than `item_completed` currently treat any reused `client_operation_id` as a successful duplicate, even when the object, operation type, actor/device context, or meaningful payload differs. This can falsely acknowledge a different command and must be corrected before field use while preserving the authorized retry behavior delivered by #130.

Behavior slice: replay admission for existing non-`item_completed` checklist operations. Actor: an authenticated user already authorized by the existing command and read policies. Source oracle: issue #131 and the native persisted operation contract on current `main`; `rapid-pilot` is explicitly not an oracle or compatibility target. Target public seam: the existing Yii2 checklist operation/photo HTTP endpoints delegating to `ChecklistSync::accept(...)`. Release value: a lost-response retry remains safe, while operation-ID collisions cannot masquerade as saved work.

## What Changes

- Define a per-operation canonical replay fingerprint from already persisted operation context and normalized meaningful payload.
- Require exact installation case, operation type, actor, device, section/item context, and the applicable normalized payload before returning `duplicate`.
- Return the existing conflict/rejection envelope with no writes when a reused ID represents a different intent.
- Apply the same equivalence decision in the ordinary duplicate path and integrity-exception/race recovery.
- Preserve the existing native `item_completed` replay implementation and #130 read-authorization ordering.
- Prove exact retry, context/type/payload conflicts, authorization, persistence counts, and both race outcomes through the existing public seam against a disposable real database.

Explicit non-goals: `rapid-pilot` inspection or compatibility, issues #35/#52/#132/#141, offline/client/service-worker changes, schema migration, generic idempotency/event/command infrastructure, UI/Vue redesign, or broad refactoring of checklist owners.

## Capabilities

### New Capabilities

- `inspection/checklist-operation-replay-equivalence`: strict replay admission for existing native Yii2 checklist operations other than `item_completed`.

### Modified Capabilities

None.

## Impact

The intended production change is bounded to `app/InspectionEvidence/MariaDbYiiChecklistMutation.php`. A focused disposable-real-DB Yii2 public-seam verifier, a stable executable specification, OpenSpec lifecycle artifacts, and delivery/review evidence are added. Existing tables already store every required datum, so no schema, client, service-worker, UI, or deployment change is planned.
