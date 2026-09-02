## Why

Текущий общий HTTP responder выдаёт `script-src 'self'` даже ответам, которым JavaScript не нужен, тогда как три действующих verifier ожидают прежнюю строгую политику. Slice `PILOT-ROUTE-CSP-001` переводит утверждённое владельцем решение `GRILL-002` в проверяемый route-scoped контракт до TEST-USER-READY.

## What Changes

- Ввести явную allowlist успешных `2xx` HTML routes, которые действительно возвращают внешние same-origin `<script src=...>`.
- Выдавать этим routes `script-src 'self'`; checklist дополнительно сохраняет уже необходимый ему `worker-src 'self'`, `connect-src 'self'` и `img-src ... blob:`.
- Оставить redirects, ошибки, assets и успешные script-free HTML на строгой CSP без `script-src`.
- Запретить inline scripts, `eval`, nonce/hash exceptions и third-party script origins.
- Удалить inline `CompletionFlow` fragment из checklist HTML и перенести его ограниченное поведение в уже загружаемый same-origin checklist asset; это security remediation, а не изменение completion semantics.
- Синхронизировать три известных CSP verifier с новым контрактом только через обязательные Gates 2–5.
- **Actor:** любой пользователь или клиент pilot HTTP boundary; политика не даёт новых прикладных полномочий.
- **Source oracle:** `app/PilotHttp/PilotHttp.php`, `PilotView.php`, HTML renderers, `rapid-pilot/router.php`, `LocalAuth.php`, `Shell.php`, `CompletionFlow.php`, route handlers и три текущих CSP regression verifier.
- **Target public seam:** полный HTTP response (`status`, `Content-Type`, `Content-Security-Policy`, body) для method/path/result tuple.
- **Release value:** минимальный JavaScript attack surface без поломки требуемых pilot interactions и без расширения error/asset policy.
- **Explicit non-goals:** RBAC, login/session semantics, route authorization matrix, business state, completion percentages/facts, Service Worker caching rules, asset content кроме bounded extraction inline behavior, third-party scripts и CSP reporting endpoint.

## Capabilities

### New Capabilities

- `security/pilot-route-csp`: Определяет allowlisted HTML routes и точную CSP по наблюдаемому типу/статусу ответа.

### Modified Capabilities

Нет.

## Impact

Затронуты централизованные HTTP response builders, непосредственные rapid-pilot responders, HTML decorators/renderers и внешний checklist asset. Existing routes и response bodies сохраняются, кроме удаления inline `<script>` и эквивалентного переноса поведения. Ожидаемое последствие — исчезновение трёх известных CSP regressions в `pilot_demo_bootstrap_001_test.php`, `pilot_http_auth_001_test.php` и `pilot_shlz_assets_001_test.php`; tests и production не меняются на стадии этого DRAFT.
