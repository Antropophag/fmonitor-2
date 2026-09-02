# Independent test rereview — PILOT-ROUTE-CSP-001 v3

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `csp_gate3_test_rereview_v3`  
Verdict: **APPROVED**

The reviewer did not author or modify the specification, tests, production
implementation, evidence, or OpenSpec tasks. This review record is the only
edit.

## Reviewed contract and hashes

- Owner-approved executable spec SHA-256:
  `47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`
- Original RED evidence:
  `5d3d26053d5e6f22e594d9020d3959e419f26dd0b99aba8b4c917f11b25a593f`
- Corrected RED evidence v2:
  `56a2abaa8c55a26343bba0ca0ca100c4d9f5ed4f1b07c61f20a8086e8d42efa6`
- Corrected RED evidence v3:
  `730dbdfbc3dd5d0a76eb503203a5630064ac97098c0c9118ead662f8511512bd`
- `pilot_route_csp_001_test.php`:
  `f3e336e7c87b261b981704b9077381abbc036827fd39efdc1ef9b1296628bded`
- `pilot_route_csp_login_001_test.php`:
  `eec985d8181a590d9ff2c96aef50be56347b69737e5692b1bd217928bbf2dd75`
- `pilot_route_csp_inventory_001_test.php`:
  `cb5ad5fe37a7bf4a5afd90314a11b1ff6af6b5be7e679dca93f16852d8d83668`
- `pilot_route_csp_completion_flow_001_test.php`:
  `1d35bfb8e1fa6219c17090e6f221a18409598aa679a5b7acca14ae83eafbe913`
- `pilot_route_csp_completion_final_html_001_test.php`:
  `3d1d6cc64a4a3a59ab7c1e62574df453f7259407fb3f06a4cc51815782955f6a`
- Node browser verifier:
  `e6552822e3585ad733b178d6b16b8867f26aa620778e30e7d906c52437210470`

The executable hash matches the Gate 1 rereview and owner approval. Strict
OpenSpec validation passes. All five PHP files pass `php -l`; the Node verifier
passes `node --check`.

## Review result

All prior Gate 3 findings are closed. The tests trace A1-A12 through complete
HTTP responses, final rendered HTML, the route/result classification boundary,
and executable external progress behavior. Expected policies are byte-exact and
independently copied from the approved specification. GET/HEAD parity, the sole
successful scripted login POST, all named error classes, redirects, exact route
near-misses, the complete allowlist inventory, representative assets, the exact
Service Worker exception, checklist-only directives, and banned executable/CSP
forms are sensitive to plausible over-broad or incomplete implementations.

The v2 preservation blocker is closed:

- CSS, SVG, font, Service Worker, and coordinator JavaScript response bytes are
  compared by SHA-256 with independently read source assets; exact media type,
  source-derived Content-Length, cache policy, `nosniff`, and worker scope are
  asserted where applicable.
- Representative coordinator success, HEAD, authentication error, redirect,
  JavaScript asset, and operational error responses preserve exact existing
  content/cache/security headers, body/length behavior, `Location`, and
  `Retry-After`. Repeated safe reads must be byte/header identical.
- Real incomplete and complete checklist renders snapshot full `SHOW CREATE
  TABLE` output and every ordered row/column for installation cases, completion
  facts, and process-event audit history before and after each safe render.
  INSERT, UPDATE, DELETE, audit append, or schema mutation on every reachable
  persistence seam therefore changes the oracle. Setup completion facts are
  inserted before the second baseline.

The suite is deterministic and isolated: local matrices use in-memory adapters;
database-backed fixtures use unique prefixes in the canonical test database and
drop only their own tables in `finally`. Expected values do not derive from the
planned classifier implementation. No setup/status/header/hash/history failure
was observed.

## Reproduced RED evidence

`pilot_route_csp_001_test.php` exits 255 only on current CSP mismatches for 401,
308, JavaScript/CSS assets, exact near-misses, script-free HTML, checklist 404,
and operational 503. All expected statuses and preservation assertions pass.

`pilot_route_csp_inventory_001_test.php` exits 255 only because the approved
HTTP-boundary classifier does not yet exist. `pilot_route_csp_completion_flow_001_test.php`
exits 255 only for the existing inline block and absent external executable cap
helper.

In the isolated database container, the login fixture exits 255 only for
missing or wrong-order CSP on the expected CSS/SVG/font, GET 200, POST 200, POST
403, and POST 303 outcomes. The final checklist fixture reaches both cap-85 and
cap-100 representations and exits 255 only for the existing inline script in
each; both full persistence snapshots remain identical.

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
# Same container/environment with final argument:
tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
openspec validate define-pilot-route-csp --strict
```

Gate 3 passes. Gate 4 may implement the minimum production behavior required to
make these exact approved tests GREEN without changing their expectations.
