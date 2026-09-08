# PILOT-UI-SHELL-001 — Gate 5 axis correction GREEN v2

- Date: `2026-09-04`
- Correction base: `581d80ce9f9c6782133b53d7c02b07cb9beb414c`
- Corrected production candidate: `2432746fde45a73568b090f0f5b2274848e63c92`
- Approved test HEAD: `67a61b639f7e66653ff8f75c498590a3531555db`
- Gate: `4 — correction evidence`
- Result: **GREEN for the corrected UI-shell composition axes**

This append-only record does not replace the earlier GREEN record and does not
claim Gate 5, navigation removal, repository-wide GREEN or release readiness.
No test, specification or review record changed.

## Correction

- Restored the full baseline `pilot.css` shared-consumer rules from integration
  base `796307e` and layered only the UI-shell responsive/wrapping/focus/dialog
  declarations needed by root, queue, card and upload-first prepare.
- Preserved picker/order-upload, checklist, construction-control, installer,
  user/role and pagination CSS instead of replacing the shared stylesheet with
  the historical narrow shell-only asset.
- Restored the capability-aware navigation composition for configured
  construction-control, checklist, installer and administration consumers.
- Kept a narrow explicit canonical-journey composition for root, queue, card
  and prepare, whose exact DOM is owned by the approved UI-shell test.
- Retained the pre-removal `Моя работа` link in both compositions. Other
  configured consumers keep their real conditional links and per-route
  `aria-current`; they are not collapsed into disabled placeholders.

## Automated verification

At `2026-09-04T14:18:58+03:00` through
`2026-09-04T14:19:51+03:00`, exact candidate
`2432746fde45a73568b090f0f5b2274848e63c92` passed:

```text
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
identity_access_runtime_ddl_001_test.php
PASS: IDENTITY-ACCESS-SCHEMA-001 isolated runtime observer

make architecture-check
ARCHITECTURE CHECK PASSED (7 rules)
make lint
exit 0
openspec validate remove-pilot-work-navigation-item --strict
Change 'remove-pilot-work-navigation-item' is valid
git diff --check
exit 0
```

A production-render projection with an actor granted `objects.read`,
`installers.read`, `construction_control.read` and `access.administer` observed
the exact links `/pilot/`, `/pilot/construction-control`, `/pilot/objects`,
`/pilot/installers`, `/pilot/admin/users`, `/pilot/admin/roles`. Each of
`Стройконтроль`, `Монтажники`, `Пользователи`, `Роли` and checklist's
`Объекты монтажа` context produced exactly one matching `aria-current=page`.

The navigation-removal verifier was deliberately kept RED at
`2026-09-04T14:20:00+03:00`: `Expected: 0 / Actual: 2` for the work label on
`/pilot/`. This is the intended pre-removal state; no navigation-removal
production was included in this correction.

## Browser proof

Existing external browser tooling was used without a repository harness edit.
Evidence directories:

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-correction-2432746/
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-correction-2432746-picker/
```

Both reports pin exact candidate SHA
`2432746fde45a73568b090f0f5b2274848e63c92`, Playwright `1.62.1`, Chromium
`151.0.7922.34` and Node `v22.23.1`.

The 12-case queue/card/prepare layout matrix covers `1440x900`, `768x1024`,
`320x568` and `320x568` with 200% root text. Results:

```text
HTTP 200: 12/12
overflow: 0
over-wide elements: 0
clipped elements: 0
focus failures: 0
```

The picker proof covers `1440x900`, `320x568`, and `320x568` with 200% root
text. In every case the runner opened the real picker, focused search, searched
for `Иванов`, observed exactly one result, verified the dialog remained inside
the viewport with no page overflow, closed it with Escape, and verified focus
returned to the opener. Result: `3/3 PASS`.

The isolated browser database was dropped. The exact task-owned CSS files and
`.test-artifacts` directory were removed after stopping the server.

## Consumer suite classification

The configured checklist final-HTML/CSP and session protocol tests above are
GREEN. At `2026-09-04T14:20:10+03:00` through
`2026-09-04T14:20:12+03:00`, broader current consumer suites remained non-GREEN
before making a presentation assertion:

- inspection item endpoint: fixture JSON decode `Syntax error`;
- inspection planning runtime: healthy control returned `503` instead of `200`;
- UserAccess fault/tokens suites: configured admin-users GET returned `503`
  instead of `200`.

These results are not hidden or promoted to GREEN. They do not justify
discarding the restored CSS/navigation consumers, and require their owning
integration slices before any repository-wide claim.

## Exact hashes

```text
9d8cdc4a8e75714b3d5a0b282804942375a0fde89b7fbddcd542884e4992bb12  tests/InstallationProcess/pilot_ui_shell_001_test.php
d1fbba6251ccaf7a02980b20189d63c24386e404b0026304f5a08ba4568d0c5a  app/PilotHttp/PilotView.php
0a5a78836e78ccb2219f7c99a37178f15d8229b225a84636a4017908d727a853  app/PilotHttp/PilotShellView.php
d2b98ae8103feabbc3511e4f5394dd580c790a74f64c66d0f8f9e6d4acfb069b  app/PilotHttp/ObjectListView.php
9a1ce9ab49346ec76c72206f7ccc08cc640f4a76b98809878fa0f3faa73eeef3  app/PilotHttp/ObjectCardView.php
846f7ef915af2bf25f5184a923ca44f7bb1e14a695a345fee250cd7ac2915c49  app/PilotHttp/PrepareFormView.php
80b33e1ced3b8f1771fee7e86b210de7e2a353942bb74efa48394d7a4e1dfef4  app/PilotHttp/pilot.css
```
