# Owner feedback: object 1450 status and repeated login

Owner report: 2026-09-07, during autonomous stabilization. These findings take priority over the next full verify.

## Reproduction and fixes

Read-only requests to the installed 6aa39aa stand confirmed object1450 card status
`Готов к открытию`, while its queue row showed `Требуется распоряжение`.
The queue only considered old registration/application facts. Its read owner now
joins the latest selected composition to the exact original root identity/hash and
current revision. Display, status filters and totals share this readiness basis,
including native cases that still have needs_assignment_order as stored state.
An old original/application does not authorize a newer pending composition. Queries
create no original, application or opening fact.

The session regression reproduced loss of auth_user_id when PilotCommandSession
initialized command state inside an existing LocalAuth payload. Same-actor command
initialization now preserves login identity and auth CSRF fields. Actor mismatch
still regenerates the session and discards foreign authorization.

## Focused evidence and reviews

- Queue regression: initial RED reproduced the reported status mismatch. Supplemental
  RED covered prior application followed by a newer pending composition. Final GREEN
  covers both statuses, both filters/counts, history retention and DB/file no-write.
- Calendar bounded projection and object-filter verifier PASS.
- Session shared-payload regression, payload handoff, sequential write identity and
  LocalAuth lifecycle PASS. Syntax and diff checks PASS.
- Independent queue reviews: reviews/tests/ORIGINAL-READY-QUEUE-MANUAL-2026-09-07.md
  and reviews/code/ORIGINAL-READY-QUEUE-MANUAL-2026-09-07.md.
- Independent session reviews: reviews/tests/PILOT-SESSION-SHARED-AUTH-COMMAND-001-2026-09-07.md
  and reviews/code/PILOT-SESSION-SHARED-AUTH-COMMAND-001-2026-09-07.md.

Primary evidence stays in the private manual-pilot runtime state. Objects1450/966
were read-only; no reset/import/remote action was performed.

## Delivery separation

Prepare a two-production-file hotfix based directly on the currently installed
6aa39aa, whose relevant preimages equal the stabilization branch. This delivers
these owner findings without prematurely installing photo schema19. Preserve and
back up existing DB/state volumes; build only a git archive of the exact hotfix
commit. Record source/image/restart and read-only browser proof after deployment.
Full stabilization candidate, protected E2E/bootstrap reconciliation and exact-SHA
make verify remain separate and unfinished. No production-readiness claim.
