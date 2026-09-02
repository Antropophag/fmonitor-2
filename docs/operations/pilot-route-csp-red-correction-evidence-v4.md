# PILOT-ROUTE-CSP-001 — Gate 2 fixture correction evidence v4

Дата: 2026-09-02.

Во время minimal GREEN выявлено противоречие в approved inventory test:
`POST /pilot/login` с successful `200 text/html` сначала ожидал
`SCRIPT_HTML_CSP`, затем тот же tuple внутри общего method-boundary loop ожидал
`BASE_CSP`.

Approved spec и production не менялись. Test исправлен минимально: общий POST
boundary assertion пропускает только exact `/pilot/login`; dedicated assertion
сохраняет A1b scripted exception. Все остальные allowlisted и checklist paths
по-прежнему обязаны классифицировать successful POST как `BASE_CSP`.

## Verification

```text
php -l tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
```

PASS.

На текущем незавершённом minimal GREEN:

```text
php tests/InstallationProcess/pilot_route_csp_inventory_001_test.php
pilot_route_csp_inventory_001_test: PASS
```

Для восстановления обязательного RED тот же corrected test запущен во временной
изолированной pre-GREEN filesystem fixture без boundary classifier. Общий dirty
worktree не откатывался и не изменялся:

```text
PHP Fatal error: Uncaught TestFailure:
PILOT-ROUTE-CSP-001 intended RED: HTTP-boundary route/result classifier is missing
```

Это intended missing-behavior RED, не setup failure. Временная fixture удалена
после запуска. Другие expectations и production files не менялись. Gate 3
review этим автором не выполнялся; corrected tests требуют fresh rereview.
