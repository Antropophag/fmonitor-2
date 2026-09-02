# PILOT-OBJECT-READ-RBAC-FIXTURES-001 — canonical local-RBAC object-list fixture

Статус: **DRAFT / Gate 1**  
Версия: **v1**  
Дата: **2026-09-02**

## Простыми словами

Тест списка объектов должен входить так же, как будущий тестовый пользователь:
по local user ID, active role и exact `objects.read`. Старое совпадение email,
legacy role или один факт authentication больше не делает положительный fixture.
Карточка, prepare и общий UI shell этим срезом не мигрируют.

## 1. Actor, seam и scope

Actor fixture `18` — fictional local user:

```text
user_id=18
full_name=Сотрудник ФКР (тест)
email=fkr.object-list@example.invalid
status=1
activation_state=active
role_id=5101, role.status=1
permission=objects.read
```

Public seam — exact `GET /pilot/objects` through production HTTP entrypoint.
Target authority — approved `authorizeLocalActor(18, 'objects.read')`.

Out of scope: HEAD, card `/pilot/objects/{id}`, prepare, UI-shell root,
login/session, other permissions, production role administration and domain
behavior. Existing `PILOT-OBJECT-LIST-001`, HTTP/CSP and object data contracts
are inherited unchanged after successful admission.

## 2. Canonical fixture manifest

Fixture SHALL create canonical landed identity/access schema through approved
migration/independent manifest, not reduced ad-hoc tables. It SHALL insert only
the exact local user, active role, user-role assignment and permission above.
Legacy user/role rows MAY exist as decoys but MUST NOT grant authority.

Every request process SHALL receive an explicit environment map. Positive case
sets `FMONITOR_AUTH_USER_ID=18`. Negative cases explicitly unset or replace that
key; `REMOTE_USER`, cookies and previous process environment MUST NOT leak from
positive case. DB name/prefix, CSS path and mutable roots are task-owned and
removed in finally; foreign decoys remain byte-equivalent.

## 3. Exact admission outcomes

| Case | Expected result before list handler |
|---|---|
| key absent/empty or string `0`, `-1`, `abc`, ` 18`, `18 ` | `401 Authentication required.\n` |
| trusted positive ID `9999` with no local user | `403 Access denied.\n` |
| actor absent, status 0 or activation not `active` | `403 Access denied.\n` |
| no assignment, inactive role or missing exact permission | `403 Access denied.\n` |
| only `Objects.Read`, `objects.read `, `objects.*`, `objects.read.more` | `403 Access denied.\n` |
| legacy user/active legacy role via `REMOTE_USER`, trusted actor key absent | `401 Authentication required.\n` |
| canonical RBAC schema/read unavailable | exact safe 503 contract below |
| exact manifest | handler executes and returns inherited list result |

All denials SHALL happen before object-list persistence read/render. A
test-owned handler/read sentinel and full DB/filesystem snapshot MUST prove zero
handler read, domain/audit mutation, session/cookie creation and schema repair.
Authorization check itself creates no audit fact. Process environment supplies
strings only; non-string actor inputs are covered by the separately approved
application-seam test, not fabricated by raw HTTP fixture.

Unavailable response exact: status503, body `Service unavailable.\n`, UTF-8
plain Content-Type, Content-Length21, no-store, nosniff, no-referrer, DENY, CSP
`default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'`,
exactly one `Permissions-Policy: camera=(), microphone=(), geolocation=()` and
exactly one `Cross-Origin-Opener-Policy: same-origin`, plus exactly one
`X-Correlation-ID: [0-9a-f]{12}`; no Retry-After, Set-Cookie, Location,
WWW-Authenticate, CORS, Server/X-Powered-By or unspecified application headers.
Internal test logger receives once the same ID plus one exact safe category
`AUTHORIZATION_SCHEMA_INVALID` or `AUTHORIZATION_READ_FAILED`, without actor,
permission, SQL, table/prefix/config/credential/exception. 401/403 use inherited
exact bodies/security headers and no correlation header/log.

## 4. Positive representation and route isolation

Exact granted GET SHALL return current approved `PILOT-OBJECT-LIST-001`
representation for this exact fixture:

```text
4513 | 77-000124 | Москва, ул. Вторая, д. 7 | 1 | 2026-10-01 | 2026-11-30
4512 | 77-000123 | Москва, ул. Примерная, д. 10 | 2 | 2026-10-05 | 2026-12-18
4515 | 77-000126 | Москва, ул. Третья, д. 3 | 1 | 2026-10-05 | 2026-12-20
```

All three exact process cases are imported and `needs_assignment_order`; legacy
row4999 exists but is not imported and MUST be absent. DOM order exactly
4513,4512,4515; each has one href `/pilot/objects/{id}` and visible status
`Требуется распоряжение`. Response preserves status/body/security headers/
Content-Length, list landmarks, local assets and
no raw SQL/RBAC/config disclosure. Expected values come from that approved spec,
not current production output.

`GET /pilot/objects/unknown`, `/pilot/objects/0`, trailing slash or encoded/
extra segment SHALL retain their existing exact route results before list
handler; `objects.read` MUST NOT broaden route matching. Other routes and methods
are not approval targets of this fixture slice.

## 5. Repeat, revoke and cleanup

Two requests on unchanged committed facts SHALL return byte-equivalent
authorization/list results and create no rows/counters/files. In an isolated
branch, after one successful GET, fixture admin SHALL committed-delete exact
`role_id=5101, permission='objects.read'`; next GET SHALL return 403 before list
read. Main positive fixture remains unchanged; admin mutation audit is outside
scope, while authorization check writes no audit.

Finally SHALL stop/reap server, close DB resources, drop only exact task DB/user
and delete only verified task-owned roots. Cleanup executes after failure and
must not touch production/default/foreign objects.

Negative no-handler-read process DB user SHALL receive SELECT only on exact
canonical local user/role/assignment/permission tables, not object/process/
legacy tables; forbidden downstream read would turn expected denial into DB
failure. Before/after snapshot SHALL contain SHOW CREATE, ordered complete rows
and AUTO_INCREMENT for every task DB table plus exact CSS/file metadata. Cleanup
inventory names server PID/pipes, DB connection/user/database and mutable root;
each attempted once in finally, foreign decoy DB/file bytes+metadata compared.

## 6. Gate order and Done

Done requires this exact reviewed hash owner-approved, intended public RED,
independent test approval, minimal fixture GREEN without production fallback,
focused/full verification, architecture/lint/diff-check and independent code
approval. This DRAFT does not authorize Gate 2.
