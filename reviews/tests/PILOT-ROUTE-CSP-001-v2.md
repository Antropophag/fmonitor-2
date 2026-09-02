# Independent test rereview — PILOT-ROUTE-CSP-001 v2

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `csp_gate3_test_rereview_v2`  
Verdict: **CHANGES_REQUIRED**

The reviewer did not author or modify the specification, tests, production
implementation, evidence, or OpenSpec tasks. This review record is the only
edit.

## Reviewed contract and hashes

- Owner-approved executable spec SHA-256:
  `47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`
- Original RED evidence SHA-256:
  `5d3d26053d5e6f22e594d9020d3959e419f26dd0b99aba8b4c917f11b25a593f`
- Corrected RED evidence v2 SHA-256:
  `56a2abaa8c55a26343bba0ca0ca100c4d9f5ed4f1b07c61f20a8086e8d42efa6`
- `pilot_route_csp_001_test.php`:
  `42397eeaf628da30133c122cd934f161ae0917d65d255ac299bd1fdd3af71e4e`
- `pilot_route_csp_login_001_test.php`:
  `e30bde9e35c74f849048b24c9e65f4f9978e4d61d8b1705ce474364d5cfcef03`
- `pilot_route_csp_inventory_001_test.php`:
  `cb5ad5fe37a7bf4a5afd90314a11b1ff6af6b5be7e679dca93f16852d8d83668`
- `pilot_route_csp_completion_flow_001_test.php`:
  `1d35bfb8e1fa6219c17090e6f221a18409598aa679a5b7acca14ae83eafbe913`
- `pilot_route_csp_completion_final_html_001_test.php`:
  `3d4acba2f5074778d680add3556f12f972f5587ee46ad3a35158ef143243dd6a`
- Node browser verifier:
  `e6552822e3585ad733b178d6b16b8867f26aa620778e30e7d906c52437210470`

The executable spec hash still matches the owner approval. Strict OpenSpec
validation passes. All five PHP tests pass `php -l`; the Node verifier passes
`node --check`.

## Prior blockers — disposition

1. **GET/HEAD equivalence: closed.** The coordinator test now compares
   byte-exact CSP and declared Content-Length, checks GET length against its
   body bytes, and requires an empty HEAD body.
2. **Byte-exact policy order: closed.** All seams, including login GET and the
   successful scripted login POST, use the approved normative order. The real
   responder is RED on its old order.
3. **Forbidden executable content/tokens: closed.** Representative coordinator,
   real login, and real final checklist HTML are checked for inline/non-local
   scripts, event handlers, `javascript:`, eval/Function, and third-party script
   URLs. The four policy constants are checked for every prohibited token.
4. **Executable 85/100 cap: closed.** Node executes the actual checklist asset,
   requires the tested helper to be wired into that asset, and checks incomplete
   values capped at 85 plus the completed forced value 100. Both real incomplete
   and complete final HTML representations expose the corresponding cap and are
   RED on the existing inline block.
5. **Route/result inventory: closed.** The table covers every approved scripted
   family, both checklist families, GET/HEAD, the sole POST exception, all named
   error statuses, method/media boundaries, canonical positive-id near-misses,
   commands/exports/future routes, CSS/JS/SVG/font/PDF, and the exact Service
   Worker policy.
6. **Response preservation/history neutrality: still blocking.** See below.

## Blocking finding

### Preservation and no-write sensitivity remain insufficient

The corrected tests name the required invariants but do not independently
freeze all of them:

- `prclAsset()` calls a changed asset "bytes changed" only when the response's
  declared Content-Length differs from that same response body. Production can
  alter the CSS/SVG/font bytes and update Content-Length consistently and the
  test still passes. There is no pre-change byte/hash oracle. The coordinator
  asset checks assert only status/CSP, not existing bytes, Content-Type,
  Content-Length, cache policy, or asset headers.
- Successful/error coordinator responses do not freeze the existing security
  header set called out by A1 and the approved design. Only selected cache,
  redirect, and operational-error fields are characterized; a GREEN that drops
  or changes an existing security header can pass.
- The final-HTML history check compares only row counts. An UPDATE to an
  existing installation case or completion fact preserves those counts and
  passes, despite A11 requiring no persistence write/audit/domain fact. No audit
  repository/table is observed at all.

Required correction: derive immutable expected asset bytes (or hashes) and the
relevant existing response/security headers from a pre-CSP characterization,
then compare the post-classification public response to those independent
values. Snapshot the persisted rows (and an applicable audit/history seam) before
and after safe rendering/classification so INSERT, UPDATE, and DELETE are all
detectable, rather than checking counts only. Preserve the current intended RED
and resubmit the corrected tests to another fresh independent reviewer.

## Reproduced evidence

The coordinator matrix reached every expected status and failed only on the
current globally scripted CSP for 401, 308, JS/CSS assets, near-misses,
script-free HTML, checklist 404, and operational 503. The inventory test failed
only because the approved boundary classifier is absent. The CompletionFlow
test failed on the existing inline block and missing executable external helper.

Using the canonical isolated test database, the real login test reached CSS,
SVG, font, Service Worker, GET 200, POST 200, POST 403, and POST 303 outcomes;
its failures were policy absence/order mismatches. The final-checklist test
reached both cap-85 and cap-100 representations and failed only on the current
inline script. No setup failure was observed.

Reproduced commands:

```text
php -l tests/InstallationProcess/pilot_route_csp_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_login_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
node --check tests/InstallationProcess/support/pilot_route_csp_completion_browser.js
php tests/InstallationProcess/pilot_route_csp_001_test.php
php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
docker run --rm --network host --entrypoint php \
  -v /home/antropophag/code/fmonitor-2:/workspace/fmonitor-2 \
  -w /workspace/fmonitor-2 \
  -e FMONITOR_DB_HOST=127.0.0.1 -e FMONITOR_DB_PORT=23306 \
  -e FMONITOR_DB_NAME=fmonitor2_test \
  -e FMONITOR_DB_USER=fmonitor2_test \
  -e FMONITOR_DB_PASSWORD=fmonitor2_test_local \
  fmonitor2-pilot tests/InstallationProcess/pilot_route_csp_login_001_test.php
# same container/environment with final argument:
tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
openspec validate define-pilot-route-csp --strict
```

Gate 3 remains closed. Production CSP implementation is not authorized.
