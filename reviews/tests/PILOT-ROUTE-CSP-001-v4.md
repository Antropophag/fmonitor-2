# Independent test fixture-correction rereview — PILOT-ROUTE-CSP-001 v4

Date: 2026-09-02  
Reviewer: fresh separately tasked agent `csp_gate3_fixture_rereview_v4`  
Verdict: **APPROVED**

The reviewer did not author or modify the specification, tests, production
implementation, evidence, or OpenSpec tasks. This review record is the only
edit.

## Reviewed contract and hashes

- Owner-approved executable spec SHA-256:
  `47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`
- Prior independent Gate 3 approval:
  `reviews/tests/PILOT-ROUTE-CSP-001-v3.md`
- Corrected inventory test SHA-256:
  `0a134eff5fc21457dca8804ab8988ac266ca239a8d97cfaae71c60ad9f1182f7`
- Fixture-correction evidence v4 SHA-256:
  `b81b174313158249603dfd590612d0d2a446fb6f8df35a631d97639e0f7acd84`

The executable specification hash still matches the Gate 1 and prior Gate 3
records. Strict OpenSpec validation passes.

## Review result

The post-GREEN correction is minimal and matches A1b exactly. The dedicated
assertion still requires successful `POST /pilot/login` with `200 text/html` to
receive byte-exact `SCRIPT_HTML_CSP`. The generic method-boundary loop skips
only the exact `/pilot/login` path when making its successful POST assertion;
every other scripted and checklist route remains required to return
`BASE_CSP` for POST. Error and media-type boundaries for `/pilot/login` remain
covered by the surrounding matrix.

The exception is copied from the owner-approved specification and is not
derived from the current production classifier. No route family, status,
media-type, near-miss, asset, forbidden-token, or checklist-policy expectation
was weakened. The correction removes only the internally contradictory second
expectation for the same approved login tuple.

The v4 evidence records the corrected fixture run against a temporary isolated
pre-GREEN filesystem fixture with the boundary classifier absent. It failed on
the intended missing behavior, not setup. The temporary fixture did not alter
the shared production tree. On the current implementation, syntax and the
corrected focused test pass.

## Verification

```text
php -l tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_route_csp_inventory_001_test.php

php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
pilot_route_csp_inventory_001_test: PASS

openspec validate define-pilot-route-csp --strict
Change 'define-pilot-route-csp' is valid
```

Gate 3 is restored for this single fixture correction. Gate 4 may continue
against the corrected approved inventory expectation; the remaining approved
tests and production implementation are outside this narrowly scoped rereview.
