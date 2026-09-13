## Why

После переноса действующих пользовательских маршрутов production front controller
`public/runtime.php` всё ещё загружает `rapid-pilot/router.php` для большинства
запросов. Этот второй HTTP runtime сохраняет старую auth/session composition и не
позволяет завершить этапы 3 и 7 задачи #76, хотя целевые Yii2 controllers уже есть.

## What Changes

- Перевести production seam `public/runtime.php` на единственный Yii2 web runtime
  для health, пользовательских маршрутов, assets, ошибок и неизвестных URL.
- Сохранить действующие public URL, методы, trusted-host validation, безопасные
  ошибки, CSP/cache/HEAD, session namespace и все application/domain owners.
- Удалить production reachability `rapid-pilot/router.php`, `LocalAuth.php` и
  старой session composition; rapid-pilot остаётся только тестовым oracle.
- Зафиксировать полный route/assets inventory и проверку соседних golden flows на
  реальном production front controller.
- Не переключать рабочий стенд, не менять БД, cookies/payload policy, product
  semantics, фоновые процессы или данные; deployment и общий rollback rehearsal
  остаются отдельными шагами #76.

## Capabilities

### New Capabilities

- `runtime/yii2-production-web-cutover`: production HTTP front controller
  обслуживает весь действующий web-контур через одну Yii2 composition без
  production-загрузки rapid-pilot runtime.

### Modified Capabilities

Нет.

## Impact

Затрагиваются `public/runtime.php`, Yii request/bootstrap configuration, route и
asset inventory, black-box HTTP/browser tests, architecture ratchets, production
runbook и delivery evidence. Schema, state-changing application seams и
развёрнутый стенд не меняются.
