# PILOT-SESSION-STORAGE-001 v10 accepted payload — Gate 2 RED v2

Date: 2026-09-03

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
9f18b7953d4426c42414d06186561972027e9dc238d13846b46574269507ab41  tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
badfa9ae003c986e561270b1e71450de122694fd7f8c6e5316af4ff07ce3f525  docs/operations/pilot-session-storage-v10-payload-owner-decision-2026-09-03.md
```

Public seam: raw `GET /pilot/login` through the production dependency
composition with a valid cookie and a whole-array payload committed by the real
storage owner.

Commands and observed result:

```text
php -l tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php

php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: owner payload restores existing CSRF
Expected: true
Actual: false
exit=255
```

The real owner commit, `lstat` of the committed regular material, server
readiness, valid-cookie request, HTTP 200 and response parsing succeed before
the intended failure. Production still discards the accepted start payload and
generates a replacement CSRF.

The corrected oracle retains byte equality and additionally compares the
external filesystem identity `(dev, ino)` captured before and after the raw
HTTP request. The real owner's atomic publication replaces the inode, so a
regression that calls `writeCommit($sessionId, $payload)` with identical bytes
cannot pass. The assertion does not inspect a private method or derive identity
from production code.

No production file changed for this RED. The prior v1 evidence and
`CHANGES_REQUESTED` review remain immutable history.
