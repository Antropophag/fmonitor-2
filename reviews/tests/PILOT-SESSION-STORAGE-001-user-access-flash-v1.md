# Independent Gate 3 review — PILOT-SESSION-STORAGE-001 v10 UserAccess flash v1

- Date: 2026-09-03T22:10:38+03:00
- Reviewer: separately tasked agent `/root/session_user_access_gate3`
- Test/implementation author: not this reviewer
- Reviewed commit: `f72f6ba74d4116bcc1f3c294a84cc6c809ec0e1f`
- Scope: the focused UserAccess flash RED requested by the prior consumers
  review, against `PILOT-SESSION-STORAGE-001` v10 sections 3, 6–8 and 11
- Prior append-only review:
  `reviews/tests/PILOT-SESSION-STORAGE-001-consumers-v1.md` —
  `CHANGES_REQUESTED`
- Verdict: **CHANGES_REQUESTED**

## What is now sound

The revised fixture closes the principal setup defect identified by the prior
consumer review. It commits a whole-array canonical PHP `serialize()` payload
through the real storage owner. The payload has the fictional active actor,
`access.administer` role, fixed valid CSRF, and a fixed successful invitation
flash URL. Database inserts and owner seeding complete before the raw request.
The reproduced failure is the expected `GET /pilot/admin/users` `404`, not a DB,
configuration, session-read or server-start failure: the injected factory graph
currently routes this known session path into the read application without the
UserAccess directory/enhancer graph that can return the expected authenticated
`200`.

The assertions after that intentional RED are well directed for the happy
path. They require the fixed URL in the actual response, inspect the resulting
payload through a new real owner, require removal of
`fm2_invitation_flash`, and require a second raw GET to omit the URL. Thus a
static response, a non-consuming read, or an identical repeat response cannot
satisfy the test. The `finally` block terminates a surviving child, removes the
fictional user (and cascading credentials/assignments), removes the role, closes
the DB connection, and recursively removes the task-owned state root. The
reproduction left no `/tmp/fmonitor2-session-storage-tests` tree.

## Blocking findings

1. The fault branch does not prove that the configured exact fault tuple
   `rename / committed / ordinal 1 / native false` was reached. The filesystem
   wrapper counts the tuple only inside the child process, but neither router
   nor parent exports an observer/primitive trace or a verifier-owned receipt.
   Therefore any earlier unavailable outcome on the fault-router execution
   could produce the asserted `503`, empty success leakage and unchanged input
   bytes while the intended flash-removal publication was never attempted.
   This is a sensitivity gap at the exact mutation boundary required by v10
   section 8 and by the prior review's smallest vertical RED.
2. The failure response is not checked against the exact section-6 contract.
   It asserts only status `503`, body, and absence of `Location`/`Set-Cookie`.
   A regression may omit or alter `Content-Length`, `Retry-After`, CSP,
   `nosniff`, referrer/frame/permissions/COOP/cache headers, add forbidden
   application headers, or emit duplicate headers and still pass. Because this
   test is the Gate 2 authorization for buffering at the UserAccess removal
   boundary, the exact response envelope cannot be delegated to a generic
   protocol test whose fault is not bound to this consumer mutation.

## Required correction

Keep the present canonical seed, real-owner payload inspection, repeated GET,
and cleanup. Add verifier-owned evidence from the fault child (an external
primitive-trace/receipt is appropriate) that identifies exactly one injected
`rename|committed|1|native_false` match and demonstrates that it belongs to the
flash-removal `writeCommit`. Then compare the complete raw response to the exact
section-6 status/header/body envelope, including absence of all unspecified and
forbidden application headers. Capture and hash a fresh honest RED. This is a
Gate 2 correction; implementation remains unauthorized until a fresh independent
Gate 3 approval.

## Independent reproduction

At the reviewed exact SHA:

```text
$ php -l tests/Support/pilot_session_storage_user_access_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_router.php

$ php -l tests/Support/pilot_session_storage_user_access_fault_router.php
No syntax errors detected in tests/Support/pilot_session_storage_user_access_fault_router.php

$ php -l tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
PHP Fatal error:  Uncaught TestFailure: accepted UserAccess session reaches admin users
Expected: 200
Actual: 404
...
exit=255

$ find /tmp/fmonitor2-session-storage-tests -maxdepth 2 -print
(no output; path absent)
```

This is an honest intended RED for the missing complete UserAccess factory
graph, but the unreachable fault assertions are not yet sufficiently sensitive
to authorize their implementation.

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
5d28d6016734319970ced3294d3061b51ae5fa49d4614e5b50d13441b124b292  reviews/tests/PILOT-SESSION-STORAGE-001-consumers-v1.md
2705e913fe041947dd734243914b7198f68eae5f94666285e866ef6374be06d6  tests/InstallationProcess/pilot_session_storage_user_access_fault_001_test.php
12e032068af4c335bbf62ed43cd16b256c64c546ae047b3a82712694353c0333  tests/Support/pilot_session_storage_user_access_router.php
e1ca5aff7cf3ba5f4a72568204e19497ed4ff99765527877ed302eab512fc67c  tests/Support/pilot_session_storage_user_access_fault_router.php
80c97eca814d9122096ce036e4adc7f1d3bf647ff1e47d893715c81def94ab27  docs/operations/pilot-session-storage-user-access-flash-red-evidence.md
```

Gate 3 for this focused UserAccess flash slice is **CHANGES_REQUESTED**.
