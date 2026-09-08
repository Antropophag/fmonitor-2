# Independent test review — INVITATION-REISSUE-001

Date: 2026-09-08
Reviewer: separately tasked agent `/root/review_invitation`
Reviewed commit: `1a3555c82c559647f85016e25ff10e7cd6e8c115`
Baseline: `38ffe30a393f9b085cbbcc6a6f61564e5c8a4b1e`
Verdict: **APPROVED**

The reviewer did not author or edit the specification, test, helper, or production
implementation. The test is traceable to the OpenSpec delta and uses the public
HTTP lifecycle plus the public `ReissueUserInvitation::reissue(actorId, userId)`
application seam. Expected outcomes come from INVITATION-REISSUE-001 rather than
implementation internals.

The authentic RED supplied by the test author was reviewed: after invitation
creation, successful one-time display, leaving the page and returning, the test
failed with `INTENTIONAL_RED: pending user exposes a CSRF-protected reissue action
after return`. This isolates the missing #46 action rather than fixture failure.

The final test is sensitive to the material contract:

- create/leave/return/reissue, distinct token, old-token rejection, one-time new
  activation, unchanged user and roles, attributed append-only invitation history;
- expired and sequential reissue, active/blocked/missing targets, non-admin actor;
- missing and invalid CSRF, GET 405 and full no-mutation snapshots;
- two concurrent application workers leaving three historical rows and exactly
  one live invitation;
- a test-only MariaDB insert failure proving revocation rolls back atomically.

The worker is deterministic, uses the fixture's isolated schema, and carries no
production mutation behavior. The test is registered in `suites.tsv` as `db` and
in `categories.json` as `integration`. Registration first produced the authentic
inventory RED `repository baseline membership`; the reviewed one-line
`added_by_suite['db']` extension removes exactly this intentional new member before
checking the unchanged protected digest. The inventory suite is GREEN, 15/15.

Reviewer verification:

```text
FMONITOR_TEST_DB_PORT=23307 FMONITOR_TEST_DB_ADMIN_USER=root \
FMONITOR_TEST_DB_ADMIN_PASSWORD=fmonitor2_test_root_local \
php tests/InstallationProcess/invitation_reissue_http_001_test.php
PASS invitation reissue HTTP lifecycle
```

The isolated database and test fixtures were used; the owner stand was not
mutated. Full `make test`/CI remains separate integration evidence and is not
implied by this Gate 3 verdict.
