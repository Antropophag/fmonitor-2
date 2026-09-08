# ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-HTTP-001

Версия0.3,2026-09-07; independent Gate1 required.

## Простыми словами

После выбора состава ФКР может загрузить подписанный PDF сразу или после
необязательного шаблона. Ошибочный оригинал исправляется новой загрузкой с причиной.
Дата подтверждается по документу. Ни загрузка, ни исправление состав не применяют
и работы не открывают. Это операторский write/UI slice; общий history/download
для ОТиЗ/назначенных инженеров остаётся в parent original-HTTP change.

## 1. Inherited authority and seams

Reuses approved ASSIGNMENT-ORDER-ORIGINAL-UPLOAD-001, selected-original binding,
ORIGINAL-ATTEMPT-AUDIT-001 и template/date reader. Native behavior не меняется.
Public HTTP — real rapid-pilot/router.php → canonical entrypoint, fresh flow1.
POST /pilot/objects/{objectId}/assignment-orders/{orderId}/originals — submission;
GET/HEAD /pilot/objects/{objectId}/assignment-orders/{orderId}/originals/submit — form.
IDs positive canonical PHP int; это assignmentOrderId, не version. Other methods405
с Allow POST или GET, HEAD. Anonymous/revoked native session следует inherited303
login до application adapter; actor никогда не задаётся HTTP header/body.

Mutation only ProductionAssignmentOrderOriginalFactory::createForSelections
→submitAssignmentOrderOriginal. HTTP передаёт IDs из authorized context resolver,
CSRF/local exact permission; native command повторно проверяет process capability.
Original bytes/private metadata создаёт только approved original application.

Public read-only submission-context owner в AssignmentOrderOriginal:
resolveSubmissionContext(actorId,objectId,orderId,mode) возвращает case/order и
immutable composition после active FKR/manager + exact local upload/correct grant.
readSubmissionForm(actorId,objectId,orderId) дополнительно требует explicit local
assignment_order.original.read и exact grant текущего действия (initial если root
отсутствует; correction иначе). DTO: objectId,caseId,orderId,orderVersion,composition
(installers/engineer snapshots), mode, current original nullable (rootId,revisionId,
revisionNumber,documentDate,sha256,byteSize,uploadedAt), suggestedDocumentDate.
Root/leaf metadata проверяется approved StoredReader в readonly snapshot и должна
совпадать с immutable registered composition. No filesystem paths/filename/hash
inventory. Query не пишет audit/facts. Private bytes не читаются для формы.
Нет object/order→context fallback через old prepare/physical writer.

## 2. Binary transport

PDF передаётся raw body с Content-Type application/pdf, один File из UI.
Это техническая замена unapproved multipart candidate: PHP auto multipart parsing
теряет duplicate scalar fields; transport не должен молча принимать их.
Content-Length обязателен, canonical decimal0..20971520; missing411 LENGTH_REQUIRED,
некорректный400 INVALID_REQUEST, больше cap413 REQUEST_TOO_LARGE. Transfer-Encoding
не допускается (400 INVALID_REQUEST). Application reads bounded native byte stream;
declared/native-stream mismatch, наблюдаемый adapter, не вызывает command и
даёт400 INVALID_REQUEST. Content-Length относится к framed request/native input,
не ко всем bytes TCP connection. Raw short/extra framing может быть отвергнут
HTTP server до PHP: тогда application JSON/status promise не применяется.
Characterized PHP CLI8.5 закрывает такие соединения без response; проверяется
closed-not-timeout + healthy server после запроса + unchanged original facts.
Это server rejection, не успешный upload и не skip. Другой deployment server
может вернуть собственный400; его bounded framing проверяется отдельно. До command
считывается максимум20MiB+1; временный memory stream принадлежит adapter и закрывается
во всех outcomes. PHP/webserver preliminary buffering отдельно ограничивается
launch deployment; текущий controlled CLI server — loopback synthetic evidence.

X-FMonitor-Original — canonical standard base64 (с padding) от minified UTF8 JSON,
≤16384 ASCII bytes. JSON exact ordered keys:
csrfToken,requestId,mode,documentDate,compositionConfirmed,rootOriginalId,
targetRevisionId,expectedCurrentRevisionId,correctionReason,originalFilename.
Encoding соответствует JSON.stringify (unescaped Unicode/slashes, включая U2028/29).
Decode→canonical re-encode обязан совпасть byte-for-byte: duplicate keys, extra keys,
whitespace/alternate encoding не принимаются. Это закрытый transport contract.
Scalar fields: первые четыре и filename strings; confirmed bool; lineage/reason
string|null. Mode initial|correction; shape ошибок400 INVALID_REQUEST.
requestId canonical lowercase UUID v1–5 (inherited command grammar; UI генерируетv4).
Nullable initial/correction/date/reason business validity затем решает command.
Malformed/missing metadata400, media415 UNSUPPORTED_MEDIA_TYPE. Ни body, ни metadata,
ни CSRF, ни имя файла не печатаются в logs/errors. JSON-string header не является URL.

Order of admission: recognized method → trusted session → media/declared bounds/
metadata shape → CSRF → active local exact role permission → context → bounded
body acquisition/declared match → production original command. Forbidden admission
не вызывает application command и не создаёт original/terminal/domain audit facts.
CSRF empty/mismatch403 CSRF_INVALID. Unknown fields/shape400 прежде CSRF.
Safe admission log: FMONITOR_ORIGINAL_HTTP_REJECT status=<int> reason=<allowlisted>
без attacker strings. Rejected domain attempts полностью следуют native audit policy:
не заменять original first-denial terminal + repeated-denial audit selection policy.

## 3. Response

Для requests, дошедших до adapter, pre-command error JSON exactly {"error":"CODE"}+LF, UTF8/no-store/nosniff/inherited
CSP/security headers, exact byte length. Missing local permission403 ACCESS_DENIED;
context/order missing404 NOT_FOUND; dependency/config/storage unavailable503
SERVICE_UNAVAILABLE + Retry-After60. HEAD same headers and zero response bytes.

Command Result JSON — existing production WorkerResultEncoder 11 canonical fields,
final LF; status enums lower-case. accepted201; replayed200; rejected authorization
403/order_not_found404/file_too_large413/other422; conflict409; failed503+Retry-After60.
Не заменять native Result envelope pre-command error после invocation. No automatic
server retry. Full matching replay не создаёт revision; audit semantics inherited.

## 4. UI and date

На выбранном current order ссылка «Загрузить оригинал»/«Исправить оригинал» только
при доступном submission form grant. Новый markup использует existing PilotView shell,
public shlz field/date-input/checkbox/button. Имя файла может показываться только
через textContent/escaped HTML. File input single accept PDF; no manual number.
Форма показывает exact selected crew, one file, date input, explicit composition
confirmation. Correction дополнительно требует reason и показывает current immutable
original date/revision. Current root/target/expected leaf берутся из GET DTO в hidden
fields; пользователь не вводит identities вручную.

Initial suggested date: approved dateReader(case,order) last successful template date,
если есть; иначе сегодняшняя Europe/Moscow. Correction defaults current original date.
User может изменить дату по документу; future date отклоняется native command.
Template date read следует отдельному approved snapshot, bound exact order identity.
Ни selectionDate, ни upload timestamp не подменяют documentDate.

Только successful GET/HEAD form получает CSP с script-src 'self' и connect-src
'self' для upload; без worker-src/blob/unsafe-inline. Остальные original responses
сохраняют BASE CSP. Это узкое расширение существующего route CSP allowlist.

Small same-origin JS берёт File как body и metadata header из формы. Input change
создаёт новый requestId; network/retryable failure без changes сохраняет exact key,
File и metadata для retry. Повторная кнопка не посылает concurrent request пока
предыдущий in-flight. 201/200 accepted/replayed переводит GET form; 4xx показывает
RU reason и новый intent key для последующего исправленного ввода. Небезопасные
strings/stack/private paths не вставляются в HTML. No false success on network error.
Без JS видна честная инструкция включить JavaScript для загрузки; обычный form POST
не отправляет файл в несовместимом формате. PDF original хранится только native owner.

## 5. Wiring and configuration

Same fresh flag as composition HTTP. No legacy registration/prepare writer reopened.
GET form/POST работают на selected immutable identity, включая correction accepted
older order после new pending selection. Stale initial selection решает approved
binding; HTTP не навязывает template/latest-only rule historical correction.
Required original config outside web root: FMONITOR_ARTIFACT_STORAGE_ROOT,
FMONITOR_ORIGINAL_SAFE_LOG_FILE, FMONITOR_ORIGINAL_DB_PASSWORD_FILE. DB host/port/name/
user/prefix — existing configured native contour. Missing/mismatched config503,
никакого runtime mkdir/DDL/credential-file generation из HTTP. Fresh reader factory
использует approved password-file recovery configuration, не silent unavailable port.
Local upload/correct/read grants не создаются HTTP. Boot/grants отдельный launch slice.

## 6. Independently fixed checks

Real native HTTP fixture actor18 FKR, order81 selected через public HTTP без PDF,
crew7001/engineer73, no original. GET suggested today; after template — last date.
PDF corpus327bytes SHA4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784,
documentDate2026-09-01: POST201 exact result; same request/body→200 replayed, one
revision. Correction new request/date2026-09-02/reason→revision2; old1 preserved;
repeat stale target with new intent409. Composition/checklist/opening rows unchanged.

Negative real HTTP cases: missing session, manager allowed/admin-only denied,
revoked local/native capability, invalid CSRF/header/duplicate JSON/noncanonical
JSON/metadata types/mode/UUID, media, missing/invalid/oversized Content-Length,
native-stream mismatch и characterized pre-PHP framing rejection, malformed/active PDF, future date, missing confirmation/reason,
wrong object/order and source/storage unavailable. No production/native interception.
Core parser/selection binding/attempt-audit approvals reused as domain evidence;
new transport proves real received bytes and unchanged domain results.
Browser GET form→file/date/confirmation→success, correction and retry; date prefill
and no-template parity required. Visual/focus/Impeccable + architecture/lints/diff.
Gate1→RED→independent Gate3→minimal GREEN→Gate5→evidence/commit.
Parent full read/download/assigned engineer policy and full VERIFY remain open.
