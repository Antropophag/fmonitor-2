# Independent Gate 5 code review — PILOT-SESSION-STORAGE-001 v9 unknown-route

- Date: 2026-09-04
- Reviewer: separately tasked agent `/root/session_ratchet_gate5_v2`
- Independence: reviewer did not author the specification, tests, RED evidence,
  implementation, or GREEN evidence for this sub-slice
- Reviewed implementation / branch HEAD:
  `e9bc61d3e63d4156d2d38f0b9898f0c6bcd58389`
- Fixed point: `origin/main` at
  `2bff0a0e6baaab61679321001c57cbc916609295`
- Gate 1 authority:
  `docs/operations/pilot-session-storage-gate1-rereview-v12.md` — `APPROVED`
- Gate 3 authority:
  `reviews/tests/PILOT-SESSION-STORAGE-001-v9-unknown-route-v3.md` — `APPROVED`
- Verdict: **CHANGES_REQUESTED**

The review covers the v9 owner-approved unknown-route amendment, the approved
tests, GREEN evidence, the production diff and its interaction with existing
rapid-pilot route handlers. This append-only review record is the reviewer's
only change.

## Standards

### S1 — blocking: route recognition invokes a stateful request handler before authentication

`rapid-pilot/router.php:213` computes `knownAdapterRoute` by calling
`RapidPilotCompletionFlow::blocksLegacyCompletion($path)`. Despite its boolean
name, that method is not a predicate: at `rapid-pilot/CompletionFlow.php:45-48`
it reads `php://input`, parses the command, sets response status/headers and
emits JSON. The new call is before `RapidPilotLocalAuth::handle()` on line 214.

This violates the repository boundary rule and the approved route-priority
contract. Route recognition must be side-effect-free; it cannot consume a
command body or emit a business rejection before authentication/session route
admission. It also calls the same method again after authentication on line
240, when the body may already have been consumed. Consequently a POST to a
known checklist-operation route can be corrupted, and the `itemId=42` branch
can disclose/emit its business result before authentication.

Replace the admission argument with a pure path predicate. Keep the existing
`blocksLegacyCompletion()` command handling solely after authentication, or
separate its pure `matches` check from its body-consuming handler.

No other blocking standards or maintainability finding was found in the
unknown-route implementation. The lazy session construction is an appropriate
boundary for ensuring unknown routes do not read session configuration.

## Specification

The implementation produces the required exact 404 for the reviewed anonymous,
cookie and authenticated unknown-route cases, and preserves outer Host/URI,
asset and known login-route controls. However, S1 violates the Gate 1 condition
that route matching is an admission step before authentication while known
route behavior remains distinct. A known command is being partially executed
during what is presented as recognition.

The approved tests would not catch this plausible regression: their known-route
controls do not send an unauthenticated/authenticated checklist-operation POST
whose body triggers `blocksLegacyCompletion()`, and they do not assert that
adapter route predicates leave `php://input`, headers and output untouched.
Because closing S1 requires a sensitivity test for side-effect-free known-route
recognition, this finding returns the sub-slice to Gate 2 and requires a fresh
independent Gate 3 approval before another GREEN.

## Verification evidence

At exact SHA `e9bc61d3e63d4156d2d38f0b9898f0c6bcd58389`:

```text
php tests/InstallationProcess/pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer

FMONITOR_TEST_DB_ADMIN_PASSWORD=<REDACTED> \
  php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)

git diff --check 2bf6534..e9bc61d
# exit 0, no output

php -l <each PHP file changed by e9bc61d>
# 9 files, all report no syntax errors
```

The focused GREEN is real but insufficient to override the uncovered
pre-authentication side effect above.

## Gate consequence

**CHANGES_REQUESTED.** Do not mark Gate 5 complete for the v9 unknown-route
sub-slice at `e9bc61d`. Preserve this record, add a RED oracle proving pure
known-route admission around the checklist operation route, obtain a fresh
Gate 3 `APPROVED`, then apply the minimal predicate/handler separation and
request a fresh Gate 5 rereview.
