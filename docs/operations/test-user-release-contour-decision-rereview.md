# TEST-USER release contour decision rereview

- Review date: `2026-09-02`
- Reviewer task: fresh independent rereview after the prior
  `CHANGES_REQUESTED` verdict
- Reviewed decision:
  `docs/operations/test-user-release-contour-decision.md`
- Reviewed live routing: `docs/operations/status.md`
- Reviewer did not author or modify the decision, status, OpenSpec artifacts,
  runtime code or tests.

## Evidence and boundaries checked

- The owner's clarified decision is unambiguous: “Только make up”; the future
  working deployment is also intended to use the same Compose approach.
- The prior review in
  `docs/operations/test-user-release-contour-decision-review.md` identified
  three stale live-routing statements in the generation-metadata IN_PROGRESS
  and READY entries.
- `docs/development-process.md` keeps planning, Gate 1, RED and implementation
  as separate approvals and gates.
- GRILL-004 remains the owner boundary for fixture contents, personal-data
  exclusion, legacy cutover and exact destructive reset semantics.

## Closed findings

All three stale status findings from the prior review are closed:

1. IN_PROGRESS no longer says that the TEST-USER release-contour choice is
   pending. It records Compose `make up` as the sole contour and standalone CLI
   as a synthetic harness.
2. READY no longer routes work as though the release-contour choice were
   unresolved. It routes generation-metadata planning only after fresh
   rereview.
3. READY no longer asks for further generation-sentinel discovery. It records
   ownership/separation discovery and the release-contour choice as complete.

The live status also correctly retains GRILL-004 reset/fixture scope and keeps
Gate 1, RED and implementation pending. The decision's statement that the
future working deployment follows the same Compose approach does not claim
that present credentials, backup, scaling or production operations are
approved.

## Blocking finding

1. **The record overstates the confirmation evidence for modifying the four
   `separate-pilot-generation-metadata` OpenSpec artifacts.** Required
   consequence 5 says the owner “explicitly confirmed the proposed revisions
   to all four” artifacts, and status repeats that the four-artifact update is
   owner-confirmed. The available clarification explicitly confirms the
   deployment choice—“Только make up” and the same approach for the future
   working contour—but does not itself explicitly authorize editing those four
   named artifacts. The earlier two-part “Да” answered the original contour and
   object-detail questions, not a generation-metadata OpenSpec update. Under the
   confirmation guard, a contour decision is not by implication authorization
   to mutate an existing change.

   Correct the decision/status to distinguish the accepted contour from the
   still-required explicit four-artifact update confirmation, or obtain and
   cite an explicit owner confirmation for that update. Until then, do not use
   this record as authority to edit those OpenSpec artifacts.

## Verdict

**CHANGES_REQUESTED**

The previous three status-routing defects are closed, and the Compose-only
decision, future deployment direction, GRILL-004 boundary and delivery gates
are otherwise represented correctly. The unsupported claim of explicit
four-artifact update authorization prevents
`READY_FOR_CONFIRMED_OPENSPEC_UPDATE`. This verdict authorizes no OpenSpec edit,
Gate 1, RED, implementation, reset or fixture decision.
