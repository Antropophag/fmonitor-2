# PILOT-ROUTE-CSP-001 — Gate 2 correction evidence v2

Дата: 2026-09-02. Основание возврата в Gate 2:
`reviews/tests/PILOT-ROUTE-CSP-001.md`, verdict `CHANGES_REQUIRED`.
Approved executable spec не изменялся; production code не изменялся.

## Исправления blocking findings

1. GET/HEAD теперь сравниваются по byte-exact CSP и `Content-Length`; GET body
   проверяется против declared length, HEAD body обязан быть пуст.
2. Login fixture использует exact approved `SCRIPT_HTML_CSP` order. Текущий
   direct responder теперь RED также на неправильном порядке directives.
3. Реальные final login/checklist HTML проверяются на inline script, event
   attributes, `javascript:`, eval/Function и third-party script URLs. Все четыре
   approved policies проверяются на banned CSP tokens.
4. External cap теперь выполняется Node verifier: `[97,85] -> 85`, `[85,85] ->
   85`, `[64,85] -> 64`, а cap `100` наблюдаемо даёт `100`. Удаление inline
   fragment без подключённого tested helper не даст GREEN.
5. Отдельная table-driven inventory matrix перечисляет каждый approved route
   family, GET/HEAD/method/status/media boundaries, обе checklist families,
   positive-id near-misses, command/export/future routes, CSS/JS/SVG/font/PDF и
   exact Service Worker policy.
6. Main HTTP fixture фиксирует body/status/type/length/security/cache headers,
   operational `503` с `Retry-After`, redirect `Location`, repeated safe-read
   identity. Final checklist fixture сравнивает counts installation/history и
   completion facts до/после safe rendering.

## Commands and intended RED

Все PHP/Node syntax checks PASS:

```text
php -l tests/InstallationProcess/pilot_route_csp_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_login_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
node --check tests/InstallationProcess/support/pilot_route_csp_completion_browser.js
```

`php tests/InstallationProcess/pilot_route_csp_001_test.php` — intended RED,
exit 255 after all expected statuses are reached: base policy differs on 401,
308, JS/CSS assets, both near-misses, script-free HTML, checklist 404 and
operational 503. Scripted GET/HEAD parity, response preservation, checklist
success and worker policy reach their assertions without setup failure.

`php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php` —
intended RED, exit 255: `HTTP-boundary route/result classifier is missing`.
This is missing approved Gate 4 behavior, not an environment failure; the full
table executes once that boundary helper exists.

`php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php` —
intended aggregate RED, exit 255: one inline script block and executable external
cap helper missing. Node itself starts and executes the actual checklist asset.

Login and final checklist commands use only the canonical test database:

```text
docker run --rm --network host --entrypoint php \
  -v /home/antropophag/code/fmonitor-2:/workspace/fmonitor-2 \
  -w /workspace/fmonitor-2 \
  -e FMONITOR_DB_HOST=127.0.0.1 -e FMONITOR_DB_PORT=23306 \
  -e FMONITOR_DB_NAME=fmonitor2_test \
  -e FMONITOR_DB_USER=fmonitor2_test \
  -e FMONITOR_DB_PASSWORD=fmonitor2_test_local \
  fmonitor2-pilot tests/InstallationProcess/pilot_route_csp_login_001_test.php

# same command/environment, final argument:
tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
```

Login intended RED, exit 255: CSS/SVG/font CSP absent; GET and successful POST
login have non-approved directive order; 403 and 303 CSP absent. Expected
status, preloader, CSRF/session, `Location`, bytes/cache assertions pass.

Final checklist intended RED, exit 255: both real incomplete cap-85 and complete
cap-100 representations contain the current inline CompletionFlow script. Both
caps, external checklist asset, persisted fact counts and all other forbidden
forms reach expected assertions; no setup failure.

Gate 3 remains closed pending a fresh independent rereview. This author did not
edit the review record or production implementation.
