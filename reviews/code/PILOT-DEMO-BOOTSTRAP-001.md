# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 5 — fresh independent review of the exact cookie correction
- Reviewer: separately tasked agent `/root/bootstrap_cookie_code_review`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Implementation author: commit author `antropophag`
- Specification commit: `71e5e50`
- Approved test commit: `cc5dfe3`
- Approved test review: `171b241`
- Exact reviewed HEAD: `a55eb2d7079108a31a3aa16191766478bc481edd`
- Review date: 2026-08-29
- Verdict: `CHANGES_REQUESTED`

## Verdict

`CHANGES_REQUESTED`. The exact cookie attributes and regression suite pass, and an inbound HTTP header cannot forge the process-environment nonce. However, the trusted-demo decision is not bound to the configured canonical host and port. The client-controlled `Host` therefore selects both whether `Secure` is removed and which plain-HTTP Origin is accepted.

## Standards

No blocking documented-standard finding. Non-blocking Fowler judgement: the raw trusted-loopback predicate is duplicated in `PilotE2ECoordinator.php` for cookie and Origin handling. A single named predicate/context would prevent those two security decisions drifting.

## Spec

Blocking security/spec finding: specification sections 4–5 fix `FMONITOR_TRUSTED_REQUEST_HOST=127.0.0.1:8092`, restrict HTTP to this loopback demo composition, and require a canonical Host. In `app/PilotHttp/PilotE2ECoordinator.php:117`, any syntactically valid inbound `Host: 127.0.0.1:<port>` removes `Secure` whenever a format-valid environment nonce exists. Lines 127–132 accept that same client-selected host as `Origin: http://...`. `PilotHttpRequestFactory` obtains Host from inbound `HTTP_HOST` under `cli-server`, while the router forwards the environment nonce but does not bind the decision to `FMONITOR_TRUSTED_REQUEST_HOST`. Thus a request sent to the demo listener with `Host: 127.0.0.1:4444` and matching Origin is treated as trusted even though it is not the configured endpoint.

The correction otherwise preserves the production `Secure`, `HttpOnly`, `SameSite=Strict`, and `Path=/pilot` attributes. The nonce and actor remain environment-sourced rather than header-sourced.

## Required changes

1. Bind the plain-HTTP cookie and Origin exception to one router-provided trusted demo context containing the canonical configured host/port, and compare the request Host exactly. Do not accept an arbitrary loopback port merely because its syntax is valid.
2. Add a Gate 2 regression proving that a non-canonical loopback Host plus matching Origin cannot activate the demo exception; obtain fresh independent Gate 3 approval before correcting production code.

## Verification

```text
$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PASS PILOT-DEMO-BOOTSTRAP-001 public launch, walkthrough, persistence, reset and cleanup

$ for test_file in tests/InstallationProcess/*_test.php; do php -d display_errors=1 -d error_reporting=E_ALL "$test_file"; done
48/48 test files PASS

$ find app public bin tests/InstallationProcess tests/Support -type f -name '*.php' -print0 | xargs -0 -n1 php -l
148 PHP files lint PASS

$ git diff --check 71e5e50..HEAD
PASS

$ pgrep -af 'fmonitor2-pilot-demo|php.*-S 127\\.0\\.0\\.1'
No demo/router residue (only the inspection shell matched itself)

$ find /home/antropophag -maxdepth 5 -type d -name '*pilot-demo-test-*'
No test-state residue
```

Gate 5 remains closed for exact reviewed commit `a55eb2d7079108a31a3aa16191766478bc481edd`.
