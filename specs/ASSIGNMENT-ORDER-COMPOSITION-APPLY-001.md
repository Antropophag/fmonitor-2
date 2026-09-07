# ASSIGNMENT-ORDER-COMPOSITION-APPLY-001

Версия0.1,2026-09-07. Gate1 candidate; executable approval ещё нет.

## Простыми словами

Отдельное действие ФКР превращает выбранный состав и принятый оригинал в
действующее закрепление монтажников и инженера. До открытия исправленный
оригинал можно отдельно применить повторно. Каждое применение сохраняется новым
фактом; загрузка PDF, выбор состава и применение не открывают работы.

## 1. Actor и public interface

Actors — активный сотрудник ФКР или Руководитель ФКР с exact permission
`assignment_order.composition.apply`. Ни selection/upload/open permission, ни
administrator сами его не заменяют. Native local role family обязательна;
legacy roles/registration/responsstroicontrol не являются источником полномочий.
Role grants при deployment принадлежат отдельной IAM configuration, не этой команде.

Namespace FMonitor2\AssignmentOrderComposition:

```php
ProductionAssignmentOrderApplicationFactory::create(
    mysqli $db, string $prefix = '', ?SelectionClock $clock = null
): AssignmentOrderApplication;
interface AssignmentOrderApplication {
    public function applyAssignmentOrderOriginal(ApplyAssignmentOrderOriginalCommand $command): AssignmentOrderApplicationResult;
}
```

Config: PHP64bit, prefix `[A-Za-z0-9_]{0,25}`; invalid prefix throws fixed
`AssignmentOrderApplicationConfigurationUnavailable`, message
`Assignment order application configuration unavailable.`, code0/previousnull.
Factory no SQL/FS/env/network, не меняет connection/session. Null clock выбирает
production UTC clock; supplied SelectionClock используется для deterministic
synthetic verification, не передаётся из HTTP inputs.

Command final readonly constructor order:
`requestId:string, objectId:int, orderId:int, originalRevisionId:string,
expectedApplicationSequence:int, actorId:int`.
requestId — lowercase UUIDv4; IDs positive PHPints; revisionId existing original
opaque ID grammar `[A-Za-z0-9][A-Za-z0-9._:-]{0,79}`; expected sequence0..2147483647.
Прочие данные (crew/date/current state) caller не передаёт. Invalid input →
rejected/invalid_command без SQL/clock. Caller SQL transaction не допускается:
failed/dependency_unavailable без begin/commit/rollback или изменения caller facts.

Result final readonly fields:
`status:string, reason:?string, application:?array`.
status exactly applied/replayed/rejected/conflict/failed. applied/replayed iff
reasonnull/applicationnonnull; rejected/conflict/failed iff reasonnonnull/applicationnull.
Safe reason values перечислены ниже; raw SQL/connection/path/exception/PII нет.
Application payload exact keys/order:
`applicationId, caseId, objectId, sequence, orderId, orderVersion,
originalRevisionId, originalRevisionNumber, documentDate, compositionIdentity,
compositionSha256, engineerUserId, installerTabIds, previousApplicationId,
kind, appliedAt, appliedBy`.
kind initial/new_order/reapplication. appliedAt UTC RFC3339 seconds from owner clock;
installerTabIds ascending unique numeric ints from immutable selected composition.
No names, email, private file identity/bytes, tokens or configurable paths in result.

## 2. Preconditions, order и authority

Runtime database selected/utf8mb4/idle, compatible canonical schemas1..15 and
compatible application storage (отдельный prerequisite migration contract).
Runtime никогда не создаёт/исправляет schema. Missing/corrupt dependency →
failed/dependency_unavailable, без application writes.

Order identity должна принадлежать object/case и selection registry. Current
original, lineage/composition identity/hash и selected members подтверждаются
публичным AssignmentOrderOriginalApplicationReferenceReader:
readCurrent до owner write transaction; confirmCurrent после case lock в той же
native connection. Forged/stale/copied reference не принимается. PDF bytes уже
приняты original owner; этот command не читает PDF и не утверждает availability
скачивания. Raw original tables не являются альтернативным application source.

Authorization проверяется до чтения stored request/application и ещё раз под
write transaction перед mutation. Active local user, active fkr_operator/manager
role и exact permission необходимы одновременно. Missing/incompatible local
family → dependency_unavailable, healthy absent grant → authorization_denied.
Replays тоже требуют текущую authorization; revoked actor не получает old payload.
Engineer должен сейчас быть active local construction_control_engineer user.

Case отсутствует → object_not_found; чужой/несуществующий order или original →
original_not_found. Target — самый новый accepted selected order по orderVersion;
более новый ещё неподписанный выбор не отменяет уже принятое основание.
Более новый принятый original другого order → conflict/target_not_current.
Current originalRevisionId отличается от command → conflict/original_changed.
Несогласованная original/registry/selection lineage → dependency_unavailable.

Completed case/декларация/legacy workdatefinish → object_completed;
PTO fact/legacy ptoactdate → object_has_pto_act. Эти факты читаются как условия,
не изменяются. Несогласованные opening fields → dependency_unavailable.
Clock failure/noncanonical UTC → dependency_unavailable; date calculation Europe/Moscow.
Document date не позже owner today; иначе rejected/document_date_in_future.

## 3. Employment eligibility

Selected names/crew/document composition immutable: команда не подменяет монтажников
или инженера актуальным каталогом. Перед применением проверяет каждого selected
installer по текущему интеграционному catalog и сохраняет отдельный eligibility
snapshot/provenance этого решения. Unknown/malformed dependencies не скрываются
под отсутствием человека.

Missing installer → installer_not_in_catalog. Current dismissed или
missing_from_delivery → installer_not_employed. Known employedFrom не позже documentDate
и known employedTo не раньше documentDate; invalid dates/contradictory period →
dependency_unavailable. Текущий employed status обязателен, даже если старая дата
документа попадает в прежний период.

При unknown employedFrom назначение разрешено только при подтверждённом полном
current snapshot: authority_system1c_zup, delivery_systembitrix24, positive stable
personId, reconciliationdelivered; row last_successful_sync_run_id/at совпадают с
singleton metadata и completed run, observed_at совпадает, failure_codenull,
page_count>=1, delivered_count>=1, valid normalized checksum. Missing/incoherent
proof → dependency_unavailable. Никогда не подставлять sync day. Known dates также
проверяются, даже если полный snapshot существует. Если integration metadata
присутствует частично/противоречиво, её нельзя игнорировать ради known-date fallback.

Known-date catalog без нового publication proof допускается по ранее утверждённому
WORKFORCE-CATALOG-001 contract: это не расширение unknown-date разрешения. Возраст
снимка сохраняется в provenance; freshness age threshold не изобретается этим срезом.
Normalization/publication и переключение selection unknown-date policy остаются
отдельными gates, но application следует уже подтверждённому owner decision.

## 4. Initial, sequential, reapplication

Sequence — append-only порядок применения по одному case, начинается1.
expectedApplicationSequence MUST совпасть с текущим maximum sequence (0до первого),
иначе conflict/application_changed. Проверка и запись сериализованы общим case lock.

- Нет прежнего application: kindinitial; previousApplicationIdnull.
- Другой order: orderVersion MUST быть больше предыдущего применённого; documentDate
  MUST быть не раньше прежней applied documentDate, иначе effective_date_before_current.
  Kindnew_order, ссылка на предыдущее application. Same-day sequential orders
  упорядочены application sequence: текущий состав — последняя применённая версия.
- Тот же order и новая current original revision: kindreapplication допустим только
  ДО открытия. Дата может быть исправлена в обе стороны, сохраняя обычные calendar/
  employment проверки и не будучи раньше последнего применённого другого order
  (иначе effective_date_before_current); previousApplicationId указывает на предыдущий immutable fact.
- Тот же order/revision при новом requestId и актуальном expected sequence →
  rejected/no_changes. Повтор уже accepted requestId обрабатывается разделом5.
- Reapplication после opening → rejected/reapplication_after_opening. Новое
  последовательное распоряжение после opening допускается; дата не раньше actual
  start date, иначе effective_date_before_opening. Opening/checklist snapshot и
  любые существующие inspection attribution не меняются.

Application — новый неизменяемый fact, не UPDATE прежнего назначения. Предыдущие
факты/selected snapshots/original revisions остаются точными. Для текущих экранов
и engineer authorization source — последнее применённое application, не последний
выбор/загруженный PDF и не legacy responsstroicontrol. Исторические checklist facts
сохраняют свой зафиксированный assignment/application reference. Это не пересчёт
прошлых checklist attribution и не официальный premium calculation.

## 5. Atomic persistence, replay, audit, concurrency

Одна owner transaction включает: locked case/current authority/original guard/
eligibility; append application+selected crew/eligibility snapshots; accepted request
fingerprint; append process event `assignment_order_composition_applied`; append
application attempt audit. У прежних facts нет UPDATE/DELETE. Case opening fields,
checklist, completion facts, selection/original/workforce/role rows не изменяются.

Fingerprint — SHA256 UTF8 JSON array
`[requestId,objectId,orderId,originalRevisionId,expectedApplicationSequence,actorId]`,
без whitespace, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES. accepted requestId
глобально уникален в application storage. Matching accepted request → replayed с
точным прежним application payload, без второго application/process event; новая
attempt audit фиксирует replay. Другой fingerprint → conflict/request_id_conflict,
без раскрытия prior result. History/backing must be coherent before replay.

Каждая syntactically valid authorized/rejected/replayed попытка получает отдельный
append-only audit: attempt identity, requestId, actorId, objectId, orderId,
status/reason, attemptedAt; без names/raw error/credentials. Healthy authorization
denial тоже audit-ится, без чтения stored application/request payload. Invalid
command/ambient transaction/clock или unavailable storage могут не иметь audit;
результат не выдаёт durable audit обещание. Неуспешная запись audit не выдаёт
подтверждённый business result: failed/persistence_failure либо outcome unknown.
Непринятый requestId не резервируется навсегда: после устранения причины команда
может быть повторена, но все state/precondition checks выполняются заново.

Конкурентные одинаковые UUID → один fact, второй replay после lock release.
Разные UUID с одинаковым expected sequence → один applied, второй application_changed.
Correction/selection/opening используют тот же case lock. Изменившийся original
между readCurrent/confirmCurrent → original_changed; его прежние backing не мутируют.
No blind mutation retry внутри вызова. Подтверждённый rollback → persistence_failure;
unknown commit/rollback acknowledgement → persistence_outcome_unknown без application
payload. Новый вызов с прежним command через healthy idle connection проверяет
accepted ledger, затем заново state: committed outcome replay, иначе обычные checks.
Не закрывать caller connection; освобождать только собственную transaction/handles.

Application/sequence/event/audit ID вне positive PHPint или sequence>2147483647 →
allocation_capacity_exhausted после подтверждённого rollback. Auto-increment gaps
допустимы; acknowledged partial fact недопустим. SQL text и errno не возвращаются.

## 6. Read interface for downstream consumers

Trusted readonly construction:
`AssignmentOrderApplicationReaderFactory::create(mysqli $db,string $prefix='')`.
`readCurrent(int objectId)` и
`readHistory(int objectId,int afterSequence=0,int limit=50)`.
Factory тот же pure prefix contract. Reads собственный idle-only consistent readonly
snapshot; caller transaction не трогают. Нет actor grants: HTTP авторизуется отдельно.
Result final readonly `AssignmentOrderApplicationReadResult` fields
`status:string,value:?array`; status found/not_found/invalid_argument/unavailable,
value iff found. Prefix/connection/config failures follow construction/runtime
rules above, без native error text.

readCurrent.value exact keys `application,selectedInstallers,selectedEngineer,eligibility`.
application — exact17field command payload из section1. selectedInstallers ascending
list exact `{tabId:int,fullName:string,position:string}` из принятого selected snapshot;
selectedEngineer exact `{userId:int,fullName:string,position:string}` из того же snapshot.
Names/positions valid UTF8 nonempty <=300Unicode characters. Изменение текущих
имён/ролей не переписывает эти values. eligibility ascending list exact keys:
`tabId,employmentStatus,employedFrom,employedTo,workforceSource,sourceUpdatedAt,
authoritySystem,deliverySystem,deliveryPersonId,reconciliationState,proofKind,fullSnapshot`.
Status employed, даты nullable canonical YYYY-MM-DD, source nonempty<=80characters,
sourceUpdatedAt valid source RFC3339<=40bytes; остальные source identity values
nullable for known_period, full_current требует section3 provenance.
proofKind known_period/full_current. fullSnapshot null для known_period, иначе exact
`{runId:string,observedAt:string,normalizedChecksum:string,deliveredCount:int,pageCount:int}`.
Все values копируются при application; историческое чтение не переоценивает текущий
кадровый статус или свежесть/доступность live source. Это evidence тогдашнего допуска.

readHistory.value exact `{items:list,nextSequence:?int}`. Items — тот же exact
four-key payload, ascending sequence, limit1..100, afterSequence0..2147483647.
Cursor равен sequence последнего возвращённого item только если есть следующий;
иначеnull. Empty valid page found/items[]/nextnull; absent object not_found.
Существующий object без applications: current not_found, history found/empty.
Unknown object и positive IDs различаются только после healthy lookup; invalid
bounds/IDs → invalid_argument без SQL. DB read failure → unavailable, без partial page.

Read проверяет application chain/request fingerprint/event/crew snapshot backing:
contiguous sequences1..N, correct predecessor/order/reapplication relationships,
matching single accepted request/event per fact, IDs/hashes/dates/shapes по contract.
Corruption → unavailable, не пропуск строк и не fallback к selection/legacy slots.
Исторический original revision/current source не переоценивается: frozen application
сохраняет основание, проверенное original owner при записи. Обычная последующая
correction или изменение кадрового каталога не делает прошлый application unavailable.
Reader emits no audit/log/stdout/stderr и не обновляет cursor/last-viewed metadata.

## 7. Reasons и validation order

rejected: invalid_command, authorization_denied, object_not_found,
original_not_found, installer_not_in_catalog, installer_not_employed,
control_engineer_not_eligible, object_completed, object_has_pto_act,
document_date_in_future, effective_date_before_current,
effective_date_before_opening, reapplication_after_opening, no_changes.
conflict: request_id_conflict, application_changed, original_changed, target_not_current.
failed: dependency_unavailable, persistence_failure, persistence_outcome_unknown,
allocation_capacity_exhausted.

Precedence: scalar→idle/runtime/clock/authority→authorized accepted-request lookup→
locked case/authority→expected sequence→current original/target→completion/PTO→
calendar/lifecycle→current crew eligibility→append commit. Replay не переоценивает
старую crew eligibility или current document state, но требует нынешнюю authority
и целостность своего immutable backing. Audit persistence failures override business result.

## 8. Independent examples and evidence

Native fixture создаёт synthetic case501, active FKR11 с exact apply permission,
engineer21 и installers101/102 через существующие approved selection+original seams.
ДокументA dated2026-09-02, revisionR1, selected [101,102]/engineer21:
command expected0 at2026-09-07T07:00:00Z → applied seq1, kindinitial, prevnull,
documentDate2026-09-02; case unopened/checklist blocked, no legacy writer rows.
Same UUID replay → тот же fact/время; новые audit only. Чужой fingerprint конфликт.
Correction R2 date2026-09-01 before opening, command expected1 → seq2,reapplication,
prevApp1; App1/R1/selection bytes exact. Stale expected1 после этого → application_changed.
New orderB [102]/engineer22, date2026-09-03, expected2 → seq3,new_order,prevApp2;
current engineer22 и installer102; все прежние facts точны. Same-day next order
выигрывает по sequence, не переписывая предыдущий fact. Older-date next order rejected.
После opening reapplyB запрещён; new orderC допустим при выполнении preconditions,
старый opening/checklist attribution остаётся связан со своим immutable application.

Rejected cases, both prefix0/25, native transactions/locks/race, rollback and request
recovery, grant revocation before replay, malformed original/application backing,
known/unknown dates with honest full snapshot proof, zero legacy side writes и
public readers проверяются независимо. Primary logs/DB snapshots outside repository;
synthetic fixtures только, нет actual Bitrix/production imports/preview/remote mutation.

## 9. Gates and remaining integration

Controlling owner record: composition-reapply-unknown-employment-owner-decision-2026-09-07.md;
PRODUCT/CONTEXT/pilot contracts и earlier sequential document-date decision inherited.
Gate1 application contract → separately gated compatible schema prerequisite →
application native RED→independent Gate3→GREEN→regressions/architecture→independent Gate5.
HTTP/directory binding, actual opening/golden path, full VERIFY/CI/deploy/restart
остаются обязательными downstream work; этот application slice их не объявляет done.
