# Inspection item-completion behavior evidence

Evidence cut: 2026-09-01. This records current rapid-pilot behavior for
`item_completed`; it is not Gate 1 approval and does not replace the draft target
contract under `migrate-inspection-item-completion`.

## Release and journey gap

The automated golden journey currently proves queue → assignment-order prepare
→ artifact download → registration → work opening and then only renders the text
“Инженеру: провести первую инспекцию”. It performs no engineer state-changing
action. Evidence is `specs/PILOT-E2E-FLOW-001.md`,
`tests/InstallationProcess/pilot_e2e_flow_001_test.php` and
`tools/verification/run.sh`.

A focused PILOT_ONLY item-completion characterization is therefore the smallest
READY discovery slice that crosses the first post-open mutation. It does not
decide inspection cadence, installation completion, premium or payment.

## Current seams and authorization

The business mutation is `ChecklistSync::accept(objectId, actor, operation)`.
Production HTTP reaches it through `PilotE2ECoordinator::checklist` at POST
`/pilot/objects/{id}/checklist/operations`; construction-control pages enqueue to
that same canonical route. Offline IndexedDB/service-worker behavior is a client
queue, not another server seam.

HTTP performs, in order: route/method; trusted server identity; active local
user; object lookup; command resources and `ChecklistSync::ensureSchema`; then
`opened && (checklist.edit OR current control engineer)`; body limit; session,
same-origin and CSRF; JSON parsing; `accept`. Thus DDL runs before authorization.
Accepted/duplicate map to 200, conflict to 409, ordinary rejection to 422,
malformed input to 400, auth to 401/403 and infrastructure failure to 503.

`ChecklistSync` itself has no authorization. No current path checks
`inspection.item.complete`. The target draft requires an active user with that
exact capability, while GRILL-003 recommends exact capability plus current
assignment. Those two target sources must be reconciled by the owner; current
pilot's broad OR-policy is only characterizable as PILOT_ONLY.

## Accepted envelope and facts

Before case lookup the pilot requires lowercase RFC4122 v4 UUIDs for client and
device, a parseable nonempty device time of at most 40 bytes, integer
`baseRevision >= 0`, and a section in the hard-coded 1..8 map. `item_completed`
then requires an integer item belonging to that hard-coded section and a
nonempty installer-id array unique after string normalization. Every installer
must be in the latest registered-order crew. Extra keys are ignored.

For a working case with one exact valid template association, success atomically:

- inserts one operation with case, client/device ids, type, section/item, actor,
  device/server time, submitted base and next accepted revision;
- stores canonical payload `{"installerTabIds":["..."]}` and template
  snapshot id/version/hash;
- inserts one immutable installer snapshot per selected current-crew member with
  `assignment_source=completion`;
- advances the case checklist revision.

There is no separate process event/run audit. Crew resolution uses the latest
registered assignment order, but current workforce values override order
snapshots. Server-side acceptance does not reject a dismissed installer if that
person remains in the current registered crew.

Projection exposes actor/times/revision/template/installer snapshots and current
assignment/employment comparison. A later operation for the same item overwrites
the displayed item projection, although prior operation rows remain.

## Current idempotency and concurrency

These observations materially contradict the draft target and must not be
silently preserved as target requirements:

1. Duplicate lookup happens immediately after envelope validation and before
   object, case state, type, template or payload checks. The same client UUID
   returns the original revision even for another object/type/item/installers or
   after the case becomes ineligible. Replay is payload-unaware.
2. Only `baseRevision > current` conflicts. A lower stale base is accepted and
   receives the next revision.
3. Case/revision locking serializes concurrent commands, but because stale lower
   bases remain valid, two distinct operations with the same expected base can
   both succeed sequentially as revisions N+1 and N+2.
4. A new operation id may complete the same item again. It appends another row;
   the projection shows the later one.
5. A duplicate-key race is converted to duplicate after rollback, still without
   comparing payload.

## Rejection and integrity boundaries

Current stable rejection candidates are: malformed envelope; absent/non-working
case; absent, multiple or inconsistent template association; template/device/
server date before validity; item outside the hard-coded section; empty,
duplicate or non-current installer selection; and ahead revision. The code does
not prove that the item is present in template payload and does not compare
device time with association `effective_at` beyond UTC calendar-date checks.

`projection()` is not read-only for pre-attribution legacy completions: it fills
missing installer snapshots from the **current** crew with
`pilot_backfill_current_order`. This can manufacture historical attribution at
read time and is a separate integrity/ownership risk, not an accepted invariant.

## Schema ownership

`ChecklistSync::ensureSchema` creates revisions, operations, installer snapshots
and photo tables and performs two runtime ALTER upgrades. It is called from page,
mutation and sync-context paths. The prepared
`INSPECTION-EVIDENCE-SCHEMA-001` instead requires canonical ownership and
fail-closed runtime behavior, but remains blocked behind earlier migrations.

## Existing verifier coverage

- `verify-native-checklist-template-binding.php` covers missing/mismatched/date
  template rejection, one accepted template triple and an exact replay no-op.
- `verify-checklist-current-crew.php` seeds completion via SQL and verifies only
  projection/history after crew change.
- `verify-checklist-offline-behavior.mjs` covers per-user service-worker cache
  isolation and logout, not operation acceptance or replay.

No verifier currently proves a real HTTP item-completion exchange, payload-aware
or payload-unaware changed replay, stale acceptance, concurrent result, full raw
facts, zero-mutation rejections, or projection-side backfill.

## Recommended next slice

`CHARACTERIZE-INSPECTION-ITEM-COMPLETION-001` is READY for planning and Gate 1
draft. Through the real HTTP seam and private fixtures it should independently
prove:

1. accepted operation, exact operation/installer/revision/template facts and
   projection;
2. exact replay and changed-payload same-id duplicate precedence;
3. lower stale-base acceptance and ahead-base conflict;
4. two distinct same-base commands through real concurrent connections;
5. one compact set of non-working/template/item/crew zero-mutation rejections;
6. historical crew snapshot preservation and the separately labelled legacy
   projection-backfill mutation;
7. current broad editor/current-engineer/neither HTTP policy as PILOT_ONLY;
8. unchanged structures, deterministic transcript, bounded cleanup and decoy
   preservation.

If this is too large for one reviewed cycle, split auth and legacy-backfill into
follow-up characterizations; do not drop the replay/stale/concurrency contrast,
because those directly protect the target RED from false compatibility.

## Classification

- `PRODUCT_ACCEPTED` context: completion evidence is append-only, attributed to
  actor/template/installers, and belongs behind one application seam.
- `PILOT_ONLY`: hard-coded item map, broad OR authorization, payload-unaware
  duplicate, stale-base acceptance, both concurrent commands succeeding,
  repeated completion projection overwrite and current response wording/status.
- `UNKNOWN`: target current-assignment conjunction, handling of dismissed current
  crew, correction semantics, legacy backfill policy and exact client conflict UX.
- `BLOCKED TARGET ONLY`: owner approval of `inspection.item.complete` and
  reconciliation with GRILL-003. Characterization discovery remains unblocked.
