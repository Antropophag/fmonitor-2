# Извлечение HTTP-владельца user access/session

Дата: 2026-09-07. Реализация выполнена агентом `/root/auth_review` в рамках
OpenSpec change `restore-pilot-http-architecture-ownership`, задачи 3.1–3.3.
Независимый code review остаётся следующим обязательным шагом.

## Результат

Из `PilotE2ECoordinator` извлечена полная ответственность owner-backed session и
административных маршрутов пользователей. Coordinator теперь только делегирует:

- `PilotUserAccessHttpHandler` распознаёт owner-session и user-admin routes,
  восстанавливает authenticated owner session, управляет login и публикует
  итоговое session state не более одного раза;
- `PilotUserAdminHttpHandler` выполняет прежнее HTTP-сопоставление directory,
  invite и role commands с серверной проверкой `access.administer`;
- `PilotUserAdminSession` владеет command/owner session различием, CSRF token
  generation/consumption, Origin/Sec-Fetch-Site и form-body validation;
- `PilotUserAccessResponse` сохраняет единый status/header/CSP/HEAD envelope.

Приватные методы coordinator не превращались в публичные callbacks. Request не
передаёт actor или capability: owner handler строит trusted request только из
принятого session payload, а admin handler повторно читает активный локальный
профиль и exact capability. Invite/role mutation по-прежнему принадлежит
существующему `MariaDbPilotUserDirectory`; нового state-changing application seam
не создано.

Исходный порядок сохранён: только login и owner-user routes с доступным session
owner делегируются до configuration guards. `/pilot/admin/roles` и fallback без
session owner проходят прежние guards и соседние route checks до user-admin
handler. `/pilot/users` redirect, checklist/sync routes и остальные handlers не
перехватываются.

`PilotE2ECoordinator.php` уменьшен с 308 до 249 строк. Новые файлы имеют 40, 84,
100 и 29 строк; новых hotspot нет. `tools/architecture/baseline.json` не изменялся.

## Exact source evidence

Implementation starting point:

```text
6a919d90d20c272d9b7d4413489593498c2f2c48
```

Working-file SHA-256:

```text
3e3d7e47cf4fdbdbe22a3893010f79acf4d2293f12a4c14b2962a9c48a0fafd8  app/PilotHttp/PilotE2ECoordinator.php
2a612083b91dca94b5274100e526ab530a73641cf76f4b5bb2d25a8c6100a2c0  app/PilotHttp/PilotUserAccessHttpHandler.php
b6ba040b491c2c252cd7076464fd54e8bec5ed0100ddc7c14e14a80960acd551  app/PilotHttp/PilotUserAdminHttpHandler.php
bd226836ce1b476cd252195ee9b44ca21189ab23f020e448e2a2a78a6703c9e3  app/PilotHttp/PilotUserAdminSession.php
50e90f488e72ddfe26e333647ed1635d8832048394db68548c4000ce102d6e64  app/PilotHttp/PilotUserAccessResponse.php
```

Diff SHA-256 (coordinator against starting point, then each new file as
`git diff --binary --no-index /dev/null <file>`):

```text
abca5825f7e20bfd6b0fe78f516a9b94d59287de5ac769b84bfe1d94d7f1ceff  PilotE2ECoordinator.php.diff
54b32843f9012064471042d74b7a3591d44f569115e5dee98ccdbeaa5b776791  PilotUserAccessHttpHandler.php.diff
c49d525dfb734694724840dab7a124e827a1f63b9747d8af6b967c2106431098  PilotUserAdminHttpHandler.php.diff
2e61760ac99857bf1c75e9951e9a55c0fef46be47afef54f61612b4fbdce4565  PilotUserAdminSession.php.diff
e1cbeed40cbca942fd76bea5d335a0c97ee26fd371d62487b3a197eacc7d40fc  PilotUserAccessResponse.php.diff
```

## Focused verification

```text
pilot_session_storage_user_access_fault_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 UserAccess flash owner handoff

pilot_session_storage_accepted_payload_http_001_test.php
PASS: PILOT-SESSION-STORAGE-001 v10 accepted payload raw HTTP

pilot_local_trusted_scheme_001_test.php
SETUP_OK compose / PASS compose / CLEANUP_OK compose
SETUP_OK missing / PASS missing / CLEANUP_OK missing

pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary

local_rbac_objects_route_admission_001_test.php
PASS: LOCAL-RBAC-AUTH-CONTRACT-001 real GET /pilot/objects admission

pilot_shlz_assets_001_test.php
PASS: PILOT-SHLZ-ASSETS-001 public CSS manifest

tools/architecture/check
ARCHITECTURE CHECK PASSED (7 rules)

PHP lint всех пяти изменённых/new PHP-файлов
PASS

git diff --check для owned PHP-файлов
PASS
```

Дополнительный global-call aggregate сейчас падает только на параллельно добавленном
`MariaDbObjectQueue.php`: 18 unqualified calls. В пяти session-owned файлах finding
нет. Этот внешний интеграционный остаток передан root и не скрыт как GREEN.

Тесты использовали disposable fixtures. Global reset, schema/data migration,
stand/restart, production data, remote/CI и Bitrix actions не выполнялись.
