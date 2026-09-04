# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 sequential write identity v2

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/session_sequential_gate3`
- Test/implementation author: not this reviewer
- Reviewed corrected RED commit: `9facee6ff2d47bdbde3253b8916b33a8222faf11`
- Owner approval commit: `565be908a101ec26aff52c219df642083e610f6a`
- Prior Gate 3: `reviews/tests/PILOT-SESSION-STORAGE-001-sequential-write-identity-v1.md`
- Verdict: **APPROVED**

## Correction reviewed

The Gate 4 constructibility finding was valid. V1 used one linear entropy
queue, so the value reserved for the defect-only second 32-byte ID occupied the
position where corrected production would request its second 16-byte stage
token. That would make the intended GREEN path fail on a wrong-length entropy
result before reaching the approved aggregate assertion.

Commit `9facee6` changes only that fixture and adds append-only correction
evidence. Its test-local entropy port stores separate FIFO queues by requested
length and records every requested length in one ordered trace. Both relevant
paths are now constructible:

- current defect: `32,16,32,16` consumes the two deterministic ID values and
  the two deterministic stage values;
- corrected path: `32,16,16` consumes the first deterministic ID and both
  deterministic stage values without seeing a wrong-length value.

Exhaustion still returns the production typed `failed()` entropy result, and
every supplied `ok()` value has exactly the requested length. The fixture does
not branch on test outcome, owner state, cookie identity or production class;
it only implements the approved public entropy port by its explicit length
argument. The ordered `requestedLengths` observation therefore remains an
independent sensitivity oracle rather than a scripted verdict.

## Retained Gate 3 properties

All behavioral assertions approved in v1 are byte-unchanged. The test still
uses the public production `PilotCommandSession` with the real owner created by
`PilotSessionStorageFactory`; only the four approved ports are injected. It
still requires:

- the first accepted `Set-Cookie` header to remain unchanged;
- exactly one committed file, addressed by that cookie ID;
- exact final whole-array bytes containing the literal token update;
- successful real-owner reopen under the same cookie identity with those
  exact bytes;
- the corrected entropy trace `[32,16,16]` with no second session-ID request;
- every committed-file primitive event to retain `sha256(cookieId)`.

Consequently the test continues to reject a stale cookie, an alternate orphan
identity, updating only the orphan, copying state to two identities, rotating
the cookie during the second write, or fabricating an in-memory success. The
expected relationships remain derived from the owner-approved v10 contract
and its Gate 1 worked scenario, not from planned implementation details.

Cleanup is also unchanged: `finally` closes the owner, removes only the random
task-owned root, verifies the foreign sibling sentinel digest before deleting
the sentinel, and removes the shared parent only when empty. Independent
post-run inspection found no matching owned or foreign fixture residue.

## Independent RED reproduction

```text
$ php -l tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php

$ php -d display_errors=1 -d error_reporting=-1 \
    tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: sequential write retains accepted cookie/current identity and exact updated state
Expected:
  soleCommittedCookieIdentity=true
  cookieMaterialHasUpdatedToken=true
  cookieReopens=true
  reopenedIdentityIsCookie=true
  reopenedPayloadHasUpdatedToken=true
  entropyLengths=[32,16,16]
  committedEventsRetainCookie=true
Actual:
  soleCommittedCookieIdentity=false
  cookieMaterialHasUpdatedToken=false
  cookieReopens=true
  reopenedIdentityIsCookie=true
  reopenedPayloadHasUpdatedToken=false
  entropyLengths=[32,16,32,16]
  committedEventsRetainCookie=false
exit=255

$ grep -Ec '(^| )Warning:|PHP Warning:' /tmp/session-sequential-g3-v2.out
0

$ find /tmp/fmonitor2-session-storage-tests -maxdepth 1 \
    \( -name 'task-sequential-*' -o -name 'foreign-sequential-*' \)
(no output)

$ git diff --check 9facee6^ 9facee6
(no output; exit 0)
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
296c9fe1c2d30dec359413cce6813e747005b694858e0393a4fd902d5b10faab  docs/operations/pilot-session-storage-gate1-rereview-v15.md
b8a81791040411353ec610ffb5053ac484f812998380821490157f23dc8c6ff7  docs/operations/pilot-session-storage-v10-owner-approval-2026-09-04.md
ad871362746b391c18b50b8338e862bad3c0fd8d844eac3d8b309b9fa91558a6  tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
f2b19764838cb1fb2e99167fad424dc8e8c0d841c1fa176a302b53f38c3df922  docs/operations/pilot-session-storage-sequential-write-red-v2-2026-09-04.md
```

Fresh Gate 3 v2 is **APPROVED** for these exact expectations. Gate 4 may resume
with the smallest production change that preserves the first successfully
published anonymous identity across later ordinary writes.
