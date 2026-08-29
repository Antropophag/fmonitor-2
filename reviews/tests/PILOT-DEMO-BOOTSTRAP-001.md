# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh review after browser-cookie correction
- Reviewer: separately tasked agent `/root/bootstrap_cookie_test_review`
- Independence: reviewer authored neither specification nor reviewed test
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `3cda9ba`
- Specification commit: `71e5e50`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate demo CLI, its printed loopback HTTP URL, and response headers/browser-shaped requests served by production `public/router.php`
- Date: `2026-08-29`
- Verdict: `CHANGES_REQUESTED`

## Findings

- **Blocking — cookie attributes are not asserted exactly.** Both new policy checks use substring membership. `str_contains($setCookie, 'Path=/pilot')` also accepts `Path=/pilot-anything`; the combined checks similarly do not prove that `HttpOnly`, `SameSite=Strict`, and `Path=/pilot` are complete semicolon-delimited cookie attributes. Consequently the test can pass a plausible path-scope regression and does not establish the required exact non-transport protections at the public `Set-Cookie` seam. Parse the header into exact semicolon-delimited attributes (or assert an independently fixed exact attribute suffix/set) for both ordinary production and trusted demo responses. Preserve the exact demo absence of the standalone `Secure` attribute and ordinary production presence of it.
- **Traceability — otherwise pass.** The exception is required by specification sections 1, 4, 5, 6, and 8 together: the operator must complete ordinary browser POSTs at the printed `http://127.0.0.1` URL without manual cookie substitution, while CSRF/session remain the production contract. A `Secure` session cookie is therefore unusable for that approved launch contract. Comparing the demo CLI composition with an ordinary production-router composition correctly bounds the transport exception to trusted demo startup.
- **Public seam and sensitivity — otherwise pass.** The assertions observe the real public `Set-Cookie` response header, not a private session helper. The independent run provisions the bootstrap, reaches the trusted demo response, and fails because current production still emits `Secure`; this is the intended missing behavior rather than a syntax, fixture, MariaDB, port, or bootstrap failure. The ordinary composition assertion passes first and proves its current cookie retains the production transport policy.
- **Expected-value independence and preservation — otherwise pass.** Cookie name, loopback HTTP exception, and retained protections come from the approved browser launch contract and established production security contract, not planned implementation. The existing subsequent full browser-shaped POST journey remains capable of detecting a missing usable session, and the ordinary HTTP POST rejection plus unchanged demo projection guards against broadening origin acceptance or process mutation.
- **Determinism and cleanup — pass.** The independent RED completed in 16 seconds with the test's bounded child-process cleanup and isolated database/home fixtures.

## RED evidence

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: trusted loopback HTTP demo cookie omits Secure for real browser POST
Expected: false
Actual: true
at tests/InstallationProcess/pilot_demo_bootstrap_001_test.php:177
exit 255
```

The RED is honest and reaches the public response-header seam after successful provisioning and ordinary-production checks.

## Reviewed manifest

```text
71e5e50  specification
3cda9ba  corrective test
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
01d592adc2e8d6fc421c4b33bf6fbbf9037e1db55992f88a56e68d2c63e3dd40  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
fceab9b40c2d3fe766217e2cda2d4d54966cef141bc6cd8fe7a6e90836d2cd38  app/PilotHttp/PilotE2ECoordinator.php
```

## Required changes

1. Replace substring checks for `HttpOnly`, `SameSite=Strict`, and `Path=/pilot` with exact cookie-attribute token/set checks in both ordinary-production and trusted-demo assertions.
2. Re-run the focused test and request a fresh independent Gate 3 review. Gate 4 must not begin from commit `3cda9ba`.

## Superseded review history

The prior approval covered corrective test commit `a1fca72`. Test commit `3cda9ba` changed the reviewed executable contract and invalidated that approval.
