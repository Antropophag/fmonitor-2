# LOCAL-RBAC-AUTH-CONTRACT-001 — единая local RBAC авторизация pilot routes

- Статус: `DRAFT — ожидает независимый Gate 1 review и отдельное утверждение владельца`
- Версия: `0.1`
- Дата: `2026-09-02`
- Актор: пользователь, уже аутентифицированный local authentication contour
- Публичный seam: `authorizeLocalActor(authenticatedLocalUserId, requiredPermission)`
- Основание решения: `docs/operations/security-artifact-contract-owner-decision.md`, `GRILL-002 / APPROVED_ALL`

## Простыми словами

Вход в систему сам по себе больше не даёт доступа к рабочим страницам и действиям. Для каждого маршрута система заново проверяет: локальный пользователь включён, активация завершена, ему назначена включённая роль и у этой роли есть ровно нужное право.

Старые пользователи, старые роли, совпавшее имя или похожее право не могут «подстраховать» отсутствующее локальное разрешение. Этот slice задаёт общий контракт проверки; он не меняет login/session, набор прав маршрутов, экраны управления доступом, CSP или PDF.

## 1. Цель и граница

Slice создаёт один application-owned read-only authorization seam и переводит
на него первый vertical route `GET /pilot/objects` с exact permission
`objects.read`. Остальные local-authenticated routes мигрируют отдельными
следующими slices, но MUST переиспользовать этот seam и не вводить альтернативную
authority/fallback model. HTTP route владеет только статической картой
`route → requiredPermission`, вызывает seam до защищённого handler-а и
отображает его результат в existing generic HTTP response.

Scope включает:

- authoritative local user identity по positive integer ID из уже проверенной local session;
- обязательные `user.status=1` и `activation_state=active`;
- обязательное назначение хотя бы одной local role с `role.status=1`;
- byte-exact permission на любой из активных назначенных ролей;
- отсутствие legacy/name/authenticated-only fallback;
- fail-closed ошибки и повторную проверку актуального committed snapshot.

Вне scope: проверка пароля, создание/ротация session, invitation flow,
административные команды изменения RBAC, migration/schema ownership, изменение
route-permission matrix, другие routes и их fixtures, CSP и document artifacts.

## 2. Preconditions и input

Input seam:

```text
authenticatedLocalUserId: positive integer | absent/invalid
requiredPermission: application-owned nonblank exact permission literal
```

`authenticatedLocalUserId` поступает только от утверждённого local authentication/session boundary. Request body, query, header, cookie field вне этой boundary, `REMOTE_USER`, email, display name, role name/code и legacy ID не являются input seam.

`requiredPermission` выбирается статической per-route mapping до чтения user-controlled payload. Клиент не может передать, заменить, расширить или нормализовать это значение.

Blank, malformed или unknown application-owned literal является programming/
configuration fault и возвращает `AUTHORIZATION_UNAVAILABLE` с internal safe
category `AUTHORIZATION_CONFIGURATION_INVALID`; он никогда не становится `403`
и не запускает handler.

Canonical authorization facts находятся только в local identity/RBAC namespace:

- local user;
- состояние активации local user;
- назначения local user roles;
- активность local roles;
- exact permissions local roles.

Legacy directory/roles/rights разрешено использовать только как import evidence вне этого seam. Они не читаются как fallback authority.

## 3. Command/action и результаты

Вызов:

```text
authorizeLocalActor(authenticatedLocalUserId, requiredPermission)
```

возвращает ровно один результат:

| Result | Условие | HTTP mapping adapter-а |
|---|---|---|
| `AUTHORIZED(actorUserId)` | exact user активен и активирован; существует назначенная активная роль с exact permission | продолжить только текущий handler |
| `AUTHENTICATION_REQUIRED` | нет валидного positive local user ID от authentication boundary | generic `401` |
| `ACCESS_DENIED` | actor известен, но любое обязательное RBAC-звено отсутствует/неактивно | generic `403` |
| `AUTHORIZATION_UNAVAILABLE(category, correlationId)` | canonical RBAC read/schema недоступны, несовместимы, identity result неоднозначен либо trusted permission mapping невалиден | generic `503` |

Никакой отказ не запускает защищённый handler, business persistence или read model query. HTTP body не раскрывает, отсутствовал ли пользователь, активация, роль или permission.

Для `AUTHORIZATION_UNAVAILABLE` наружу возвращается generic `503` с opaque
12-hex correlation ID. Internal log содержит correlation ID и одну stable safe
category (`AUTHORIZATION_CONFIGURATION_INVALID`, `AUTHORIZATION_SCHEMA_INVALID`
или `AUTHORIZATION_READ_FAILED`), но не user/role/permission values, SQL,
credentials или schema identifiers.

## 4. Exact authorization contract

Для `AUTHORIZED` одновременно обязательны все утверждения:

1. ID от local authentication boundary — положительное целое.
2. Существует ровно одна canonical local user identity с этим ID.
3. `user.status = 1`.
4. `user.activation_state = 'active'` byte-exact.
5. Пользователю назначена как минимум одна local role.
6. Хотя бы одна назначенная роль имеет `role.status = 1`.
7. Та же активная назначенная роль имеет permission, byte-exact равный `requiredPermission`.

Permission другой активной роли того же пользователя также допустим: effective grant является объединением exact permissions всех его активных назначенных ролей. Неактивная роль ничего не добавляет.

Прямые user exceptions, implicit authenticated-user grant, wildcard/prefix/case-folded permission, role hierarchy и наследование между permissions в этот slice не входят и запрещены.

## 5. Rejected cases и exact reason

| Case | Result / reason |
|---|---|
| missing, zero, negative, non-integer session actor ID | `AUTHENTICATION_REQUIRED` |
| local user отсутствует, неактивен или не `active` по activation | `ACCESS_DENIED` |
| user-role assignment отсутствует | `ACCESS_DENIED` |
| назначена только неактивная роль | `ACCESS_DENIED` |
| exact permission отсутствует | `ACCESS_DENIED` |
| есть только case/space/prefix/wildcard near-match permission | `ACCESS_DENIED` |
| permission есть только у неактивной либо неназначенной роли | `ACCESS_DENIED` |
| есть legacy user/role/right, совпавшее имя/email либо лишь успешная authentication | `ACCESS_DENIED`, fallback запрещён |
| blank/malformed/unknown trusted required permission | `AUTHORIZATION_UNAVAILABLE(AUTHORIZATION_CONFIGURATION_INVALID, …)` |
| local RBAC schema missing/incompatible или неоднозначная canonical identity | `AUTHORIZATION_UNAVAILABLE(AUTHORIZATION_SCHEMA_INVALID, …)` |
| local RBAC DB read error | `AUTHORIZATION_UNAVAILABLE(AUTHORIZATION_READ_FAILED, …)` |

## 6. Route capability ownership

Slice сохраняет current mapping `GET /pilot/objects → objects.read` и не
утверждает новые permission literals. Следующие route slices берут expected
permission из своих утверждённых route specs/mappings. Worked example ниже
сравнивает существующие literals, но в GREEN этого slice подключается только
`GET /pilot/objects`:

- list/card read route требует `objects.read`;
- prepare command route требует `assignment_order.prepare`.

Grant одного permission не разрешает другой route. Route adapter не может вызывать authorization с более широким или альтернативным permission ради совместимости.

## 7. Audit, history, idempotency и concurrency

Authorization seam read-only. Он не изменяет local users, activation, roles, assignments, permissions, credentials, session version, login metadata, security events или domain facts. Поэтому отдельный audit fact успешной/неуспешной проверки в этом slice не создаётся; аудит административного grant/revoke принадлежит отдельным state-changing seams.

Два вызова на одном неизменившемся committed snapshot дают одинаковый результат. Каждый новый HTTP invocation выполняет новую проверку current committed snapshot. Committed revoke permission, role/user deactivation либо смена activation с `active` закрывает следующий invocation. Ранее выданный `AUTHORIZED` действует только на текущий handler invocation и не является долгоживущим grant.

Если RBAC меняется конкурентно во время одной проверки, seam обязан получить один согласованный DB statement/transaction snapshot либо fail closed как `AUTHORIZATION_UNAVAILABLE`; смешивать звенья из разных committed состояний нельзя.

## 8. Independently determined examples

### A. Exact read grant

Facts:

```text
user 7301: status=1, activation_state=active
role 701: status=1
assignment: user 7301 → role 701
role 701 permission: objects.read
requiredPermission: objects.read
```

Expected: `AUTHORIZED(7301)`.

### B. Cross-route denial

Те же facts, но `requiredPermission=assignment_order.prepare`.

Expected: `ACCESS_DENIED`. Наличие `objects.read` не подразумевает prepare.

### C. Inactive-role denial despite exact row

Facts как в A, но `role 701: status=0`; строка `objects.read` сохраняется.

Expected: `ACCESS_DENIED`; permission неактивной роли неэффективен.

### D. Legacy/name fallback denial

Local user `7301` активен и активирован, но не имеет назначенной local role. Legacy user с тем же email/display name имеет legacy administrator role/right.

Expected: `ACCESS_DENIED`; legacy row не читается как authority и handler не вызывается.

### E. Revocation on next invocation

Example A сначала возвращает `AUTHORIZED(7301)`. Затем строка `role 701 → objects.read` удалена committed административной командой. Начинается новый invocation с тем же actor ID.

Expected: `ACCESS_DENIED`; первый успех не кэшируется как grant.

### F. Union of two active assigned roles

User `7301` active/activated. Assigned active role `701` имеет только
`assignment_order.prepare`; assigned active role `702` имеет exact
`objects.read`. Required permission — `objects.read`.

Expected: `AUTHORIZED(7301)`. Если role `702` становится inactive, expected
`ACCESS_DENIED`: permission первой роли не подходит, а inactive role ничего не
добавляет.

### G. Safe unavailable diagnostics

Route mapping передаёт blank permission либо canonical RBAC read падает.

Expected: handler не вызывается; external response generic `503` с opaque
12-hex correlation ID; internal event содержит тот же ID и только соответствующую
safe category без RBAC facts/SQL/configuration disclosure.

## 9. Gate 1 acceptance

Gate 1 может быть передан на независимый review, когда reviewer подтверждает, что:

- seam и четыре результата наблюдаемы без private methods или DB side-channel assertions;
- все обязательные звенья local grant и запреты fallback выражены в acceptance examples;
- route mapping остаётся route-owned и клиент не выбирает permission;
- отказ предшествует handler/read/business persistence;
- read-only/history и current-snapshot concurrency contract тестируемы;
- этот draft не считается owner-approved только на основании общего решения `GRILL-002`.

После независимого review владелец отдельно утверждает эту версию Gate 1. Только затем разрешён Gate 2 RED.

## 10. Evidence и зависимости

- `PRODUCT.md`, `CONTEXT.md`;
- `docs/fmonitor-2-pilot-spec.md`;
- `docs/fmonitor-2-pilot-data-model.md`;
- `docs/development-process.md`;
- `docs/operations/security-artifact-contract-owner-decision.md`;
- `specs/PILOT-HTTP-AUTH-001.md` как исторический upstream/legacy HTTP auth contract, не fallback authority для этого slice;
- current oracle: `rapid-pilot/LocalAuth.php`, `app/PilotHttp/AccessPolicy.php` и route-specific HTTP specs/tests.
