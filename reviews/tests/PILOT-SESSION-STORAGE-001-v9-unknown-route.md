# Test review: PILOT-SESSION-STORAGE-001 v9 unknown-route amendment

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, test, RED evidence, or implementation
- Reviewed RED commit: `3d8e42d9476c06d66057f11b9daccea3f49e739e`
- Specification Gate 1: `docs/operations/pilot-session-storage-gate1-rereview-v12.md` — `APPROVED`
- Owner decision: `docs/operations/pilot-session-storage-unknown-route-owner-decision-2026-09-03.md`
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

1. **The required authenticated unknown-route case is not exercised.** In each positive http/https iteration the test performs anonymous `GET /pilot/login`, extracts the returned cookie, but then calls `pssRequest()` for `/pilot/not-found`. That helper has no cookie parameter and sends no `Cookie` header. The second request is therefore anonymous again. No login POST or other fixture step establishes a valid authenticated session. Gate 1 v12 explicitly requires identical 404 behavior for both anonymous and requests carrying a valid authenticated session.

2. **The new 404 assertions do not prove the exact inherited response contract.** They check status, body and absence of `Location`/`Set-Cookie`, but omit the required exact `Content-Type: text/plain; charset=UTF-8`, matching `Content-Length`, inherited security/cache headers, and forbidden application headers. A partial or malformed 404 response would pass.

3. **Zero session/auth dependency access is not observed.** The invalid `FMONITOR_SESSION_STATE_ROOT` case shows route-first priority over one bad configuration value, but it does not prove Gate 1's stronger zero session-environment, session-filesystem, primitive and authentication calls. A handler could read environment or invoke auth/session collaborators and still return the asserted 404. The positive router uses a `RecordingPilotSessionObserver`, but the test has no cross-process observation assertion for the unknown route.

Return to Gate 2 with explicit anonymous and valid-authenticated raw HTTP requests carrying distinguishable session state. Both must assert the complete inherited 404 headers/body and no auth/session headers. Use recording/failing dependency probes visible to the parent test to prove zero session environment/config/filesystem/primitive/auth access. Preserve known asset, public login and known login-required controls so a broad pre-routing 404 cannot pass.

## Checks that passed

- **Authority and traceability:** v9 hash `7135cb...` matches the independently approved Gate 1 package and owner decision. `PILOT-HTTP-AUTH-001` independently supplies the generic unknown-route 404 and route-before-auth priority.
- **Intended RED:** known and unknown assets first bypass invalid session configuration. The unknown non-asset then returns 503 because current production enters storage configuration; expected 404 is missing behavior, not test/bootstrap failure.
- **Invalid-storage priority:** the new first assertion does distinguish route-first 404 from the current storage-unavailable 503.
- **Basic protocol:** both invalid-storage and positive-server unknown requests require status 404, exact body `Not found.\n`, and absence of login redirect/session cookie.
- **Compatibility controls:** malformed Host/URI retain outer-boundary 400 behavior; known `/pilot/login` remains public and creates its session cookie; known login-required `/pilot/admin/users` remains on the storage/auth path; asset routes retain asset-first behavior. This is consistent with `PILOT-HTTP-AUTH-001` route priority and prevents the simplest blanket 404 implementation.
- **Isolation/cleanup:** servers are stopped in nested `finally` blocks and random state roots are recursively removed. Syntax validation passes.

## RED evidence reviewed

```text
FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 \
FMONITOR_DB_NAME=fmonitor2_test FMONITOR_DB_USER=fmonitor2_test \
FMONITOR_DB_PASSWORD=<REDACTED> \
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

Recorded exit is `255` at the intended first new assertion:

```text
INTENTIONAL_RED: unknown non-asset rejected before invalid storage
Expected: 404
Actual: 503
```

Local syntax verification:

```text
No syntax errors detected in tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

## Reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
c58d4e21cafe91f1e589deb3d4c413ce5528ed2367df1403b43cea01967ec30c  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
484b22b9a48219d9640a1881e6983339339c669058f8e4fcd29615789bf98118  docs/operations/pilot-session-storage-unknown-route-owner-decision-2026-09-03.md
```

Gate 3 is not approved. No unknown-route implementation is authorized from this test revision.
