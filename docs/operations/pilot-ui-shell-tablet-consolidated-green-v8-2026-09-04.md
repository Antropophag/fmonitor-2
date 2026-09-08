# PILOT-UI-SHELL-001 — tablet consolidated browser GREEN v8

- Date: `2026-09-04`
- Correction base: `c5f645fa1813185dca2bd809dd0cc4e97478bb30`
- Production candidate: `f18f61d7844236db4abcb27c26f5418e06a6b255`
- Gate: `4 — consolidated tablet correction evidence`
- Result: **GREEN**

This append-only record does not claim Gate 5, navigation removal,
repository-wide GREEN or release readiness. Tests, specifications, reviews and
repository browser harness files were unchanged.

## Production correction

A single content-driven tablet rule for `768–959px` replaces the inherited
64px sidebar at that range:

```text
shell columns: minmax(15rem,16rem) minmax(0,1fr)
sidebar: content-width, 1.25rem padding
navigation: full labels, vertical, >=44px items
main: min-width:0, 1.5rem padding
object layout: one fluid main column
```

The rule leaves `>=960px` desktop and `<=767px` normal-flow mobile behavior
unchanged. CSS ownership remains application-only, full shared-consumer rules
remain present, and `Моя работа` remains in the pre-removal navigation.

## One consolidated browser report

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-f18f61d/
```

The directory contains one report and all 23 screenshots. `report.json` pins
exact candidate `f18f61d7844236db4abcb27c26f5418e06a6b255`, Playwright
`1.62.1`, Chromium `151.0.7922.34` and Node `v22.23.1`.

```text
feb677bbbe1408f6eb5a7c954148d874b2046a61b803afa7639a445a85564ab7  report.json
```

The consolidated five-part oracle covers:

- canonical queue/card/prepare × `1440x900`, `768x1024`, `320x568`, and
  `320x568` with 200% root text;
- page/form horizontal overflow;
- nonzero/unclipped geometry for every visible specified text/control and a
  separate long/hostile/control geometry subset;
- primary region DOM/layout order and all pairwise positive-area overlaps;
- complete native sequential Tab traversal, visible outline and
  viewport-contained focused rect after browser scroll;
- picker open/search/result/all-child scroll containment/padded visibility,
  Escape/focus return, and exact 2× font scaling;
- construction-control/checklist/installers/admin configured shells at mobile
  normal and 200% with visible actor name/email.

Final exact summary:

```text
layout cases: 12
layout failures: 0
picker cases: 3
picker failures: 0
configured consumer cases: 8
configured consumer failures: 0
verdict: GREEN
```

At `768x1024`, each canonical surface reports:

```text
identity: x=28..244, width=216
navigation: x=28..244, width=216
main: x=264..760, width=496
identity/main overlap: 0
navigation/main overlap: 0
```

Identity precedes navigation inside the left column; both finish before main
begins horizontally. The tablet queue and prepare screenshots were inspected:
actor identity and navigation labels are readable/nonclipped, the 20px column
gap is visible, and main content remains usable.

Picker and administration-at-200% screenshots were also inspected. Enlarged
picker content remains internally scrollable and the noncanonical actor block
remains visible without overlap.

The earlier evidence-lineage RED at candidate `5574828` remains immutable and
correct for that candidate. This new exact-candidate report closes its three
tablet failures; it does not rewrite the earlier record.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed.

## Automated verification

At `2026-09-04T15:48:27+03:00` through
`2026-09-04T15:49:27+03:00`, exact candidate passed:

```text
pilot_ui_shell_001_test.php --css-ownership-only
PASS: PILOT-UI-SHELL-001 actual CSS ownership
pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell
pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form
pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS
pilot_route_csp_inventory_001_test.php
pilot_route_csp_inventory_001_test: PASS
pilot_route_csp_completion_final_html_001_test.php
pilot_route_csp_completion_final_html_001_test: PASS
pilot_route_csp_completion_flow_001_test.php
pilot_route_csp_completion_flow_001_test: PASS
local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract
local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission
make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
make lint
exit 0
openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
git diff --check
exit 0
```

At `2026-09-04T15:49:39+03:00`, the standalone object-card verifier reached
only its separately approved future navigation-removal assertion: expected
anchors omit `/pilot/`, while this pre-removal candidate correctly retains it.
Exit was `255`; no card/content/CSS regression is claimed GREEN from that
standalone run. The full UI-shell verifier exercised the configured card and
passed.

The Impeccable detector was not rerun, per instruction.

## Exact hashes

```text
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
7ca4ff4af448303f998b39f3094db97411dfdaac4d2e38e07146c2a2f2c623c2  app/PilotHttp/pilot.css
```
