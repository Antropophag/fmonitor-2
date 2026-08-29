# Code review: PILOT-DEMO-BOOTSTRAP-001 v0.2

- Gate: 5 — fresh independent production review
- Reviewer: separately tasked agent `/root/bootstrap_v2_code_review`
- Independence: reviewer authored neither specification, approved test, nor implementation
- Specification: `919383966f962fb9811a5bf6350536310de03683`
- Approved test review: `bf1aeb9`
- Exact reviewed HEAD: `d421a095197c3c5dc5a56f14ca3fc9b9e013d5e9`
- Review date: 2026-08-29
- Verdict: `REJECTED`

## Standards

No independent documented-standard violation or actionable Fowler smell was found in the production delta. Reusing the production `ShlzCssManifest` keeps bootstrap graph interpretation aligned with the public HTTP asset boundary, and activation remains after successful public smoke. The implementation also preserves the prior generation, marker, prefix, artifact, server-stop, and no-ready-banner safety paths.

## Specification

### Blocking finding

`bin/fmonitor2-pilot-demo.php:247-265` loses the required shlz-specific error classification after the server is spawned. A non-`200`, byte mismatch, or wrong MIME for any transitive manifest member only sets `$graphOk=false`; after the retry deadline the common `!$ok` branch calls `demoFailure()` and emits `{"ok":false,"reason":"STARTUP_FAILED"}` with exit 70.

Specification section 4 requires any non-`200` or wrong-MIME transitive smoke response to emit exact redacted `SHLZ_ASSETS_UNAVAILABLE`, exit nonzero, stop the spawned server, and print no ready banner. Server stop and banner suppression are present, but the observable reason is wrong. The preflight catch at lines 311-312 correctly returns `SHLZ_ASSETS_UNAVAILABLE`; it does not cover a router/public-response mismatch discovered only by HTTP smoke.

Required correction: preserve whether the failed smoke predicate is a shlz graph/member response failure and, after terminating/closing the spawned server, return `demoFailure('SHLZ_ASSETS_UNAVAILABLE', 78)` for that case. Retain generic `STARTUP_FAILED` for non-shlz smoke failures. Add a reviewed public-seam sensitivity that makes preflight succeed but a transitive HTTP response fail status/MIME/bytes, and asserts exact classification, stopped server, unchanged active generation, and no ready banner.

## Verification

```text
php tests/InstallationProcess/pilot_demo_bootstrap_001_test.php   PASS
php tests/InstallationProcess/pilot_shlz_assets_001_test.php     PASS
tests/InstallationProcess/*_test.php                             49/49 PASS
PHP lint: app public bin tests/InstallationProcess tests/Support 149/149 PASS
git diff --check 9193839..d421a09                                PASS
demo/router process residue                                      none
.test-artifacts residue                                          none
```

Passing tests do not waive the explicit v0.2 error contract. Gate 5 is `REJECTED` for exact reviewed commit `d421a095197c3c5dc5a56f14ca3fc9b9e013d5e9`.

## Review manifest

```text
7268f732d659ab93232d21d1e597dfd4abdb38509481d3082d4d3df67f7cbd0c  specs/PILOT-DEMO-BOOTSTRAP-001.md
9d191e221838ae2b4368b96788aff7f2e38b7036650843ff1d93aee6ddafd854  reviews/tests/PILOT-DEMO-BOOTSTRAP-001.md
d10837aa786eb834fabcb76e8a60265db24af4b0dfacbc3dfab382f3c2498321  tests/InstallationProcess/pilot_demo_bootstrap_001_test.php
112fd3f0f6dcc7fb55d95bba28b7161e28569fb6085fff07ec6b77285a3a5b1e  app/PilotHttp/PilotHttp.php
e8082943b02fdc5dda4ea9d489296789a2d41e9751c9982ec48c2f28b4352aa8  bin/fmonitor2-pilot-demo.php
```
