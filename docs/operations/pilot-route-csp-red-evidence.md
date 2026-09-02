# PILOT-ROUTE-CSP-001 — Gate 2 RED evidence

Дата: 2026-09-01.

Executable specification: `specs/PILOT-ROUTE-CSP-001.md`, owner-approved hash
`47727bc12e904980fb751f9547f52b7a2abe67be1639c6c9e9a28b2016cd68ef`.

## Public HTTP matrix (task 2.1)

Test: `tests/InstallationProcess/pilot_route_csp_001_test.php`.

Public seam: `PilotHttpCoordinator::handle(PilotHttpRequest) -> PilotHttpResponse`.
The test uses in-memory identity, user, CSS and card adapters; it does not read
MariaDB, Compose or the network. It aggregates results so an early assertion
cannot hide later route classes.

Command:

```text
php -l tests/InstallationProcess/pilot_route_csp_001_test.php
php tests/InstallationProcess/pilot_route_csp_001_test.php
```

Result: syntax PASS; deterministic intended RED, exit 255. Successful scripted
GET and HEAD and successful checklist already have their expected policies.
The following reached their expected status and then failed only on the current
global `script-src 'self'` policy:

- unauthorized scripted route (`401`), A4/A11;
- redirect (`308`), A5;
- JavaScript asset (`200 text/javascript`), A6;
- `/pilot/objects/0` and `/pilot/objects/4512/unknown` (`404`), A7;
- successful script-free HTML (`200 text/html`), A4/A12.1;
- checklist error (`404`), A9.

There were no setup/status failures after correcting the test-only identity
adapter to raise the production-recognized `InvalidServerIdentity`.

Direct login test: `tests/InstallationProcess/pilot_route_csp_login_001_test.php`.
It starts the real `rapid-pilot/router.php`, creates only three uniquely prefixed
tables in the canonical test database, and removes them in `finally`. It obtains
the real session cookie and CSRF token through `GET /pilot/login`, then exercises
the exact public HTTP outcomes. It does not use the production database.

Commands:

```text
php -l tests/InstallationProcess/pilot_route_csp_login_001_test.php
docker run --rm --network host --entrypoint php \
  -v /home/antropophag/code/fmonitor-2:/workspace/fmonitor-2 \
  -w /workspace/fmonitor-2 \
  -e FMONITOR_DB_HOST=127.0.0.1 -e FMONITOR_DB_PORT=23306 \
  -e FMONITOR_DB_NAME=fmonitor2_test \
  -e FMONITOR_DB_USER=fmonitor2_test \
  -e FMONITOR_DB_PASSWORD=fmonitor2_test_local \
  fmonitor2-pilot tests/InstallationProcess/pilot_route_csp_login_001_test.php
```

Result: syntax PASS; deterministic intended RED, exit 255. The following setup
and positive assertions passed before the aggregate failure:

- scripted `GET /pilot/login` returned `200` with `SCRIPT_HTML_CSP`;
- scripted invalid-credential `POST /pilot/login` returned `200` with the same
  policy and `/pilot/assets/preloader.js` in the body;
- invalid-CSRF POST reached the expected `403`;
- valid-password POST reached the expected `303 /pilot/objects` redirect;
- every response preserved `Cache-Control: no-store`.

The only failures were the intended missing CSP behavior on existing direct
responses:

```text
- A4 login error 403 CSP: expected [BASE_CSP], actual [missing]
- A5 successful login redirect 303 CSP: expected [BASE_CSP], actual [missing]
```

Together the coordinator and direct-login tests cover the task 2.1 minimum:
scripted GET/HEAD success, scripted POST login success, script-free success,
login/other error, redirect, asset, exact near-miss, checklist success/error and
the checklist-only worker/connect/blob policy. All current failures are observed
policy mismatches after expected HTTP outcomes, not environment/setup failures.

## CompletionFlow external-cap RED (task 2.2)

Test: `tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php`.

Command:

```text
php -l tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
php tests/InstallationProcess/pilot_route_csp_completion_flow_001_test.php
```

Result: syntax PASS; deterministic intended RED, exit 255:

```text
TestFailure: A10 CompletionFlow does not inject executable inline script into final checklist HTML
Expected: 0
Actual: 1
```

Both source files were read successfully before the assertion. The test has no
DB/environment dependency. After inline removal it additionally requires the
existing external `app/PilotHttp/checklist.js` behavior to read
`data-progress-cap` and preserve both `85` and `100`; therefore deleting the
fragment without transferring the cap behavior cannot make it GREEN.

No production file was changed and no Gate 3 review was authored by this test
author.
