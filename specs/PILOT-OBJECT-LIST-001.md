# PILOT-OBJECT-LIST-001 — прочитать список явно импортированных объектов монтажа

- Статус: `APPROVED`
- Версия: `0.1`
- Дата: `2026-08-28`
- Актор: exact active legacy-пользователь с active legacy-ролью, аутентифицированный доверенным HTTP-сервером
- Публичный seam: HTTP `GET|HEAD /pilot/objects`
- Successor contracts: `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-OBJECT-CARD-001 v0.2`

## 1. Цель и единственный acceptance tracer

Дать аутентифицированному пользователю минимальную read-only очередь пилота: канонический список всех и только явно импортированных монтажных дел с переходом по обычной ссылке в уже утверждённую карточку.

Единственное acceptance statement среза:

> При `GET /pilot/objects` успешно аутентифицированный пользователь видит каждый явно импортированный pilot case ровно один раз, в каноническом порядке `(plannedStartDate ASC, installationObjectId ASC)`, с утверждёнными идентификационными и плановыми фактами и с exact ссылкой `/pilot/objects/{installationObjectId}`; legacy-объекты без импортированного case отсутствуют, а чтение ничего не изменяет.

`HEAD` доказывает тот же результат через status и headers, включая `Content-Length` GET-представления, но не отправляет body.

Срез не реализует широкую FKR-очередь из screen flow: нет process tasks, SLA/просрочки, следующего действия, ответственных, блокировок, поиска, фильтров или state matrix. Это первый кликабельный collection tracer к существующей OBJ-01.

## 2. Наследуемая boundary

Без исключений наследуются `PILOT-HTTP-AUTH-001 v0.12` и применимые read-only инварианты `PILOT-OBJECT-CARD-001 v0.2`:

- trusted Host и `REMOTE_USER`, exact active legacy user + active role, spoof resistance;
- route parsing order, validated public `shlz-ui` CSS descriptor, security/cache headers, redaction, correlation/reporting и attempt-all cleanup;
- exact generic `400`, `401`, `403`, `405`, `503` outcomes и failure priority;
- query не участвует в route, ordering, выборке или rendering;
- любой успешно разрешённый active user с active role имеет read access; process capability, роль ФКР и участие в деле не требуются;
- все DB-derived strings HTML-escaped; GET/HEAD не создают session/cookie, audit/event, task или иной факт.

Для syntactically valid collection route порядок проверок exact: valid Host → route/method → `REMOTE_USER` syntax → CSS → active-user lookup → list read. Missing/malformed identity даёт inherited `401` до config/DB/CSS reads; invalid CSS — inherited `503` до DB; unresolved/inactive/ambiguous user or inactive/missing role — inherited `403` до list read.

## 3. Exact route, query and methods

| Request | Observable result |
|---|---|
| `GET /pilot/objects` | authenticated list; `200` even when the imported set is empty |
| `HEAD /pilot/objects` | same status and application-controlled headers as GET, exact GET `Content-Length`, empty body |
| `GET|HEAD /pilot/objects?anything` | same canonical unfiltered list; query ignored |
| `GET|HEAD /pilot/objects/` | generic inherited `404` before identity/config/DB/CSS |
| duplicate slash, suffix, extra segment, encoded slash/backslash/dot/NUL or invalid encoding | inherited `404` before identity/config/DB/CSS |
| `POST|PUT|PATCH|DELETE /pilot/objects` and any other non-GET/HEAD method | inherited `405`, `Allow: GET, HEAD`, before identity/config/DB/CSS and without reading body |

No redirect aliases are introduced. Existing exact card route `/pilot/objects/{positive-id}` keeps `PILOT-OBJECT-CARD-001 v0.2` behavior.

After this route exists, successful `/pilot/` shell navigation replaces only the formerly disabled `Объекты монтажа` text with an ordinary link to `/pilot/objects`. The shell remains otherwise unchanged; this link is navigation to the same approved collection, not a second behavior or mutation.

## 4. Membership, read composition and canonical order

Membership begins from production-owned `fm2_installation_cases`. A legacy `fm_maintable` row is included only when exactly one imported case references its positive `id`. Existence in legacy alone, matching date, status, address or any other heuristic never makes an object a pilot case.

Each included row reads only the already approved `PILOT-OBJECT-CARD-001 v0.2` legacy facts and normalization:

| List value | Exact source/result |
|---|---|
| `installationObjectId` | positive `fm_maintable.id`, equal to imported-case reference |
| `objectRegistrationNumber` | trimmed nonblank `regnumber` |
| `address` | trimmed nonblank `ordadr_address` |
| `entrance` | trimmed nonblank `entrance` |
| `plannedStartDate` | calendar date from `workdatestart` |
| `plannedFinishDate` | valid nonzero `workdateendadjusted`, otherwise valid nonzero `plan_finish_date` |
| card link | literal `/pilot/objects/` plus canonical decimal `installationObjectId` |

The list does not load or render process projection, task, order, team, event, checklist, audit, legacy status or unapproved census fields. It cannot infer a next action or deadline.

Canonical order is ascending ISO `plannedStartDate`; ties are ascending numeric `installationObjectId`. DB collation, insertion/import order, registration number and address never break ties. Every imported case appears exactly once.

Duplicate/corrupt imported identity, duplicate legacy identity, dangling imported case, or an imported row with invalid required approved value is an integrity failure for the collection and returns inherited redacted exact `503`; the response must not silently omit the bad imported case. DB connection/query/schema failure is also `503`.

## 5. Pagination decision and empty result

Version `0.1` has no pagination and no pagination/query parameters. A successful response contains the complete explicitly imported pilot set. This is deliberate for the bounded pilot import and avoids inventing an unapproved default page size or a partial list.

The production read must apply a configured hard safety ceiling of exactly `500` imported cases. It may query up to `501` rows to detect overflow. More than `500` otherwise valid imported cases returns inherited redacted exact `503`; it never truncates the list or presents a partial page as complete. Any future pagination, filtering or larger pilot boundary requires a separate Gate 1 decision.

With zero imported cases, GET returns `200` and the successful page shell with exact visible empty text `Импортированные объекты монтажа пока отсутствуют.` and no object-card links. This is not a DB/error state.

## 6. Successful HTML contract

`GET` success returns `200`, `Content-Type: text/html; charset=UTF-8`, inherited headers and UTF-8 HTML with:

- inherited doctype, Russian language, charset, `/pilot/assets/shlz.css`, `body.shlz-scope`, skip link, product header and escaped actor name;
- navigation links `Моя работа` → `/pilot/` and current `Объекты монтажа` → `/pilot/objects`;
- `<main id="main-content" tabindex="-1">` and one `<h1>Объекты монтажа</h1>`;
- one semantic list/table whose item order is the canonical order from section 4;
- for each item, exact visible ID, registration number, address, entrance, planned start and planned finish, and one ordinary accessible object link with exact href `/pilot/objects/{ID}`;
- either the complete list or the exact empty state from section 5, never both.

No full row is a click target. The page contains no form, input/select/textarea, button, pagination control, inline script/style, mutation/command URL, process status, next-action claim, fake count, role switcher, document link or inline editing.

The page consumes the inherited public `shlz-ui` export and documented public vocabulary (`shlz-scope`, typography, `Table`/semantic list, `Link`, text `Status`/`Tag` where applicable). It does not copy or imitate tokens/components and adds no CSS/JavaScript. At narrow width each item remains readable in document flow without horizontal scrolling or hidden hover actions.

## 7. Independently fixed executable example

Actor fixture is inherited: `18 / Сидоров Сергей Сергеевич / sidorov@shlz.ru`, active user and active role.

Imported case references are independently fixed as `4515`, `4512`, `4513`. Legacy row `4999` exists but is not imported and must not appear.

Normalized literals:

| ID | Regnumber | Address | Entrance | Planned start | Planned finish |
|---:|---|---|---|---|---|
| 4515 | `77-000126` | `Москва, ул. Третья, д. 3` | `1` | `2026-10-05` | `2026-12-20` |
| 4512 | `77-000123` | `Москва, ул. Примерная, д. 10` | `2` | `2026-10-05` | `2026-12-18` |
| 4513 | `77-000124` | `Москва, ул. Вторая, д. 7` | `1` | `2026-10-01` | `2026-11-30` |
| 4999 | `77-009999` | `Москва, ул. Не пилотная, д. 1` | `4` | `2026-09-30` | `2026-10-30` |

Parsed DOM contains exactly three object items in this order:

```text
4513 → /pilot/objects/4513
4512 → /pilot/objects/4512
4515 → /pilot/objects/4515
```

The tie on `2026-10-05` is resolved numerically by `4512 < 4515`. Text/links for `4999` are absent. Reordering fixture insertion/import operations does not change the DOM item order.

## 8. HEAD parity, zero mutation and access outcomes

HEAD executes the same authentication and list decision as GET, including full validation and the 500-case ceiling. It returns the same status and exact application-controlled headers, including GET body length, with empty body.

Before/after fingerprints are byte-equivalent for every row and catalog/auto-increment state of all `fm2_*` tables, selected and nonselected legacy rows (`users`, `users_roles`, `fm_maintable`, logs/session/lastlogin included), artifact files and `../shlz-ui`. Repeated and concurrent GET/HEAD for the same committed state produce the same ordered representation. Request bodies are not read.

Exact post-route outcomes:

| Condition after valid route/method/Host | Result |
|---|---|
| missing/malformed `REMOTE_USER` | inherited `401 Authentication required.\n` |
| CSS/config or active-user lookup infrastructure failure | inherited `503 Service unavailable.\n`, `Retry-After: 60` |
| absent/inactive/ambiguous user or inactive/missing role | inherited `403 Access denied.\n` |
| list DB/integrity failure or more than 500 cases | inherited `503 Service unavailable.\n`, `Retry-After: 60` |
| zero valid imported cases | successful `200` empty state |
| one to 500 valid imported cases | successful `200` complete canonical list |

No collection outcome is `404` after the route has matched. Failures expose no principal, object values/IDs, counts, SQL/schema/path, exception or integrity reason.

## 9. Audit and persistence

The action is observationally read-only. It authorizes a read but does not call an `InstallationProcess` command and persists no process, task, event, security/login/read audit, timestamp, revision, session, cookie, view marker, file or artifact access. The HTTP database principal requires only `SELECT` on the exact existing legacy/process tables used by inherited authentication and this list.

## 10. Out of scope

- filtering, search, sorting controls, URL state and pagination;
- FKR task queue, urgency, SLA/overdue labels, process state, next action, blockers and assignees;
- process commands, forms, CSRF/session workflow and command capabilities;
- legacy objects not explicitly imported;
- object-card expansion, documents, workforce facts, events and audit UI;
- custom application CSS/JavaScript and `PilotHttp.php` refactoring.

## 11. Gate 2 boundary

Gate 2 writes one RED test for the acceptance statement in section 1 through real HTTP. It fixes the example values independently, observes canonical item/link order, exclusion of a non-imported legacy row, HEAD parity, empty success, access/failure priority, the 500/501 boundary, forbidden controls and zero mutation. It must not derive expected values from renderer output or inspect private methods/SQL as the assertion seam.

## 12. Gate 1 approval

- Product owner: project user
- Approved by: separately tasked Gate 1 Codex agent `/root/pilot_queue_gate1`
- Date: `2026-08-28`
- Decision: `APPROVED`
- Comment: the user instructed autonomous implementation from the session handoff under mandatory SSD + TDD. Version `0.1` resolves the collection ambiguities before tests: exact `/pilot/objects`, all and only explicitly imported cases, deterministic date/ID order, no pagination up to a fail-closed 500-case pilot ceiling, inherited any-active-user read access, exact empty and failure outcomes, one canonical list-to-card tracer, and no process/audit mutation.

Gate 2 is permitted only for version `0.1` and must be authored by a new separately tasked agent. Gate 3 and Gate 5 require their own new independent reviewers.
