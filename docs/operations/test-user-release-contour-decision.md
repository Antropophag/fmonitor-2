# TEST-USER release contour owner decision

- Decision date: `2026-09-02`
- Decision owner: project owner
- Decision: `APPROVED_COMPOSE_CONTOUR`
- Primary operator seam: `make up`

## Decision

The supported TEST-USER release contour is the Compose lifecycle entered through
`make up`. Its persistent native-only/import/worker orchestration, generation
identity, readiness and restart behavior form the release acceptance surface.

The future working deployment will use the same Compose-based deployment
approach rather than a parallel standalone-CLI contour. This fixes deployment
direction, but does not claim that current local credentials, scaling, backup or
production operations are already approved.

`bin/fmonitor2-pilot-demo.php` is not an equal release contour. It remains a
synthetic fixture harness with its separately characterized owner/ready/active
metadata lifecycle. Its successful execution does not prove TEST-USER readiness.

## Required consequences

1. TEST-USER runbooks route the owner to Compose `make up` and identify ordinary
   restart as state-preserving.
2. The standalone harness and Compose use disjoint state roots, artifacts and DB
   prefixes in every supported topology. Any explicitly supported co-location
   uses a contour discriminator and proves zero cross-contour mutation.
3. Generation-metadata planning may remove the release-contour `NEEDS_GRILL`,
   while preserving GRILL-004 for fixture contents, personal-data exclusion and
   the exact destructive reset policy.
4. This decision does not approve an executable Gate 1 specification, RED,
   implementation, production migration changes or destructive reset behavior.
5. Existing `separate-pilot-generation-metadata` artifacts remain unchanged
   until the owner separately confirms their named revisions under
   `openspec-update-change`; the contour decision alone is not mutation consent.

## Provenance

The owner first answered “Да” to the recommendation and, after the term
TEST-USER contour was explained, explicitly clarified: “Только make up” and
that the future working contour will be deployed the same way. This record
supersedes the unresolved GRILL-008 status only; it does not rewrite historical
evidence or reviews.
