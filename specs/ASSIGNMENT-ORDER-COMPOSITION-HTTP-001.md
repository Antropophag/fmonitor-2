# ASSIGNMENT-ORDER-COMPOSITION-HTTP-001

Версия0.1,2026-09-07; Gate1 required.

## Простыми словами

ФКР выбирает состав и сохраняет распоряжение без обязательного PDF. При желании
скачивает шаблон отдельной кнопкой. Ожидающий оригинала состав можно заменить.
Ни выбор, ни PDF не применяют состав и не открывают работы.

## 1. Scope and public seam

Actor — active fkr_operator/manager с exact assignment_order.composition.select.
Inherited contracts: ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 v0.11, native Gate5
ASSIGNMENT-ORDER-SELECTION-NATIVE-001-native-v1, selected-original-binding Gate5,
ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001 v0.2/full Gate5. Existing approvals reused.
Owner2026-09-07 разрешил использовать только public shlz-ui без ServiceDesk source.

Public seam — реальный HTTP через rapid-pilot/router.php → canonical production
PilotHttpEntrypoint, с native session/LocalAuth и production factories.
`FMONITOR_FRESH_ORDER_FLOW=1` явно включает новый пустой процессный контур.
Это deployment selection, не migration/history compatibility. Без флага новые
routes404; старый preview image/защищённый E2E не меняются этим включением.

Внутренний public read-only application seam composition owner:
`readSelectionPortal(objectId,actorId): array` с status found/rejected/failed,
reasonCode; при found — caseId, objectId, object (address,entrance,registrationNumber,
plannedStartDate,plannedFinishDate), selectionRevision, latest nullable immutable
selection summary (orderId/version, installer snapshots, engineer snapshot,
hasAcceptedOriginal), candidates (employed installers with provenance; active
local control engineers), lastTemplateDate nullable. No private bytes/paths.
HTTP не читает/пишет domain SQL. Query авторизует до object/history/candidates;
object/selection/candidates читаются одним consistent snapshot; lastTemplateDate
читается отдельно approved dateReader для exact latest order identity из этого
snapshot (reader владеет своей transaction). Не объявлять общий snapshot каталога
и момента формирования PDF. No writes/audit, unavailable fails closed. Fact ownership и
query dependencies остаются в AssignmentOrderComposition.

## 2. Routes and UI

| Method/path | Result |
|---|---|
| GET/HEAD /pilot/objects/{objectId}/assignment-order/selection | 200 form/read projection |
| POST тот же path | native select command |
| POST /pilot/objects/{objectId}/assignment-orders/{orderId}/template | native on-demand PDF |

IDs — canonical positive PHP_INT_MAX decimal. Иные методы405 + Allow. Malformed
path400/404 следует inherited admission. Query string не заменяет command input.
Authenticated actor/CSRF берутся только из native session; HTTP headers/body с
actorUserId не задают actor. Anonymous normal router перенаправляет303 на login;
invalid/revoked session не может вызвать domain command. У подставленного
REMOTE_USER HTTP header нет эффекта. Missing permission/role403; inactive session следует inherited303 login;
actor, потерявший активность после session admission, получает403 на application seam;
authorization infrastructure failure503; missing object/case404.

GET показывает объект, текущий immutable выбранный состав (если есть), каталог
монтажников с source/time, radio инженера и его явное подтверждение. Монтажники
выбираются native shlz-checkbox (без зависимости от JS), инженер shlz-radio.
Все значения escaped; label/fieldset/legend и visible keyboard focus обязательны.
Shell — существующий PilotView::document, public shlz styles; no private UI copies.
Если pending selection есть, форма сохраняет mode=replace_pending и latest revision;
иначе mode=new_order (в том числе после accepted original). Скрытые requestId UUID
и expectedSelectionRevision сопровождают форму. GET никогда не создаёт selection,
PDF, original, assignment, opening или checklist facts. HEAD body empty.

После успешного выбора GET показывает сохранённый snapshot и отдельную POST
кнопку «Скачать PDF-шаблон». Latest template date показывается при наличии.
UI называет состав выбранным, не применённым. Нет ручного номера/registration gate,
нет заявлений о доступном opening или готовом upload, пока следующие slices не wired.
Существующая ссылка из object card на /assignment-order/prepare при fresh flag
(GET/HEAD) ведёт303 на selection route. Это обеспечивает достижимость новой формы.

## 3. Selection command input and response

POST Content-Type application/x-www-form-urlencoded (optional charset=utf-8),
body≤32768 received bytes. Превышение413, иной media415. Поля ровно:
csrfToken, requestId, mode, expectedSelectionRevision, controlEngineerUserId,
controlEngineerConfirmed, installerTabIds[] (0..500 entries). Нет scalar duplicates,
unknown fields, nested bracket keys, malformed percent escapes:400 invalid_request.
Omitted installer list превращается в пустой список; omitted engineer/confirmation
→422 control_engineer_required/confirmation_required. Confirmation ровно yes.
Scalar numeric strings canonical: engineer/installer positive PHP_INT_MAX,
revision0..4294967295; UUID lower-case canonical v4 и mode new_order/replace_pending.
Неверная shape400, duplicate installer IDs делегируются domain invalid_command.

CSRF constant-time equality с trusted session token; mismatch403 csrf_invalid,
no command. Missing/invalid actor до body decoding. Admission errors safe log
`FMONITOR_ORDER_HTTP_REJECT status=<code> reason=<allowlisted-code>` в stderr,
без cookie, CSRF, filenames, body, user data или exception text. Domain command
сам сохраняет свои approved audits; HTTP их не дублирует. GET errors no domain audit.

Valid selection вызывает только ProductionAssignmentOrderCompositionFactory::create
→selectAssignmentOrderComposition. Returned selected/replayed:303 Location на GET
selection; no extra facts on semantic replay. Errors: object_not_found404,
authorization_denied403, invalid_command400, остальные rejected422, conflict409,
failed503 (включая retryable=false allocation_capacity_exhausted). Exact domain
reason code сохраняется в data-error-code escaped HTML, понятная RU причина и
ссылка вернуться к форме. Failed retryable=true дополнительно содержит retry form
с теми же intent fields/requestId; это не новый intent. После 4xx новая GET форма
создаёт новый requestId с актуальной revision. HTTP не повторяет command сам.

## 4. PDF request

POST body only csrfToken, те же bounds/media/CSRF. Object→case берётся из authorized
query, case/order mismatch404. Только ProductionAssignmentOrderTemplateFactory
→generateAssignmentOrderTemplate(caseId,orderId,actorId); no prepare or stored renderer.
Success200 application/pdf, Content-Length exact received bytes,
Content-Disposition attachment; filename="assignment-order.pdf" плюс RFC5987 UTF8
filename* из approved constant, nosniff, Cache-Control:no-store, base CSP.
No template files/versions. Latest successful Moscow template date и audit сохраняет
approved owner; repeated POST может сформировать новый PDF/audit, selection identity
не меняется. Stale order409 target_not_current, not found404, denied403,
other rejected422, failed503. Errors никогда не имеют PDF media/body. GET405 не
формирует PDF. Source/PDF failure не показывает success и не изменяет latest date.

## 5. Fresh route disposition / exclusions

В fresh flow POST /assignment-order/prepare, все методы /assignment-orders/{id}/registration,
POST /control-engineer и POST /open возвращают410 fresh_route_unavailable до вызова
legacy writer. Открытие подключается отдельным следующим slice. Старые order/appendix/
signed_original artifact routes410 в fresh mode, поскольку старых files/history нет.
Не удалять legacy implementation ради этого wiring; не добавлять compatibility writers.
Explicit env flag не выдаёт grants. Bootstrap и clean native deployment следуют отдельно.

## 6. Worked examples and verification

Fictional fixture: actor18 FKR, engineer73, installers7001/7002 employed, object/case4512,
allocator next81, latest selection absent; planned2026-10-05..2026-12-20.
GET200 shows candidates/provenance, revision0/mode new_order; domain facts unchanged.
POST uuid...000001,new_order,revision0,7001,73,confirmed yes→303; registered public
composition reader returns order81/version1/installers[7001]/engineer73. No artifacts,
original, applied assignment, opening or checklist mutations. Identical POST→303,
all selection rows/audits byte-equivalent. GET then replace_pending/revision1.
POST uuid...000002,replace_pending,revision1,7002→order82/version2/revision2; old81
unchanged. Stale revision0 with new UUID409. After accepted original public command,
GET proposes new_order with current revision; HTTP new_order creates next identity.

PDF82→200 valid real PDF with selected7002/engineer73, today's Moscow date, one
additional process event and date projection; no template artifacts. PDF81→409.
After two templates latest date correct, identity unchanged. Negative cases include
CSRF, forged actor field/header, permission revoke, manager permission, admin-only,
invalid method/media/size/duplicate scalar/malformed escape, no installer/engineer/
confirmation, bad object, stale request conflict, source failure and disabled flag.
Public raw HTTP tests use native session/LocalAuth, production factories, fictional
native MariaDB and real renderer; no production import or native interception.
Before RED demonstrate native command/renderer setup independently. Tests derive
expected IDs/history from these examples and approved command outcomes. Read-only
observations may inspect fixtures; commands run only through public HTTP seam.
Gate1→RED→independent Gate3→GREEN→native regression/architecture/UI checks→Gate5.
Browser visual/keyboard QA and public shlz visual/focus/Impeccable gates before Done.
