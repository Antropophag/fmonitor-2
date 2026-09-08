# PILOT-UI-SHELL-001 — identity spacing consolidated GREEN v9

- Date: `2026-09-04`
- Correction base: `62db85de7d819cf51cfc862ef782b45b17befe44`
- Production candidate: `6bf97f72254fb96849c1ef4322325a2e0b997ee4`
- Gate: `4 — final bounded UI-shell correction evidence`
- Result: **GREEN**

This append-only record does not claim Gate 5, navigation removal,
repository-wide GREEN or release readiness. Tests, specifications, reviews and
repository browser harness files were unchanged.

## Production correction

One base application-owned declaration makes both identity groups explicit:

```css
.fm2-identity,.fm2-queue-identity{display:grid;gap:.25rem}
```

Product organization, product name and actor now occupy distinct ordered rows
at every breakpoint. Queue object ID and registration number likewise occupy
distinct ordered rows. The relative gap respects root text scaling. Mobile,
tablet, desktop, CSS ownership and full consumer rules are otherwise unchanged.

## One exact-candidate consolidated report

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-consolidated-6bf97f7/
```

The directory contains one `report.json` and all 23 screenshots. It pins exact
candidate `6bf97f72254fb96849c1ef4322325a2e0b997ee4`, Playwright `1.62.1`, Chromium
`151.0.7922.34` and Node `v22.23.1`.

```text
f459baefa0cf3fa35e86af76da9224c61b01e8a3b586e10c086c5fb5ba31b1eb  report.json
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

The single canonical oracle retains all prior axes: page/form horizontal
overflow, full visible text/control geometry, long/hostile/control subset,
primary region order and overlap, complete native sequential Tab traversal,
focus outline/viewport containment, picker scroll containment and configured
consumer actor visibility.

It additionally records and asserts:

- canonical root computed font before and after setup for every case;
- exact `16px` before/after on all normal cases;
- exact `16px → 32px`, ratio `2.0`, for queue/card/prepare text-200 cases;
- direct child rects, visual order and pairwise overlap for every
  `.fm2-identity`;
- direct child rects, visual order and pairwise overlap for every
  `.fm2-queue-identity`.

At `768x1024`, identity children are:

```text
АО «ЩЛЗ»:                 y=28..46
FMonitor 2.0:             y=50..68
Сидоров Сергей Сергеевич: y=72..90
ordered=true, overlaps=[]
```

The three queue identities at the same viewport report respectively:

```text
4513 y=144..165; 77-000124 y=169..187
4512 y=241..262; 77-000123 y=266..284
4515 y=338..359; 77-000126 y=363..381
ordered=true, overlaps=[]
```

Desktop queue, tablet queue and tablet prepare screenshots were inspected.
Identity rows and ID/registration pairs have visible spacing; tablet sidebar
and main remain separated and main content remains usable.

Picker fonts still scale exactly `2.0`; picker failures remain empty.
Construction-control/checklist/installers/admin actor cases remain `8/8` with
visible nonclipped identity and zero overlap.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed.

## Automated verification

At `2026-09-04T15:58:09+03:00` through
`2026-09-04T15:59:02+03:00`, exact candidate passed:

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

The separately approved future navigation-removal assertion still makes the
standalone card verifier RED on the intentionally retained `/pilot/` anchor.
The configured card in the full UI-shell verifier is GREEN.

The Impeccable detector was not rerun, per instruction.

## Diff-check scope

Before adding this record:

```text
git diff --check 62db85d..6bf97f7
exit 0
working tree
clean except the committed production candidate ahead of upstream
```

Historical range `c5f645f^..6bf97f7` returns exit `2` only for the immutable v7
evidence blank EOF documented separately. That historical whitespace is not a
new candidate diff and was not rewritten.

## Exact hashes

```text
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
70f357ceb387c55738139f03512065d86551477359c8291dadcf7c5403ea36cd  app/PilotHttp/pilot.css
```

