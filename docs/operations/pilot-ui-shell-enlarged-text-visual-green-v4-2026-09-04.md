# PILOT-UI-SHELL-001 — enlarged-text visual correction GREEN v4

- Date: `2026-09-04`
- Correction base: `ade8bc8e8e539822afa1b03a00bbb4a67b7ce4d3`
- Production candidate: `b78c59e6d0f3e3a3ad511e71c151030c32ee2ffd`
- Gate: `4 — P0 visual correction evidence`
- Result: **GREEN**

This append-only record does not claim Gate 5, navigation removal,
repository-wide GREEN or release readiness. Repository tests, specifications,
reviews and browser harness files were not changed.

## Correction

The bounded responsive pass changes only production CSS and two
application-owned engineer choice hooks in `PrepareFormView`:

- at `<=767px`, sidebar identity and primary navigation use normal-flow,
  auto-height single-column layout instead of the fixed 64px mobile bar;
- header/navigation precede main without sticky/fixed overlap;
- main, breadcrumb, queue, order surface, nested fieldsets and engineer choices
  retain `min-width:0`, full-width single-column composition and readable word
  wrapping;
- available navigation, picker controls and cancel action retain feasible
  44px touch targets;
- the skip link is clipped while unfocused and becomes a viewport-fixed visible
  control only when focused;
- the picker is bounded by 16px viewport insets, scrolls internally, prevents
  scroll chaining and supplies an application-owned background overlay;
- desktop/tablet rules, full consumer CSS, CSS ownership, pre-removal
  `Моя работа`, route scripts and application behavior remain unchanged.

## Confirmation browser evidence

Existing Playwright tooling was executed in memory; no repository or external
harness source was edited. Evidence:

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-p0-confirm-b78c59e/
```

`report.json` pins exact candidate
`b78c59e6d0f3e3a3ad511e71c151030c32ee2ffd`, Playwright `1.62.1`, Chromium
`151.0.7922.34` and Node `v22.23.1`.

One confirmation batch captured queue/card/prepare at `1440x900`, `768x1024`,
`320x568`, and `320x568` with 200% root text. Its enhanced oracle verifies:

- mobile header/navigation/main top order and zero positive-area overlap;
- every visible heading, navigation/breadcrumb/status/database text, label and
  native control has a nonzero box, no hidden/clip truncation and no internal
  scroll overflow;
- every keyboard-focused rect is fully viewport-contained after browser
  scrolling and has a visible outline;
- page and form have no horizontal overflow;
- skip link is hidden while unfocused.

Result:

```text
layout cases: 12/12
layout failures: 0
```

The same batch opened the real picker, focused search, found one `Иванов`
result, checked dialog bounds/internal overflow/background semantics, closed
with Escape and verified focus return at `1440x900`, `320x568`, and
`320x568` with 200% root text:

```text
picker cases: 3/3
picker failures: 0
```

Queue, prepare and open-picker screenshots at `320x568` with 200% root text
were visually inspected after the confirmation batch. Identity/navigation and
main are in normal flow, content is readable in one column, the picker remains
inside the viewport with a visible background overlay, and no overlap or
horizontal clipping is present.

The isolated browser database was dropped and exact task-owned
`.test-artifacts` was removed.

## Automated verification

At `2026-09-04T15:11:28+03:00` through
`2026-09-04T15:12:13+03:00`, exact production candidate passed:

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

## Impeccable detector

The required detector was run exactly once after UI work:

```text
node /home/antropophag/.codex-me/skills/impeccable/scripts/detect.mjs \
  --json app/PilotHttp/pilot.css app/PilotHttp/PrepareFormView.php
```

It reported one warning at the unchanged tab-navigation rule:
`border-accent-on-rounded` for `border-bottom:3px solid`. This is the existing
active-tab underline inside a rounded tab surface, not a card accent and not a
changed P0 target. The bounded pass did not alter that separate consumer.

## Exact hashes

```text
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
cd6390eb1f2dda7e0928a8a2c3e9f32bebd1d97eec78babbfebc5f92936294b4  app/PilotHttp/pilot.css
20b6975a9e2917341b09fa1fb43096b4e9ba9a64787301847acaa3d63c621eed  app/PilotHttp/PrepareFormView.php
```
