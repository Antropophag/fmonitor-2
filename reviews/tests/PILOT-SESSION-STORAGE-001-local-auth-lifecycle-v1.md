# Independent Gate 3 test review — PILOT-SESSION-STORAGE-001 v10 LocalAuth lifecycle v1

- Date: 2026-09-03T23:04:26+03:00
- Reviewer: separately tasked agent `/root/session_local_auth_gate3`
- Test/implementation author: not this reviewer
- Reviewed commit: `90db9b0c84d5f43c2ed7b286a25103cd9315457d`
- Specification: owner-approved `PILOT-SESSION-STORAGE-001` v10, sections 3,
  6–9 and 11
- Public seam: raw HTTP through `rapid-pilot/router.php` and the real
  `RapidPilotLocalAuth`
- Red command: `php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php`
- Intended failure: first `GET /pilot/login` returns `503` rather than `200`
  because LocalAuth still ignores the configured owner and attempts its absent,
  non-creatable legacy native-session path
- Verdict: **CHANGES_REQUESTED**

## Findings

The focused happy-path sequence is valuable and reaches the right public seam.
It uses prefix-scoped fictional authentication tables and a random test-owned
0700 state root, obtains the CSRF value from the rendered form, exercises a
protected GET to establish a literal safe return-to, logs in using a literal
fixture identity and password, and logs out through raw HTTP. The resulting
file observations distinguish owner publication from request-memory mutation:
anonymous material must appear under the configured instance, regeneration
must make the old ID unaddressable while publishing authenticated actor facts
under the new cookie ID, and destroy must leave no addressable committed
session.

The RED is honest and attributable to missing production behavior. On exact
`90db9b0`, syntax passes and the test fails at the first intended assertion with
`Expected: 200`, `Actual: 503`, exit 255. The database and server readiness
completed before this assertion. Independent probes found
`/home/fmonitor/.local/state/fmonitor2/sessions` absent both before and after the
run, so no foreign legacy material was created. The test-owned root was removed
and no matching task directory remained. The random database prefix and root
make concurrent runs isolated; `finally` reaps the server, drops the exact
prefix tables, closes the database and removes only the owned root.

However, this test permits an implementation to emit successful redirects or
cookies before failed owner commits. The reviewed production change must replace
all six remaining LocalAuth native lifecycle fingerprints at once, but the test
contains only successful `writeCommit`, `regenerate`, and `destroyCommit`
paths. The older protocol tracer injects an anonymous-login write failure and
checks generic invalid-configuration GET/HEAD/POST envelopes; it does not reach
the LocalAuth return-to write, successful-login regeneration, or authenticated
logout destruction branches. Owner-level lifecycle fault tests cannot prove
HTTP response buffering. Therefore sections 6, 7, 8 and 11 are not sensitive at
the seam where this slice changes behavior.

The canonical payload oracle is also incomplete. `unserialize()` plus selected
field comparisons proves whole-array framing and values, but does not require
the committed bytes to be the byte-identical canonical `serialize()` encoding
mandated by section 3. The test should retain the raw bytes and independently
assert `serialize($decoded) === $bytes` for each inspected committed payload;
after the return-to request it should reopen/read and require the literal
`auth_return_to` value as committed owner state rather than infer persistence
only from the later redirect.

Expected statuses, body, header exclusions, paths, actor values and return-to
are otherwise literals derived from the specification and fixture, not copied
from production. Direct reads are limited to the contractually public managed
session artifacts and are appropriate here as external publication/invalidation
evidence.

## Required changes

1. Add an external primitive-fault raw-HTTP branch for the protected anonymous
   GET return-to `writeCommit`. Fail the exact publication primitive and require
   the complete section-6 `503` envelope, no `Location` or `Set-Cookie`, exactly
   one expected safe trace event, and preservation of the prior committed
   bytes.
2. Add the equivalent raw-HTTP branch for successful-login `regenerate`.
   Require that buffered `303`, return-to `Location`, and replacement cookie are
   absent on failure; assert the exact `503` response and the section-5
   pre-invalidation validity outcome selected by the injected primitive.
3. Add the equivalent raw-HTTP branch for logout `destroyCommit`. Inject a
   pre-unlink failure so the old authenticated session must remain valid, and
   require exact `503` with neither redirect nor deletion cookie plus one exact
   safe trace event.
4. For anonymous, return-to-updated, and authenticated committed states, retain
   the raw payload and require byte-identical `serialize($decoded)` equality.
   Reopen the session after the protected GET and assert the literal
   `/pilot/objects/4512` return-to is committed before attempting login.
5. Reproduce a fresh intended RED after these assertions are present and update
   the append-only evidence/hashes. Request a new independent Gate 3 review of
   that exact commit.

## Independent reproduction

```text
$ git rev-parse HEAD
90db9b0c84d5f43c2ed7b286a25103cd9315457d
$ php -l tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
$ php tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
PHP Fatal error: Uncaught TestFailure: INTENTIONAL_RED: LocalAuth GET uses configured owner
Expected: 200
Actual: 503
exit=255
$ legacy=/home/fmonitor/.local/state/fmonitor2/sessions
$ test ! -e "$legacy" # before and after run
exit=0
$ find /tmp/fmonitor2-session-storage-tests -mindepth 1 -maxdepth 1 -type d
(no output)
$ git diff --check HEAD^ HEAD
exit=0
```

## Exact reviewed hashes

```text
054004a16fad845e9c42d5c8f5cf2f0303998c082695b2792a73e03bf20ca30f  specs/PILOT-SESSION-STORAGE-001.md
f8cfa30eace1eb62c8e32cc3a5d308019003088992746769e671e4635c8dcb9a  tests/InstallationProcess/pilot_session_storage_local_auth_lifecycle_001_test.php
f1836e8ce9106e2b0b08a24af69dc7fc53a5ffab2a8dff1d0ce8ee79b9e3a83a  docs/operations/pilot-session-storage-local-auth-lifecycle-red-evidence.md
```

Gate 4 must not begin for this LocalAuth lifecycle slice until a fresh
independent Gate 3 review approves the strengthened exact oracle.
