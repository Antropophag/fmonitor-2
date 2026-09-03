# Code review: PILOT-SESSION-STORAGE-001 v10 accepted payload — GREEN v1

- Gate: 5 — independent code review
- Reviewer: separately tasked agent `/root/session_accepted_gate5`
- Recorded at: `2026-09-03T21:50:02+03:00`
- Independence: reviewer did not author or edit the specification, approved test,
  RED evidence, or reviewed production implementation
- Reviewed implementation commit: `598a798600085577a9f51c17200aef90feef1376`
- Parent commit: `e3aece19a7f07cf19423d8039a126f0cd57186db`
- Production diff: `git diff e3aece1..598a798 -- app/PilotHttp/PilotE2ECoordinator.php`
- Gate 4 evidence commit: `32f88362d87cf335d9981c011ed544cd19812c6f`
- Approved Gate 3 record:
  `reviews/tests/PILOT-SESSION-STORAGE-001-accepted-payload-v2.md`
- Verdict: **APPROVED**

## Findings

No blocking findings.

The implementation decodes the owner's canonical whole-array payload and
reuses an existing exact 64-character lowercase hexadecimal `auth_csrf`. On
that accepted branch it performs no `writeCommit` and emits no replacement
`Set-Cookie`. The approved public-seam test independently observes the fixed
CSRF in the raw HTTP response and proves both `(dev, ino)` and committed bytes
remain unchanged. It therefore detects both state loss and an identical-byte
atomic rewrite without inspecting implementation internals.

The changed branch remains fail-closed for a codec rejection. Missing state or
state without an acceptable CSRF follows the pre-existing creation path, now
using the specified whole-array `serialize()` representation. Object/reference
payload rejection, owner payload handoff, and the raw HTTP protocol tracer all
remain green. The diff adds no payload, session ID, path, cookie, exception, or
correlation value to logs or error responses.

The production diff is one line in the existing HTTP composition method and
does not add another filesystem owner or bypass `PilotSessionStorage`. A
standards-axis review noted a non-blocking maintainability smell: payload
decoding, the HTTP-owned `auth_csrf` schema, serialization, persistence, and
cookie publication remain densely coordinated in `sessionResponse()`. Moving
the complete state transition behind a focused HTTP session-state abstraction
may reduce Feature Envy / Primitive Obsession and future Shotgun Surgery, but
that refactor is outside this independently reviewed minimal GREEN and is not
required to satisfy this narrow acceptance statement.

## Scope

This approval closes only the accepted-payload reuse behavior exercised by
`GET /pilot/login`. It does **not** approve or prove POST behavior,
regeneration, the second HTTP consumer, removal of native-session consumers,
Compose restart preservation, full task 3.2, repository-wide architecture
GREEN, or release readiness.

`make architecture-check` remains RED with the known predecessor set of 13
`session_storage_ownership` fingerprints: 2 in
`app/PilotHttp/PilotE2ECoordinator.php`, 6 in `rapid-pilot/LocalAuth.php`, and
5 in `rapid-pilot/UserAccessView.php`. This slice neither claims to remove that
set nor changes its cardinality; the subsequent consumer-removal slice owns
that work.

## Independently reproduced verification

Against `HEAD = 32f88362d87cf335d9981c011ed544cd19812c6f`, which contains exact
implementation commit `598a798600085577a9f51c17200aef90feef1376`:

```text
php -l app/PilotHttp/PilotE2ECoordinator.php
No syntax errors detected in app/PilotHttp/PilotE2ECoordinator.php

php tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP

php tests/InstallationProcess/pilot_session_storage_malformed_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 object payload raw HTTP

php tests/InstallationProcess/pilot_session_storage_reference_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 reference payload raw HTTP

php tests/InstallationProcess/pilot_session_storage_payload_handoff_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 owner payload handoff

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer
```

All 18 `tests/InstallationProcess/*_test.php` files referring to
`PILOT-SESSION-STORAGE-001` or `PilotSessionStorage` were then run
individually: **18/18 PASS**.

```text
git diff --check e3aece1..598a798
exit 0

git merge-base --is-ancestor 598a798 HEAD
exit 0

make architecture-check
exit 2; exact 13 predecessor session-storage ownership fingerprints described above
```

## Reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
ed596ff6516275d193d91a222399c402b5e5d097719524a3b47d7b586de148cc  reviews/tests/PILOT-SESSION-STORAGE-001-accepted-payload-v2.md
9f18b7953d4426c42414d06186561972027e9dc238d13846b46574269507ab41  tests/InstallationProcess/pilot_session_storage_accepted_payload_http_001_test.php
2ce287912275253ecc90a3663426a34a85c27366fbb4eca81ac2972b7be9704e  docs/operations/pilot-session-storage-accepted-payload-red-evidence-v2-2026-09-03.md
e70e345906ab976e15072341d61b2cc81a990265a84aaa7111bf51bf64d59894  docs/operations/pilot-session-storage-accepted-payload-green-2026-09-03.md
4f62a7b14bfe45fc343d4465958f29b0b26078d7d18d43e9df66ae8bddbedb9c  app/PilotHttp/PilotE2ECoordinator.php
```

Gate 5 is **APPROVED** for the exact narrow accepted-payload implementation at
`598a798600085577a9f51c17200aef90feef1376`.
