# PILOT-UI-SHELL-001 — scaled picker and mobile actor GREEN v6

- Date: `2026-09-04`
- Correction base: `07a63333415c5f0f5e2428700fa5ba05f3daa8fb`
- Production candidate: `557482811a26b0df6f4bef7d4baba0a47c5baeb5`
- Gate: `4 — bounded P0 axes correction evidence`
- Result: **GREEN**

This append-only record does not rewrite prior evidence and does not claim
Gate 5, navigation removal, repository-wide GREEN or release readiness. Tests,
specifications, reviews and repository harness files were unchanged.

## Production correction

- Removed the fixed `24px`/`16px` mobile picker typography introduced by the
  prior containment correction.
- Picker heading uses `1.5rem`; body, search, metadata, result and footer use
  `1rem`. Controls inherit the same scalable size.
- Picker padding and vertical spacing are relative; the existing bounded
  dialog owns internal scrolling instead of capping enlarged text.
- Mobile `.fm2-sidebar-user` is no longer globally hidden. In full configured
  consumer shells it renders as a normal-flow actor row with visible name and
  email. Canonical root/queue/card/prepare continue to expose the actor through
  their independent exact `.fm2-identity` composition.
- Mobile logo and navigation group wrapping, plus the clipped unfocused skip
  link width, prevent the restored full shell from creating page overflow at
  200% root text.

No navigation item, route, application behavior, desktop/tablet composition or
CSS ownership boundary changed.

## Picker scale and containment proof

Existing Playwright tooling was executed in memory without editing its source:

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-scale-consumers-5574828/
```

The report pins exact candidate
`557482811a26b0df6f4bef7d4baba0a47c5baeb5`, Playwright `1.62.1`, Chromium
`151.0.7922.34` and Node `v22.23.1`.

Computed font sizes at `320x568` normal → 200% root text:

```text
picker h3:      24px → 48px  ratio 2.0
picker body:    16px → 32px  ratio 2.0
search control: 16px → 32px  ratio 2.0
result meta:    16px → 32px  ratio 2.0
result button:  16px → 32px  ratio 2.0
```

For both normal and 200% cases, search/meta/result are inside the dialog's
scrollable content range, can each be scrolled fully inside the padded visible
content box, have no text clipping or horizontal overflow, and the page has no
horizontal overflow. Escape closes the picker and returns focus to its opener.
Result: `pickerFailed=[]`.

The initial external scale measurement aligned a scrolled rect fractionally to
the border (`552.28` vs `552`) through `scrollIntoView(nearest)`. The final
measurement explicitly accounts for dialog bottom padding when setting its
own scroll position; no production exception or font-size cap was added.

## Full configured consumer proof

The same report records production `PilotView::document` output for four
representative configured contexts at `320x568` and at 200% root text:

- construction-control;
- checklist (`Объекты монтажа` context);
- installers;
- administration users.

All eight cases prove exact actor strings `Тестовый Инженер` and
`test.engineer@example.invalid` are visible and nonclipped. Sidebar,
navigation, actor row and main occur in normal top order; every measured
positive-area overlap is `0`; page horizontal overflow is false.
Result: `consumerFailed=[]`.

Picker and all four consumer screenshots at 200% were visually inspected.
The actor block remains visible below navigation, main starts after it, and the
enlarged picker text scrolls inside its white bounded overlay.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed.

## Automated verification

At `2026-09-04T15:25:03+03:00` through
`2026-09-04T15:25:49+03:00`, exact production candidate passed:

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

The Impeccable detector was not rerun: it had already been run once on the
changed targets in the preceding bounded visual pass, as required.

## Exact hash

```text
8866d9270868f68f27fa69b0b9644126c059476fcbba5c0470fe0de99e3c8050  app/PilotHttp/pilot.css
```
