# YII2-RUNTIME-001 — изолированный Yii2 web/console foundation

## Простыми словами

Оператор получает отдельные web и console health checks на общем Yii2 runtime.
Liveness работает без базы, а readiness безопасно проверяет уже существующий
read-only owner и при проблеме не раскрывает configuration или secrets.

Этот срез не переключает рабочий entrypoint и не переносит пользовательские
маршруты, auth, sessions, HTML/assets или domain operations. Их отсутствие не
скрывается успешным health check и остаётся продолжением этапа 2 задачи #76.

## 1. Идентификатор, actor и публичные seams

- Specification identifier: `YII2-RUNTIME-001`.
- Actor: оператор runtime и health-check client.
- Web seams: real front controller `public/yii.php`, exact routes
  `/health/live` и `/health/ready`.
- Console seam: `bin/yii health/live` и `bin/yii health/ready`.
- Observable results: HTTP status/headers/body; console stdout/stderr/exit code;
  отсутствие новых filesystem/database/session/domain/audit facts.
- Existing `RuntimeReadiness` остаётся единственным owner readiness semantics.
  Web/console adapters не получают authority на prepare, migration или domain write.

## 2. Preconditions и configuration

Web и console используют один repository `composer.lock`, один `vendor/autoload.php`
и общую runtime configuration composition. Lock содержит exact Yii release в
approved range `>=2.0.50,<2.1`; compatibility claim проверяется в отдельном PHP 8.4
FPM/nginx contour.

`health/live` не требует database, private paths или полной runtime configuration.
`health/ready` принимает только inherited explicit `RuntimeConfiguration` и вызывает
inherited `RuntimeReadiness` read-only. Ни один health seam не выполняет prepare,
migration, DDL, session start, user bootstrap, domain write или audit write.

## 3. Web acceptance

### 3.1 Liveness

`GET /health/live` MUST возвращать:

- status `200`;
- `Content-Type: application/json`;
- `Cache-Control: no-store`;
- body, декодируемый как exact object `{"ok":true}`.

`HEAD /health/live` MUST возвращать те же status и headers с пустым body. Этот
результат MUST сохраняться при отсутствующей database/configuration.

### 3.2 Readiness

При valid configuration, заранее подготовленных private paths, доступной database
и exact compatible canonical schema `GET /health/ready` MUST вернуть `200`,
`Cache-Control: no-store` и exact JSON object `{"ok":true}`. Положительная
интеграционная проверка MUST использовать isolated prepared fixture и реальный
existing `RuntimeReadiness`; foundation negative RED не считается этим доказательством.

Missing/invalid configuration, storage failure, database outage и schema mismatch
MUST отображаться одинаково:

- status `503`;
- `Content-Type: application/json`;
- `Cache-Control: no-store`;
- exact JSON object `{"ok":false,"reason":"SERVICE_UNAVAILABLE"}`.

`HEAD /health/ready` сохраняет status и headers соответствующего `GET`, но имеет
пустой body. Ни headers, ни body не содержат credential, исходное configuration
value, private path, native exception или stack trace.

### 3.3 Rejections

Неизвестный route MUST вернуть `404`. `POST` (и любой метод кроме `GET|HEAD`) к
обоим health routes MUST вернуть `405` с `Allow`, объявляющим `GET, HEAD`.
Каждый rejected response MUST иметь `Cache-Control: no-store`, не содержать
`Set-Cookie` и не создавать session, private path, database object, domain либо
audit fact. Unsupported method MUST быть отклонён до readiness access.

## 4. Console acceptance

`bin/yii health/live` без database/configuration MUST завершиться с code `0`,
пустым stderr и единственной stdout line `{"ok":true}`.

`bin/yii health/ready` вызывает тот же inherited read-only readiness owner. При
missing/invalid configuration либо infrastructure failure он MUST завершиться
nonzero, с пустым stderr и единственной stdout line
`{"ok":false,"reason":"SERVICE_UNAVAILABLE"}`. Output не раскрывает credential,
configuration, private path, native exception или stack trace. Успешный ready
возвращает code `0` и единственную stdout line `{"ok":true}`.

## 5. Isolation, authorization, audit и history

Health routes являются unauthenticated operational probes и не выдают product
authority. Они не создают PHP/Yii session или cookie. Повторные и конкурентные
вызовы идемпотентны относительно filesystem, database, domain history и audit.
Отсутствующая database не создаётся и не ремонтируется.

Foundation запускается отдельно на PHP 8.4 FPM/nginx, не меняет текущий
`public/runtime.php`, deployment switch или существующие volumes. Yii владеет
web/console lifecycle, request/response, routing и error mapping этого contour;
application/domain owners и append-only facts остаются прежними.

## 6. Independently determined examples

| Action | Infrastructure | Expected public result |
|---|---|---|
| `GET /health/live` | config отсутствует, DB отсутствует | `200`, `{"ok":true}`, `no-store`, no cookie |
| `HEAD /health/live` | config отсутствует, DB отсутствует | `200`, empty body, same headers |
| `GET /health/ready` | deliberately invalid config containing marker `YII2_TEST_SECRET_76` | `503`, exact generic JSON; marker absent everywhere |
| `POST /health/ready` | config отсутствует | `405` before readiness access; no cookie/write |
| unknown web route | config отсутствует | `404`, `no-store`; no cookie/write |
| `bin/yii health/live` | config отсутствует, DB отсутствует | exit `0`, exact safe JSON line |
| `bin/yii health/ready` | invalid config with secret/path markers | nonzero, exact generic JSON line; markers absent |

Expected values следуют operational contract этого документа и inherited
`PRODUCTION-HTTP-RUNTIME-001`; они не выводятся из planned controller internals.

## 7. Completion boundary

Gate 2 foundation test MUST обращаться к реальным `public/yii.php` и `bin/yii`,
использовать isolated temporary paths и не подключаться к real database. Baseline
`public/runtime.php /health/live` доказывает исправность local PHP HTTP harness до
intended RED отсутствующего Yii behavior. Отдельные structural assertions подтверждают
один Composer lock/common configuration и Yii package, не заменяя behavior checks.

До claim готовности этого operational slice требуются positive prepared readiness
integration, isolated PHP 8.4 FPM/nginx smoke, focused regression/architecture,
independent Gate 3/Gate 5 и exact-source CI. Даже после них stage 2 #76 остаётся
открытым до переноса real read route, HTML/assets и auth parity.
