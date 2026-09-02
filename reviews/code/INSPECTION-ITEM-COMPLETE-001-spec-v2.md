# INSPECTION-ITEM-COMPLETE-001 — independent Gate 5 Spec rereview v2

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
- Corrective independent test review:
  `reviews/tests/INSPECTION-ITEM-COMPLETE-001-http-v6.md`, verdict
  `APPROVED`.
- Prior Spec review:
  `reviews/code/INSPECTION-ITEM-COMPLETE-001-spec.md`, verdict
  `CHANGES_REQUESTED`.

I reinspected the full application/MariaDB slice, `ChecklistSync`, the actual
checklist endpoint and offline sync-context admission, corrective tests, and
the relevant Gate 1/Gate 3 records. Unrelated dirty changes were ignored.

## Prior finding closure

### SPEC-01 — closed

`ChecklistSync::accept` now dispatches `item_completed` before the retained
legacy envelope/static-section guard (`app/PilotHttp/ChecklistSync.php:53-58`).
The command therefore reaches object-to-case resolution and the public
recording seam, where current authorization precedes syntax and template
membership owns section/item validity. All application results are mapped to
the exact two-field adapter shape.

The v6 independently approved corrective test changes only one valid command
field to literal `not-a-uuid` and proves one resolver call, one recording call,
trusted actor `7301`, canonical case `9512`, preservation of the malformed
field, and exact `{status: rejected, revision: 0}` mapping. Non-item isolation
remains covered.

### SPEC-02 — closed

`MariaDbInspectionAuthorization::schemaAvailable` now calls the authoritative
read-only `InspectionEvidenceSchemaMigration::isCompleteCompatible` check for
the full canonical v8 family. It no longer substitutes a four-table partial
column probe.

The independently approved drift test starts from canonical v1-v8, introduces
an unexpected index, calls the public production application seam, and requires
typed `INSPECTION_SCHEMA_UNAVAILABLE(0)` before clock use. Exact
`SHOW CREATE TABLE` plus deterministic row snapshots for all four canonical
evidence tables prove both no schema repair and no slice-owned business
mutation.

## New blocking finding

### SPEC2-03 — real HTTP/offline admission still enforces assignment or legacy `checklist.edit`

The owner-approved rule is that every active engineer with exact capability
`inspection.item.complete` may complete an item on every object, regardless of
current control-engineer assignment. The approved spec also requires the HTTP
and offline adapters to translate the command into the public application seam,
which owns receipt-time exact-capability authorization.

The real endpoint still computes:

```text
opened AND (canEditChecklist(user) OR assignedControlEngineer == user)
```

and returns HTTP 403 before parsing or delegating when that expression is false
(`app/PilotHttp/PilotE2ECoordinator.php:64-69`). `canEditChecklist` checks the
different legacy capability `checklist.edit`, not
`inspection.item.complete` (`app/PilotHttp/PilotHttp.php:462-468` and
`app/PilotHttp/AccessPolicy.php:12`). The offline sync-context endpoint repeats
the same assignment/legacy-capability gate before issuing the CSRF token
(`app/PilotHttp/PilotE2ECoordinator.php:78`).

Therefore an active, unassigned engineer who has exactly the owner-approved
`inspection.item.complete` capability but lacks legacy `checklist.edit` cannot
send the operation at all. The application module correctly supports broad
authority, but the product endpoint prevents that behavior and bypasses its
receipt-time decision. Reverting the earlier endpoint modification restores
the pre-slice authorization conjunction and directly contradicts the owner
decision.

Required correction: make both operation admission and the offline
sync-context path permit the item-completion flow based on exact
`inspection.item.complete` without assignment conjunction, while preserving
legacy admission for photos, corrections, and section completion. The
application seam must remain the authoritative receipt-time recheck. Add an
independent endpoint-level test that distinguishes an unassigned exact-capability
engineer from both an assigned engineer without that capability and legacy
`checklist.edit`, including the sync-context needed for queued offline delivery.

## Reconfirmed conforming behavior

- Application authorization uses active user plus exact
  `inspection.item.complete`, not current assignment; actor and assigned
  engineer remain separate audit facts.
- Every valid receipt/replay reauthorizes current status and capability;
  `deviceTime` is audit-only.
- Explicit object-to-case resolution supports unequal ids, deterministic
  absence, and retryable ambiguity/failure.
- Replay/conflict precedence, immutable evidence, installer normalization,
  revision locking, rollback behavior, and typed result mapping remain aligned
  with the approved contract.
- Only `item_completed` is rewired inside `ChecklistSync`; other operation
  mutation branches remain legacy and no item-completion SQL fallback exists.
- Factory composition remains caller-connection-owned, prefix-safe,
  clock-injected, DDL-free, and independent per worker.

## Independent verification

Current focused checks reproduced:

```text
PASS: INSPECTION-ITEM-COMPLETE-001 HTTP wiring
PASS: INSPECTION-ITEM-COMPLETE-001 example A
PASS: INSPECTION-ITEM-COMPLETE-001 receipt-time authorization
PASS: INSPECTION-ITEM-COMPLETE-001 authorization before replay all
PASS: INSPECTION-ITEM-COMPLETE-001 precedence all
PASS: InspectionEvidence SQL owner policy
ARCHITECTURE CHECK PASSED (7 rules)
```

These checks close SPEC-01/SPEC-02 but do not exercise the outer endpoint or
sync-context gate identified by SPEC2-03. Gate 5 Spec approval remains blocked
until that product-level authorization path is corrected, independently tested,
and rereviewed.
