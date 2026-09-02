# Inspection section completion behavior evidence

Evidence snapshot: 2026-09-01. This is the current rapid-pilot oracle, not a
target application contract.

## Product basis and current divergence

The fast-pilot checklist spec requires a separate section-completion command by
the currently assigned construction-control engineer when all current-projection
items are complete, at least one non-revoked accepted photo exists and work is
open. Completion adds no progress weight.

Current server has a `section_completed` operation, but the browser silently
auto-enqueues it after item/photo changes and during local-state restoration;
there is no deliberate completion action. Server readiness counts historical
`item_completed` ids from a hard-coded map and active photo rows. It does not
consult correction/retraction projection, prior section completion or template
item definitions. These differences are `PILOT_ONLY` migration hazards.

## HTTP admission and validation order

Production POST routes are `/pilot/objects/{id}/checklist/operations` and the
construction-control alias. Processing resolves trusted identity, active user
and object card, obtains command resources and runs checklist runtime DDL before
checking opened object plus broad `checklist.edit` **or** current engineer.
Session, same-origin headers, object/user-bound CSRF, exact body length and JSON
content type are then required.

`ChecklistSync::accept` validates envelope UUIDs/time/base/section, checks the
global client-operation duplicate before object/payload semantics, locks one
working case, validates one effective template association and locks revision.
Only an ahead base conflicts; a lower stale base remains acceptable.

For `section_completed`, readiness is exactly:

- distinct historical `item_completed.item_id` count equals the hard-coded
  number of ids for the requested section; operation `section_id` is not part of
  that count; and
- at least one non-revoked `fm2_checklist_photos` row exists for the same
  case/section.

## State change and projection

An accepted command appends one `fm2_checklist_operations` row with null item,
empty JSON payload, actor/device/server times, base/accepted revision and
template id/version/hash, then advances `fm2_checklist_revisions`. It creates no
installer, photo/blob, process-event or task fact and adds no progress weight.

Projection iterates operations in insertion order and exposes only the latest
completion per section: client operation id, server time, accepted revision and
template. Raw repeated completions remain in history. Projection omits actor,
device time and base revision for the completion view.

## Replay, concurrency and invalidation hazards

- Exact replay is duplicate/no mutation at the original revision.
- Same id with changed object/section/body is also duplicate before semantic
  checks.
- A distinct lower-stale command is accepted; ahead conflicts.
- Distinct repeated or two concurrent same-base completions both append and
  advance revision; there is no already-completed guard.
- Revoking the last active photo later does not remove the old completion from
  projection. A new completion would reject, but the previous fact remains
  visibly completed.
- Because no product `completion_retracted` exists, every historical
  `item_completed` continues to satisfy readiness forever.

These are oracle observations, not target concurrency/invalidation policy.

## Rejection and mutation boundaries

Missing any hard-coded item or having zero active photos produces HTTP 422 and
no POST business mutation. Non-working/not-open or unauthorized requests can be
rejected at HTTP admission with 403; invalid template is 422; ahead revision is
409. GET/session acquisition initializes revision zero through projection, and
GET/post-response projection may backfill missing legacy installer snapshots
from current crew. A verifier must snapshot after GET so those read mutations
are not attributed to a rejected POST.

## Coverage gap and deterministic characterization

No focused verifier currently proves section-completion HTTP acceptance,
readiness, raw facts/projection, replay, repeated completion, concurrency or
post-completion photo-revoke behavior. Existing tests cover offline tokens,
ingredients or presentation incidentally.

A minimal private fixture can use section 8/item 42, one working case, fixed
current engineer without broad editor capability, one template association,
one crew member and a tiny valid PNG. Real HTTP GET supplies cookie/CSRF; real
POSTs accept item, photo and section facts. Raw rows prove no progress-weight
mutation. Isolated cases cover missing item/photo, revoked photo, non-working,
unauthorized and invalid template. Two loopback servers/clients with a barrier
prove current repeated same-base completion behavior. All observed auto-action,
hard-coded readiness, replay/stale/repeat/revoke behavior stays PILOT_ONLY.

## Classification

- `ACCEPTED` product basis: separate append-only section completion; all current
  items + one active accepted photo; open work; current assigned engineer; no
  additional progress weight; exact-delivery idempotency.
- `PILOT_ONLY`: automatic invisible enqueue; broad authorization; hard-coded
  historical readiness; payload-blind duplicate; stale acceptance; repeat/two-
  success completion; limited last-write projection; response details; runtime
  DDL and mutating reads.
- `UNKNOWN/NEEDS_GRILL` for target: correction or last-photo-revoke invalidation,
  photo-reuse confirmation, repeated/distinct concurrency result, payload
  conflict and queued command after reassignment. These do not block a strictly
  PILOT_ONLY characterization.

## Sources

- `docs/fmonitor-2-fast-pilot-checklist-spec.md:41-47, 176-187, 243-252`
- `app/PilotHttp/PilotE2ECoordinator.php:58-71`
- `app/PilotHttp/ChecklistSync.php:22-75`
- `app/PilotHttp/checklist.js`
- checklist verifiers under `rapid-pilot/`
