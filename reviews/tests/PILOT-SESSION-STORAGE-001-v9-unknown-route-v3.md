# Test review: PILOT-SESSION-STORAGE-001 v9 unknown-route amendment — RED v3

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, RED evidence, or implementation
- Reviewed RED commit: `96a2c262320cd502277cc742d9ef22ab7b151f19`
- Gate 1 authority: `docs/operations/pilot-session-storage-gate1-rereview-v12.md` — `APPROVED`
- Verdict: **APPROVED**

## Findings

No blocking findings.

The two-test oracle jointly covers the v9 amendment without duplicating the mature HTTP seam:

- `pilot_session_storage_protocol_001_test.php` proves unknown non-asset 404 before invalid session configuration, exact raw response bytes/headers, and route priority when an existing anonymous session cookie is supplied under both HTTP and HTTPS transport settings.
- `pilot_http_auth_001_test.php` creates `successServer` with valid trusted `REMOTE_USER=sidorov@shlz.ru`, proves that identity/context with a successful 200 shell, then sends `/pilot/unknown` through the same server and requires the inherited exact 404. This is genuine authenticated-server coverage rather than merely relabeling an anonymous cookie.
- The companion real-entrypoint spy requires unknown-route calls to be exactly `['correlation','close']`; its environment probe requires zero environment reads. Together they exclude identity/auth resolution, configuration, DB/CSS/session dependencies and filesystem primitives before the 404.

## Protocol and priority review

`pssUnknown404()` now requires:

- status `404`, exact `Content-Type`, `Content-Length: 11`, and body `Not found.\n`;
- exact cache, nosniff, referrer, frame, CSP, permissions and COOP headers;
- no Location, Set-Cookie, WWW-Authenticate, CORS, X-Powered-By or Server header;
- no header outside the exact application set plus permitted transport Date/Connection/Host.

The invalid-storage server first proves known and unknown assets retain asset-first behavior, then the unknown non-asset expectation distinguishes the required 404 from current 503. Malformed Host/URI retain the outer 400 boundary. Known login and login-required paths retain their distinct session/storage behavior, so a broad unconditional 404 cannot pass.

This matches `PILOT-HTTP-AUTH-001`: valid unknown routes are resolved before method and authentication, while request-integrity Host/URI checks remain outside and earlier than route matching.

## Intended RED

Recorded command:

```text
FMONITOR_DB_HOST=127.0.0.1 FMONITOR_DB_PORT=23306 \
FMONITOR_DB_NAME=fmonitor2_test FMONITOR_DB_USER=fmonitor2_test \
FMONITOR_DB_PASSWORD=<REDACTED> \
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
```

Known/unknown asset controls pass. The first new unknown non-asset case then returns current `503` instead of expected `404`, exiting `255`. PHP syntax validation passes for both reviewed tests. This is missing route-first behavior, not setup failure.

## Isolation and cleanup

Loopback servers and per-scheme random session roots are owned by nested `try/finally` blocks. Servers are stopped on failure, mutable state roots are recursively removed, and the companion HTTP test separately restores database/CSS fixtures and closes resources. No production state or external service supplies expected values.

## Reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
315825c95c7ba4059b63e298bf3f710621ff9ffd3e33c57c4982fb43146204d3  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
0c8074ed4548f34fc12e7c3f6a4a30458939f0726caaa78d7b21c3b1b4b1c118  tests/InstallationProcess/pilot_http_auth_001_test.php
```

## Authorized minimal GREEN

Gate 3 authorizes only route recognition/fallback placement needed for every valid unknown `/pilot/*` request to return the inherited 404 before session/config/filesystem/auth access, while preserving outer Host/URI checks and known route behavior. Test expectation changes restart Gate 2.

Gate 3 is **APPROVED**.
