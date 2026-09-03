# Independent Gate 3 rereview — PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle v2

- Date: 2026-09-03T23:10:35+03:00
- Reviewer: separately tasked agent `/root/session_local_auth_gate3_v2`
- Test/implementation author: not this reviewer
- Reviewed commit: `a2dd5581d9459ad23526824f1ac866c2ebc7badc`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10 exact hash,
  sections 3 and 6–9, plus the prior v1 `CHANGES_REQUESTED` record
- Public seam: raw HTTP through the production rapid router and through the
  public injected-dependency entrypoint factory
- Verdict: **CHANGES_REQUESTED**

## Findings

The v2 additions close most of the prior fault-boundary gap. The return-to
tracer seeds canonical owner material, enters through raw HTTP, then reopens it
through the real owner and requires both the literal safe return-to and
`serialize($decoded) === $bytes`. The fault tracer constructs all three
requested failures through the public factory graph: existing-session
`rename(committed, ordinal 1) -> native false` for return-to write, and the
pre-invalidation `unlink(committed, ordinal 1) -> native false` for regenerate
and destroy. Those tuples are constructible: each server receives a fresh
filesystem wrapper, so the relevant operation has ordinal one; each request is
seeded with a committed old session; and the queued entropy covers the new ID,
stage, revoked artifact and correlation paths that can precede the selected
failure. The assertions require the complete section-6 status/body/header
envelope, forbid buffered redirect/cookie headers, require exactly one matching
redacted failure tuple, and compare the old committed bytes after the request.

The fixture roots and database prefixes are random and test-owned. `finally`
reaps the current server, drops only the exact prefix tables and recursively
removes only the exact owned roots. Independent runs left the hard-coded legacy
root absent and no task directory behind. Expected paths, response values,
actor facts and fault tuples are literal contract/fixture values rather than
production-derived expectations.

One requirement from the v1 rereview remains unimplemented. The complete
lifecycle test still decodes the initial anonymous payload and regenerated
authenticated payload but never retains their raw bytes or asserts
`serialize($decoded) === $raw`. The only new byte-identical assertion is in the
separate return-to test, after `auth_return_to` has been written. Consequently a
future LocalAuth implementation could publish a non-canonical initial
anonymous encoding, or a non-canonical authenticated encoding during
regeneration, while all three reviewed tests pass. This is precisely the
section-3 sensitivity gap identified in v1 required change 4, which explicitly
required the oracle for anonymous, return-to-updated **and authenticated**
committed states. The v2 evidence statement that “the later assertions require
`serialize(decoded) === raw`” overstates the current suite's coverage.

## Required changes

1. In the lifecycle tracer, retain the raw anonymous bytes and require
   `serialize($anonymous) === $anonymousBytes` before the protected request.
2. Retain the raw regenerated authenticated bytes and require
   `serialize($authenticated) === $authenticatedBytes` before logout.
3. Reproduce the intended RED without production implementation, update the
   append-only evidence and hashes, and request a fresh independent Gate 3
   review of that exact commit.

## Independent reproduction

```text
$ git rev-parse HEAD
a2dd5581d9459ad23526824f1ac866c2ebc7badc
$ php -l tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
$ php -l tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
$ php -l tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
$ php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
INTENTIONAL_RED: LocalAuth GET uses configured owner; expected 200, actual 503; exit=255
$ php tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
INTENTIONAL_RED: owner LocalAuth redirects anonymous protected GET; expected 303, actual 401; exit=255
$ php tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
INTENTIONAL_RED: LocalAuth write exact fault status; expected 503, actual 401; exit=255
$ test ! -e /home/fmonitor/.local/state/fmonitor2/sessions # before and after
exit=0
$ find /tmp/fmonitor2-session-storage-tests -mindepth 1 -maxdepth 1 -type d
(no output)
$ git diff --check 90db9b0..a2dd558
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
08676b53f6052cce06f33f5e2e3c48ae1f6bb559a9338f8f1174a5cd0a5bccad  tests/InstallationProcess/pilot_session_storage_local_auth_faults_001_test.php
965c7d7a0cbe88d60fa311b8542ef8bb1173e9009cf4952f002e185e23edaf09  tests/InstallationProcess/pilot_session_storage_local_auth_return_to_001_test.php
048d764f25d9609ed2b0c899d45b5adc187dd15c62215488918d43dd6555a6cd  tests/Support/pilot_session_storage_local_auth_fault_common.php
0c7ab38aafe880c85d6518546ffad75b470c49f5ec9ca55ee6ee829dfcca7d08  tests/Support/pilot_session_storage_local_auth_write_fault_router.php
4736aec39bf3c4eb5d3ccdda2f0232520eab3cf6859ddae78825db8ae710aeae  tests/Support/pilot_session_storage_local_auth_regenerate_fault_router.php
2e23f903635c26adbf52a82f2b4b8823974e790bb8a431fb419efe8a90c859f9  tests/Support/pilot_session_storage_local_auth_destroy_fault_router.php
84b5392735ea96dd58cdab962a066374fd571819d4d78cae6b7f97276dcaf2c1  tests/Support/pilot_session_storage_local_auth_router.php
8ad78b6769490868834b2fa7cc8b5a8fd2f00988307ab7dc730a14e09e9111f4  docs/operations/pilot-session-storage-local-auth-lifecycle-red-evidence-v2.md
```

Gate 4 remains closed for this LocalAuth lifecycle slice.
