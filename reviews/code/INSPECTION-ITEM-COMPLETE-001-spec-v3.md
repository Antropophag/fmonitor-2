# INSPECTION-ITEM-COMPLETE-001 — independent Gate 5 Spec rereview v3

Date: 2026-09-01  
Reviewer: `/root/item_code_spec` (independently tasked; did not author the
specification, tests, production implementation, or corrective changes)  
Mission: `TEST-USER-READY`  
Verdict: `CHANGES_REQUESTED`

## Reviewed basis

- Approved executable spec SHA-256:
  `c895095bf9dbda9e69ef3e10afe4226d01893a2fcbbede1c3d8cdd6dd729d8eb`.
- Approved OpenSpec delta SHA-256:
  `d3d2b90ef251e9fed550d23c666a4dd42b69eefcffc95e9036857bf2f949262c`.
- Endpoint/prefix Gate 3 v6:
  `reviews/tests/INSPECTION-ITEM-COMPLETE-001-endpoint-prefix-v6.md`, verdict
  `APPROVED`.
- Prior Spec rereview:
  `reviews/code/INSPECTION-ITEM-COMPLETE-001-spec-v2.md`, verdict
  `CHANGES_REQUESTED`.

I reinspected the application/MariaDB module, factory/configuration, resolver,
`ChecklistSync`, raw checklist endpoint, sync-context endpoint, page renderer,
corrective tests, and prior Gate records. Unrelated dirty changes were ignored.

## Prior finding closure

### SPEC2-03 server admission — closed

The raw operation endpoint is now operation-aware
(`app/PilotHttp/PilotE2ECoordinator.php:64-70`): an unassigned active user with
exact `inspection.item.complete` can submit `item_completed`, while photos and
other operation types retain the assignment/legacy-`checklist.edit` gate. The
command still reaches the application seam, which independently rechecks
current database authorization at receipt.

The sync-context endpoint now admits the same exact-capability engineer and
returns the CSRF/revision context needed for queued offline delivery
(`app/PilotHttp/PilotE2ECoordinator.php:74-78`). The approved raw endpoint test
uses actor 7301 with exact capability only, assigned engineer 7302, no
`checklist.edit`, an unequal object/case id, actual HTTP/session/CSRF, a 422
application rejection for malformed `item_completed`, a retained 403 for a
non-item operation, and a 200 sync-context result.

### Prefix validation — conforming

`ProductionInspectionEvidenceConfig` validates the canonical ASCII 0..25-byte
prefix at construction, before the factory touches the supplied connection.
The approved prefix test uses an unconnected handle and covers invalid
characters, 26 ASCII bytes, and non-ASCII input. Factory composition otherwise
retains caller-owned connection lifecycle, `utf8mb4`, injected/default clock,
and no DDL/business mutation.

## Remaining blocking finding

### SPEC3-04 — the browser UI remains inert for the newly authorized engineer

The owner-approved behavior is that every active engineer with exact
`inspection.item.complete` can mark every object, including an object assigned
to another engineer. Raw HTTP and offline receipt now permit that action, but
the normal checklist page still calculates edit availability only as:

```text
opened AND (assigned engineer OR legacy roleAccess/checklist.edit)
```

`ProductionChecklistRenderer` uses that expression at
`app/PilotHttp/ChecklistView.php:23`, then emits `data-enabled="false"` and an
`inert` checklist for the unassigned exact-capability engineer
(`app/PilotHttp/ChecklistView.php:24-26`). The coordinator passes only legacy
`roleAccess` to the renderer and does not include
`inspection.item.complete` (`app/PilotHttp/PilotE2ECoordinator.php:64-65`).
Client code refuses to enqueue an item when `data-enabled` is false.

The endpoint test confirms only HTTP 200 plus a CSRF token for the page; it does
not assert that the page permits the engineer to initiate item completion.
Indeed its fixture (exact capability, no `checklist.edit`, assigned to 7302)
receives a disabled checklist and must craft the POST directly. Thus the API
contract is reachable, but the user-visible behavior approved by the owner is
not.

Required correction: expose item-completion availability separately from
legacy whole-checklist edit availability. An unassigned exact-capability
engineer must be able to enqueue `item_completed`, while photos,
`item_installers_changed`, and section completion must retain their existing
legacy/assignment controls. Add an independently reviewed page/client-boundary
test using the exact-only unassigned actor; it must prove item controls are
enabled without accidentally enabling the retained legacy operations.

## Reconfirmed conforming behavior

- Application authorization is current active status plus exact capability,
  with no assignment conjunction; actual actor and assigned engineer are
  separate immutable audit facts.
- Authorization precedes syntax, canonical v8 compatibility, replay/conflict,
  and mutable case/template/crew checks. `deviceTime` is audit-only.
- Explicit object-to-case resolution, deterministic absence, retryable
  ambiguity/failure, typed result mappings, and no legacy item SQL fallback are
  preserved.
- Exact replay, payload conflict, canonical installer normalization, immutable
  attribution, per-case revision locking, one-winner/one-stale concurrency,
  rollback, and public evidence queries remain aligned with the spec.
- Canonical v8 compatibility is checked authoritatively and read-only; the
  drift test proves no repair or business mutation.
- Only the `item_completed` server branch is delegated; other operations retain
  their legacy mutation and authorization paths.

## Decision

The previously reported endpoint/offline and prefix blockers are closed, and
the supplied focused verification is consistent with those closures. Gate 5
Spec approval remains blocked solely by SPEC3-04: the normal product UI still
prevents the exact-capability, unassigned engineer from performing the action
that the owner explicitly approved.
