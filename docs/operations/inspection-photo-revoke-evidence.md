# Inspection photo revoke and re-upload evidence

Date: 2026-09-01  
Scope: discovery only; current rapid-pilot behavior, not an approved target contract.

## Sources inspected

- `PRODUCT.md`, `CONTEXT.md`, `docs/fmonitor-2-pilot-spec.md`, and
  `docs/fmonitor-2-pilot-data-model.md`.
- `app/PilotHttp/ChecklistSync.php`, `app/PilotHttp/ChecklistView.php`,
  `app/PilotHttp/checklist.js`, and checklist dispatch in
  `app/PilotHttp/PilotE2ECoordinator.php`.
- PB-05, GRILL-003, and GRILL-006 in `docs/operations/`.
- Existing focused upload, rejection, and limit/concurrency specifications,
  tests, verifiers, and evidence.

## Actor, entry points, and current authorization

The browser presents an `Отозвать фото` button to every user for whom the
checklist is enabled. The HTTP boundary admits an active user when the
installation case is open and either broad `checklist.edit` access is present
or the user id equals the card's current control engineer. CSRF/session checks
are then applied. A revoke is posted as a normal checklist operation to
`/pilot/objects/{id}/checklist/operations`.

The public behavior oracle is `ChecklistSync::accept(...)`, with
`ChecklistSync::projection(...)` as its public observation seam. The
application object itself accepts an already admitted `HttpUser` and performs
no capability or current-assignment check. Exact target authorization and the
queued-operation policy after reassignment therefore remain GRILL-003.

## Exact current server behavior

A `photo_revoked` envelope passes the common operation validation: canonical
client and device UUIDs, parseable device time, non-negative integer base
revision, and a hard-coded section id 1..8. The command then locks the working
installation case, validates its template association, locks/reads the current
revision, and rejects only a base revision ahead of the server.

The photo id must be an integer. The server updates exactly one active row when
`id`, installation case, and section all match, setting `revoked_at` to the
injected server time. It then appends a `photo_revoked` operation whose payload
contains only `photoId`, records actor, device/server time, base/accepted
revision and template snapshot identity, increments the case revision, and
commits. The response is `accepted` with the new revision. Projection omits the
revoked photo but retains all operation history internally.

A missing id, wrong case/section, or already-revoked photo returns `rejected`
without changing the photo row, operation log, revision, or filesystem. Exact
Russian copy is presentation-only. The blob is never removed. There is no
confirmation dialog, reason, caption, `revoked_by` column, or explicit link
from the photo row to the revoke operation; actor/reason evidence is available
only through the appended operation, and reason is absent.

## History, completion, and browser behavior

- History is partly append-only: upload and revoke commands are appended, but
  the active projection is implemented by mutating `fm2_checklist_photos.revoked_at`.
  The original upload metadata and blob remain.
- Revoking the last active photo does not retract an already accepted
  `section_completed` operation. Projection continues to expose that completion
  fact while exposing no active photo. The browser visually derives section
  readiness from active photos, but its `completed` map still contains the old
  server fact. Target consistency/correction semantics are not defined.
- The browser hides a photo immediately before server acceptance and has no
  confirmation or undo. A rejected revoke remains locally hidden because the
  local `revoked` flag is not restored from projection.
- Clicking remove on a locally queued, not-yet-accepted upload merely hides it.
  It creates no revoke operation and does not cancel the queued upload, so the
  upload can later be accepted by the server while remaining hidden in that
  browser state.

These UI outcomes are defects/oracle observations, not requirements.

## Idempotency, concurrency, and re-upload

- Sequential exact replay after a committed revoke returns `duplicate` with
  the original accepted revision and causes no mutation.
- A new operation id targeting an already-revoked photo is rejected with zero
  mutation.
- Case-row locking serializes concurrent revokes. Two different operation ids
  targeting one active photo yield one acceptance and one rejection.
- A concurrent exact replay can pass the pre-transaction duplicate lookup in
  both callers. After the winner commits, the loser sees an inactive photo and
  returns `rejected`, because operation identity is not rechecked after taking
  the case lock. Thus concurrent exact replay is not reliably idempotent.
- Active-photo duplicate lookup excludes revoked rows, but the schema's unique
  key on `(installation_case_id, section_id, sha256)` is unconditional.
  Re-uploading identical bytes after revoke passes the active lookup and then
  fails at photo-row insert with a MariaDB unique-key exception. The public
  seam throws rather than returning a domain result; no operation/revision is
  committed and the pre-existing content-addressed blob remains.
- Re-uploading different valid content after revoke can be accepted. Revoked
  rows do not count toward the pilot limit of ten active photos.

## Persistence effects

- Updated: the matching `fm2_checklist_photos.revoked_at` value.
- Inserted: one `fm2_checklist_operations` row with type `photo_revoked`.
- Updated/created: `fm2_checklist_revisions` for the next accepted revision.
- Locked/read: installation case, template association/snapshot, operation id,
  photo row, and revision.
- Unchanged: the content-addressed `<storageRoot>/checklist/<sha256>.bin` file,
  upload metadata, and prior operations.

## Classification

- **PRODUCT_ACCEPTED:** only current photo evidence contributes to section
  readiness; original upload evidence/history must not be silently erased; a
  state change belongs behind an explicit Inspection Evidence application seam.
- **PILOT_ONLY:** hard-coded sections, exact messages, broad HTTP authorization,
  stale-base acceptance, mutable `revoked_at` projection, blob retention policy,
  no confirmation/reason, current retry/local-hide behavior, and numeric limit.
- **UNKNOWN / needs product decision:** who may revoke; whether a reason and
  confirmation are mandatory; whether author/supervisor rules apply; what must
  happen when the last photo of a completed section is revoked; whether identical
  bytes may be re-uploaded as a new fact; retention/deletion policy; queued revoke
  after reassignment; and correction/undo semantics.

## Narrow READY characterization

`CHARACTERIZE-INSPECTION-PHOTO-REVOKE-001` can proceed without product answers
as an explicitly `PILOT_ONLY` oracle characterization:

1. upload one valid photo through `ChecklistSync::accept(...)`;
2. revoke it through the same public seam and assert accepted revision `2`;
3. assert projection has no active photos while SQL audit proves the upload row,
   blob, upload operation, and appended revoke operation remain;
4. exact sequential replay returns duplicate at revision `2` with zero mutation;
5. a fresh operation against the already-revoked photo is rejected with zero
   mutation;
6. identical-content re-upload through the public seam throws the observed SQL
   unique-key failure with zero additional DB mutation and unchanged blob.

The characterization must not approve target authorization, reason/confirmation,
last-photo completion behavior, re-upload policy, blob retention, concurrent
replay behavior, or exact messages. A target `InspectionRecording::revokePhoto`
slice remains blocked by the decisions below.

## Recommended GRILL additions and blocked slices

Add a compact revoke package (or extend GRILL-003/006 without mixing
implementation detail):

1. Must revoke require `inspection.photo.revoke` plus current assignment?
   Recommendation: yes; supervisor override is a separate audited command.
2. Must the actor confirm and provide a reason?
   Recommendation: confirmation plus mandatory bounded reason, stored with the
   append-only revoke fact.
3. What happens when revoking the last active photo of a completed section?
   Recommendation: reject ordinary revoke until replacement is supplied; allow
   an elevated correction command that appends a section-readiness correction.
4. May identical bytes be uploaded after revoke?
   Recommendation: yes, as a new evidence fact with a new identity while retaining
   the prior upload/revoke history; content deduplication may reuse blob storage.
5. How long are revoked blobs retained?
   Recommendation: retain through the test-user release and define deletion only
   in a later approved evidence-retention policy.

Exactly blocked: target acceptance/RED/implementation for
`INSPECTION-PHOTO-REVOKE-001` and a revoke/re-upload offline UX slice. The narrow
PILOT_ONLY characterization above is READY.
