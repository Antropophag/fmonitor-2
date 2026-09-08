# Test review: PILOT-SESSION-STORAGE-001 v10 accepted payload — RED v1

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/session_accepted_test_review`
- Test author: parent delivery agent `/root`
- Independence: this reviewer did not author or edit the specification, test, RED evidence, shared HTTP router, or production implementation
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: raw `GET /pilot/login` through the production HTTP composition with a valid cookie and whole-array bytes committed by the real storage owner
- Verdict: **CHANGES_REQUESTED**

## Blocking finding

The final material assertion proves only byte equality, not the claimed absence
of a rewrite. A handler that restores the accepted payload, then calls
`writeCommit($sessionId, $payload)` on this unchanged GET passes every current
assertion: the response is 200 with the fixed CSRF, no replacement cookie is
needed, and the committed file still contains the same canonical serialized
bytes. This is a plausible regression against the slice's explicit
“restoration and unchanged reuse only” boundary and against the evidence claim
that the test detects a material rewrite.

Return to Gate 2 with an independent observable proving the accepted unchanged
GET does not publish/rewrite committed material. For this real owner's atomic
replacement path, preserving the committed file identity across the request is
the smallest direct oracle; an externally captured primitive trace proving no
write/stage/publish operations would also satisfy the requirement. Retain the
byte-equality assertion as the separate content oracle. Recapture RED and exact
hashes after the test change.

## Checks that passed

- **Traceability and scope:** the test cites v10 sections 3, 7, and 8 and stays
  within the requested first consumer slice: accepted payload restoration for
  existing `GET /pilot/login`. It does not claim mutations, POST, regeneration,
  or complete replacement of both HTTP consumers.
- **Real seam and owner:** the production factory creates the real owner, that
  owner generates the ID and commits the literal whole-array payload, and the
  subsequent request crosses a raw loopback HTTP connection through the
  production router. The test neither synthesizes an operation result nor
  reopens storage to restore state for the application.
- **Independent expected values:** the 64-character CSRF and serialized payload
  are fixed literals assembled by the test, not decoded, encoded, or otherwise
  derived by production code. Their relationship is explicit and valid.
- **Response precondition and cookie sensitivity:** the test first requires
  exact HTTP 200 before looking for the fixed CSRF, and rejects any
  `Set-Cookie` header. A response-only fake that issues a replacement cookie is
  therefore detected.
- **Material-content sensitivity:** byte equality catches reseeding, native
  `name|value` framing, and non-identical re-encoding. It does not, however,
  catch an identical-byte rewrite, which is the blocking gap above.
- **Readiness, isolation, and cleanup:** the test owns a random 0700 leaf,
  ephemeral port, process and pipes; readiness and request operations are
  bounded; `finally` terminates/reaps the server and removes the leaf after
  both success and failure. No production state or external service supplies
  expected values.

## Intended RED independently reproduced

```text
$ php -l tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: owner payload restores existing CSRF
Expected: true
Actual: false
exit 255
```

The real owner commit, server readiness, valid-cookie request, HTTP 200 and
response split all precede this failure. Current production discards the
successful-start payload and generates another CSRF, so the observed RED is the
missing handoff behavior rather than broken setup. This intended RED remains
valid, but the test is not yet sufficiently regression-sensitive for approval.

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
3365340f81b2646934bc63c6e20d4c376794d7833410b3f4b8c9f4aca8e056ce  tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
dcd7da2dea75ebb370fc34e63b8f627c6d70a50182cb08ed6b8c16b19638e6bd  docs/operations/pilot-session-storage-accepted-payload-red-evidence-2026-09-03.md
badfa9ae003c986e561270b1e71450de122694fd7f8c6e5316af4ff07ce3f525  docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md
```

Gate 3 is not approved. No accepted-payload GET implementation is authorized
from this test revision.
