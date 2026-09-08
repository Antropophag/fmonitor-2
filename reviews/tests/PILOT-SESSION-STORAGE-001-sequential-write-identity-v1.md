# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 v10 sequential write identity v1

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/session_sequential_gate3`
- Test/implementation author: not this reviewer
- Reviewed RED commit: `54743c18d59dbf24014d09f51137e6933e9660aa`
- Owner approval commit: `565be908a101ec26aff52c219df642083e610f6a`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10, sections 3,
  4, 6, 8 and 11
- Verdict: **APPROVED**

## Findings

The reviewed commit adds only the focused test and append-only RED evidence.
It exercises the public production `PilotCommandSession` seam backed by the
real owner from `PilotSessionStorageFactory`. The fixture replaces only the
four explicitly approved ports: filesystem primitives, clock, entropy and
lifecycle observer. It does not construct operation results, call a private
owner method or synthesize the behavior under review.

The test performs the exact Gate 1 sensitivity scenario. An anonymous
`open(..., create: true)` commits the initial whole-array state and publishes
the accepted cookie. A later `replace(..., commit: true)` adds a literal
token-like value on the same command session. The expectations require that
the first accepted cookie header remain unchanged, exactly one committed file
exist under that cookie identity, and that its exact bytes equal the final
whole-array `serialize($state)` representation.

The material assertions are independently corroborated by a subsequent real
`start(cookieId)`: it must reopen successfully, retain the cookie ID and return
the exact updated payload. The entropy-length oracle independently proves that
the second write requests only a fresh 16-byte stage token and does not rotate
through another 32-byte session ID. Paired public observer events prove that
every committed-file primitive remains bound to `sha256(cookieId)`. Together
these assertions reject a stale cookie, a hidden alternate committed identity,
copying the update to both identities, merely changing the response header, or
fabricating an in-memory success without owner-readable material.

The expected cookie grammar, token value, serialized final state relationship,
entropy lengths and identity/hash relationships come from the approved
contract and worked Gate 1 scenario. Random bytes are used only for isolated
task paths and the command-session secret; no expected identity or token is
copied from an implementation constant. Using the owner-accepted first cookie
ID as the relational identity is necessary because the contract permits the
first anonymous no-clobber attempt to select a new owner-generated candidate
after collision.

The reproduced failure is the intended missing transition, not setup failure:
the cookie session reopens under its original ID, but the second write consumes
an additional 32-byte value, publishes an alternate committed identity, leaves
the cookie-addressed payload without the update, and emits committed events for
both hashes. All four independent manifestations change together when the
first successful anonymous `writeCommit` result becomes the owner's current
non-anonymous identity.

The test is deterministic apart from collision-resistant test-owned path names.
Its `finally` closes the owner, recursively removes only the exact random
task-root, verifies a foreign sibling sentinel byte-for-byte, deletes that
sentinel, and removes the shared parent only when empty. Independent post-run
inspection found no `task-sequential-*` or `foreign-sequential-*` residue.

No blocking traceability, public-seam, sensitivity, expected-value,
determinism, isolation or cleanup finding remains.

## Independent RED reproduction

```text
$ php -l tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
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

$ find /tmp/fmonitor2-session-storage-tests -maxdepth 1 \
    \( -name 'task-sequential-*' -o -name 'foreign-sequential-*' \)
(no output)
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
296c9fe1c2d30dec359413cce6813e747005b694858e0393a4fd902d5b10faab  docs/operations/pilot-session-storage-gate1-rereview-v15.md
b8a81791040411353ec610ffb5053ac484f812998380821490157f23dc8c6ff7  docs/operations/pilot-session-storage-v10-owner-approval-2026-09-04.md
e5ffd081a0d7bf93c2468280bb09ba016736ea35874b42c63fa8bd080bdbf07b  tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
09fc1cb65c5f91289d56e59f320ad0216617fb541149e789aba87a8ac9613f3a  docs/operations/pilot-session-storage-sequential-write-identity-red-evidence-2026-09-04.md
```

Gate 3 is **APPROVED** for these exact expectations. Gate 4 may make the
smallest production change that preserves the first accepted session/cookie
identity across later ordinary writes, without weakening this test.
