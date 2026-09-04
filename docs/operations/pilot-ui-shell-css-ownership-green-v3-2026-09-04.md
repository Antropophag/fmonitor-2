# PILOT-UI-SHELL-001 — CSS ownership correction GREEN v3

- Date: `2026-09-04`
- Gate 3 reviewed test HEAD: `5774aedff5ffe4376fd55800a8ef05e61c7ef2fd`
- Production candidate: `cd25498db67f154170529a25804026d4cdf4efb5`
- Gate: `4 — correction GREEN evidence`
- Result: **GREEN**

This is a new append-only correction record. It does not claim Gate 5,
navigation removal, repository-wide GREEN or release readiness. Tests,
specifications and review records were unchanged.

## Production correction

The exact 20 CSS ownership findings from the approved RED were removed:

- application layout no longer targets `.shlz-*` selectors directly, in
  selector lists or as descendants;
- root, focus and selection rules are scoped through `.fm2-shell`;
- checklist dock rules target their application-owned container and native
  button descendants;
- pagination keeps public `shlz-ui` component classes in markup and adds
  `fm2-pagination-item` / `fm2-pagination-list` layout hooks;
- reduced-motion declarations no longer use `!important`;
- no `--shlz-*` custom property is defined or copied; public token reads via
  `var(--shlz-*, fallback)` remain intact.

The full shared-consumer stylesheet, capability-aware navigation, canonical
root/queue/card/prepare shell, picker/order-upload, checklist,
construction-control, installer and user/role rules remain present.

## Automated verification

At `2026-09-04T14:53:21+03:00` through
`2026-09-04T14:54:04+03:00`, on exact production candidate
`cd25498db67f154170529a25804026d4cdf4efb5`:

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

pilot_session_storage_protocol_001_test.php
PASS: PILOT-SESSION-STORAGE-001 raw HTTP protocol tracer

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
make lint
exit 0
openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
git diff --check
exit 0
```

A direct production renderer probe additionally proved that pagination emits
the same six `shlz-pagination__item` component classes together with six
`fm2-pagination-item` hooks and one `fm2-pagination-list` hook.

## Browser evidence

Existing external browser tooling was used without a repository harness edit:

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-css-ownership-cd25498/
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-css-ownership-cd25498-picker/
```

Both reports pin exact Git SHA
`cd25498db67f154170529a25804026d4cdf4efb5`, Playwright `1.62.1`, Chromium
`151.0.7922.34`, and Node `v22.23.1`.

Queue/card/prepare layout matrix:

```text
HTTP 200: 12/12
overflow: 0
over-wide elements: 0
clipped elements: 0
focus failures: 0
```

Picker interaction at `1440x900`, `320x568`, and `320x568` with 200% root
text passed `3/3`: one `Иванов` result, search focus, dialog inside viewport,
no overflow, Escape close, and focus returned to the opener.

The isolated browser database was dropped and the exact task-owned
`.test-artifacts` directory was removed.

## Exact hashes

```text
b85724b83453a8387b0a7ff742ca0a3586c3bf0ed90267dc03d2d3a644d37c4b  tests/InstallationProcess/pilot_ui_shell_001_test.php
0f87d2612a52e3ab30f289654ca5a8f9274da5a21a263085cd9f5293efcb5d51  app/PilotHttp/pilot.css
dc84358ebf4e1fbe879dc05140aecb6d8c72e18ef4fa0151bf1e8b8baeaba883  app/PilotHttp/PilotView.php
```
