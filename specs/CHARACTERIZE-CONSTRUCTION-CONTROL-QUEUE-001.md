# CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001 v0.1

Status: `DRAFT_AWAITING_INDEPENDENT_REVIEW_AND_OWNER_APPROVAL`. SHA-256 этого файла SHALL быть зафиксирован fresh independent Gate 1 reviewer и затем явно одобрен владельцем до RED. Это строго `PILOT_ONLY` characterization текущего rapid-pilot oracle, а не одобрение target visibility, assignment, inspection, completion, ordering, pagination или read-model API.

## Простыми словами

Срез фиксирует, что сегодня реально показывает очередь «Стройконтроль» через настоящий HTTP entrypoint, чтобы будущий перенос read model не изменил поведение незаметно. Он ничего не меняет в продукте и намеренно не объявляет правильными текущие спорные детали: общую видимость объектов, browser-фильтр «Мои» после пагинации, checklist activity вместо доказанной инспекции, legacy PTO вместо завершения и относительные подписи времени.

## Actor, intent и release value

Discovery/test agent нужен deterministic oracle release-critical read-only пути до выделения целевого projection seam.

- admitted actor: active fictional local user `8101`, principal `control.queue.allowed@example.test`, одна active role `9101` с exact current permission `construction_control.read`;
- denied actor: active fictional local user `8102`, principal `control.queue.denied@example.test`, одна active role `9102` без этого permission;
- inactive actor: fictional local user `8103`, principal `control.queue.inactive@example.test`, inactive account при otherwise matching role;
- unauthenticated case: malformed/absent trusted identity.

Имена, email, ids и все object data вымышлены. Current permission и local-role lookup — oracle inputs, не target authorization policy.

## Public oracle seam

- Planned focused entry point: `php tests/Verification/characterize_construction_control_queue_001_test.php`.
- Каждое behavioral observation SHALL быть реальным request через test-owned loopback worker, созданный `ProductionPilotHttpEntrypointFactory`, к exact path `/pilot/construction-control` или `/pilot/construction-control?page=<value>`.
- Worker SHALL использовать production identity resolution, assets, local authorization projection, MariaDB query и production renderer. Direct renderer call, fabricated response DTO или test-owned reconstruction graph не квалифицируются.
- Target candidate `InspectionExecution::getConstructionControlQueue(actorId, page)` не подтверждается и не создаётся этим slice.

## Exact response headers

Header names сравниваются case-insensitively, values — byte-exact. Если ниже не сказано иное, каждый response SHALL содержать ровно следующие application-owned security/cache headers плюс `Content-Length`; server-added transport headers (`Date`, `Server`, `Connection`) игнорируются:

```text
X-Content-Type-Options: nosniff
Referrer-Policy: no-referrer
X-Frame-Options: DENY
Permissions-Policy: camera=(), microphone=(), geolocation=()
Cross-Origin-Opener-Policy: same-origin
Cache-Control: no-store
```

Successful HTML `GET/HEAD` SHALL additionally contain:

```text
Content-Type: text/html; charset=UTF-8
Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
Content-Length: <decimal byte length of the corresponding GET representation>
```

Error `401/403/503` SHALL contain `Content-Type: text/plain; charset=UTF-8` and base CSP:

```text
Content-Security-Policy: default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'
```

Every `503` SHALL additionally contain `Retry-After: 60`. `401` and `403` SHALL omit `Retry-After`. `HEAD` SHALL return empty bytes while preserving status, all headers and `Content-Length` of the corresponding GET body.

## Literal fixture

Expected values below are Gate 1 literals, not copied from future verifier output and not calculated with production projection code.

### Principal rows

The fixture SHALL contain only the actor facts above plus the canonical minimum role/permission joins needed by production composition. No actor has `objects.read` unless fixture setup requires it for a separately fingerprinted decoy; the allowed outcome must therefore depend on `construction_control.read`.

### Small semantic page

Exact working cases:

| object | case | state | address / entrance / regnumber | engineer source | operations | PTO |
|---|---:|---|---|---|---|---|
| `451201` | `6101` | `working` | `ул. <Тестовая & 1>` / `1` / `REG-&-001` | event id `7202`: user `7302`, `Инженер <Событие>`; older event id `7201` and legacy user `7301` differ | none | NULL |
| `451202` | `6102` | `working` | `ул. Тестовая 2` / `2` / `REG-002` | no event; legacy user `7301`, `Инженер Фолбэк` | none | `2026-08-30` |
| `451203` | `6103` | `working` | `ул. Тестовая 3` / `3` / `REG-003` | neither valid source | `item_completed` at `2026-08-28T09:00:00+03:00`; later `photo_uploaded` at `2026-08-29T10:15:00+03:00` | NULL |
| `451204` | `6104` | `working` | `ул. Тестовая 4` / `4` / `REG-004` | legacy user `7301` | `item_completed` at `2026-08-31T08:45:00+03:00` | NULL |
| `451299` | `6199` | `needs_assignment_order` | `НЕ ПОКАЗЫВАТЬ` / `9` / `REG-299` | legacy user `7301` | none | NULL |

Event `7202` payload contains exact snapshot `{"engineer":{"userId":7302,"fullName":"Инженер <Событие>","position":"Инженер"}}`. Current selection of highest event identity, legacy fallback, any-operation `MAX(device_time)` and PTO-presence flag SHALL be labelled `PILOT_ONLY`.

### Pagination page

A disjoint scenario SHALL contain exactly working objects `452001` through `452051`, with cases `6201` through `6251`, no activity and otherwise valid literal display fields `Тестовый объект <object id>`, entrance `1`, registration `PAGE-<object id>`. Actor `8101` is assigned as engineer only to `452051`; objects `452001`–`452050` have engineer `7301`.

- `page=1` SHALL contain exactly object ids `452001`–`452050`, in ascending order, footer `Показано 50 из 51`, and a link to page 2.
- `page=2` SHALL contain exactly `452051`, footer `Показано 1 из 51`, and a link to page 1.
- This proves only server pagination-before-browser-filter risk. It MUST NOT approve page size 50, cross-assignment visibility or client default `mine` behavior.

## Scenario matrix

### A. Authorization and exact errors

1. Missing/malformed trusted identity: `GET` status `401`, exact body `Authentication required.\n`, `Content-Length: 25`, SHA-256 `971cfaaf421c7874bc205759bc6e2d771706b39358770928952ba2b8ea580dd5`.
2. Inactive actor `8103`: `GET` status `403`, exact body `Access denied.\n`, `Content-Length: 15`, SHA-256 `5a96ae11555504787da4b5f09ca3175a006392cff7c2c7df1a57f08ca2ebda02`.
3. Active denied actor `8102`: same exact `403` body/hash/length.
4. Active allowed actor `8101`: `GET` status `200` and successful HTML headers above.
5. Corresponding `HEAD` requests preserve status/headers/GET `Content-Length` and return exactly zero body bytes.

Denied cases SHALL not query queue-owned case/event/operation facts. A test-owned read-only SQL audit/log MAY prove query absence, but SHALL not replace public response observation.

Stable milestone:

`CONSTRUCTION_CONTROL_QUEUE admission unauthenticated=401 inactive=403 denied=403 allowed_get=200 allowed_head=200 mutations=0 PILOT_ONLY`

### B. Small-page projection and escaping

Allowed `GET` SHALL render exactly four row identities in order `451201,451202,451203,451204`; `451299` and its sentinel text SHALL be absent. It SHALL show:

- escaped visible bytes `ул. &lt;Тестовая &amp; 1&gt;`, `REG-&amp;-001`, `Инженер &lt;Событие&gt;`, with no unescaped injected elements;
- engineer event snapshot `7302` for `451201`, fallback `7301`/`Инженер Фолбэк` for `451202`, and `Инженер не назначен` for `451203`;
- canonical hrefs `/pilot/construction-control/objects/451201/checklist` through `.../451204/checklist`, once per row, and no non-positive/foreign checklist href;
- first two rows with exact text `Инспекций ещё не было` and `data-completed="false"`/`"true"` respectively;
- `451203` activity derived from literal later `photo_uploaded` time, not the earlier item operation; `451204` follows it because its activity timestamp is later.

Exact relative-time wording for activity rows SHALL be accepted only when independently bounded by worker wall clock. If a date boundary makes the expected branch ambiguous, the entire private run SHALL be discarded and retried within the deadline; the normalized transcript SHALL not contain that wording.

Stable milestone:

`CONSTRUCTION_CONTROL_QUEUE projection working=4 excluded_nonworking=1 order=451201,451202,451203,451204 engineer=event,fallback,absent activity=none,none,max,max pto=false,true,false,false escaped=1 mutations=0 PILOT_ONLY`

### C. Pagination-before-client-filter contrast

The two pagination responses SHALL satisfy the literal 50/1 identities and footers above. Source inspection SHALL separately prove that `control-queue.js` applies `mine/all`, completed and search filters after receiving that page; browser JS SHALL not execute in this characterization.

Stable milestone:

`CONSTRUCTION_CONTROL_QUEUE pagination total=51 page1=50 page2=1 assigned_only_on_page2=8101 browser_filter=not_executed PILOT_ONLY`

### D. Exact infrastructure failures

Each case starts from an otherwise valid admitted fixture and SHALL return status `503`, exact UTF-8 body `Service unavailable.\n` including trailing LF, `Content-Length: 21`, SHA-256 `38c9439b9ab2abf40304675451d0fae7069809a7e3c8fe0ef96274c8680f21eb`, base CSP and `Retry-After: 60`:

1. `page=0`;
2. `page=abc`;
3. page one greater than the computed last page (`page=2` for small fixture with one page);
4. required queue SQL read denied to runtime principal;
5. malformed selected row with empty address;
6. malformed newest engineer event JSON.

Every failure SHALL leave all owned schema/rows/files/sessions and ambient decoy unchanged. Exact error bytes are current infrastructure presentation, not target failure semantics.

Stable milestone:

`CONSTRUCTION_CONTROL_QUEUE failures page_zero=503 page_text=503 page_range=503 sql_denied=503 malformed_row=503 malformed_event=503 body_sha256=38c9439b9ab2abf40304675451d0fae7069809a7e3c8fe0ef96274c8680f21eb mutations=0 PILOT_ONLY`

### E. Repeat and concurrent reads

Two sequential allowed GETs and two independently authenticated simultaneous workers SHALL observe byte-equivalent normalized row identities/projection tokens from one immutable fixture. There is no domain idempotency key for a read. No request SHALL create/update audit, history, session, cookie, timestamp, auto-increment, file or domain fact.

Stable milestone:

`CONSTRUCTION_CONTROL_QUEUE reads sequential=2 concurrent=2 equivalent=1 db_mutations=0 file_mutations=0 session_mutations=0`

## Isolation, anti-fake and cleanup

1. Caller SHALL provide exactly one unpredictable 12-lowercase-hex `FMONITOR_CONTROL_QUEUE_VERIFY_RUN_TOKEN` and exact repository-owned directory `FMONITOR_CONTROL_QUEUE_VERIFY_ARTIFACT_ROOT`. Missing/malformed/symlinked/out-of-bound/occupied inputs fail before mutation. `/`, `/tmp`, home roots and fallbacks are forbidden.
2. Token derives one ASCII `[a-z0-9_]+` SQL prefix whose longest exact table name remains ≤64 bytes, one direct artifact child `construction-control-queue-<token>`, one session child and one request-log file below that owned child. No wildcard ownership is authorized.
3. Exact owned SQL inventory SHALL be declared in Gate 2 test before mutation and include only minimum canonical identity/RBAC, installation-case/process-event/checklist-operation and legacy object tables required by fixtures. Occupied exact names are rejected; no reuse, repair, truncate, rename or drop is allowed.
4. Privileged setup connection MAY create/seed owned fixture tables. Runtime worker SHALL use a separately created least-privilege principal and SHALL not receive DDL privileges. Direct SQL is allowed only for setup, independent snapshots/sensitivity mutation and exact cleanup, never as behavioral seam.
5. Privileged setup connection SHALL создать ровно четыре unique MariaDB runtime accounts at exact validated loopback host: `fm2_ccq_<token>_s` (все sequential request groups), `fm2_ccq_<token>_a` (concurrent worker A), `fm2_ccq_<token>_b` (concurrent worker B), `fm2_ccq_<token>_x` (sensitivity double). Username length SHALL оставаться в MariaDB limit. Каждому выдаётся одинаковый grant profile: только `SELECT` на exact owned fixture tables и никаких global/schema write, `CREATE TEMPORARY TABLES`, `EXECUTE`, `FILE`, `SUPER`, `PROCESS` или administrative privileges. Setup/cleanup/observer connections SHALL использовать distinct privileged test credentials и MUST NOT быть доступны HTTP workers. Effective grants всех четырёх accounts проверяются до worker start; fixture creation/inserts завершаются privileged setup connection до read-only runtime.
6. До изменения server state meta-test SHALL read-only preflight, что `performance_schema` включена, statement instruments для SQL statements enabled+timed, consumer `events_statements_history_long` enabled и history capacity достаточна для bounded request budget. Harness MUST NOT включать/изменять instruments, consumers, history sizes, general log, audit plugins или другую global configuration. Неуспешный preflight — `SETUP_FAILURE` до behavioral request.
7. Для каждого request test-owned router SHALL остановиться на barrier сразу после production composition установила runtime DB connection и до dispatch. Все non-concurrent requests SHALL использовать account `_s` строго последовательно: предыдущий response audit и connection teardown обязаны завершиться, observer обязан доказать zero active `_s` connections, и только затем стартует следующий request. Independent observer SHALL для exact slot username разрешить ровно один active connection, связать его `PROCESSLIST_ID` с `performance_schema.threads.THREAD_ID`, записать текущий maximum `EVENT_ID`, затем release dispatch. После получения application response router SHALL остановиться на втором barrier до teardown/connection close; observer SHALL прочитать все новые statement events exact `THREAD_ID` и доказать отсутствие statement classes/SQL verbs `INSERT`, `UPDATE`, `DELETE`, `REPLACE`, `CREATE`, `ALTER`, `DROP`, `TRUNCATE`, `RENAME`, `GRANT`, `REVOKE`, transaction write и stored-program invocation. Denied и rolled-back attempts считаются failure. Неоднозначный/missing/extra connection, gap/overflow, truncated/unclassifiable statement или teardown до audit — `SETUP_FAILURE`, не success.
8. Concurrent scenario SHALL запустить worker A только с account `_a` и worker B только с account `_b`. Observer SHALL увидеть ровно один active connection каждого username и никаких иных active connections четырёх owned usernames, независимо связать два `PROCESSLIST_ID` с двумя distinct `THREAD_ID`, записать оба initial `EVENT_ID`, и только затем одним test-owned release разрешить обоим dispatch. Оба workers SHALL остановиться на response barriers; observer SHALL audit обе exact thread histories до release teardown любого worker. Один account/thread hint не может поступать от production response или verifier stdout. Missing, duplicate, shared или extra mapping — `SETUP_FAILURE`.
9. Before every request group and immediately after it, meta-test SHALL fingerprint exact table definitions, all rows and auto-increment metadata, owned filesystem tree, owned session directory and an unpredictable ambient decoy sibling. Comparison occurs before cleanup. Statement audit дополняет, а не заменяет fingerprints/public HTTP observation.
10. Request log SHALL contain an independently generated per-run nonce, method, raw path/query and worker slot (`serial`, `concurrent-a`, `concurrent-b`) for every exchange. Meta-test SHALL prove expected exchange cardinality from this log and independently parse HTTP bytes, DB state, effective grants and statement history; verifier stdout/status alone are insufficient.
11. Sensitivity SHALL использовать отдельный test-owned double только с account `_x`: между теми же barriers он выполняет literal `INSERT` в exact owned sentinel table, ловит denial и пытается `ROLLBACK`. Observer MUST независимо связать единственный `_x` connection/thread и увидеть DML attempt; sentinel fingerprint MUST остаться прежним; meta-test MUST отклонить run. Если attempt не видна, audit preflight считается недостоверным и основной characterization не может стартовать. Double не является production seam и не участвует в success transcript.
12. Worker startup/readiness, every socket exchange, both DB barriers, audit query and process exit SHALL have bounded deadlines. Success or failure reaps exact child PIDs, closes all runtime/setup/observer connections, removes only exact owned tables, four exact runtime accounts, artifact/session children, and proves no owned member/account remains. Every account name/host/grants is validated exact before `DROP USER`; duplicate/unexpected owned username blocks destructive cleanup and yields `SETUP_FAILURE`. Process-name scans, broad kills, wildcard SQL/user cleanup and recursive broad cleanup are forbidden.
13. Verifier SHALL run twice with distinct unoccupied tokens. Normalized stdout SHALL be byte-identical and stderr empty. Ports, PIDs, connection/thread/event ids, nonces, tokens, usernames, prefixes, database/path names, SQL, secrets, live timestamps and translated relative-time text SHALL not appear.

## Stable normalized transcript

Successful normalized stdout SHALL contain exactly these six LF-terminated lines in this order and no others:

```text
CONSTRUCTION_CONTROL_QUEUE admission unauthenticated=401 inactive=403 denied=403 allowed_get=200 allowed_head=200 mutations=0 PILOT_ONLY
CONSTRUCTION_CONTROL_QUEUE projection working=4 excluded_nonworking=1 order=451201,451202,451203,451204 engineer=event,fallback,absent activity=none,none,max,max pto=false,true,false,false escaped=1 mutations=0 PILOT_ONLY
CONSTRUCTION_CONTROL_QUEUE pagination total=51 page1=50 page2=1 assigned_only_on_page2=8101 browser_filter=not_executed PILOT_ONLY
CONSTRUCTION_CONTROL_QUEUE failures page_zero=503 page_text=503 page_range=503 sql_denied=503 malformed_row=503 malformed_event=503 body_sha256=38c9439b9ab2abf40304675451d0fae7069809a7e3c8fe0ef96274c8680f21eb mutations=0 PILOT_ONLY
CONSTRUCTION_CONTROL_QUEUE reads sequential=2 concurrent=2 equivalent=1 db_mutations=0 file_mutations=0 session_mutations=0
CHARACTERIZATION_OK CHARACTERIZE-CONSTRUCTION-CONTROL-QUEUE-001
```

Gate 3 SHALL pin exact spec, test and expected-transcript SHA-256. Verifier hash is not Gate 3 input and is pinned only at Gate 5.

## Sensitivity requirements

Gate 2 meta-test MUST demonstrate that otherwise plausible fake/broken implementations fail independently when they:

- return static expected HTML without reaching the logged production HTTP seam;
- skip permission denial or query queue facts before denial;
- include non-working `451299`, reverse the two activity rows, ignore event precedence or use earlier activity;
- paginate after selecting actor `8101` rather than producing the literal 50/1 server pages;
- emit unescaped fixture markup or a wrong checklist href;
- return `503` with wrong body byte, missing LF, wrong CSP/Retry-After/Content-Length;
- attempt DML then catch/rollback/restore it: SELECT-only grant SHALL prevent persisted write and independent per-thread history SHALL still make the attempt fail;
- create a session/cookie/file, damage decoy or leak an owned resource;
- serialize/fabricate the two-worker scenario without both request-log entries and barrier overlap.

At least one deliberate expectation perturbation SHALL produce assertion failure while setup remains healthy. Sensitivity work remains test-only.

## Failure classification

- `SETUP_FAILURE`, exit `2`: unavailable test MariaDB/assets; invalid or occupied ownership; fixture/four-runtime-account/grant failure; unavailable/disabled/insufficient `performance_schema` statement instrumentation/history; missing/extra/ambiguous per-slot connection or runtime thread, non-distinct concurrent mapping, history gap/overflow or unclassifiable statement; bind/readiness/socket/barrier/reaping failure; incomplete independent log/snapshot; ambiguous clock boundary beyond retry budget; or inability to audit/clean exact owned resources/accounts.
- Qualifying Gate 2 `RED`: focused verifier absent, or healthy isolated public-seam test demonstrates failure of one exact statement in this approved contract.
- `ASSERTION_FAILURE`, exit `1`: intentional Gate 2 absence/expected mismatch before characterization exists.
- `REGRESSION_FAILURE`, exit `1`: after implementation, HTTP/header/body/projection/transcript drift, mutation, nondeterminism, sensitivity failure, decoy damage or cleanup leak.

Setup/environment failure is never RED. RED evidence SHALL retain exact command and relevant output for fresh independent Gate 3 review.

## PILOT_ONLY hazards and explicit non-goals

Characterized but not approved: `construction_control.read`; broad working-case visibility; current role union; event-by-highest-id precedence; legacy engineer fallback; absence label; `MAX(device_time)` across any checklist operation; `ptoactdate IS NOT NULL`; null-first/oldest-first ordering; page size 50; out-of-range `503`; exact Russian labels; live-clock relative labels; current security/error headers.

Not exercised or inferred: target assignment visibility, per-engineer server filter, meaning of inspection/activity/completion, schedule/calendar/cadence/overdue, engineer assignment/reassignment, checklist/photo/section mutation, offline queue, sessionStorage, IndexedDB, service worker, prefetch, search/completed filters, browser accessibility/visual behavior, pagination redesign, target public API/module, runtime DDL/schema ownership, production/primary evidence, secrets and migration cutover.

Every target behavior above remains `NEEDS_GRILL` or a separate Gate 1 slice. Future work MAY cite this file only as compatibility contrast.

## Done definition

This draft satisfies only executable-spec authoring task 1.1. The slice is Done only after:

1. fresh independent reviewer returns `READY_FOR_OWNER_REVIEW` for exact hash;
2. owner append-only approves that exact reviewed hash, enabling only Gate 2;
3. separate RED author demonstrates intended RED;
4. fresh independent Gate 3 reviewer records `APPROVED` for spec/test/transcript hashes;
5. minimal test-only GREEN passes focused twice and canonical characterization without production or rapid-pilot changes;
6. relevant RBAC/planning/queue regressions, lint, `git diff --check`, `make architecture-check` and `make verify` are honestly recorded (`VERIFY_OK` is not inferred);
7. a different fresh independent Gate 5 reviewer records `APPROVED` and verifier hash;
8. inventory/status keep every listed hazard `PILOT_ONLY`, then OpenSpec lifecycle proceeds only on a separate explicit command.

No reviewer SHALL approve their own work. This v0.1 remains DRAFT until independent review and exact-hash owner approval.
