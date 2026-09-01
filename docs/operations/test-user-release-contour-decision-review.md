# TEST-USER release contour decision review

- Review date: `2026-09-02`
- Reviewer task: fresh independent review of the owner-decision record and its
  current operations-status update
- Reviewed decision:
  `docs/operations/test-user-release-contour-decision.md`
- Reviewer did not author or modify the decision, status, OpenSpec artifacts,
  runtime code or tests.

## Authoritative evidence checked

- the owner's explicit “Да” answer to the question whether Compose `make up`
  is the TEST-USER release contour and standalone remains non-equal;
- `docs/operations/pilot-generation-metadata-evidence.md` and its three
  independent rereviews, ending in `READY_FOR_OPENSPEC`;
- `docs/operations/pilot-generation-metadata-planning-review.md`;
- all four current, intentionally unchanged artifacts under
  `openspec/changes/separate-pilot-generation-metadata/`;
- `docs/operations/runtime-ddl-migration-plan.md` and the current
  `docs/operations/status.md`;
- `docs/development-process.md` and the confirmation guard in
  `openspec-update-change`.

## Accepted decision boundary

The decision record faithfully resolves only the release-contour choice:
Compose `make up` is the supported TEST-USER seam and the standalone CLI is a
synthetic fixture harness rather than an equal release contour. Its isolation
consequences follow the accepted evidence: documented host/image operation is
already physically disjoint, while any supported explicit co-location needs a
contour discriminator and zero-cross-mutation proof.

The record does not silently approve fixture contents, personal-data use,
legacy cutover or the exact destructive reset policy; those remain under
GRILL-004. It also expressly withholds Gate 1, RED, implementation, production
migration and destructive-reset authority. Finally, it correctly leaves the
existing OpenSpec files untouched until the proposed revision of each affected
artifact is shown and explicitly confirmed under `openspec-update-change`.
Thus the owner's answer is evidence for resolving GRILL-008, not authorization
to mark task 1.1 complete, start Gate 2, or mutate planning/code by implication.

## Blocking finding

1. **The current operations status contradicts the accepted decision in three
   live routing statements.** The new DONE entry correctly says GRILL-008 is
   resolved, but the generation-metadata IN_PROGRESS entry still says its final
   planning rereview “awaits explicit owner choice of TEST-USER release
   contour.” READY still directs work “while the generation release-contour
   choice is pending” and says to continue generation-sentinel discovery even
   though the evidence phase is already `READY_FOR_OPENSPEC`. These are not
   harmless historical records: they are current pipeline routing. Supersede
   them in `status.md` with the actual boundary—owner choice recorded; OpenSpec
   revisions await per-artifact confirmation; task 1.1 also retains its
   DDL-free-predecessor and GRILL-004-reset prerequisites; no Gate 1/RED or
   implementation is authorized.

The unchanged OpenSpec `NEEDS_GRILL` wording and unchecked task 1.1 are not a
defect at this review point because the decision record explicitly preserves
the required confirmation-before-edit boundary. They must remain unchanged
until that confirmation is obtained.

## Verdict

**CHANGES_REQUESTED**

The owner-decision record itself is accurate and narrowly scoped, but the
current authoritative operations status is internally contradictory and would
route the autonomous pipeline as though GRILL-008 were still unresolved.
Correct only those live status statements, then assign a different fresh
rereviewer. This verdict does not authorize an OpenSpec edit, Gate 1, RED,
implementation, reset or fixture decision.
