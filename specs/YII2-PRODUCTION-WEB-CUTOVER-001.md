# YII2-PRODUCTION-WEB-CUTOVER-001 — единый production HTTP runtime

## Простыми словами

Все уже перенесённые web-маршруты FMonitor обслуживаются одним Yii2 runtime и
одной auth/session composition. Production front controller больше не включает
старый rapid-pilot router, но URL, права, данные, документы и история остаются
прежними. Этот срез не переключает рабочий стенд и не удаляет тестовые oracle.

## 1. Идентификатор, actors и seam

- Specification identifier: `YII2-PRODUCTION-WEB-CUTOVER-001`.
- Actors: пользователь FMonitor, health client и оператор production image.
- Public seam: реальные HTTP requests через `public/runtime.php` (в image —
  nginx → PHP-FPM → тот же front controller).
- Source oracles: принятые `YII2-*` route contracts и исторический
  `rapid-pilot/router.php` только для inventory/parity.
- Observable results: status, headers, body, cookies, persisted facts/files и
  набор PHP-файлов, загруженных request lifecycle.

## 2. Preconditions и неизменяемые данные

Для пользовательских routes заданы valid `RuntimeConfiguration`, trusted Host,
prepared private paths, compatible canonical schema и DML runtime principal.
`/health/live` остаётся доступен без DB; readiness использует существующий
read-only `RuntimeReadiness`.

Срез не меняет schema, cookie name/payload policy, users/roles/permissions,
domain facts, PDF/фото, jobs/outbox или application owners. Все state-changing
routes сохраняют прежнюю авторизацию, транзакцию, idempotency/concurrency и audit.

## 3. Единая composition и route inventory

`public/runtime.php` MUST запускать один Yii2 web application для каждого
accepted request. Ни один production request MUST NOT include/require файл под
`rapid-pilot/`, создавать `RapidPilotLocalAuth` либо запускать legacy session
composition. `rapid-pilot` MAY использоваться отдельными тестами как oracle.

Production inventory MUST включать:

- `/health/live`, `/health/ready`, `/`, login/logout/activation;
- object queue/card, installers, construction-control queue, checklist/photo/
  offline context, completion, inspection schedule;
- assignment selection/template/original upload/history/download/opening и
  принятые gone compatibility URLs;
- roles/users/invite/reissue/role/status;
- OTIZ register, publication, acceptance, export, payments/reverse и evidence;
- все CSS/JS/service-worker/font/favicon assets, на которые ссылаются принятые
  Yii2 pages.

Полный машинно-читаемый перечень method/path и независимых guest outcomes
находится в `tests/Support/yii2_production_web_cutover_contract.php`. Источники
ожиданий — принятые route-specific контракты `YII2-AUTH-001`,
`YII2-USER-ACCESS-001`, `YII2-PREOPENING-JOURNEY-001`,
`YII2-INSPECTION-JOURNEY-001`, `YII2-DOCUMENTARY-CLOSURE-001` и
`YII2-OTIZ-WORKFLOW-001`; status не вычисляется общей формулой. Каждый запрос
MUST записывать в include frontier уникальные case id, method и path.

`GET /` MUST вернуть `302` с `Location: /pilot/objects` и `Cache-Control:
no-store`. Unknown route MUST вернуть safe `404`; unsupported method MUST вернуть
safe `405` с exact `Allow` действующего route. HEAD сохраняет GET status и
headers, возвращает пустой body и корректный `Content-Length`.

## 4. Authentication, authorization и session

Новый Yii session namespace и ранее принятое обязательное повторное открытие
сессии сохраняются без bridge к `fm2auth`. Legacy cookie один не аутентифицирует.
Active Yii cookie переживает process restart через configured persistent path.

Guest redirect, CSRF, permission denial и canonical revoke MUST совпадать с
принятыми route contracts. Runtime/controller не получает права подменять
application-level authorization state-changing commands.

## 5. Host, errors и response security

До authentication, session и DB/domain access user routes MUST сверить exact
trusted Host. Missing, malformed или mismatched Host даёт safe `400`, не создаёт
cookie/fact и не отражает rejected value в application body/headers. Тестовая
echo-линия `Host`, которую PHP development SAPI пишет до router, исключается из
application disclosure oracle. Health contract сохраняет принятый
операционный порядок проверок.

Startup/config/DB/storage/session failure MUST давать generic unavailable без
credential, path, native message или stack trace. Partial success и success
redirect до durable session close запрещены.

Dynamic/error responses сохраняют `Cache-Control: no-store`,
`X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`, CSP,
`Permissions-Policy` и COOP по действующим Yii contracts. Static assets
сохраняют media type, nosniff, same-origin и accepted cache/service-worker scope.
Exact bytes каждого accepted asset фиксируются независимым SHA-256 manifest в
`tests/Support/yii2_production_web_cutover_contract.php`, а не читаются из
production asset во время проверки.

## 6. History, idempotency, concurrency и rollback

Read/health/asset/unknown requests MUST быть идемпотентны и не создавать
database/files/session/audit facts (кроме session только там, где её требует
auth flow). Повторные и конкурентные commands наследуют accepted atomic owners;
front controller не добавляет собственную транзакцию или retry.

Поскольку schema/data не меняются, rollback среза — запуск предыдущего image.
Он MUST NOT требовать конвертации или удаления БД, sessions, PDF/фото или jobs.
Deployment рабочего стенда и общий backup/restore rehearsal не входят в PR.

## 7. Независимые примеры

| Request через `runtime.php` | Preconditions | Expected |
|---|---|---|
| `GET /health/live` | DB отсутствует | `200`, exact safe JSON, no cookie/write |
| `GET /` | valid runtime config | `302 /pilot/objects`, no legacy include |
| `GET /pilot/login` | valid schema/storage | `200` Yii form, no rapid-pilot include |
| `GET /pilot/objects` | guest | `303 /pilot/login` по принятому route contract |
| `GET /pilot/assets/pilot.css` | asset present | exact bytes/media/security headers |
| `GET /pilot/does-not-exist` | valid config | safe `404`, no legacy include/write |
| `GET /pilot/login` | mismatched Host | safe `400` before session/DB, no disclosure |
| protected golden flows | authorized fixtures | прежние results/facts/history через Yii owners |

Expected values взяты из уже принятых Yii2/public runtime contracts, а не из
новой реализации front controller.

Durable session close, authenticated cookie после process restart и unavailable
session/DB без partial redirect/cookie проверяет принятый
`tests/Yii2/yii2_authentication_001_test.php`. Private storage и artifact/
readiness fault contracts дополнительно сохраняют
`tests/Runtime/runtime_storage_001_test.php` и
`tests/Runtime/production_process_readiness_001_test.php`; эти проверки явно
привязаны в `verification-input.json` и не подменяются агрегатной ссылкой.

## 8. Done

Gate 2 public test и inventory MUST сначала падать только из-за legacy fallback.
После Gate 3 implementation проходит public/include-boundary test, focused
auth/FKR/inspection/user/OTIZ regressions, architecture-check и один full
exact-source CI; Gate 5 независим. Merge PR завершает только этот bounded slice.
