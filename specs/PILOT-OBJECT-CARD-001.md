# PILOT-OBJECT-CARD-001 — прочитать карточку явно импортированного объекта монтажа

- Статус: `APPROVED`
- Версия: `0.2`
- Дата: `2026-08-28`
- Актор: exact active legacy-пользователь с active legacy-ролью, аутентифицированный доверенным HTTP-сервером
- Публичный seam: HTTP `GET|HEAD /pilot/objects/{positive-id}`
- Successor contract: `PILOT-HTTP-AUTH-001 v0.12`

## 1. Цель и вертикальная граница

Дать любому уже аутентифицированному пользователю FMonitor одну каноническую read-only карточку явно импортированного монтажного дела:

```text
trusted HTTP identity
→ exact active legacy user + active role
→ exact positive object ID
→ explicitly imported fm2 installation case
→ approved legacy identity/plan facts + current process projection
→ safe OBJ-01 overview HTML
```

Это первый видимый tracer bullet карточки, а не полный экран из семи вкладок. Он показывает общую шапку и «Обзор»; не добавляет очередь, формы, command endpoints, checklist embedding, документы для скачивания, кадровые подробности, расчёты, custom CSS/JavaScript или новые process states.

## 2. Наследуемая HTTP/security boundary

Без исключений наследуются `PILOT-HTTP-AUTH-001 v0.12`:

- trusted Host и `REMOTE_USER`, exact legacy user lookup, spoof-header resistance;
- request parsing order, single secured entrypoint, redaction, correlation/reporting and request-scoped attempt-all cleanup;
- validated `shlz-ui` CSS descriptor and production bootstrap;
- все security/cache headers, no cookie/CORS/banner, exact `Content-Length`, HTML escaping;
- generic `400`, `401`, `403`, `405`, `503` bodies и их priority;
- query не участвует в route, identity, object ID или rendering.

Карточка доступна любому успешно разрешённому active user с active role. Process capability, роль ФКР, назначение инженером и участие в конкретном деле для чтения не требуются. Командные capabilities остаются отдельной boundary и не выводятся в HTML.

Для syntactically valid `GET|HEAD` card route сначала проверяется `REMOTE_USER`, точно как для `/pilot/`: missing/malformed identity даёт inherited exact `401` и не читает config/DB/CSS, даже если CSS или DB configuration одновременно сломаны. Только после valid identity проверяется CSS; невалидный CSS даёт inherited exact `503` до DB user/object reads. После valid CSS выполняется exact user lookup, затем object-card lookup. Failure не раскрывает principal, object existence или внутреннюю причину.

## 3. Exact routes and method matrix

`{positive-id}` — canonical ASCII decimal `/^[1-9][0-9]*$/`, числовое значение `1..9223372036854775807`; sign, whitespace, leading zero, non-ASCII digit и overflow запрещены.

| Request | Observable result |
|---|---|
| `GET /pilot/objects/4512` | authenticated object-card lookup; `200` только для exact доступного imported case |
| `HEAD /pilot/objects/4512` | тот же status/headers и exact GET `Content-Length`, body empty |
| `GET|HEAD /pilot/objects/4512?tab=overview` | тот же overview; query ignored |
| `GET|HEAD /pilot/objects/0`, `/01`, `/-1`, `/+1`, overflow | generic `404` до identity/config/DB/CSS |
| missing ID, trailing slash, duplicate slash, suffix or extra segment | generic `404` до identity/config/DB/CSS |
| percent-encoded digit, slash, backslash, dot segment, NUL or invalid encoding | inherited generic `404` before identity/config/DB/CSS |
| `POST|PUT|PATCH|DELETE` на syntactically valid card route | inherited `405`, `Allow: GET, HEAD`, before identity/config/DB/CSS and without reading request body |
| любой иной method | inherited `405` on valid route |

Маршрут `/pilot/objects/{positive-id}` не redirect-ится. `/pilot/objects/4512/` не является alias. Existing `/pilot`, `/pilot/`, `/pilot/assets/shlz.css` retain v0.12 behavior except successful shell navigation now exposes an ordinary link `Объекты монтажа` only when a separately specified list route exists; this slice does not fabricate that route, so existing shell DOM remains unchanged.

## 4. Preconditions and non-disclosing availability

Success requires all conditions:

1. HTTP actor passed inherited exact active-user/active-role resolution.
2. `fm2_installation_cases.legacy_installation_object_id = requested ID` exists. This row is the sole proof that the case was explicitly selected by `PILOT-CASE-IMPORT-001`; existence of `fm_maintable.id` alone is insufficient.
3. Exactly one matching legacy `fm_maintable` row exists and its approved card fields are readable and valid under section 5.
4. Process projection for that exact imported case is internally consistent under the already approved persistence contracts.

Failure of conditions 2 or 3, including a dangling imported case, returns exactly:

```text
404
Content-Type: text/plain; charset=UTF-8
Not found.\n
```

The same response bytes, application-controlled headers, DB-access shape after authentication and reporter behavior apply to:

- unknown ID with no legacy row and no imported case;
- legacy row which exists but was not imported;
- imported case whose legacy row is missing;
- imported case with missing/invalid required approved card identity/plan value.

No `403`, distinct reason, timing-dependent message, redirect, object ID, address, regnumber, SQL/table name or “not imported” text distinguishes these cases. Gate 2 uses padded fixture populations and repeated samples only to catch gross lookup/enumeration branches; it does not assert wall-clock equality.

DB connection/query/schema failure, duplicate/corrupt process identity, corrupt process projection or unexpected value maps to inherited redacted exact `503`, not `404`. Authentication is still evaluated before object existence for a syntactically valid route: missing/malformed identity gives `401`; unresolved/inactive user or role gives `403`; neither outcome reveals whether the ID exists.

## 5. Exact read composition

The read model combines only two approved sources. It never reconstructs process state from legacy columns and never uses controller SQL as a write seam.

### 5.1. Legacy identity and plan facts

For the requested exact `fm_maintable.id`, one parameterized read selects only:

```text
id, ordadr_address, entrance, regnumber,
workdatestart, workdateendadjusted, plan_finish_date
```

Mapping and normalization are exactly inherited from `LEGACY-OBJECT-SNAPSHOT-001 v0.2`:

| Card field | Exact source |
|---|---|
| `installationObjectId` | positive `id`, also equal to imported-case reference and route ID |
| `address` | surrounding-whitespace-trimmed `ordadr_address` |
| `entrance` | surrounding-whitespace-trimmed `entrance` |
| `objectRegistrationNumber` | surrounding-whitespace-trimmed `regnumber` |
| `plannedStartDate` | calendar date from `workdatestart` |
| `plannedFinishDate` | valid nonzero `workdateendadjusted`, otherwise valid nonzero `plan_finish_date` |

All five textual/date facts are required and nonblank after approved normalization. No fallback uses `workdatefinish`. This slice does not read or render `zavnumber`, UNOM, contractors, contacts, `object_status`, `responsstroicontrol`, `installator*`, checklist percentages, photos, comments, financial fields or the remaining census columns. Those require their own approved read-model decisions.

### 5.2. Process facts

The production card reader loads the same durable process projection represented by `InstallationProcess.getInstallationObjectProcess(id)`, from production-owned `fm2_*` tables, and exposes only:

- `processState` and the derived Russian main status in section 6;
- root `actualStartDate`, `openedAt`, `openedByUserId` when present;
- `installationOpened`, `checklistAvailable`;
- current assignment-order version: highest exact `version`, with `status`, `assignmentOrderDate`, nullable `registrationNumber`, and its already persisted object snapshot;
- current order installers: `tabId`, `fullName`, `position`, persisted snapshot status;
- current order control engineer: `userId`, `fullName`, `position`;
- `organizationType`;
- latest three process events in durable event order, displayed newest first as type, `occurredAt`, `actorId`; event payload and technical IDs are not rendered in this slice.

“Current order” requires exactly one highest version and no ambiguity. If no order exists, all order/team sections use their explicit empty state. Historical orders, artifacts, hashes, open tasks, rejected-event payload, raw table revision and security audit are not rendered. They remain unchanged in storage.

Legacy live identity/plan facts are shown as current integration data. Once an order exists, its immutable `installationObjectSnapshot` is used only as document provenance/integrity input; disagreement with the live identity makes the read fail closed as `503` in this slice rather than silently choosing or editing either source.

## 6. All observable process states

The main status is derived by this exhaustive table. A database value not covered here is corrupt and gives redacted `503`.

| Durable process facts | Main visible status | Overview consequence |
|---|---|---|
| `processState = needs_assignment_order`, no current order, not opened | `Требуется распоряжение` | `Распоряжение ещё не сформировано`; team empty; checklist `Работы ещё не открыты` |
| `processState = assignment_order_prepared`, current order `prepared`, not opened | `Распоряжение подготовлено` | document `Ожидается номер 1С ДО`; preliminary team; checklist closed |
| `processState = assignment_order_prepared`, current order `registered`, not opened | `Готов к открытию` | registration number visible; checklist `Работы ещё не открыты` |
| `processState = working`, current order `registered`, root opening facts present | `В работе` | actual start and opening audit visible; checklist `Доступен` |
| `processState = needs_assignment_change`, current order `registered`, root opening facts present | `Требуется изменение` | current registered basis remains visible; checklist availability follows persisted gate |

`Готов к открытию` is deliberately not a stored `ready_to_open` state: `REGISTRATION-CONFIRM-001` keeps `processState = assignment_order_prepared`, and readiness follows the current exact order status `registered`. Document statuses have separate texts: `prepared → Ожидается номер 1С ДО`, `registered → Зарегистрировано в 1С ДО`, `superseded → Заменено новой версией`, `cancelled → Отменено`. A superseded/cancelled version cannot be the sole current basis; inconsistent combinations return `503`.

This read slice does not create `needs_assignment_change`; it renders it if a later approved command persists that state. No UI button is exposed for any state in v0.1.

## 7. Successful HTML contract

`GET` success returns `200`, `Content-Type: text/html; charset=UTF-8`, inherited headers and a UTF-8 document with:

- `<!doctype html>`, `<html lang="ru">`, one charset meta and stylesheet link `/pilot/assets/shlz.css`;
- `<body class="shlz-scope">`, inherited skip link, product header and escaped actor display name;
- navigation with `Моя работа` linking `/pilot/` and current non-mutating text/link `Объект монтажа` for this page; no nonexistent object-list link is invented;
- `<main id="main-content" tabindex="-1">`;
- one `<h1>` `Объект монтажа № {ID}`;
- an accessible text status, definition groups `Идентификация`, `Сроки`, `Распоряжение и команда`, `Работы`, and `Последние события`;
- facts in this exact semantic order: registration number, address, entrance; planned start, planned finish, then actual start/opening audit when present; current order then engineer then installers; checklist state; newest three events;
- empty sections represented by explanatory text, never blank cells or fake values.

No value is directly editable. HTML contains no `form`, input/select/textarea, inline script/style, mutation URL/button, disabled copy of another role’s command, document-download URL, full-row click target, role switcher, fake count or wide legacy table. Links in this slice are limited to `/pilot/` and the stylesheet/skip target.

Every DB-derived string is escaped with quotes/substitution. Dates remain exact ISO `YYYY-MM-DD`; timestamps remain exact stored RFC3339 strings; the UI does not reinterpret timezone or silently localize values in this slice.

### `shlz-ui` boundary

The page consumes only public `shlz-ui` CSS already delivered by `/pilot/assets/shlz.css` and public documented class/component vocabulary (`shlz-scope`, typography, text `Status`/`Tag`, `Person Tag`, `Link`, semantic definition/list composition). It does not copy tokens, CSS declarations, component markup from `../shlz-ui`, use showcase/private sources, create a local imitation, or add application CSS/JavaScript. `Tabs` are not emitted because this slice implements only Overview; empty fake tabs would promise unavailable routes.

The DOM remains a single semantic column at narrow widths and at most two meaningful groups on desktop using document flow; no horizontal table/scroll dependency, giant CTA or KPI tiles are introduced.

## 8. Independently fixed executable examples

Expected values below are normative literals, not copied from production output, computed by production mapping, or snapshots of prototype HTML.

### Example A — newly imported case

Actor fixture is inherited shell user `18`, `Сидоров Сергей Сергеевич`, `sidorov@shlz.ru`. Exact imported process fixture:

```text
legacy_installation_object_id = 4512
processState = needs_assignment_order
assignmentOrders = []
events = []
installationOpened = false
checklistAvailable = false
```

Legacy literals:

```text
ordadr_address = "  Москва, ул. Примерная, д. 10  "
entrance = " 2 "
regnumber = " 77-000123 "
workdatestart = "2026-10-05 14:30:00"
workdateendadjusted = "2026-12-18 09:15:00"
plan_finish_date = "2026-12-20"
```

The parsed DOM contains exact visible values:

```text
Объект монтажа № 4512
Требуется распоряжение
77-000123
Москва, ул. Примерная, д. 10
Подъезд 2
Плановое начало 2026-10-05
Плановое окончание 2026-12-18
Распоряжение ещё не сформировано
Подтверждённая команда ещё не сформирована
Работы ещё не открыты
Событий пока нет
```

No installer/engineer/actual-start value or process action is present.

### Example B — registered and opened case

Independent fixture for imported `4513`:

```text
address = "Москва, ул. Вторая, д. 7"
entrance = "1"
objectRegistrationNumber = "77-000124"
plannedStartDate = "2026-10-01"
plannedFinishDate = "2026-11-30"
processState = working
actualStartDate = 2026-10-03
openedAt = 2026-10-03T08:15:30+03:00
openedByUserId = 18
installationOpened = true
checklistAvailable = true
```

Current version is independently fixed as version `2`, `registered`, order date `2026-10-02`, registration number `19-Р`, `organizationType = brigade`; engineer `73 / Петров Пётр Петрович / Инженер строительного контроля`; installers ordered by numeric tab ID are `1042 / Иванов Иван Иванович / Монтажник / employed` and `1057 / Смирнов Алексей Олегович / Монтажник / employed`. Exact latest event order in storage is:

```text
assignment_order_prepared 2026-10-02T09:00:00+03:00 actor 18
assignment_order_registered 2026-10-02T15:10:00+03:00 actor 18
installation_opened 2026-10-03T08:15:30+03:00 actor 18
```

DOM shows `В работе`, `Зарегистрировано в 1С ДО`, `Распоряжение № 19-Р от 2026-10-02 · версия 2`, both installers in tab-ID order, the engineer, `Бригадная`, exact opening facts, `Чек-лист: Доступен`, and the three events newest first. It never shows event payload, artifact hash/bytes or a checklist mutation link.

### State sensitivity examples

Using the same valid identity fixture and exact imported case:

- current version `1/prepared`, `processState = assignment_order_prepared`, closed gates renders `Распоряжение подготовлено` and `Ожидается номер 1С ДО`;
- changing only that version to `registered` with number `12-Р` renders `Готов к открытию` and `Зарегистрировано в 1С ДО`, while checklist remains closed;
- `needs_assignment_change` with registered basis and opening facts renders `Требуется изменение` without a command control.

These expected labels are fixed by section 6. Tests do not ask production renderer to generate expected HTML.

## 9. GET/HEAD parity and zero mutation

For every route outcome, HEAD executes the same authentication and read decision as GET, returns the same status and exact application-controlled headers including the `Content-Length` of the GET representation, and sends an empty body. HEAD does not use a reduced projection or different authorization rule.

GET and HEAD are observationally read-only. Before/after fingerprints must be byte-equivalent for:

- every row and auto-increment/catalog state of all `fm2_*` tables;
- the selected and nonselected legacy rows, including `users`, `users_roles`, `fm_maintable`, legacy logs/session tables and `lastlogin`;
- artifact-store files and `../shlz-ui`.

No process/security/login/read event, task completion, timestamp/revision update, session/cookie, “viewed” marker, file creation or artifact access is produced. Repeated GET/HEAD and concurrent reads return the same representation for the same committed database state. The read DB principal required by this HTTP slice has only `SELECT` on exact required process/legacy tables; write privilege is neither required nor used.

Request bodies are never read. A syntactically valid GET/HEAD card request with a supplied body behaves exactly as the same request without it.

## 10. Failure priority, redaction and cleanup

After valid Host, route grammar/method precede identity. For a valid GET/HEAD card route the priority is:

```text
invalid/missing REMOTE_USER → 401
CSS failure → 503
active-user resolution infrastructure failure → 503
no exact active user/role → 403
object unavailable by section 4 → 404
object-read infrastructure/integrity failure → 503
success → 200
```

Это exact successor расширение inherited shell order: valid identity всегда предшествует любому чтению environment/config, CSS или DB; затем CSS availability предшествует user DB lookup; object configuration/read создаётся и читается только после exact active-user resolution. Поэтому missing/malformed `REMOTE_USER` остаётся `401` при одновременно broken CSS/DB/object config, а valid identity с broken CSS остаётся `503` без user/object DB reads.

The inherited outer entrypoint still catches every unexpected Throwable, reports only approved category/correlation data and closes every acquired CSS/DB resource exactly once. Card-specific expected `404` carries no exception or raw ID to the reporter. All failure responses exclude principal, user name/email, requested/raw IDs, object values, DB/env credentials, SQL/table/prefix/path, driver/filesystem metadata, exception details and stack.

## 11. Gate 2 public seam and required sensitivity

Gate 2 starts the real PHP built-in server on a random loopback port against isolated real MariaDB process/legacy fixtures, sends raw HTTP and observes only status, raw headers, body and parsed DOM. It does not call renderer, repository, process environment or private methods as assertion seams and does not query DB to derive expected page values. Separate before/after DB fingerprints are permitted only as the zero-mutation assertion.

Required sensitivity includes:

- exact Examples A/B and every state row in section 6;
- exact route/method/positive-ID matrix, ignored query, body-not-read, GET/HEAD parity;
- authenticated broad read for a user with no process capability;
- unknown, non-imported legacy, dangling imported and invalid-card-data cases sharing exact `404` output;
- missing/malformed/spoofed identity, inactive/duplicate user or role and DB failures retaining inherited priority/redaction;
- live legacy fallback `workdateendadjusted → plan_finish_date`, whitespace normalization and adversarial escaped actor/object/person strings;
- corrupt/mismatched current version, process state, opening gates or immutable order snapshot returning redacted `503`;
- exact current-order selection, installer ordering, event newest-three truncation, absence of forbidden fields/controls/URLs;
- all inherited headers, no cookies/CORS/banner, validated public CSS boundary and request-scope cleanup;
- full before/after fingerprints for success, HEAD, `404`, `403` and `503`, plus write-denied DB credentials proving SELECT-only operation.

Expected strings/values are taken only from this specification and inherited approved literal contracts. A test/support oracle may contain independently authored literal HTML/DOM expectations; it must not transform production output or reuse production mapping/rendering code.

## 12. Review manifest and approval invalidation

Gate 3 records one SHA-256 manifest containing at minimum:

- this exact spec file and exact `PILOT-HTTP-AUTH-001.md` successor input;
- the complete Gate 2 test and every test support/oracle/bootstrap file it loads;
- every `app/PilotHttp/*.php` file, represented both by individual hashes and one binary-path-sorted per-file manifest digest;
- every `app/InstallationProcess/*.php` production file transitively loaded by the card read composition, likewise individually and with a sorted manifest digest;
- `public/router.php` and `app/PilotHttp/production-entrypoint.php`;
- the current Gate 3 review record path.

The review records repository HEAD only as ancestry; dirty working-tree bytes are identified by SHA-256. Addition, removal or rename in either scanned production set, specification version/bytes change, test/support/bootstrap byte change or manifest membership change invalidates Gate 3. Gate 4 may modify production only after `APPROVED` Gate 3 and must not change any approved expected value/test input.

Gate 5 pins the approved Gate 3 record plus the same complete spec/test/support/bootstrap/production manifest and verification evidence. Any listed byte or scanned-set membership change invalidates Gate 5; test/support/spec changes also invalidate Gate 3 and restart at Gate 2. A verdict is never inferred from filename, HEAD or silence.

## 13. Not in this slice

- object list/search/queue and `/pilot/objects` collection route;
- tabs or full seven-tab object-card implementation;
- process command buttons/forms, session, CSRF and mutation HTTP methods;
- artifact/document download, checklist endpoint or photo evidence;
- changed-assignment command/state creation;
- new process audit/security audit/read tracking;
- live workforce refresh or Bitrix publication/history;
- sensitive HR details, calculations, payments or permissions for them;
- reconciliation/editing of legacy identity or immutable document snapshots;
- custom application CSS/JS and copying any `shlz-ui` source.

## 14. Decisions and evidence

- `PRODUCT.md`, pilot spec/data model: one process-controlled object, read-only legacy identity and append-only history.
- `docs/fmonitor-2-screen-flow.md`, decision 4: one OBJ-01 for all authenticated users; broad read, narrow write; role-independent basic facts.
- `docs/fmonitor-2-object-card-spec.md`: compact persistent header, subject sections, no 116-field table, public `shlz-ui` composition.
- `PILOT-CASE-IMPORT-001`: only explicit process-case row proves pilot inclusion.
- `LEGACY-OBJECT-SNAPSHOT-001`: approved exact identity/plan mapping and date fallback.
- `REGISTRATION-CONFIRM-001`: readiness is document status, not a new process state.
- `PILOT-HTTP-AUTH-001 v0.12`: trusted identity, HTTP/security/error/composition/cleanup boundary inherited without exception.
- Read-only inspection of current public seams: `InstallationProcess.getInstallationObjectProcess(id)`, `ProductionPilotHttpEntrypointFactory.create(EnvironmentSource)` and fixed `public/router.php` bootstrap. This specification adds behavior through those public boundaries without approving controller writes or private test seams.

## 15. Gate 1 approval

- Product owner: project user
- Approved by: separately tasked Gate 1 correction Codex agent `/root/object_card_gate1_correction`
- Date: `2026-08-28`
- Decision: `APPROVED`
- Comment: the user explicitly instructed autonomous continuation from the session handoff with mandatory SSD + TDD and a new independent agent at every Gate. Version `0.2` preserves the approved OBJ-01 behavior and corrects its mixed-failure order to inherit `PILOT-HTTP-AUTH-001 v0.12` exactly: `REMOTE_USER` validation precedes CSS/config/DB reads. A full recheck found no other internal or parent-contract contradiction affecting executable behavior.

Gate 2 is permitted only for version `0.2`. Gate 2 must be authored by a new separately tasked agent; Gate 3 and Gate 5 require their own new independent reviewers.
