# Inspection attribution correction behavior evidence

Evidence snapshot: 2026-09-01. This document describes the current rapid-pilot
oracle. It is not approval of target correction semantics.

## Product contrast

`docs/fmonitor-2-fast-pilot-checklist-spec.md` defines correction as
`completion_retracted`: it references the original completion, requires a short
reason, makes the item incomplete and reopens a completed section. It also
defers assigning actual installers to individual items.

Current `item_installers_changed` does something else: it appends another
operation whose installer snapshots replace the visible attribution for the
item. It has no reason or reference, does not retract completion, does not
change progress and does not reopen a section. Therefore it is a `PILOT_ONLY`
behavioral oracle, not an accepted implementation of product correction.

## Current admission and HTTP boundary

- POST routes:
  `/pilot/objects/{id}/checklist/operations` and
  `/pilot/construction-control/objects/{id}/checklist/operations`.
- Request processing resolves a trusted identity and active user, reads the
  object card, obtains command resources and executes runtime schema DDL before
  checking mutation authorization.
- Pilot authorization is opened object plus broad `checklist.edit` **or** the
  card's current control engineer. `ChecklistSync` has no authorization policy.
- Mutation then requires an existing session, matching same-origin headers,
  valid per-user/per-object CSRF token, exact body length and JSON content type.
- Stable result categories are HTTP 200 accepted/duplicate, 409 conflict, 422
  domain rejection, and admission/protocol 400/401/403/404/405/413/503. Exact
  translated messages are not target requirements.

## Current command behavior

`ChecklistSync::accept` validates the envelope, then performs a global duplicate
lookup before object, type, item, payload or template semantics. Inside the
transaction it requires one working case, one coherent effective template,
`baseRevision <= current revision`, an item in the hard-coded section, a
non-empty string-unique installer list, every installer in the latest registered
order crew, and any earlier `item_completed` for the item.

An accepted correction appends:

- one `fm2_checklist_operations` row with actor/device/server times,
  base/accepted revisions, template triple and installer-id payload;
- one immutable `fm2_checklist_operation_installers` snapshot per selected
  installer with `assignment_source=correction`;
- the next `fm2_checklist_revisions` value.

The original completion and its installer rows are not updated or deleted.
Projection iterates both types in insertion order and exposes only the latest
row for an item, so correction replaces visible actor/time/template/installers.
Progress still counts existence of `item_completed`; repeated corrections do
not retract or reopen anything. Projection can separately manufacture missing
legacy installer rows from current crew using
`pilot_backfill_current_order`, including after a rejected POST.

## Replay, revision and concurrency observations

- Exact replay returns duplicate at the original accepted revision with no new
  fact.
- Same client operation id with changed object/type/item/installers/base also
  returns duplicate before changed semantics are checked.
- A lower stale base is accepted as the next revision; only an ahead base
  conflicts.
- Two distinct same-base corrections serialize on the case lock and both
  succeed at consecutive revisions. The later inserted row wins projection.
- Same-id concurrent delivery yields one accepted and one duplicate, without
  comparing payloads.

These observations are migration contrasts. Target payload-aware idempotency,
expected-revision policy, one-winner policy and correction semantics require an
approved application contract.

## Existing verification gap and fixture boundary

Existing verifiers SQL-seed current-crew projection or inspect offline queue
priority; none executes `item_installers_changed` through real HTTP/session/CSRF
and audits raw history.

A deterministic private fixture can use one working case, exact object card,
current engineer without broad editor capability, one template association,
registered crew A/B, revision zero, and an attributed prior completion. A
production-composed GET obtains cookie/CSRF, followed by real POST exchanges.
Raw before/after evidence must cover original completion immutability, correction
snapshots/projection, replay, stale/ahead, two-client concurrency, isolated
rejections and a separate legacy-backfill case. Required GET revision-zero and
projection-backfill mutations must be accounted for separately from rejected
POST mutation.

## Classification

- `PRODUCT_ACCEPTED`: append-only history; original fact preservation; immutable
  attribution snapshots; server-side authorization recheck.
- `PILOT_ONLY`: operation name/meaning, hard-coded catalogue, broad OR
  authorization, payload-unaware replay, stale/two-success concurrency,
  last-write projection, missing reason/reference, current-workforce overlay,
  dismissed-crew acceptance, response text/status and read-time backfill.
- `NEEDS_GRILL`: whether target attribution adjustment exists separately from
  `completion_retracted`; its exact authorization, reason/reference,
  supersession/projection and concurrency rules. This blocks only target
  `INSPECTION-ATTRIBUTION-CORRECT-001`, not characterization.

## Sources

- `app/PilotHttp/PilotE2ECoordinator.php:58-71`
- `app/PilotHttp/ChecklistSync.php:22-75`
- `app/PilotHttp/checklist.js`
- `docs/fmonitor-2-fast-pilot-checklist-spec.md:41-47, 156-162, 310-316,
  355-376`
- `rapid-pilot/verify-checklist-current-crew.php`
- `rapid-pilot/verify-checklist-offline-prefetch.php`
