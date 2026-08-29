# Test review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 3 — fresh review after exact cookie-parser correction
- Reviewer: separately tasked agent `/root/bootstrap_cookie_test_rereview`
- Independence: reviewer authored neither specification nor reviewed test
- Test author: separately tasked agent (commit author `antropophag`)
- Reviewed commit: `cc5dfe3`
- Specification commit: `71e5e50`
- Specification: `specs/PILOT-DEMO-BOOTSTRAP-001.md`, version `0.1`, `APPROVED`
- Public seam: separate demo CLI, its printed loopback HTTP URL, and response headers/browser-shaped requests served by production `public/router.php`
- Date: `2026-08-29`
- Verdict: `APPROVED`

## Findings

- **Traceability — pass.** The cookie assertions prove the browser-usable loopback exception required by specification sections 1, 4, 5, 6, and 8 while retaining the inherited production session/CSRF contract. The ordinary router composition is checked separately, so the expected exception remains confined to trusted demo startup.
- **Exact parser and token sets — pass.** `pdbCookie()` requires one cookie name/value pair, splits only semicolon-delimited attribute tokens, rejects empty or multi-`=` tokens, normalizes attribute names case-insensitively, and rejects duplicate names. Sorted exact-array equality rejects omitted, extra, malformed, or wrong-valued attributes. Ordinary production requires exactly `HttpOnly`, `Path=/pilot`, `SameSite=Strict`, and `Secure`; trusted demo requires exactly the same set without `Secure`. Both paths also assert the exact `fm2pilot` name and an independently fixed opaque-value grammar.
- **Public seam and sensitivity — pass.** The checks consume actual `Set-Cookie` response headers from production `public/router.php`, not private session helpers. A plausible extra attribute, broadened path, missing retained protection, duplicate attribute, malformed token, absent ordinary `Secure`, or retained demo `Secure` causes failure before the browser journey proceeds.
- **Expected-value independence and rejected behavior — pass.** Expected attributes come from the approved browser launch and inherited security contracts, not implementation details. The ordinary HTTP POST remains expected to fail with `403`, and an unchanged public projection proves rejection creates no domain mutation.
- **Determinism, isolation, and cleanup — pass.** The focused run completed against isolated database/home fixtures and failed after successful provisioning at the intended public response-header assertion. Existing bounded process cleanup remained effective.

No blocking or non-blocking findings.

## RED evidence

```text
$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PHP Fatal error: Uncaught TestFailure: trusted demo exact browser-usable cookie attribute set
Expected: array (
  'httponly' => NULL,
  'path' => '/pilot',
  'samesite' => 'Strict',
)
Actual: array (
  'httponly' => NULL,
  'path' => '/pilot',
  'samesite' => 'Strict',
  'secure' => NULL,
)
at tests/InstallationProcess/pilot_demo_bootstrap_001_test.php:178
exit 255
```

The RED is honest: provisioning and the ordinary-production cookie checks succeeded, then the reviewed test failed solely because current trusted-demo production behavior still emits the forbidden standalone `Secure` attribute.

## Reviewed manifest

```text
71e5e50  specification
cc5dfe3  exact cookie-parser corrective test
e6b082c9b2ed2bd0c8aca370fa785dd2aa25a38901c12d620f8b6e1e1d048263  specs/PILOT-DEMO-BOOTSTRAP-001.md
af15642a6eb96514e85f7ae1577d0ed83bc73ce5ef621f21aa9a19fcf0ecfcc9  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
800d135a043633260ce59440579f35f4dcf16553c61f7e149d82825c9e6c3509  tests/bootstrap.php
fceab9b40c2d3fe766217e2cda2d4d54966cef141bc6cd8fe7a6e90836d2cd38  app/PilotHttp/PilotE2ECoordinator.php
```

## Gate decision

Gate 3 is `APPROVED`. Gate 4 may implement the minimal trusted-loopback cookie transport exception required to make reviewed commit `cc5dfe3` pass without changing its expectations.

## Superseded review history

The prior `CHANGES_REQUESTED` verdict for commit `3cda9ba` is superseded by this fresh independent review of corrective commit `cc5dfe3`.
