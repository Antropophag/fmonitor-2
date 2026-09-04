# PILOT-SESSION-STORAGE-001 v10 — sequential write identity Gate 2 RED

Date: `2026-09-04`

Authority: owner-approved replacement Gate 2 package in
`docs/operations/pilot-session-storage-v10-owner-approval-2026-09-04.md`,
commit `565be90`; independent Gate 1 v15 is
`docs/operations/pilot-session-storage-gate1-rereview-v15.md`, commit
`4cef572`.

## Public seam and oracle

`tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php`
constructs the real owner with `PilotSessionStorageFactory`, then performs the
two writes through production `PilotCommandSession`:

1. anonymous `open(..., create: true)` performs the first commit and publishes
   the accepted `fm2auth` cookie header;
2. `replace(..., commit: true)` adds an exact token-like value and performs the
   second commit on the same command session.

The fixture replaces only the approved filesystem, clock, entropy and observer
ports. It does not call an owner-result/event factory. Independent observations
are the published cookie header, exact committed basenames and bytes under the
task-owned root, entropy request lengths, owner-emitted event accessors and a
subsequent real `start(cookieId)` payload.

Expected material is exactly one committed file addressed by the accepted
cookie, containing `serialize($state)` after the token update. The second write
may request one new 16-byte stage token, but no new 32-byte session ID. Every
committed event must retain `sha256(cookieId)`. A second committed file is an
orphan alternate identity and is forbidden.

## Demonstrated RED and sensitivity

```text
$ php -l tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php

$ php tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
Fatal error: Uncaught TestFailure: INTENTIONAL_RED: sequential write retains accepted cookie/current identity and exact updated state
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
```

The deterministic first ID is 64 `1` characters. Current production retains
that ID in `anonymousIds` after its first successful publication. On the second
write it therefore consumes the otherwise-unused 32-byte `0x33` entropy value,
publishes the alternate ID of 64 `3` characters and leaves both committed files
present. The already published cookie still reopens the first identity, but
that payload lacks the token update. Thus the test is sensitive to the exact
missing transition: accepting the first successful `writeCommit` identity as
the owner's current non-anonymous identity removes the extra 32-byte request,
the alternate committed hash and all four false observations together.

The failing run completed its `finally`: the owner was closed, the exact task
root was removed, the foreign sentinel digest was preserved, and no owned
`task-sequential-*` path remained.

## Exact hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
79f41f73ff2f64c52b4c07d0a10fb14cf09f2517650d97ffb5ab4a3f2ef0d1b2  openspec/changes/define-pilot-session-storage-contract/specs/security/pilot-session-storage/spec.md
296c9fe1c2d30dec359413cce6813e747005b694858e0393a4fd902d5b10faab  docs/operations/pilot-session-storage-gate1-rereview-v15.md
b8a81791040411353ec610ffb5053ac484f812998380821490157f23dc8c6ff7  docs/operations/pilot-session-storage-v10-owner-approval-2026-09-04.md
e5ffd081a0d7bf93c2468280bb09ba016736ea35874b42c63fa8bd080bdbf07b  tests/InstallationProcess/pilot_session_storage_sequential_write_identity_001_test.php
```

Production, executable specification, OpenSpec artifacts and review records
were not edited by this Gate 2 slice. Gate 3 remains pending and independent.
