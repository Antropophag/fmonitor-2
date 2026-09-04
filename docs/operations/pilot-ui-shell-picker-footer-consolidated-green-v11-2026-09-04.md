# PILOT-UI-SHELL-001 — picker footer consolidated GREEN v11

- Date: `2026-09-04`
- Correction base: `6fa0f8380fe24a4fcb845798bbe412e699df1789`
- Production candidate: `d17803a9a259ea85e8b551ec70db2f85cf437768`
- Gate: `4 — minimal footer correction evidence`
- Result: **GREEN**

This append-only record does not claim Gate 5, navigation removal,
repository-wide GREEN or release readiness. Tests, specifications, reviews and
repository browser harness files were unchanged.

## Minimal production correction

The mobile picker footer now has relative bottom breathing room:

```css
.fm2-picker-footer{margin-bottom:.25rem}
```

At 200% root text this becomes an 8px scroll-end safety margin. Typography
remains fully scalable; no font cap, tolerance change, clipping or oracle
exception was introduced.

## One full consolidated v11 report

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-v11-d17803a/
```

The directory contains one `report.json` and 23 screenshots. All canonical,
picker and configured-consumer cases were freshly exercised against exact
production candidate `d17803a9a259ea85e8b551ec70db2f85cf437768`.
Runtime: Playwright `1.62.1`, Chromium `151.0.7922.34`, Node `v22.23.1`.

```text
c43a3d9993b77bf290cd23e39f948a7312bd373ea61bddb804b89bdd8c816cc0  report.json
```

Final summary:

```text
canonical layout cases: 12
canonical layout failures: 0
picker cases: 3
picker failures: 0
configured consumer cases: 8
configured consumer failures: 0
verdict: GREEN
```

The canonical report retains root-font `16/32`, full visible text/control and
long/hostile geometry, identity/queue sibling order, primary region overlap,
page/form overflow and complete sequential keyboard focus evidence.

The picker aggregation explicitly enumerates and checks:

```text
heading, body, selection, search, meta, results, result, footer
```

Every child must be a dialog descendant, unclipped and individually fully
inside the padded visible content after controlled scrolling. Footer uses
exact maximum scroll. Close state is stored and required in two independent
fields: `close.hidden` and `close.focusReturned`.

At `320x568` with 200% root text:

```text
footer requested scroll: 489
footer actual scroll: 489
dialog maximum scroll: 489
footer rect: left=40 top=476.40625 right=280 bottom=519.59375
padded content: left=40 top=40 right=280 bottom=528
footer paddedVisible: true
footer unclipped: true
bottom safety margin: 8.40625px
close.hidden: true
close.focusReturned: true
```

Heading/body/search/meta/result/footer computed font ratios remain exactly
`2.0` between normal 320px and 200% root text.

Tablet queue, mobile enlarged prepare, mobile enlarged picker and mobile
enlarged administration screenshots were inspected. Tablet composition and
identity spacing remain intact; footer is visible inside the white picker
surface with bottom breathing room; actor identity remains readable.

This v11 exact-candidate report closes the single v10 footer RED. The v10
lineage record remains immutable historical evidence.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed.

## Automated verification

At `2026-09-04T16:15:09+03:00` through
`2026-09-04T16:15:56+03:00`, exact candidate passed:

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

The standalone card verifier remains classified RED only at the separately
approved future navigation-removal anchor assertion. The full configured card
passes in the UI-shell verifier and consolidated browser report.

No Impeccable detector rerun was performed, per instruction.

## Exact hashes

```text
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
784252cb7ea31be4694204ee7a60aa9704928ad5e59dc37649d9ce1a49df7fa5  app/PilotHttp/pilot.css
```
