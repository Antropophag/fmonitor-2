# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — independent Gate 5 review v1

- Date: 2026-09-04T00:12:23+03:00
- Reviewer: separately tasked agent `/root/object_list_gate5`
- Implementation/test author: not this reviewer
- Reviewed commit: `d143b53efc2c395d295ce20ac507f8ba810b28c6`
- Gate 3 approval: appended rereview v2 in `reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md`
- Owner-approved specification SHA-256: `e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828`
- Gate 4 diff: `2080ff5..d143b53`
- Verdict: **CHANGES_REQUESTED**

## Review result

The minimal fixture change does replace actor 18's generic role `900018` with
the canonical active role `5101`, one assignment and byte-exact
`objects.read`. The approved leading revoke tracer now passes through the
production `GET /pilot/objects` seam. The Gate 4 diff contains no production
fallback or production-code change; it is confined to the test fixture and its
attempt record.

Gate 5 nevertheless cannot approve this commit. The required focused test is
non-zero, the slice has not reached GREEN, and its cleanup oracle raises a
second failure caused by observation of its own foreign decoy directory.

## Blocking findings

1. **The relevant test is not GREEN.** At exact reviewed HEAD the focused
   command exits `255` on the later independently owned navigation predecessor:
   `approved removal predecessor: no work item or root navigation destination`
   (`Expected: 0`, `Actual: 2`). This proves the leading canonical-role tracer
   advanced, but it does not satisfy Gate 4 or the specification's Done rule,
   both of which require the reviewed test and relevant regression suite to be
   green. The stored Gate 4 record correctly calls itself an attempt and says
   tasks 3.1–4.3 remain open; that evidence cannot support Gate 5 approval.

2. **The cleanup preservation oracle is self-mutating and masks the primary
   failure.** The same run enters `finally` and reports `foreign file bytes and
   metadata preserved` because only the foreign directory's `atime` changes by
   one second (`1788469920` to `1788469921`); inode, mode, ownership, size,
   `mtime`, `ctime`, file metadata and file hash remain identical. The test
   includes the foreign directory in `$polProtectedPaths`, so its own traversal
   can update directory access time before the final raw `lstat()` equality.
   It then wraps this as `attempt-all cleanup failure`, replacing the useful
   navigation exception with cleanup noise. Make the preservation measurement
   observationally stable while retaining the approved bytes and meaningful
   metadata coverage, and preserve/report the primary exception when cleanup
   also fails. Because this is an approved-test oracle change, return to Gate 2
   and obtain a fresh independent Gate 3 approval before another Gate 4 run.

## Standards axis

No production-boundary or maintainability finding in the fixture GREEN itself.
The canonical actor override is small and localized, append-only records were
not rewritten, and `git diff --check 1ec876e..d143b53` exits 0. The cleanup
oracle defect above is a hard verification/reliability finding, not a style
preference.

## Specification axis

The installed positive manifest matches the approved actor, active role 5101,
assignment and exact permission, and the production authorization seam is
unchanged. There is no production fallback. Specification completion is still
contradicted by the exact focused result and by the unstable foreign-decoy
metadata assertion; full matrix, navigation and regression completion have not
been demonstrated.

## Independent verification at exact reviewed commit

```text
$ git rev-parse HEAD
d143b53efc2c395d295ce20ac507f8ba810b28c6

$ php -l tests/Support/PilotObjectReadRbacFixture.php
No syntax errors detected in tests/Support/PilotObjectReadRbacFixture.php

$ php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

$ openspec validate pilot-object-read-rbac-fixtures --strict
Change 'pilot-object-read-rbac-fixtures' is valid

$ FMONITOR_TEST_DB_ADMIN_PASSWORD=<REDACTED> \
  php tests/InstallationProcess/pilot_object_list_001_test.php
TestFailure: approved removal predecessor: no work item or root navigation destination
Expected: 0
Actual: 2
Next TestFailure: foreign file bytes and metadata preserved
[foreign directory atime expected 1788469920, actual 1788469921]
Next TestFailure: attempt-all cleanup failure: foreign file bytes and metadata preserved
exit 255

$ git diff --check 1ec876e..d143b53
exit 0
```

## Reviewed hashes

```text
e3858f094c1f5c4411887b7a242122f714ec6d488febca87bd591553f5b05828  specs/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
6c7929a4a599919d25eba2330c097e03450925435b0b50a49026d08cbc1e20f9  tests/Support/PilotObjectReadRbacFixture.php
42e8c066638f41de4ca0486f489273d0e58ed45fa0467fcd56cfd7809d238c4c  tests/InstallationProcess/pilot_object_list_001_test.php
189e2e2710dc7133227e651ee6962c1a2bde232e54ac04735ffdf38b509e5950  docs/operations/pilot-object-read-rbac-fixtures-green-attempt.md
d0d2365377edbe12c6e78a34c4b675ecca78a256b05b15e669829f3be7b44168  reviews/tests/PILOT-OBJECT-READ-RBAC-FIXTURES-001.md
```

## Required changes

- Correct the foreign-decoy metadata oracle without weakening its approved
  protection, demonstrate a fresh intended RED if the test blob changes, and
  obtain fresh independent Gate 3 approval.
- Resolve or land the approved navigation predecessor so the exact focused
  test and full relevant matrix finish with exit 0.
- Record exact GREEN evidence, then request a new independent Gate 5 review.

Gate 5 is **CHANGES_REQUESTED** for `d143b53`; no completion may be inferred
from the successful leading tracer alone.
