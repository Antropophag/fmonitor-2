# PILOT-ROUTE-CSP-001 — route-scoped Content Security Policy

Статус: **DRAFT / Gate 1**  
Версия: **v1**  
Дата: **2026-09-02**  
Основание: `docs/operations/security-artifact-contract-owner-decision.md`, `GRILL-002 / APPROVED_ALL`

## Простыми словами

JavaScript разрешается только успешным HTML-страницам, которые действительно загружают наши внешние script-файлы. Ошибки, перенаправления, файлы и страницы без JavaScript получают более строгую защиту. Встроенный script из checklist удаляется, но расчёт и показ прогресса остаются прежними.

Этот slice не меняет права пользователей, login/session, данные, completion rules или Service Worker caching.

## Public seam

Наблюдаемый seam — полный pilot HTTP response для пары method/path и получившегося результата:

- status;
- `Content-Type`;
- `Content-Security-Policy`;
- остальные security/cache headers;
- `Content-Length` и body (для `HEAD` — body пуст).

Actor — любой pilot HTTP client; наличие `script-src` не является authorization grant.

## Normative policy values

`BASE_CSP`:

```text
default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
```

`SCRIPT_HTML_CSP`:

```text
default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
```

`CHECKLIST_HTML_CSP`:

```text
default-src 'none'; style-src 'self'; script-src 'self'; worker-src 'self'; connect-src 'self'; img-src 'self' blob:; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
```

Checklist Service Worker asset сохраняет отдельно утверждённую worker-script policy:

```text
default-src 'self'; connect-src 'self'
```

Ни одна политика не содержит `unsafe-inline`, `unsafe-eval`, nonce, script hash, wildcard или third-party origin.

## Script-enabled successful HTML allowlist

Только успешный `2xx text/html` результат `GET` или эквивалентного `HEAD`
следующих exact route patterns получает `SCRIPT_HTML_CSP`, если итоговая
representation загружает внешний same-origin script. Единственное исключение по
method — `POST /pilot/login`, когда invalid credentials или промежуточный login
step возвращает существующий `200 text/html` с `/pilot/assets/preloader.js`;
этот response также получает `SCRIPT_HTML_CSP` без изменения login semantics.

| Route pattern | Required external script evidence |
|---|---|
| `/pilot/login` | `/pilot/assets/preloader.js` |
| `/pilot/` | `/pilot/assets/navigation.js` |
| `/pilot/objects` | navigation plus queue/scheduling scripts in deployed adapter |
| `/pilot/objects/{positive-id}` | navigation plus `/pilot/assets/object-details.js` |
| `/pilot/objects/{positive-id}/assignment-order/prepare` | navigation plus `/pilot/assets/picker.js` |
| `/pilot/objects/{positive-id}/checklist` | navigation plus `/pilot/assets/checklist.js?...` |
| `/pilot/construction-control` | navigation plus control queue script |
| `/pilot/construction-control/objects/{positive-id}/checklist` | navigation plus checklist script |
| `/pilot/installers` | navigation plus installer-directory script |
| `/pilot/admin/users`, `/pilot/admin/roles` | navigation; users may add users/invite scripts |
| `/pilot/calendar`, `/pilot/calendar/` | navigation plus calendar script |
| listed OTIZ read routes below | navigation plus OTIZ script |

OTIZ HTML read routes: `/pilot/otiz`, `/pilot/otiz/`, `/pilot/otiz/objects`, `/pilot/otiz/payments`, `/pilot/otiz/history`, `/pilot/otiz/reconciliation`, `/pilot/otiz/reconciliation/quarantine`, `/pilot/otiz/active-baselines`, `/pilot/otiz/historical-replay`, `/pilot/otiz/snapshots/{positive-id}`. Export и command routes в allowlist не входят.

`{positive-id}` — canonical decimal integer `[1-9][0-9]*`, принятый существующим router целиком; substring/prefix match запрещён. Новый route по умолчанию получает `BASE_CSP` и требует отдельного reviewed allowlist change.

## Acceptance scenarios

### A1 — successful allowlisted GET

При `GET /pilot/objects` с валидным actor и исправным environment:

- status `200`, `Content-Type: text/html; charset=UTF-8`;
- CSP byte-exact `SCRIPT_HTML_CSP`;
- body содержит только внешние same-origin script elements;
- отсутствуют inline `<script>...</script>`, inline event attributes, `javascript:` URL, third-party script source и eval contract;
- существующие `Cache-Control: no-store`, security headers, body и authorization outcome не меняются.

### A1b — successful scripted login POST

`POST /pilot/login`, который при invalid credentials или промежуточном шаге
возвращает existing `200 text/html` с `/pilot/assets/preloader.js`, получает
byte-exact `SCRIPT_HTML_CSP`. Status/body/login error/session behavior не
меняются; redirect responses остаются на `BASE_CSP`. PRG conversion вне scope.

### A2 — equivalent HEAD

`HEAD` к allowlisted route получает CSP и declared Content-Length соответствующего GET representation, но empty body. HEAD не выполняет mutation.

### A3 — script-free successful activation HTML

Успешный `GET /pilot/activate?token=<valid>` получает `BASE_CSP`; body не содержит script element или inline event handler. Activation POST semantics вне scope.

### A4 — errors on script-enabled route

`401`, `403`, `404`, `409` и `503` от allowlisted route получают `BASE_CSP`, а не script/checklist policy. Status, exact redacted body, `Retry-After`/correlation header при их обычном наличии и `Cache-Control: no-store` сохраняются.

### A5 — redirects

`GET /pilot` (`308 /pilot/`) и unauthenticated redirect к `/pilot/login` получают `BASE_CSP` без `script-src`; `Location`, status, empty/existing body и no-store сохраняются.

### A6 — assets

CSS, JS, SVG, font, PDF и другие non-HTML assets не получают `script-src`, независимо от успешного или ошибочного status. Их bytes, Content-Type, Content-Length, cache policy и asset headers сохраняются. Исключение только для exact Service Worker asset policy, которая также не содержит `script-src`.

### A7 — exact route matching

`/pilot/objects/0`, `/pilot/objects/4512/unknown`, unknown OTIZ paths и любые частичные совпадения не входят в allowlist. Их обычный rejected response получает `BASE_CSP`.

### A8 — checklist extensions

Успешные `GET /pilot/objects/4512/checklist` и `GET /pilot/construction-control/objects/4512/checklist` получают byte-exact `CHECKLIST_HTML_CSP`. Ни один другой successful HTML route не получает `worker-src`, `connect-src` или `blob:` из checklist policy.

### A9 — checklist errors

Любой rejected/error checklist response получает `BASE_CSP` без `script-src`, `worker-src`, `connect-src` и `blob:`.

### A10 — CompletionFlow inline removal

Для незавершённого checklist итоговый HTML содержит `data-progress-cap="85"` и внешний checklist asset, но ноль inline script blocks/handlers. После выполнения внешнего behavior отображаемый total progress не превышает `85`. Для завершённого объекта cap и отображаемый итог равны `100`. Сохранённые completion facts, веса, state transitions и command responses byte-for-byte не меняются этим slice.

### A11 — authorization and history neutrality

Actor без permission получает существующий `401/403` с `BASE_CSP`; CSP selection не создаёт DB writes или audit/domain facts. Два одинаковых safe read responses имеют byte-identical CSP и не требуют locking/idempotency key.

### A12 — known regression closure

Gate 2 RED должен отдельно показать как минимум:

1. текущий responder ошибочно выдаёт `script-src 'self'` error/asset/script-free response;
2. allowlisted successful scripted HTML нуждается в `SCRIPT_HTML_CSP`;
3. successful scripted `POST /pilot/login` получает ту же узкую policy, а
   redirect/error login outcomes остаются strict;
4. checklist body содержит блокируемый inline CompletionFlow script либо не сохраняет cap после его удаления.

Известные regression entrypoints: `pilot_demo_bootstrap_001_test.php`, `pilot_http_auth_001_test.php`, `pilot_shlz_assets_001_test.php`. Их ожидания нельзя механически ослабить: они должны различать allowlisted success и strict non-success/non-HTML responses.

## Rejected cases and exact reasons

- Не allowlisted route: `BASE_CSP`; причина — external JavaScript need не утверждён.
- Не `2xx` result: `BASE_CSP`; причина — error/redirect body не должен выполнять script.
- Не HTML media type: CSP без `script-src`; причина — asset не является script-executing document.
- Inline/eval/third-party script request: запрещено политикой; исключение в рамках этого slice не предусмотрено.
- Inline CompletionFlow fragment: должен быть удалён/перенесён, а не разрешён nonce/hash/`unsafe-inline`.

## Authorization, audit, idempotency and concurrency

- Существующая route authorization выполняется независимо и раньше выдачи расширенной success policy.
- CSP не является capability и не расширяет доступ.
- Никаких новых audit/domain facts или persistence writes.
- Safe reads детерминированы для одинакового representation; idempotency key и locking не нужны.
- Любая ошибка policy classification fail-closed возвращает `BASE_CSP`, а не более широкую policy.

## Explicit non-goals

RBAC/login/session changes, route permission matrix, business mutations, completion semantics, Service Worker cache lifecycle, CSP reporting endpoint, third-party scripts, nonce/hash infrastructure и новый UI behavior.

## Gate record

- Owner cross-cutting decision: `GRILL-002 / APPROVED_ALL`.
- Эта конкретная редакция: **DRAFT**, ещё не owner-approved.
- Gate 2 tests и production implementation не разрешены до отдельного approval этой reviewed версии.
