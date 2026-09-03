# Test rereview: PILOT-SESSION-STORAGE-001 v10 accepted payload — RED v2

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/session_accepted_gate3_v2`
- Test author: parent delivery agent `/root`
- Reviewed commit: `85ea520873b0920bfd6051065346a586d5e88aaf`
- Recorded at: `2026-09-03T21:45:28+03:00`
- Independence: this reviewer did not author or edit the specification, owner decision, test, RED evidence, shared HTTP router, or production implementation
- Specification: `PILOT-SESSION-STORAGE-001` v10, owner-approved exact hash
- Public seam: raw `GET /pilot/login` through the production HTTP composition with a valid cookie and whole-array bytes committed by the real storage owner
- Red command: `php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php`
- Intended failure: `INTENTIONAL_RED: owner payload restores existing CSRF`, expected `true`, actual `false`, exit `255`
- Verdict: **APPROVED**

## Findings

- **Traceability and scope:** the test cites v10 sections 3, 7, and 8 and proves
  the first accepted-payload consumer behavior only: an existing canonical
  whole-array payload is restored for `GET /pilot/login`, reused unchanged, and
  does not cause a replacement cookie or material publication. POST,
  regeneration, malformed payloads, and the second HTTP consumer remain outside
  this focused RED and have their own slices/reviews.
- **Public seam and owner:** the production factory constructs the real storage
  owner. That owner generates the session ID and commits the literal payload;
  the assertion request then crosses a raw loopback HTTP connection through the
  production router with the valid cookie. The test neither fabricates a result
  DTO nor reads storage on behalf of the application.
- **Independent expectations:** the CSRF and canonical serialized payload are
  fixed test literals. HTTP 200 is established before the CSRF assertion. The
  expected material, absence of `Set-Cookie`, and preserved filesystem identity
  are not calculated by the codec or HTTP implementation.
- **Sensitivity to the missing behavior:** current production obtains the
  successful start payload but does not restore its array into the request
  session, then generates a new CSRF. The reproduced failure therefore occurs
  at the intentional fixed-CSRF assertion after owner commit, committed-file
  `lstat`, server readiness, valid-cookie request, HTTP 200, and response split;
  it is not a setup failure.
- **Identical-byte rewrite sensitivity:** v2 captures external `(dev, ino)`
  immediately after setup commit and again after the request, with targeted
  stat-cache invalidation. The real owner's specified and implemented normal
  commit publishes a newly staged regular file by same-directory atomic rename,
  so even `writeCommit($sessionId, $payload)` with byte-identical data replaces
  the inode and fails this oracle. The separate byte equality assertion still
  detects reseeding, native `name|value` framing, and non-identical re-encoding.
  Thus the v1 blocking finding is closed without inspecting a private method.
- **Determinism and isolation:** the expected values are fixed; random values
  select only an owned 0700 leaf and ephemeral loopback port. The request and
  readiness operations are bounded and do not contact production services.
- **Setup and cleanup:** the real commit and initial identity check establish
  the material precondition. `finally` terminates and reaps the PHP server,
  drains/closes pipes, recursively removes only the random owned leaf, and
  removes the shared parent only when empty. After the reproduced failure there
  were no owned leaf descendants and no surviving router process.
- **Artifact integrity:** reviewed hashes match both the owner-approved package
  and RED v2 evidence. The reviewed commit changes only this test and the new
  append-only RED v2 evidence relative to its parent; no production file changed.

No blocking findings.

## Independently reproduced RED

```text
$ git rev-parse HEAD
85ea520873b0920bfd6051065346a586d5e88aaf

$ php -l tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
PHP Fatal error:  Uncaught TestFailure: INTENTIONAL_RED: owner payload restores existing CSRF
Expected: true
Actual: false in /home/antropophag/code/fmonitor-2/tests/bootstrap.php:36
exit=255
```

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
9f18b7953d4426c42414d06186561972027e9dc238d13846b46574269507ab41  tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
badfa9ae003c986e561270b1e71450de122694fd7f8c6e5316af4ff07ce3f525  docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md
2ce287912275253ecc90a3663426a34a85c27366fbb4eca81ac2972b7be9704e  docs/operations/pilot-session-storage-accepted-payload-red-evidence-v2-2026-09-03.md
```

Gate 3 is approved for this exact test revision and reviewed commit. Gate 4 may
implement only the accepted-payload GET behavior covered here; this verdict does
not approve implementation, broader consumer migration, Gate 5, or release
readiness.
