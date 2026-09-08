# Independent code review — INVITATION-REISSUE-001

Date: 2026-09-08
Reviewer: separately tasked agent `/root/review_invitation`
Reviewed commit: `1a3555c82c559647f85016e25ff10e7cd6e8c115`
Baseline: `38ffe30a393f9b085cbbcc6a6f61564e5c8a4b1e`
Verdict: **APPROVED**

The reviewer did not author or edit the reviewed production implementation or
tests. No blocking standards or specification findings remain.

## Standards

The new mutation has one owning application seam in `IdentityAccess`. Its MariaDB
adapter validates the table prefix, starts one transaction, verifies the actor is
active and receives byte-exact `access.administer` through an active role, locks
the invited target, revokes all unused live invitations, inserts the new hashed
24-hour invitation with its actor, and rolls back on failure. The target-row lock
serializes concurrent reissues. Existing user, credentials and role grants are
untouched; prior invitation rows and their original hashes/times are retained.

HTTP only adapts routing, POST/CSRF/session input and result presentation. The CLI
uses a read-only recipient lookup then calls the same application operation; it no
longer contains invitation mutation SQL or creates anonymous history. There is no
`IdentityAccess` dependency on `PilotHttp` and no new rapid-pilot writer. The
architecture ratchet passed all seven rules. No smell-baseline issue was material
enough to request a change in this bounded integration.

## Spec

The native catalogue distinguishes active, invited and blocked users, reports
whether a live invitation exists, and exposes the CSRF-protected reissue action for
invited users after return. The owner session redirects successful reissue to the
catalogue and displays the new URL once in a labelled readonly field; failure is
shown without disclosing a token. Rapid-pilot presentation relocates the same form
to its existing Actions column and keeps the established responsive table. The
status filter handles invited rows. Route admission and the asset path remain in
the native shell.

The reviewed HTTP/application test proves authorization, state rejection, GET and
CSRF refusal, history, activation, repeated and expired reissue, concurrency and
transaction rollback. The test is present in both verification inventories.

Reviewed evidence:

```text
focused INVITATION-REISSUE-001 lifecycle on isolated MariaDB 23307: PASS
PHP lint for all changed/new PHP files: PASS
git diff --check 38ffe30a...1a3555c8: PASS
tools/architecture/check --json: {"errors": [], "ok": true, "rules": 7}
OpenSpec strict validation, visual contract, focus contract and inventory: PASS
verification inventory after exact db-member baseline extension: 15/15 PASS
headless Chromium create/leave/return/reissue/activate clicks: PASS
desktop 1440 and mobile 390 screenshots: inspected; console/network clean
```

The browser and ancillary verification transcripts are recorded in
`docs/operations/invitation-reissue-issue46-2026-09-08.md`; the reviewer inspected
the exact committed UI diff and the recorded evidence. The mobile table retains
its documented horizontal scroll behavior. Full `make test`/CI, publishing,
deployment and production readiness remain outside this local verdict.
