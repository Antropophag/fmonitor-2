# PILOT-UI-SHELL-001 — upload-first integration GREEN

- Date: `2026-09-04`
- Approved Gate 3 review: `reviews/tests/PILOT-UI-SHELL-001-upload-first-integration-v4.md`
- Approved test HEAD: `67a61b639f7e66653ff8f75c498590a3531555db`
- Production candidate HEAD: `b082bbb9a4f414bf1d314a87c12a129677bfe4fd`
- Gate: `4 — GREEN evidence`
- Result: **GREEN for the approved UI-shell integration scope**

This record does not claim Gate 5, navigation removal, repository-wide GREEN,
integration completion or release readiness. Tests, specifications and review
records were not changed during Gate 4.

## Implemented production composition

- Restored the configured shared header, identity, primary navigation,
  breadcrumbs, semantic queue and responsive application CSS required by
  `PILOT-UI-SHELL-001 v0.4`.
- Preserved the Gate-5-approved object-card fact/body composition and the
  upload-first Prepare v0.2 picker, engineer, provenance and action semantics.
- Added the CSP-approved exact script order: `navigation.js`, followed only on
  the applicable route by `object-details.js` or deferred `picker.js`.
- Preserved the unconfigured predecessor documents with one `shlz.css`, no
  shared-shell DOM, and the successor-approved scripts/CSP.
- Separated MariaDB installer/user readers from HTML views and moved session
  login/invitation markup out of `PilotE2ECoordinator`; public behavior and
  application seams are unchanged.
- The `Моя работа` navigation item remains present. Removal belongs to the
  separately gated `remove-pilot-work-navigation-item` Gate 4.

## Automated GREEN

At `2026-09-04T14:02:23+03:00` through
`2026-09-04T14:03:14+03:00`, on exact production candidate HEAD
`b082bbb9a4f414bf1d314a87c12a129677bfe4fd`:

```text
FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 \
  php tests/InstallationProcess/pilot_ui_shell_001_test.php
PASS: PILOT-UI-SHELL-001 public UI shell

FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 \
  php tests/InstallationProcess/pilot_prepare_form_001_test.php
PASS: PILOT-PREPARE-FORM-001 public HTTP read-only form

php tests/InstallationProcess/pilot_route_csp_001_test.php
pilot_route_csp_001_test: PASS
php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
pilot_route_csp_inventory_001_test: PASS
php tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
pilot_route_csp_completion_final_html_001_test: PASS
php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
pilot_route_csp_completion_flow_001_test: PASS

php tests/InstallationProcess/local_rbac_auth_contract_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 public seam contract
FMONITOR_TEST_DB_ADMIN_PASSWORD=... FMONITOR_TEST_DB_PORT=23306 \
  php tests/InstallationProcess/local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

FMONITOR_TEST_DB_HOST=127.0.0.1 FMONITOR_TEST_DB_PORT=23306 \
FMONITOR_TEST_DB_ADMIN_USER=root FMONITOR_TEST_DB_ADMIN_PASSWORD=... \
  php tests/InstallationProcess/identity_access_runtime_ddl_001_test.php
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

All PHP production files under `app/PilotHttp` passed `php -l` immediately
before the exact candidate commit.

## Browser proof

At approximately `2026-09-04T14:00:00+03:00`, the existing
`final-v04-smoke.js` runner exercised the real isolated HTTP fixture with
Playwright `1.62.1`, Chromium `151.0.7922.34` and Node `v22.23.1`.

External evidence:

```text
/home/antropophag/code/fmonitor-2-visual-tools/evidence/ui-shell-integration-b082bbb/
```

The recorded `runtime.json` pins exact Git SHA
`b082bbb9a4f414bf1d314a87c12a129677bfe4fd`. Its 12 cases cover queue, card
and prepare at `1440x900`, `768x1024`, `320x568`, and `320x568` with 200% root
text. All 12 returned HTTP `200`; report aggregation is:

```text
overflow=0
clipped=0
wide=0
focusFailures=0
```

The failed pre-CSS candidate evidence directory is not used as GREEN proof.
Both isolated fixture databases were dropped and task-owned temporary CSS
directories were removed after the run.

## Classified predecessor RED

At `2026-09-04T14:03:19+03:00` through `2026-09-04T14:03:21+03:00`:

- `pilot_http_auth_001_test.php` reaches its historical shell CSP assertion and
  expects pre-successor `BASE_CSP`; actual is the owner-approved scripted HTML
  CSP required by `PILOT-ROUTE-CSP-001` and this UI-shell Gate 3.
- `pilot_object_list_001_test.php` reaches the already-reviewed future
  navigation-removal assertion and reports `Expected: 0 / Actual: 1` for the
  intentionally retained `/pilot/` work item.
- `pilot_object_card_001_test.php` reaches the same future removal axis: its
  expected anchor multiset omits `/pilot/`, while current Gate 4 correctly
  retains that link.

These are not accepted regressions or repository-wide GREEN. The latter two
become actionable only after the independently reviewed navigation-removal
Gate 3 authorizes production removal. The historical HTTP-auth assertion must
be reconciled through its own governed predecessor correction; production was
not weakened to satisfy it.

## Exact hashes

```text
9d8cdc4a8e75714b3d5a0b282804942375a0fde89b7fbddcd542884e4992bb12  tests/InstallationProcess/pilot_ui_shell_001_test.php
24d019e71d9bcb3d13851ffb45f81ee49a84d9864716401770101ba9b9386cda  app/PilotHttp/PilotView.php
da3bbd9dd0479af5523b9b1491d204834f73bc74953a80e0381e026dfa01d30b  app/PilotHttp/PilotShellView.php
c8421c927f400a652107887eeb9de34d896f9db2be0dc8f738de36ae551ec506  app/PilotHttp/ObjectListView.php
d61a808afcee2e39f594afee2e73069d95d2e5e8fbe57315e883f313db4f61e5  app/PilotHttp/ObjectCardView.php
d421a3e71bbbc113780259a661286070b90ed5ab8965786519a6a8da02200859  app/PilotHttp/PrepareFormView.php
3ac54d11b90484cd7e22ca3df73d348c5b7245726a6d54ded232d05b300a6699  app/PilotHttp/pilot.css
d1dd2e1a4041ec6380beaba4e2b017646ee404832a76252565751439de56ce69  app/PilotHttp/MariaDbInstallerDirectoryReader.php
d77b6aaba05b95ac230e93825dec37b390f8202b185b4e6f3e5bd4872e69f1e6  app/PilotHttp/InstallerDirectoryView.php
994eb3938fb90e6f20c80bbf8f8d683be670b508ca9e1fd2bf0ac5708bb3dabe  app/PilotHttp/MariaDbPilotUserDirectory.php
cd4f15f96f21773bf1ec9a2cd977ceeeac424d3ef4a23f3ee32234bfc7ecdfe3  app/PilotHttp/UserDirectoryView.php
eac2615cd08df4a8525c0cfff6143464d299c5cf5cc9e959fe258f7bbd08286c  app/PilotHttp/PilotSessionView.php
545326795c383626aadfdd904fd6b9db686f34789d02630c319b4aff2ce7e683  app/PilotHttp/PilotE2ECoordinator.php
```
