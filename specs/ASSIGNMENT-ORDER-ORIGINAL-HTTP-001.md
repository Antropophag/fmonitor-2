# ASSIGNMENT-ORDER-ORIGINAL-HTTP-001 — original evidence в портале

Версия: 0.2. Дата: 2026-09-05. Статус: **DRAFT / GATE 1 NOT APPROVED**.
Public route/API choices ниже — review candidate, не delivered behavior.

## Простыми словами

Сотрудник и Руководитель ФКР загружают PDF-оригинал через карточку объекта,
исправляют ошибки новой revision и видят сохранённую историю. Разрешённые
читатели скачивают конкретную неизменяемую revision. ОТиЗ имеет доступ ко всем
распоряжениям; инженер — по закреплённым объектам. Загрузка сама работы не
открывает и не меняет ранее действовавший состав.

## 1. Источники, actors и владение

Нормативная база: approved `ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001`, pilot spec,
owner decision `docs/operations/original-http-reader-owner-decision-2026-09-05.md`.
Legacy `PilotE2ECoordinator` — oracle прежнего wiring, не источник новых
expected domain facts. Protected `PILOT-E2E-FLOW-001` этим draft не изменяется.

Mutation проходит только через `submitAssignmentOrderOriginal`. HTTP adapter
не выполняет domain SQL и не формирует accepted evidence самостоятельно.
Read/history/download использует отдельный read-only application seam, не
verification evidence reader. Actor берётся только из authenticated session.

## 2. Candidate routes и transport

`objectId` и `version` — decimal positive integer без leading zeros,
представимые PHP int. `revisionId` — opaque identity из accepted result,
не filesystem path; exact URI grammar согласуется с command ID grammar до
Gate 1 approval. Route parser отклоняет traversal/encoded separator/лишние
segments до lookup.

| Method | Path | Назначение |
|---|---|---|
| POST | `/pilot/objects/{objectId}/assignment-orders/{version}/originals` | INITIAL или CORRECTION |
| GET, HEAD | `/pilot/objects/{objectId}/assignment-orders/{version}/originals` | Metadata/history |
| GET, HEAD | `/pilot/objects/{objectId}/assignment-orders/{version}/originals/{revisionId}/download` | Exact revision PDF |

Unsupported method для распознанного route: 405 с exact Allow для этой строки.
Unknown route: 404 согласно существующему route/session admission contract.
Transport не содержит registration number, actor ID, arbitrary composition,
private storage identity либо filesystem path.

POST принимает ровно один multipart file field `original` и single scalar
fields: `csrfToken`, `requestId`, `mode`, `documentDate`,
`compositionConfirmed`, `rootOriginalId`, `targetRevisionId`,
`expectedCurrentRevisionId`, `correctionReason`. Duplicate, array-shaped,
unknown/missing fields отклоняются как malformed transport до command.
Mode exact `INITIAL|CORRECTION`; boolean exact strings `true|false`.
Nullable fields представлены пустой строкой для INITIAL. Date и reason затем
валидирует command. Для CORRECTION все lineage identities и reason обязательны.

File bytes limit остаётся `20,971,520`. Proposed total multipart cap:
`21,037,056` bytes (file limit + 65,536 overhead). Exact missing/false
Content-Length, streaming/chunked and proxy/PHP truncation mapping обязательно
закрываются Gate 1 transport review; никакой silent empty upload fallback.

## 3. Admission и command mapping

Session/Host/URI/CSP admission наследует approved pilot contracts. После
authentication POST проверяет CSRF и local exact upload/correct permission.
Domain command повторно проверяет соответствующий process capability; local
grant не заменяет domain authorization. Pre-command denial не создаёт original
facts. Safe admission audit следует IdentityAccess contract, не domain audit.

Application context resolver связывает exact object/version с case/order IDs;
HTTP не доверяет case/order из body. Resolver read API и его consistency с
command lookup требуют exact declaration до Gate 1. Шаблон не обязателен, но
распоряжение с выбранным immutable составом должно существовать. Если current
selection API ещё не умеет создать такой order без render, direct-upload
сценарий BLOCKED_PREDECESSOR; обход через скрытую генерацию шаблона запрещён.

Request identity сохраняется UI для повторного отправления того же intent
после потери ответа. При изменении пользователем намерения UI создаёт новую
identity. Command replay precedence не заменяется HTTP comparison payload.

POST возвращает JSON exact command Result из 11 fields, в порядке command
contract, с enum strings в их canonical serialized form и final LF. Adapter
не включает filename/path/raw exception/SQL. Proposed status mapping:

| Command outcome | HTTP |
|---|---:|
| ACCEPTED | 201 |
| REPLAYED | 200 |
| REJECTED/AUTHORIZATION_DENIED | 403 |
| REJECTED/ORDER_NOT_FOUND | 404 |
| REJECTED/FILE_TOO_LARGE | 413 |
| прочий REJECTED | 422 |
| CONFLICT | 409 |
| FAILED | 503 |

503 сохраняет retryable/result reason и добавляет `Retry-After: 60`.
### Exact pre-command error candidate

Recognized original routes используют JSON envelope ровно из одного string
field `error`, final LF, `Content-Type: application/json; charset=UTF-8`,
`Cache-Control: no-store`, `X-Content-Type-Options: nosniff`, exact byte
Content-Length и inherited security headers. Поля requestId/evidence не
добавляются, поскольку domain command ещё не вызван.

После inherited Host/URI/session-initialization failure precedence, порядок:
unsupported method → authentication → declared transport cap/media admission
→ bounded multipart shape → CSRF → local exact capability → context resolution
→ command. Body не читается application adapter до authentication. Web-server
или PHP предварительное buffering не считается domain stream read и требует
отдельного bounded deployment proof.

| Условие до command | HTTP | error |
|---|---:|---|
| Unsupported method | 405 | METHOD_NOT_ALLOWED |
| Нет authenticated actor | 401 | AUTHENTICATION_REQUIRED |
| Total body выше cap | 413 | REQUEST_TOO_LARGE |
| POST media type не multipart/form-data | 415 | UNSUPPORTED_MEDIA_TYPE |
| Некорректный multipart/scalar shape | 400 | INVALID_REQUEST |
| CSRF невалиден/отсутствует | 403 | CSRF_INVALID |
| Local capability отсутствует | 403 | ACCESS_DENIED |
| Authorized context отсутствует | 404 | NOT_FOUND |
| Admission/context dependency недоступна | 503 | SERVICE_UNAVAILABLE |

CSRF field отсутствует при otherwise complete request → CSRF_INVALID;
duplicate/array csrfToken остаётся malformed shape. Истёкшая/revoked session
проверяется до CSRF и body shape. 405 включает exact Allow. 503 включает
`Retry-After: 60`. HEAD возвращает такой же status/headers/Content-Length,
но пустой body. Unknown routes продолжают inherited plain-text 404; новый
JSON envelope не меняет весь pilot router.

После вызова command используется только Result mapping выше; pre-command
envelope не заменяет application rejection/retryable failure.

## 4. Read authorization

Каждое чтение требует active user/role и explicit local
`assignment_order.original.read`. Scope — объединение явно разрешающих ролей:

- `fkr_operator`, `manager`: объекты, доступные пользователю;
- `construction_control_engineer`: закреплённые за ним объекты;
- `otiz_specialist`: все распоряжения, включая прошлые revisions;
- administrator-only: нет автоматического разрешения.

Exact source of object access/current assignment должен быть определён до
Gate 1. Нельзя ограничить ОТиЗ assignment predicate или вывести право изменения
из глобального чтения. Grant/revocation действует при каждом запросе, в том
числе repeat, HEAD и скачивании исторической revision.

## 5. Metadata, history и download

Candidate metadata envelope: `objectId`, `assignmentOrderVersion`,
`rootOriginalId`, `currentRevisionId`, `revisions`. Revisions отсортированы по
revisionNumber ascending; каждый элемент содержит `revisionId`,
`previousRevisionId`, `revisionNumber`, `documentDate`, `sha256`, `byteSize`,
`uploadedAt`. Exact correctionReason/actor display disclosure не добавляется
до отдельного утверждения read DTO. Private paths/filenames не выдаются.

Authorized order без original: metadata 200 с null root/current и empty list.
Unknown order/revision: 404 без metadata. Read denial: 403 до private bytes.
Storage missing/inconsistent/unavailable mapping должен различаться в exact
Gate 1; нельзя отдавать 200 с partial/непроверенными bytes.

Download выбирает explicit revision, не неявный current leaf. До successful
headers reader доказывает digest/size по stored evidence и bytes. При успехе:
200, `Content-Type: application/pdf`, `Content-Length: <exact byteSize>`,
`Content-Disposition: attachment; filename="assignment-order-original.pdf"`,
`Cache-Control: no-store`, `X-Content-Type-Options: nosniff`.
HEAD проходит те же authorization/integrity checks и даёт те же headers без
body. Range и conditional request headers не сокращают выдачу: после обычной
authorization/integrity проверки сервер отдаёт полный 200 (HEAD без body),
не возвращает 206/304 и не посылает ETag/Last-Modified. Это candidate policy
для данного private download route, не изменение других assets.
Read не создаёт domain facts, view markers, corrections или mutation timestamps.

## 6. Independently fixed examples

Command Example A: request `00000000-0000-4000-8000-000000000001`,
case 4512/order 81, actor 18, date 2026-09-01, accepted revision 1 с PDF
SHA `4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784`,
327 received bytes. Сопоставление public object/version с case/order задаёт
fictional resolver fixture, а не private HTTP SQL.

Expected upload: HTTP201 и exact accepted command fields; повтор HTTP200 с
REPLAYED, без новой revision. Metadata и download revision 1 воспроизводят
этот digest/size и остаются одинаковыми после correction/restart. Unauthorized
actor не получает эти fields. OTIZ actor с read grant скачивает revision
несвязанного с инженером объекта; engineer без assignment получает denial.

Future date, malformed PDF, oversized file, stale correction и revoked grant
проверяются отдельно. При всех upload outcomes composition/opening/checklist
facts остаются в пределах approved command no-mutation contract.

## 7. Gate 1 readiness и Done

До RED закрыть перечисленные exact transport/URI/scope/resolver/read-port
contracts, preflight/stream consistency и legacy route disposition. Получить
independent Gate 1; complete command Gate 5 остаётся implementation predecessor.
Tests должны проходить real HTTP entrypoint, fictional dataset и независимые
observable expected values. Production changes только после demonstrated RED
и fresh Gate 3, затем relevant regressions/architecture и fresh Gate 5.

Done: все routes/role matrix/direct+template/correction/replay/download/history
и restart доказаны на exact SHA, full VERIFY_OK получен, HTTP не владеет domain
facts. Protected legacy tests не объявляются доказательством нового workflow.
