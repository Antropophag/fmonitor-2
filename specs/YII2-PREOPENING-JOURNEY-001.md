# YII2-PREOPENING-JOURNEY-001

## Простыми словами

Пользователь проходит через Yii2 от карточки объекта к выбранному составу,
подписанному оригиналу и отдельному открытию работ. Роли, документы, даты,
повторы и история сохраняются. Yii отвечает за HTTP/session/CSRF/UI; уже
существующие application owners продолжают принимать предметные решения.
Это следующий срез #76 после PR86, а не переключение рабочего стенда.

Gate1 preparation complete by root under current #76 authority; independent
Gate3 approval of the complete tests remains required.

## Authority и граница

Controlling owner #76: автономная декомпозиция, сохранение текущих сценариев,
серверного HTML/shlz, полномочий и append-only истории; повторный login разрешён.
PRODUCT.md, CONTEXT.md, pilot spec/data model обязательны. Техническая граница
существующих native owners сохраняется по ADR0003 и migration inventory.

Нормативные domain contracts наследуются целиком: COMPOSITION-SELECT-001 с section17,
SELECTION-NATIVE-001, TEMPLATE-GENERATE-001, ORIGINAL-UPLOAD-001 и selected-original
binding/attempt-audit/lifecycle, ORIGINAL-UPLOAD-HTTP-001 v0.3 для raw transport,
ORIGINAL-HISTORY-DOWNLOAD-001, open-confirmed-original-atomically и актуальные
owner amendments2026-09-07/09 (unknown employment/reapplication/PDF navigation).
Их бизнес-алгоритмы/численные bounds не переписываются в HTTP.

Для новых Yii маршрутов superseded старые REMOTE_USER/session/CSRF/response-emitter
assertions, ранний card no-controls/no-unknown-dates и unapproved multipart draft
ORIGINAL-HTTP-001. Source truth текущего wiring: FreshOrder/OriginalUpload/History/
Execution handlers, PilotE2ECoordinator card + applied reader и current decorators.
Исторические tests своих старых public seams сохраняются без ослабления.

## Public application seams и composition

Новый read owner InstallationProcess::YiiObjectCard::read(actorId,objectId)
возвращает current card model либо null для недоступного объекта; denied exact
objects.read — DomainException ACCESS_DENIED; infrastructure/inconsistent facts
вызывают unavailable exception. SQL этого перенесённого PilotHttp read boundary
принадлежит MariaDb Yii DAO adapters, actor задан явно, без web globals.

Неизменённые public owners подключаются целиком:

| Действие | Public owner |
|---|---|
| portal/installer search | AssignmentOrderSelectionPortalQuery через ProductionAssignmentOrderSelectionPortalFactory |
| select/replace_pending | AssignmentOrderCompositionApplication::selectAssignmentOrderComposition |
| template | AssignmentOrderTemplateApplication::generateAssignmentOrderTemplate |
| submission context/form | AssignmentOrderOriginalSubmissionQuery |
| initial/correction | AssignmentOrderOriginalApplication::submitAssignmentOrderOriginal через createForSelections |
| history/exact bytes | AssignmentOrderOriginalHistoryReader::readHistory/prepareDownload |
| confirmed opening | ProductionConfirmedOriginalOpeningFactory → openConfirmedOriginal |

Их native mysqli connection caller-owned и idle при входе. Каждый owner сам
владеет своей transaction/snapshot/recovery. Yii composition не открывает outer
PDO/native transaction и не делает часть команды в другом соединении. HTTP
передаёт намерение и expected versions; owner повторно авторизует и проверяет
актуальные факты. Native connection/resources закрываются в request finally.
Factories/config не открывают runtime DDL, bootstrap или demo manifest.

## Route/method matrix

Все object/order IDs canonical ASCII decimal1..PHP_INT_MAX; leading zero, signed,
overflow, лишний segment/traversal/encoded separator не становятся другой identity.
Order ID — immutable identity, не version number. Actor только Yii User/session;
body/header/FMONITOR_AUTH_* не задают его. Гость/отозванная session →303 login с
безопасным return URL. Распознанный route с неверным method →405 с Allow.

| Methods | Route suffix after /pilot/objects/{objectId} | Result |
|---|---|---|
| GET, HEAD | empty | card200 or non-disclosing404/503 |
| GET, HEAD | /assignment-order/prepare |303 current selection |
| GET, HEAD, POST | /assignment-order/selection | portal200; selected/replayed303 same portal |
| GET, HEAD | /assignment-order/installers?q=&page= | JSON items/page/hasMore |
| POST | /assignment-orders/{orderId}/template | committed generated PDF200 |
| GET, HEAD | /assignment-orders/{orderId}/originals/submit | original form200 |
| POST | /assignment-orders/{orderId}/originals | canonical native Result JSON |
| GET, HEAD | /assignment-orders/{orderId}/originals/history | current history HTML |
| GET, HEAD | /assignment-orders/{orderId}/originals/{revisionId}/download | exact verified PDF200 |
| GET, HEAD, POST | /execution | current execution read/form; command success303 |

Fresh obsolete prepare POST, registration, physical order/appendix/signed-original
artifact routes remain410; no old writer is reopened. Legacy control-engineer/open
POST paths remain410. Compatibility execution actions apply/open remain callable
with native Yii CSRF and their existing owners/capabilities, but no obsolete apply
button is added. Successful compatibility apply/open return303 to /execution. Main visible path uses open_confirmed and returns to card.
Other unowned process routes are subsequent slices, not a rapid HTTP fallback.

## Authorization matrix

1. Card requires active Yii identity and exact objects.read through an active local
   role, matching current PilotE2ECoordinator. Reading does not grant process actions.
2. Selection portal/search/template/save require the existing active builtin FKR or
   manager role and exact assignment_order.composition.select. Display names and
   arbitrary custom-role labels do not grant this native policy.
3. Initial/correction POST require corresponding original.upload/original.correct
   plus current native FKR/manager policy. Form additionally requires original.read;
   a template or latest-only restriction is not invented for historical correction.
4. History/download requires original.read and current role union: FKR/manager
   available objects, OTIZ all orders, engineer only object assigned to that actor
   by current application. Admin alone is not a document grant. Current legacy
   responsstroicontrol is not an assignment proof. Every GET/HEAD/download rechecks.
5. Open_confirmed requires exact installation.open in the owning command. It does
   not require composition.apply merely because application is part of the atomic
   opening. Standalone apply still requires composition.apply. Execution GET keeps
   its current selection-portal read admission; card inline opening supports open-only
   actor without introducing a separate permission prerequisite.
6. Blocked/invited account, inactive role, absent/revoked/near-match capability,
   spoofed actor and other-object/order references are exercised through real HTTP.
   UI controls follow exact capabilities; server denial does not rely on hiding UI.

## Card and read behavior

- Exact case + current legacy identity are required; missing/unimported/dangling or
  missing required identity is non-disclosing404. Infrastructure/corrupt linkage,
  malformed state/opening tuple is503. Invalid IDs/methods do not fall through.
- Preserve normalized address/entrance/regnumber; planned finish uses adjusted date
  then plan_finish_date. Unknown plans stay unknown, not invented dates. Source
  provenance/detail failures are represented truthfully, not a fabricated full card.
- Before opening, accepted current original of latest selection makes card ready
  even without application. New pending selection cannot inherit old original or
  application readiness. Correction before opening binds current original and
  existing application sequence. Physical registration number is not a new gate.
- After opening show durable actual date/actor/time and applied immutable crew/basis;
  opening doesn't arise from viewing, selection, template or upload. Current
  completion projection/85%-15% and change-needed labels remain their existing policy.
- Historical actor display resolves stored actor ID to local name, email fallback,
  then truthful unavailable/ID label; inactive historical author is not replaced by
  current viewer. Every displayed string is escaped.
- Preserve current card technical-detail snapshot panels, actionable next step,
  document links/history, current crew and shell/nav. Details absent/corrupt show
  the current unavailable notice; no legacy-wide editable table or factual guesses.
- GET/HEAD and technical detail reads change no domain/schema/private-file facts;
  no hidden application/opening/template generation. HEAD follows same admission
  and integrity with zero body. Yii-owned sessions retain the platform lifecycle.

## Selection, search and template HTTP

Form-urlencoded selection limit32768bytes. Native `_csrf` replaces old csrfToken;
closed decoding still rejects duplicate scalar/unknown keys, NUL, malformed escapes,
array-shaped scalar and invalid UUID/int/revision syntax. installerTabIds[] is the
only repeatable field, <=500 items; core owns empty/duplicate/eligibility outcomes.
Fields: _csrf,requestId,mode,expectedSelectionRevision,controlEngineerUserId,
controlEngineerConfirmed,installerTabIds[]. Mode new_order/replace_pending;
explicit engineer confirmation yes; revision0..4294967295, IDs positive.

GET reflects persisted pending selection; POST successful selected/replayed303
same portal; conflict409; authorization403; missing object404; invalid shape400;
other domain rejection422; failed503 (retryable reason preserved, no automatic
mutation retry). Error UI keeps a useful return/retry path and never claims success.

Search only q/page keys, GET/HEAD, current native query/eligibility/order/page bounds.
JSON exactly items,page,hasMore. Invalid query400, denied403, unavailable503.

Template POST accepts only native CSRF. Native owner validates current identity,
capability, source/crew/planned dates/completion/PTO, owns case lock+render+audit.
HTTP200 application/pdf inline only after confirmed generated result; one PDF,
current Moscow date and immutable crew. No stored template bytes/version/files.
Each generation is a new audit, not request-key caching. Last-success date drives
original prefill. Failure doesn't change last-success date or publish partial bytes.

## Original raw upload and native CSRF

Preserve ORIGINAL-UPLOAD-HTTP-001 binary/metadata/received-byte contract exactly,
including20MiB cap,16384byte metadata header (invalid/overlong metadata400), canonical base64/ordered JSON/types,
no Transfer-Encoding, explicit length and bounded read/mismatch behavior.
Metadata keys remain csrfToken,requestId,mode,documentDate,compositionConfirmed,
rootOriginalId,targetRevisionId,expectedCurrentRevisionId,correctionReason,
originalFilename. The metadata csrfToken must equal the standard X-CSRF-Token
header sent by the updated UI; Yii Request alone validates it against session.
This is transport consistency, not a second session/CSRF implementation.

Native Yii access/CSRF runs before action decoding; missing/invalid native CSRF
maps400 in inherited safe envelope (explicit platform migration from old403).
With valid native admission, media415, missing length411, bounds413, invalid closed
metadata/stream400 remain distinguishable. Body acquisition occurs after role/
context checks and doesn't itself create files/facts. Web-server framing rejection
remains its own bounded outcome, never reported as successful upload.

Native Result stays canonical11fields plusLF: accepted201, replayed200,
authorization403/not-found404/file-too-large413/other rejection422/conflict409,
failed503+Retry-After60. No server retries or replacement of invoked-command Result
with pre-command envelope. Domain attempt-audit policy remains native.

UI: one PDF file, date, explicit crew confirmation; correction reason/current
revision. Initial prefill last template date else Moscow today; correction current
original date. No manual registration number. File/metadata/requestId retained on
network/uncertain retry, changed intent gets new ID, concurrent click suppressed.
201/200 returns the object card via current data-return-url (owner/current UI
supersedes the older v0.3 GET-form return); failure is actionable and never false success. Safe text
only; native script/connect CSP, no inline/unsafe-eval/worker/blob additions.

## History, download and opening

History uses native immutable metadata/read policy and current HTML ordering;
current UI limit100 is preserved, not falsely described as new pagination. A new
pending order doesn't hide accepted history. Empty/not-found/unavailable mapping
follows the current reader/handler; private config/path/content identity not exposed.
Download requests the exact revision (including older) and returns prepared verified
bytes only, attachment filename assignment-order-original.pdf. HEAD validates full
integrity then no body; no partial200, Range/conditional shortcuts, ETag or304.
Busy/missing/corrupt/unsafe private file returns unavailable; no repair/write/view audit.

Open_confirmed form fields: _csrf,action,requestId,orderId,revisionId,sequence,
actualStartDate. Actor/object from session/route. Native compound owner validates
current original, selected crew/eligibility/template, expected sequence and date;
one transaction appends/reuses/reapplies composition and opens. Upload remains
separate. Exact replay preserves original facts; changed payload with same ID fails.
Date before document/future, stale selection/revision/sequence, absent permission,
PTO/completed/eligibility failure leave all application/assignment/opening facts exact.
Success303 card. Existing structured business failure displays422; infrastructure/
uncertain outcome reasons map503 in Yii (explicit correction of old blanket422),
with safe retry guidance and no automatic resubmission. Native reasons aren't guessed
from raw driver exceptions; unclassified native unavailability remains unavailable.

## Independently fixed acceptance groups

Synthetic case6101 / legacy object4512, actor18 FKR, installer7001/7002, engineer73; controlled
clock for native fixture where needed. Real Yii login/CSRF, isolated canonical v24
DB, DML-only runtime/fresh-reader credentials and task-owned private storage.

- Normal path: card requires order → selected composition (no opening/artifact) →
  optional template real PDF+single audit/no file → accepted original201 → ready
  card with no application → open_confirmed303 → one application/opening snapshot,
  current applied crew and exact original link. Direct original without template too.
- Same selection and original request replay; replace_pending/new selection;
  original correction/history/exact old/new download; original never applies/opens.
  Opening exact replay and conflicting ID payload; prior application and corrected
  original reapplication branches preserve previous rows/bytes.
- All matrix roles and capability revocations, malformed/unsupported HTTP, invalid
  dates/crew, wrong references, missing dependencies/storage/stream/framing; exact
  zero facts for pre-command denial and inherited audited domain rejection policy.
- Current original/selection/application changes between GET and POST rejected by
  native owner; concurrent submissions/replay don't duplicate facts; downstream
  persistence failure cannot leave partial application/opening or publish PDF early.
- Browser desktop/mobile whole path, native navigation/logout, keyboard/file/date/
  confirmation, no overflow, no missing assets; independent DB and byte/hash audit.
  Current queue/identity/OTIZ and original native suites are material adjacent flows.
- Full traces contain no PilotHttp/rapid runtime include; renderer logo asset moved
  byte-for-byte to an owned app path, real PDF verified. No tests substitute mocked
  identity, fake success JSON or direct SQL for a submitted user command.

Done: complete matrix/plan/root intended RED → independent Gate3 → separate
implementation/focused/visual/architecture → independent Gate5 → exact-source CI.
Working runtime switch and remaining #76 routes are separate delivery work.

## Traceability of transport and inherited owners

| Acceptance group | New executable HTTP/image seam |
|---|---|
| Card identity, read capability, read-only/HEAD, current original, escaped actor, corrupt tuple | yii2_object_card_001 |
| Entire selection/template/raw original/opening/replay persisted journey, real PDF semantics | yii2_preopening_http_001 |
| Closed selection syntax/bounds, whole-catalog paging, immutable replacement | yii2_selection_input_001 |
| Native identity revocation, active/exact roles, all method families, manager and open-only | yii2_preopening_authorization_001 |
| Canonical raw upload/replay/correction, history/role union, exact PDF/HEAD, unsafe/busy files | yii2_original_transport_001 |
| Invalid/stale command, obsolete writers, missing config, downstream atomic rollback | yii2_preopening_failures_001 |
| Prior application reuse, correction after GET, explicit reapplication, durable replay | yii2_preopening_lineage_001 |
| Two concurrent Yii servers: selection/original/correction/opening, distinct sessions and exact durable facts | yii2_preopening_concurrency_001 |
| Complete read/HEAD, strict order/segments, guest raw/write and retained apply/open/retired artifacts | yii2_preopening_routes_001 |
| Real acknowledged COMMIT loss, safe503, exact durable replay | yii2_preopening_uncertain_commit_001 |
| Actual controls, PDF, accepted response loss/file+intent retention, correction/opening/mobile/logout | yii2_preopening_browser_001 |
| Real production image, transitive OriginalRuntime, exact moved logo, semantic PDF | yii2_preopening_package_001 |

Each entry is tests/Yii2/<name>_test.php (package uses .py). Native owner tests in
verification-input cover unchanged eligibility/audit/concurrency/terminal recovery
and history metadata semantics; they do not substitute for the HTTP observations.
The fault proxy is test-only: real MySQL UPDATE/COMMIT, confirmed OK packet dropped
once, direct private DB audit and public native replay preflight. Missing template
snapshot was corrected in the fixture before Gate3; that setup diagnostic is not RED.
