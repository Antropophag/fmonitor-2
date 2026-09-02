# PILOT-E2E-RBAC-FIXTURES-001 — local object-list authority in golden E2E

Статус: **DRAFT / Gate 1**  
Версия: **v2**  
Дата: **2026-09-02**

## Простыми словами

Golden journey должен проходить список объектов как реальный local user, а не
из-за legacy email. Только уже migrated `GET /pilot/objects` меняется в этом
fixture slice. Prepare/register/open/download продолжают свои утверждённые
process/legacy contracts до отдельных route migrations.

## 1. Scope и actor matrix

Public target seam — exact `GET /pilot/objects` calls inside
`PILOT-E2E-FLOW-001`/demo bootstrap. Exact main fixture:

| Actor | Local identity/RBAC | Legacy/process facts | Expected list |
|---|---|---|---|
| 18, fictional FKR | active/activated, active assigned role 5301, exact `objects.read` | existing FKR descriptive user + prepare/register/open capabilities | 200 inherited list |
| 19, fictional reader | no local user/role/grant | active legacy reader only | 403 before list handler |

Actor ID propagation SHALL use existing test-only trusted production entrypoint
boundary: each isolated PHP server process receives exact process env
`FMONITOR_AUTH_USER_ID=18`, exposed as server input to local authorization;
request header/body/cookie cannot select actor. Nonmigrated routes retain exact
`REMOTE_USER=sidorov@shlz.ru` only for predecessor descriptive/auth contracts.
Actor19 process has REMOTE_USER and exact `FMONITOR_AUTH_USER_ID=19`, while no
local user/role row 19 exists; a separate missing-ID process explicitly unsets
the key and proves 401. Thus actor19
legacy email/role SHALL NOT create authority. Real LocalAuth/cookie login belongs
session-storage/E2E login work, not this fixture slice. No local permissions are added for
prepare, registration, opening, card, artifact or construction-control routes;
their approved predecessor authorization/capability semantics remain unchanged.

## 2. Main journey ordering

Before first artifact assertion main journey SHALL prove:

1. actor18 exact list GET 200 via local `objects.read` and expected object link;
2. actor19 exact list GET 403 with list handler/read sentinel untouched;
3. actor18 local role contains only `objects.read`;
4. prepare/register/open continue only because separate process capabilities and
   existing route contracts allow them, not because local object grant widens.

RBAC/setup failure SHALL stop and be classified before combined-PDF assertion;
an `ArtifactNotFoundException` after these proofs is downstream artifact evidence,
not RBAC RED.

Repeated HTTP equality SHALL compare status, body and every application header
byte-for-byte after removing only transport `Date`, `Connection` and local SAPI
`Host`; no other normalization is allowed.

## 3. Isolated revoke/repeat branch

Separate task DB/server/fixture SHALL clone exact actor18 local facts and one
valid list object. First GET returns inherited 200. Fixture admin then
committed-deletes exact `(role_id=5301, permission='objects.read')`; second GET
returns exact generic 403 before list handler/read and creates no auth/domain
audit. Expected DB delta is exactly removal of
`(role_id=5301, permission='objects.read')`; every other row, schema and
AUTO_INCREMENT remains byte-equivalent.

Unchanged isolated fixture repeated twice SHALL return byte-identical admission,
body/security headers and zero mutation. Revoke branch cleanup completes before
main journey starts; main actor18 grant MUST be byte-equivalent initial state
when downstream artifact boundary is reached.

Negative-list server DB user SHALL have SELECT only on exact canonical local
users/roles/user_roles/role_permissions, not object/process/legacy tables; any
list-handler read changes expected denial into DB failure. Full snapshot around
each authorization invocation includes SHOW CREATE, ordered rows and
AUTO_INCREMENT for every task DB table, exact process events, public process
projection/artifact metadata and owned storage tree
path/type/dev/inode/mode/uid/gid/size/hash. Isolated revoke
branch asserts exact role5301 permission row before DELETE, its sole absence
afterward and no other delta. Main branch authorization-read snapshots are
full-equal immediately before/after each list invocation.

After list admission the canonical journey MAY perform only the already approved
prepare delta before artifact boundary: one assignment-order fact, its
append-only event, corresponding artifact metadata and bytes under the owned
artifact root. Comparison from pre-prepare to artifact boundary SHALL reject
every other DB/process/storage delta. Exact local users, roles, user-role
assignments, role permissions, their schemas and authority-related counters
MUST remain byte-equivalent. Literal full DB/storage equality across prepare is
not required because it would reject the required business mutation; this does
not relax equality around authorization reads.

## 4. Authority failures and redaction

Main/isolated fixtures SHALL prove absent/empty/invalid actor string → exact 401
`Authentication required.\n`; positive unknown/inactive local user, inactive role
or near-match permission → exact 403 `Access denied.\n`; local schema/read
unavailable → status503 `Service unavailable.\n`, UTF-8 Content-Type,
Content-Length21, no-store/nosniff/no-referrer/DENY, CSP
`default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'`,
exactly one `Permissions-Policy: camera=(), microphone=(), geolocation=()` and
exactly one `Cross-Origin-Opener-Policy: same-origin`, one external opaque
`X-Correlation-ID: [0-9a-f]{12}`, no Retry-After/
cookie/location/auth/CORS/server/unspecified application headers. Internal logger
gets same ID and safe category only; category/path/SQL/RBAC facts never external.
All occur before list read. `REMOTE_USER`, forwarded
headers, cookie spoof or legacy role cannot rescue denial. No response/log leaks
role/permission/user facts, SQL, schema/prefix or credentials.

Exact list success and security/CSP/representation inherit approved
`PILOT-OBJECT-LIST-001` and `LOCAL-RBAC-AUTH-CONTRACT-001`; expected body is not
derived from production output.

## 5. Cleanup and artifacts boundary

Every fixture owns explicit DB name/user, server process, mutable/session/
artifact roots and credentials supplied outside repository. Finally order:
stop/reap server, close handles/connections, revoke/drop exact DB user/database,
delete only verified task roots. Cleanup is attempt-all and foreign decoys remain.

This spec MUST NOT modify two-HTML/combined-PDF expectations. It requires only
that RBAC proof precede artifact checks and names `PILOT-E2E-COMBINED-PDF-001`
as separate downstream change.

## 6. Gates and Done

Fresh independent review and exact-hash owner approval of this v2 amendment,
intended RBAC-before-artifact RED against the amended boundary, independent
test approval, minimal fixtures/actor propagation, focused demo/E2E/full verify,
architecture and independent code review are mandatory. Combined-PDF failure
may remain classified after RBAC slice GREEN. This DRAFT does not authorize
Gate 2.
