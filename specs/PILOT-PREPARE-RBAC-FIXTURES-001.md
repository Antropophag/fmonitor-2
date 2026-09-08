# PILOT-PREPARE-RBAC-FIXTURES-001 — local RBAC admission for prepare form

Статус: **DRAFT / Gate 1**  
Версия: **v3**  
Дата: **2026-09-02**

## Простыми словами

Открыть форму подготовки распоряжения можно только при двух независимых
основаниях: local role разрешает сам маршрут, а существующая process capability
разрешает предметное действие. Совпадение строк `assignment_order.prepare` не
объединяет эти facts. POST формирования распоряжения этим срезом не меняется.

## 1. Actor, route и наследуемый contract

Public seam:

```text
GET|HEAD /pilot/objects/{positive-id}/assignment-order/prepare
```

Все route/query/HEAD, form/eligibility, state, failure, zero-write and UI clauses
из `PILOT-PREPARE-FORM-001 v0.2` наследуются, кроме legacy-only actor
authorization prefix, который полностью заменён sections 2–4 этого spec.
POST/CSRF/command seam, card link authorization и other routes вне scope.

Canonical positive actor `18` SHALL одновременно иметь:

```text
local fm2_pilot_users: status=1, activation_state=active
local active assigned role 5201
local role permission: assignment_order.prepare
legacy descriptive user/role: exact id 18 and active
process capability row: user_id=18, capability=assignment_order.prepare
```

Email/display/legacy role/process capability alone не являются local authority.
Local role permission не является process capability.

## 2. Exact admission order

For matched route order SHALL be:

```text
valid outer Host/URI
→ exact path and GET|HEAD method
→ trusted local actor ID syntax/presence
→ one current local-RBAC snapshot with exact assignment_order.prepare
→ configured CSS/application dependencies
→ active legacy descriptive user/role
→ exact process capability assignment_order.prepare
→ imported object/card integrity and coherent process state
→ workforce/engineer catalog reads
→ render
```

Invalid/nonmatching path and unsupported method retain predecessor 404/405 before
application identity/config/DB/domain/form work. The PHP built-in transport may
already buffer/consume a fully delivered request body before invoking the
application; this app-level contract neither claims nor instruments transport
body consumption. Local denial precedes CSS/process/object/people reads.
Process-capability denial follows local success but precedes object/people reads.

## 3. Fixture manifest and exact outcomes

Fixture SHALL use canonical landed identity/access schema plus existing process
schema. Exact cases:

| Local permission | Process capability | Result |
|---|---|---|
| exact | exact | inherited 200 GET / HEAD parity when object valid |
| absent/near-match/inactive chain | exact | 403 before process/object reads |
| exact | absent | 403 before object/people reads |
| absent | absent | 403 at local boundary |
| legacy/process facts only | exact process | 403, no fallback |

Missing/invalid actor ID returns 401; local schema/read fault returns generic
503 with safe correlation; downstream CSS/directory/object/catalog faults retain
approved redacted outcomes. `objects.read` does not grant prepare. Local exact
permission does not grant object list or POST command beyond its separately
mapped contracts.

POST retains the current `PILOT-E2E-FLOW-001` command/media/CSRF/process contract
and is outside this GET|HEAD migration. Unsupported `PUT|PATCH|DELETE` retains
405 with exact `Allow: GET, HEAD, POST` before both authorization gates and all
application domain/form work. The response MUST NOT contain bytes derived from
the delivered payload. Unknown/non-imported object returns
404 only after both gates. Coherent non-prepare state returns inherited exact
409. Corrupt/dangling data returns inherited redacted 503.

## 4. Observability, revoke, HEAD и no-write

Test-owned sentinels SHALL separately observe local authorization query,
process-capability query and object/form reader. Renderer observation SHALL use
the narrow decorator seam owned by the canonical production entrypoint factory:
normal production composition always supplies an identity decorator; explicit
test composition supplies a spy decorator which receives and wraps the real
renderer created by that same factory, counts calls and delegates exact input
and output bytes unchanged. A manually reconstructed composition graph,
reflection/shadowing or a test-owned replacement renderer MUST NOT serve as
evidence of canonical wiring. Every denial proves all downstream sentinels
untouched according to section 2. Full DB/filesystem/
artifact snapshots including counters are byte-equivalent for GET success,
HEAD, 401/403/404/405/409/503. No session/cookie/audit/task/process fact created.

Exact public PHP seam:

```php
interface PrepareFormRendererDecorator
{
    public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer;
}

final class IdentityPrepareFormRendererDecorator implements PrepareFormRendererDecorator
{
    public function decorate(PrepareFormRenderer $renderer): PrepareFormRenderer
    {
        return $renderer;
    }
}

final class ProductionPilotHttpEntrypointFactory
{
    public static function create(
        EnvironmentSource $environment,
        ?PrepareFormRendererDecorator $prepareFormRendererDecorator = null,
    ): PilotHttpEntrypoint {
        throw new LogicException('Contract signature example; composition is specified below.');
    }
}
```

`null` is the normal production path and SHALL make the factory instantiate the
identity decorator. An explicit test spy is the only alternate composition.
The factory SHALL itself instantiate exactly one `ProductionPrepareFormRenderer`,
call `decorate()` exactly once with that instance and use the returned
`PrepareFormRenderer` everywhere the canonical graph needs the prepare renderer.
The decorator receives no environment, dependencies or application graph.

Factory composition for every request SHALL call decorator `decorate()` exactly
once. Allowed GET SHALL then invoke the returned wrapped canonical real renderer
exactly once and return its exact bytes through the spy. Every rejection before
render, including method, authentication, either authorization gate,
object/state and DB failures, SHALL retain that one composition-time
`decorate()` call but record zero request-time wrapped-renderer `render()` (or
compatibility render) invocations.

HEAD performs both authorization gates and all read/integrity decisions, returns
GET status/application headers and exact GET Content-Length with empty body.

In isolated branch, committed deletion of local permission makes next GET and
HEAD 403 before process read. Separately, committed deletion of process
capability with local grant intact makes next GET/HEAD 403 after local sentinel
and before object read. Main positive fixture remains unchanged. Authorization
check creates no audit; fixture-admin mutations/audit outside scope.

## 5. Environment and cleanup

Each process receives explicit trusted actor ID or explicit unset/replacement;
positive env MUST NOT leak into negative cases. Legacy `REMOTE_USER` MAY be
present only as descriptive predecessor input and cannot rescue missing local
grant. DB/user/prefix/CSS/artifact roots are task-owned; foreign decoys preserved.

Finally stops/reaps server, closes resources, drops only exact task DB/user and
deletes verified task roots. Cleanup executes for setup/assertion/fault failures.

## 6. Gates and Done

This transport-boundary clarification changes the v3 hash, so every earlier
owner approval and Gate 2/3 record is historical and insufficient for a new
Gate 2. Fresh independent review and exact-hash owner approval of this amended
v3 →
intended public RED against the canonical renderer observation seam → independent test
approval → minimal GET/HEAD route wiring + fixtures → focused/full/architecture
verification → independent code approval. Any test change returns Gate 2. This
DRAFT does not authorize tests or production changes.
