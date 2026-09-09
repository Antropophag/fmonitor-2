# Independent Gate 5 review — PRODUCTION-RUNTIME-TRUSTED-SCHEME-001

- Date: `2026-09-09`
- Reviewer: separately tasked agent `/root/runtime_review`
- Implementation author: `/root`
- Verdict: **APPROVED (BOUNDED)**

## Reviewed identities

```text
9022f89b7af50b636e17e1b1e17fbbecc8f4baf9fbbda565783f6e467f7079b2  reviews/tests/PRODUCTION-RUNTIME-BROWSER-001.md
e5a6ddda89353a04172812b4e9bd20bff7b78843be01b18483c2c6440822214d  tests/Support/pilot_current_flow_browser.cjs
8f47c8683c96fc147468756e382244a4594ceaef41109039d0d1740167722672  app/PilotHttp/PilotE2ECoordinator.php
```

## Review

Production coordinator session cookies and both form/checklist Origin checks now
derive scheme from the validated injected `EnvironmentSource`. HTTP omits Secure,
HTTPS retains it, and an invalid configured value fails closed. CLI-server demo
behavior and the legacy no-environment HTTPS fallback remain unchanged.

The command-cookie name retains the port suffix for HTTP loopback instances, avoiding
cross-instance collisions during production contour tests. No client-forwarded
scheme header is consulted.

The real production browser run after this patch passed login, selection, template
PDF, initial/corrected original upload and download, distinct-user opening, all 41
checklist item operations, seven photos, completion, and exact 85/100 progress with
zero browser errors. Its later persistence-observer failure was a stale test-side
mysqli connection after database replacement, outside request-scheme behavior.

`PilotUserAdminSession` retains an older fallback Secure calculation, but it is not
reachable in current production routing: every native admin mutation is intercepted
by the owner session and constructs that class with `ownerState`, whose `open()`
returns before cookie calculation; `/pilot/admin/roles` returns its GET view before
opening a command session. No speculative sibling edit is required.

## Verification

```text
$ php -l app/PilotHttp/PilotE2ECoordinator.php
No syntax errors detected in app/PilotHttp/PilotE2ECoordinator.php

$ php tests/InstallationProcess/pilot_http_auth_001_global_calls_test.php
PASS: PILOT-HTTP-AUTH-001 complete global-call qualification

$ git diff --check
# exit 0, no output
```

## Verdict

**APPROVED (BOUNDED).** Production trusted-scheme behavior conforms at the protected
current-flow coordinator seam. The expanded restart/admin/OTIZ browser tail and full
runtime Gate 5 remain pending.
