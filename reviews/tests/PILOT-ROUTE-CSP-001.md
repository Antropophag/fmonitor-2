# Independent test review — PILOT-ROUTE-CSP-001

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `csp_gate3_test_review`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or modify the tests, production implementation, or
OpenSpec tasks. This record is the only review edit.

## Reviewed contract and evidence

- Owner-approved executable spec SHA-256:
  `47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`
- `tests/InstallationProcess/pilot_route_csp_001_test.php` SHA-256:
  `e410d3595ba4551a718a303a732e767e4745e164147ee673107dd7b7b1673232`
- `tests/InstallationProcess/pilot_route_csp_login_001_test.php` SHA-256:
  `18f9cea00f6f902ddd71eba158d38a91e35f272fe9e280574648de185c2e1665`
- `tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php`
  SHA-256:
  `dd95df6fdbc36279a9c895adede4a7a4fbb9f722aedc26f7fe54a661f87b5268`
- RED evidence SHA-256:
  `5d3d26053d5e6f22e594d9020d3959e419f26dd0b99aba8b4c917f11b25a593f`

The executable spec hash matches the Gate 1 rereview and owner-approval record.
All three tests pass `php -l`. The coordinator matrix and CompletionFlow tests
independently reproduce deterministic intended RED. The direct-login test also
reproduces its documented intended RED in the `fmonitor2-pilot` container:
existing `403` and `303` outcomes are reached and fail only because CSP is
missing. No setup failure was observed.

## Blocking findings

### 1. HEAD equivalence is not executable

`pilot_route_csp_001_test.php` checks only status, CSP, and an empty HEAD body.
It does not issue the equivalent GET and compare declared `Content-Length`, even
though A2 requires HEAD to preserve the GET representation's CSP and
Content-Length. An implementation that drops or changes Content-Length on HEAD
would pass this test.

Required correction: assert byte-exact CSP parity and declared Content-Length
parity between equivalent GET and HEAD, while also asserting GET bytes and an
empty HEAD body.

### 2. The login success expectation is not the approved byte-exact policy

`PRCL_SCRIPT` orders directives as `default-src; script-src; style-src; ...`,
whereas the normative `SCRIPT_HTML_CSP` is `default-src; style-src; script-src;
...`. The test currently approves the pre-existing string rather than the
owner-approved byte-exact value. A minimal GREEN conforming to this test would
remain nonconforming to the spec.

Required correction: use the exact approved normative policy string in every
test seam; do not create seam-specific reorderings.

### 3. Forbidden executable-content requirements are not tested at the public seam

The coordinator test injects a hand-written safe shell, so it cannot detect
inline scripts, event-handler attributes, `javascript:` URLs, eval-like
contracts, third-party script URLs, or regressions in actual route renderers.
The CompletionFlow test only regexes the PHP source for one inline `<script>`
block. No test examines representative final successful HTML bodies for the
full A1/A8/A10 forbidden-content contract. No assertion checks that any emitted
CSP is free of `unsafe-inline`, `unsafe-eval`, nonce/hash, wildcard, and
third-party sources.

Required correction: exercise representative real final HTML through a public
response seam and assert external same-origin scripts plus all forbidden forms;
also assert forbidden CSP tokens across base, scripted, checklist, and worker
policies.

### 4. Completion cap 85/100 behavior is not demonstrated

The test only asks whether `checklist.js` contains the substrings `progressCap`,
`85`, and `100`. Those strings do not prove that the external behavior reads
`data-progress-cap`, caps an incomplete displayed total at 85, and permits a
completed displayed total of 100. A dead comment or unrelated constants would
pass after the inline block is deleted.

Required correction: execute the external behavior (or test an extracted pure
public helper used by it) against rendered incomplete and completed checklist
representations and assert observed displayed results `85` and `100`, while
retaining the no-inline assertion.

### 5. Allowlist and fail-closed route inventory are under-specified by the RED

The coordinator matrix proves `/pilot/`, one installation checklist, two object
near-misses, and a few generic response classes. It does not parameterize the
approved allowlist families (including positive-id detail/prepare, construction
control checklist, calendar slash variants, admin and bounded OTIZ read paths),
does not cover the second checklist family, and has no inventory-sensitive
assertion that newly registered or command/export routes remain base by default.
It also samples only a JavaScript asset; CSS/SVG/font/PDF and the exact Service
Worker exception are not executable here.

This leaves exact whole-pattern matching, checklist-only
worker/connect/blob isolation, asset-specific policy preservation, and the
"new route defaults to BASE" rule vulnerable to an over-broad classifier.

Required correction: add a table-driven route/result matrix covering every
approved pattern family and method boundary, both checklist families, exact
near-misses and representative non-allowlisted command/export routes; connect
it to the maintained route inventory (or add an explicit inventory
characterization) so additions fail closed. Cover representative asset classes
and the separately approved exact worker asset policy.

### 6. Response preservation and neutrality assertions are incomplete

The tests assert `Cache-Control` and redirect `Location` only in the login
fixture. The main response matrix does not freeze Content-Type, Content-Length,
body bytes, existing security/cache headers, retry/correlation headers on an
error variant, repeated-response CSP identity, or absence of persistence/audit
facts. Consequently a CSP implementation could alter the public response or
write history while these tests pass.

Required correction: characterize the relevant headers/body bytes before CSP
selection, include at least one operational error response with its existing
headers, assert repeated safe-read identity, and demonstrate no domain/audit
write through an observable repository seam.

## Reproduced commands

```text
php -l tests/InstallationProcess/pilot_route_csp_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_login_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
php tests/InstallationProcess/pilot_route_csp_001_test.php
php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
docker run --rm --network host --entrypoint php \
  -v /home/antropophag/code/fmonitor-2:/workspace/fmonitor-2 \
  -w /workspace/fmonitor-2 \
  -e FMONITOR_DB_HOST=127.0.0.1 -e FMONITOR_DB_PORT=23306 \
  -e FMONITOR_DB_NAME=fmonitor2_test \
  -e FMONITOR_DB_USER=fmonitor2_test \
  -e FMONITOR_DB_PASSWORD=fmonitor2_test_local \
  fmonitor2-pilot tests/InstallationProcess/pilot_route_csp_login_001_test.php
```

Syntax: PASS for all three files. RED: reproduced as documented, but the
coverage gaps above mean the RED is not yet sufficient to authorize Gate 4.

Gate 3 remains closed. Production CSP implementation must not begin. The test
suite must return to Gate 2, preserve the approved spec, receive fresh
independent rereview after correction, and demonstrate intended RED again.
