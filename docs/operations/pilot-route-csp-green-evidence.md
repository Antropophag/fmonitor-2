# PILOT-ROUTE-CSP-001 — Gate 4 GREEN evidence

Date: 2026-09-02  
Status: **PARTIAL_GREEN / VERIFYING**

## Minimal implementation

- `PilotRouteCsp` owns byte-exact base/script/checklist/worker classification by
  method, exact path, final status and media type; HTML response wiring also
  verifies that widened policy corresponds to a final same-origin external
  script representation.
- Both HTTP coordinator response paths use the classifier. Direct rapid-pilot
  responses install the same policy at the final header boundary, preserving
  existing status/body/cache/security headers.
- CompletionFlow no longer injects inline JavaScript. The existing checklist
  asset exposes and invokes the bounded progress-cap helper; no domain fact,
  progress weight or command behavior moved into rapid-pilot.

## Focused GREEN

```text
pilot_route_csp_inventory_001_test: PASS
pilot_route_csp_001_test: PASS
pilot_route_csp_completion_flow_001_test: PASS
pilot_route_csp_login_001_test: PASS
pilot_route_csp_completion_final_html_001_test: PASS
ARCHITECTURE CHECK PASSED (7 rules)
make lint: exit 0
```

The real login/final-HTML tests ran in the canonical test DB container. Asset
hashes, exact headers and full schema/ordered-row/audit snapshots remained
unchanged.

## Classified integration blockers

The three historical regression entrypoints currently stop before their CSP
assertions for unrelated landed debt:

- `pilot_demo_bootstrap_001_test.php` and `pilot_http_auth_001_test.php` expect
  terminal schemaVersion 8 while canonical planning v9 is landed;
- `pilot_shlz_assets_001_test.php` receives 403 from the follow-up authoritative
  local-RBAC route fixture.

No assertion was weakened. OpenSpec task 3.4 remains open pending the approved
fixture correction slices/integration alignment.

## Full verification after owned correction

`make verify` was rerun after qualifying all global PHP calls in the new
classifier. Result: `FULL_VERIFICATION_FAILURE count=3` with CSP-owned checks
green and only classified external/integration debt remaining:

- test-db reset, canonical v9 migration, architecture, lint, CSP focused suites,
  characterization and diff-check PASS;
- unit: `pilot_calendar_shlz_asset_001_test.php` cannot fetch Docker base-image
  metadata because the host credential/vsock helper fails before assertions;
- DB: stale v8 terminal catalog expectations, authoritative-RBAC follow-up
  fixtures, and ObjectQueue completion runtime-DDL prerequisite;
- E2E: existing missing assignment-order artifact/combined-PDF failure.

The first full run additionally caught the new classifier's unqualified global
calls. Production was corrected without test edits; the dedicated global-call
regression, all CSP suites and architecture check passed before the second full
run. No baseline or unrelated assertion was changed.
