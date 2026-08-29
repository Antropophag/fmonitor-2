# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.1

- Gate: 5 — fresh independent review after canonical demo Host-binding correction
- Reviewer: separately tasked agent `/root/bootstrap_host_code_review`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Implementation author: commit author `antropophag`
- Specification commit: `71e5e50`
- Approved test commit: `b7920be`
- Approved test review: `43d3821`
- Exact reviewed HEAD: `ce357bd66e229c4b34a267786a47a8993d3ecb25`
- Review date: 2026-08-29
- Verdict: `APPROVED`

## Findings

### Standards

None. The implementation is the minimal Host-binding correction required by the approved regression. It keeps the demo exception at the HTTP adapter boundary and does not change domain history, process commands, identity, authorization, legacy integration, or shlz-ui integration.

The router creates the request-scoped trusted context only for a PHP loopback server bound to exact `127.0.0.1`. Its nonce and canonical trusted Host come from process environment rather than inbound `HTTP_*` headers. Both values are grammar-checked before being copied to separate non-HTTP server keys. The coordinator independently validates the trusted context at its consumption point and requires exact byte equality between the already validated request Host and configured trusted Host.

The repeated router/coordinator validation is proportionate defense in depth, not actionable duplicated code: the router constrains creation of trusted context and the coordinator fails closed when consuming it. No material baseline smell was found.

### Specification

None. The correction satisfies specification sections 4–5: plain HTTP remains restricted to the exact canonical loopback demo composition. A non-canonical client-selected loopback Host cannot activate demo trust even when it has valid syntax and a matching Origin.

`PilotE2ECoordinator::trustedDemo()` is the single predicate for both security decisions: omitting `Secure` from the demo session cookie and accepting the canonical plain-HTTP Origin. This closes the previous policy-drift risk. Ordinary production retains `Secure` and HTTPS-only Origin behavior; canonical demo cookie/forms remain usable; a non-canonical Host retains `Secure`, rejects its matching HTTP Origin, and creates no process fact.

The implementation is cumulative with the approved bootstrap contract: actor identity remains environment-sourced, capability authorization and CSRF/session checks are unchanged, browser commands still use production `InstallationProcess`, and append-only persistence, artifact, restart, reset, cleanup, and ownership behavior remain green.

## Required changes

None.

## Verification

```text
$ php -l app/PilotHttp/PilotE2ECoordinator.php
No syntax errors detected in app/PilotHttp/PilotE2ECoordinator.php

$ php -l public/router.php
No syntax errors detected in public/router.php

$ php -l tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_demo_bootstrap_001_test.php

$ php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
PASS PILOT-DEMO-BOOTSTRAP-001 public launch, walkthrough, persistence, reset and cleanup

$ for test_file in tests/InstallationProcess/*_test.php; do php "$test_file"; done
48/48 test files PASS

$ find app public bin tests/InstallationProcess tests/Support -type f -name '*.php' -print0 | xargs -0 -n1 php -l
148/148 PHP files lint PASS

$ git diff --check 43d3821..ce357bd
PASS

$ ps -eo pid=,args= | rg 'php -S 127\\.0\\.0\\.1|fmonitor2-pilot-demo\\.php'
No demo/router process residue

$ find .test-artifacts -mindepth 1 -maxdepth 3 -print
No test artifact residue
```

Gate 5 is `APPROVED` for exact reviewed commit `ce357bd66e229c4b34a267786a47a8993d3ecb25`.
