# Test review: PILOT-SESSION-STORAGE-001 v9 unknown-route amendment — RED v2

- Gate: 3 — independent test review
- Reviewer: separately tasked agent `/root/qg_test_review`
- Independence: this reviewer did not author the specification, tests, RED evidence, or implementation
- Reviewed RED commit: `ec04c3229076bcc509eb59d9d5f62db3d042f53d`
- Gate 1 authority: `docs/operations/pilot-session-storage-gate1-rereview-v12.md` — `APPROVED`
- Verdict: **CHANGES_REQUESTED**

## Blocking findings

1. **The resent cookie is a valid anonymous session, not a valid authenticated session.** The positive loop performs only `GET /pilot/login`, which creates a session and CSRF token. It never extracts that token and submits valid credentials through `pssPostCookie`, nor otherwise proves the session contains an authenticated principal. Replaying the anonymous cookie to `/pilot/not-found` therefore does not satisfy Gate 1's explicit anonymous-versus-authenticated equivalence case. Labeling it “authenticated” does not change fixture state.

2. **The exact inherited security/header contract remains incomplete.** `pssUnknown404()` checks most security headers but omits the required exact `Content-Security-Policy`. It also does not forbid `Server` or assert the no-unspecified-application-header set. A 404 with missing CSP or an extra application header can pass, contrary to the reviewed exact inherited response contract.

Return to Gate 2 by establishing and proving an authenticated session before the unknown-route request (for example, extract CSRF, perform the approved login command with known fixture credentials, prove its authenticated success/control, then resend the resulting cookie). Extend the shared 404 assertion with exact CSP and the complete allowed/forbidden header set from the inherited HTTP contract. Recapture RED.

## Corrections that passed review

- The invalid-storage unknown route now uses a shared exact 404 assertion with status, `text/plain`, byte length `11`, exact `Not found.\n`, no redirect/cookie/auth/CORS/X-Powered-By, and most inherited security/cache headers.
- The request helper can send an exact cookie pair, and both HTTP and HTTPS positive loops actually resend the cookie returned by login GET.
- The companion `pilot_http_auth_001_test.php` exact hash `0c8074...` proves unknown route call order `['correlation','close']`, zero environment reads, no application dependencies/auth resolution, and one cleanup close at the public entrypoint seam.
- Outer Host/URI priority, known asset controls, public login behavior, known login-required paths and HTTP/HTTPS cookie attributes remain covered, preserving compatibility with `PILOT-HTTP-AUTH-001` and preventing a blanket unknown response.
- Server/state cleanup remains protected by nested `finally` blocks.

## Intended RED

The v2 evidence retains an intended first failure: known and unknown assets pass under invalid storage, then unknown non-asset returns current `503` instead of required route-first `404`. This is missing behavior rather than setup failure. PHP syntax is valid.

## Reviewed hashes

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
a7ffbccf465e3f95999f079e969cc561d85c8a2cc7625814bb8b967fdf7355d9  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
0c8074ed4548f34fc12e7c3f6a4a30458939f0726caaa78d7b21c3b1b4b1c118  tests/InstallationProcess/pilot_http_auth_001_test.php
```

Gate 3 is not approved. No unknown-route implementation is authorized from RED v2.
