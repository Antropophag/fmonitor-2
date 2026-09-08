# Independent Gate 5 rereview — PILOT-SESSION-STORAGE-001 v9 pure route recognition

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/session_ratchet_gate5_v2`
- Independence: reviewer did not author the specification, corrective test,
  RED/GREEN evidence, or implementation
- Reviewed corrective HEAD:
  `dd085262968201e1442e5672c307cbf101b0c8ad`
- Corrective diff: `74ca844..dd08526`
- Gate 1 authority:
  `docs/operations/pilot-session-storage-gate1-rereview-v12.md` — `APPROVED`
- Corrective Gate 3:
  `reviews/tests/PILOT-SESSION-STORAGE-001-v9-route-recognition-v4.md` — `APPROVED`
- Superseded Gate 5 result remains immutable:
  `reviews/code/PILOT-SESSION-STORAGE-001-v9-unknown-route.md` —
  `CHANGES_REQUESTED`
- Verdict: **APPROVED**

This record reviews only the corrective route-recognition slice. It does not
rewrite the prior finding or broaden approval to other session-storage tasks.

## Standards

No blocking finding remains.

The correction introduces `matchesLegacyCompletionBlock(string): bool` as a
pure exact-path predicate. Pre-authentication route admission now calls only
that predicate. It reads no method, request body, environment, session,
filesystem or database state; it emits no status, headers or body.

The existing `blocksLegacyCompletion()` business handler is called at exactly
one router site, after `RapidPilotLocalAuth::handle()` returns successfully. It
retains its existing POST check and delegates only its unchanged exact path
regex to the new predicate before reading `php://input`. Thus the correction
removes the pre-authentication side effect without duplicating or relocating
business behavior.

The small predicate is justified at this boundary and does not introduce a
material code smell. It makes the distinction between recognition and command
handling explicit and prevents their logic from drifting.

## Specification

The production correction exactly addresses prior finding S1. Anonymous POST
to the known checklist-operation route with invalid session configuration now
reaches authentication/session admission and returns the exact section 6 503;
no business JSON is emitted before it. Unknown routes retain their exact 404,
and existing asset, malformed Host/URI, HTTP/HTTPS cookie, authenticated route,
and login-route priority controls remain green.

Path and method semantics are not expanded:

- the new predicate uses the same anchored path expression previously embedded
  in the handler;
- it recognizes only `/pilot/objects/<positive-decimal>/checklist/operations`;
- recognition is method-neutral, as required to decide whether the path exists;
- the post-auth business handler still accepts only POST and otherwise returns
  without consuming the body;
- the `itemId=42` rejection logic and output are unchanged and remain post-auth.

The approved test hash is unchanged after Gate 3 and the public raw-HTTP oracle
would catch reintroduction of the prior body-consuming recognition: any emitted
business bytes make the exact 503 body comparison fail.

## Verification evidence

At exact SHA `dd085262968201e1442e5672c307cbf101b0c8ad`:

```text
sha256sum tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
d535ef0d8ea2856f0404e8adb56f01d743d261c65d1ea83cd33dd6aa1d2f8e82

php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer

php rapid-pilot/verify-completion-flow.php
PASS rapid completion flow 85% -> PTO -> declaration -> 100%

php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

php -l rapid-pilot/CompletionFlow.php
php -l rapid-pilot/router.php
# both report no syntax errors

git diff --check 74ca844..dd08526
# exit 0, no output
```

The HTTP boundary suite's first run encountered its known asynchronous DB
connection-cleanup observation (one process-list entry remained); an immediate
unchanged rerun passed. The corrective test, completion regression, global-call
test, architecture check, lint and diff hygiene all passed on their first run.

Reviewed hashes match Gate 3 / GREEN evidence:

```text
7135cb1418c71b61f74259c6f590179f92455e3cb2375cfd1aed19cc93f09d30  specs/PILOT-SESSION-STORAGE-001.md
d535ef0d8ea2856f0404e8adb56f01d743d261c65d1ea83cd33dd6aa1d2f8e82  tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
da1c760f5e22b515bd4a42f9f112e276c98e9c29c9294f2bee6ba62bb42eccf8  docs/operations/pilot-session-storage-unknown-route-red-evidence-v4-2026-09-04.md
60d28e21686fc0141c140f7c888e38207bd31522ee567aa7e7caeb82c6e477db  reviews/code/PILOT-SESSION-STORAGE-001-v9-unknown-route.md
6df6e09011be86c84849fd23ad4ef4dc2d53b00ff66243da009cba1d939463cc  rapid-pilot/CompletionFlow.php
e6e6dbb106261392fe96d4f5b38540c82a8cf1debce541eaf24fe902f7351a55  rapid-pilot/router.php
```

## Gate consequence

**APPROVED** for the corrective route-recognition slice at `dd08526`. Prior S1
is resolved, the Gate 3 test remains byte-identical, relevant behavior and
regressions are green, and the v9 unknown-route slice may advance past Gate 5.
