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

## Installed hotfix and read-only browser proof

- Main stabilization commit: `96ec4f5` (same two reviewed production fixes).
- Installed exact source: `a6d3a7fee363b14ec08a643ad74b63797e2298ed`,
  a direct child of previously installed6aa39aa. Only the two production files
  above changed; photo19 was not included.
- Image: `fmonitor2-manual:feedback-a6d3a7f`,
  `sha256:71294ca170fc6cac7b5fc1ebe1f4182c7184cf5a69f3e265bad2e48d53d93f18`.
- Built from git archive of that exact SHA, with revision label verified.
- Both focused regressions also passed in the exact hotfix checkout.
- Before restart, stopped only the pilot and backed up DB and state volume in
  private `runtime/backup-before-a6d3a7f/` (0700/files0600, manifest hashes).
  SQL backup1,409,622 bytes; state archive714,057 bytes. Existing volumes retained.
- Updated the existing private compose.override.yaml image, recreated only pilot
  with --no-build --no-deps, and observed running/healthy at the exact image.
- Headless Chrome fresh owner login, read-only object1450 card/queue, execution
  page and repeated navigation: card and queue both `Готов к открытию`, auth
  preserved after command page,8 read-only navigations, errors0, PASS.
- Private browser result SHA256:
  `1c071b543661c24ec6d965546bce04a129d5493305bbc9575ef6182abdf152c1`;
  queue screenshot SHA256:
  `168db638d84648317ae872babc96cd9885623586eb61885e55abab0aee111182`.

No object/original/application/checklist facts were mutated by these checks; only
the necessary authentication session was created. The stand remains at
http://127.0.0.1:8092/pilot/objects. Full VERIFY_OK remains unfinished, with
bootstrap fixture/deadline diagnostics continuing separately. Do not revert this
installed hotfix to6aa39aa based on an older checkpoint.
