# PILOT-ROUTE-CSP-001 — Gate 2 correction evidence v3

Дата: 2026-09-02. Основание: independent rereview v2
`reviews/tests/PILOT-ROUTE-CSP-001-v2.md`, verdict `CHANGES_REQUIRED` только по
preservation/no-write sensitivity. Approved spec и production не изменялись.

## Independent preservation oracles

Direct responder asset responses теперь сравниваются по SHA-256 с отдельными
source files, прочитанными до сравнения:

- CSS — `rapid-pilot/pilot.css`;
- SVG — `rapid-pilot/favicon.svg`;
- font — `rapid-pilot/fonts/golos-text-cyrillic-400-normal.woff2`;
- Service Worker — `app/PilotHttp/checklist-sw.js`.

Для каждого фиксированы exact `Content-Type`, `Content-Length` из independent
source bytes, cache policy, `nosniff`, asset-specific worker header и CSP.
Production уже не может изменить bytes и согласованно поменять свой length так,
чтобы тест остался GREEN. Coordinator JS также сравнивается SHA-256 с исходным
`app/PilotHttp/checklist.js`.

Representative coordinator success, HEAD, 401 error, 308 redirect, JS asset и
operational 503 фиксируют exact existing content/cache/security headers:
`Content-Type`, `Content-Length`, `X-Content-Type-Options`, `Referrer-Policy`,
`X-Frame-Options`, `Permissions-Policy`, `Cross-Origin-Opener-Policy`,
`Cache-Control`; дополнительно `Location` или `Retry-After`. GET bytes и empty
HEAD сохраняются независимо от CSP assertion.

## Full persistence and audit snapshots

Final checklist fixture заранее создаёт все reachable slice tables, включая
audit seam `fm2_process_events`. Перед и после каждого incomplete/complete safe
render строится SHA-256 от:

- полного `SHOW CREATE TABLE` каждой таблицы;
- всех колонок всех rows в стабильном `ORDER BY id`;
- таблиц `fm2_installation_cases`, `fm2_pilot_completion_facts` и
  `fm2_process_events`.

Поэтому INSERT, UPDATE, DELETE, audit append и schema mutation меняют snapshot.
Intentional setup двух completion facts происходит до второго baseline snapshot
и не маскирует render-side write.

## Verification

```text
php -l tests/InstallationProcess/pilot_route_csp_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_login_001_test.php
php -l tests/InstallationProcess/pilot_route_csp_completion_final_html_001_test.php
```

Все syntax checks PASS.

Coordinator RED остаётся только на утверждённых CSP mismatches; новые exact
header/hash preservation assertions проходят. Login RED остаётся только на
missing/wrong-order CSP; independent asset hash/header assertions проходят.
Final checklist RED остаётся только на inline script в реальных cap-85 и cap-100
representations; оба full schema+ordered-row+audit snapshots byte-identical.

Команды запуска и canonical isolated test DB environment совпадают с v2
evidence `docs/operations/pilot-route-csp-red-correction-evidence-v2.md`.
Setup/status/hash/header/history failures отсутствуют.

Gate 3 снова требует fresh independent rereview. Автор не менял production,
approved spec или review records.
