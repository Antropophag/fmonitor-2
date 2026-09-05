# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 — выбор состава без шаблона

Версия 0.3, 2026-09-05. **DRAFT / GATE 1 NOT APPROVED**.

## Простыми словами

ФКР выбирает монтажников и инженера и сохраняет этот выбор до загрузки
оригинала. PDF-шаблон можно сформировать отдельно, но его генерация не нужна
для сохранения выбора. Новая selection не является действующим назначением и
не скрывает прежний состав в справочнике или у стройконтроля.

## 1. Источники и подтверждённые границы

Actors — сотрудник ФКР и Руководитель ФКР. Основание: PRODUCT, pilot original
workflow и `select-assignment-order-composition-without-template`.
Inventory `16ba3e65401ab6b3acb883a96f590bc1e9eb7c3d` доказывает отсутствие
render-free production creator. Projection gap evidence
`412efcb75bd88b454b2ba1ebbf9800937e96856b` требует проверки текущих публичных
назначений, а не только физических rows.

Candidate public action: `selectAssignmentOrderComposition(command)`.
HTTP/CLI вызывают тот же production application owner; direct SQL/fixture
creator не являются реализацией пользовательского действия.

## 2. Candidate input и authority

Command candidate fields: `requestId`, `mode`, `installationObjectId`, `actorUserId`,
`installerTabIds`, `controlEngineerUserId`, `expectedSelectionRevision`.
UUID requestId — canonical lowercase; object/actor/engineer IDs positive;
installer IDs — непустой unique numeric-sorted набор положительных identities;
expectedSelectionRevision — nonnegative integer, 0 означает отсутствие selection.
Mode NEW_ORDER создаёт следующий выбранный состав; REPLACE_PENDING исправляет
ещё не принятый original-ом выбор. Оба mode сохраняют прежнюю историю.
Input не содержит PDF/filename/storage path, documentDate original или
произвольные кадровые snapshots. Actor в HTTP приходит только из session.

Candidate exact permission: `assignment_order.composition.select`.
Active builtin roles `fkr_operator` и `manager` получают explicit local grant
и соответствующий explicit user process capability через approved
bootstrap/administration mapping. Существующие arbitrary/custom role grants
не расширяются по display name. Проверки active local user, active assigned
role, exact local permission и exact process capability обязательны; grant
не выводится из `prepare`, `original.upload`, `original.correct` или read.
Отзыв любой обязательной authority блокирует также request replay.

Это proposed technical mapping для inherited двух actors ФКР; новый permission
не означает автоматическое разрешение read-only ОТиЗ, инженеру или
administrator-only. Multi-role user получает только явно назначенное union
permissions, а не wildcard. Unauthorized attempt не читает confidential
selection и не создаёт order/snapshot; safe audit не раскрывает лишние inputs.
Additive schema/catalog grant migration и exact seed/revoke behavior должны
быть включены в Gate 1 batch до реализации этого mapping.

## 3. Preconditions и selected facts

Один eligible объект монтажа, минимум один employed installer из canonical
каталога и ровно один active eligible engineer. Завершённый объект/Акт ПТО
блокируют обычный selection по inherited pilot contract. Worker и engineer
snapshots читаются production ports, не принимаются от caller.

Selection сохраняет immutable identity, ordered composition snapshots,
createdAt и actor. Это не documentDate подписанного original. Физическая
совместимость с existing `order_date` и composition hash original command
определяется до Gate 1; нельзя подставлять invented date в original evidence.

Успех возвращает stable orderId/caseId/version/compositionIdentity и новую
selection revision. Audit `assignment_order_composition_selected` append-only
и атомарен с selection; exact payload/result/DTO declaration — Gate 1 open item.
Generated artifacts отсутствуют, renderer и template storage не вызываются.

## 4. Replay, correction и failures

Authorization предшествует replay lookup. Повтор того же accepted intent
возвращает сохранённые identities без новой version/audit. Новый intent требует
новой request identity. Different selection against stale expected revision
даёт conflict без переписывания winner.

Candidate pre-original correction: новый intent против exact current selection
revision создаёт новую immutable selection revision и новую order identity,
сохраняя прежнюю selection/order и связь replacement. Прежняя identity не
переиспользуется для другого composition hash. UI показывает текущую selection
и историю выбора; ранее сформированный template остаётся привязанным к старой
identity и не перезаписывается. Optional template для нового состава требует
отдельного render нового snapshot. Exact schema relation/version numbering и
terminal result lookup определяются до Gate 1 approval; это не разрешение
редактировать старые rows или удалять незагруженные selections.
После accepted original этот command не исправляет its composition. Новое
действующее распоряжение и forward-only applicability принадлежат отдельному
lifecycle contract.

Partial persistence невозможна: snapshot/order/request/audit commit атомарны.
Technical DB failure не превращается в business rejection; unknown commit
требует replay той же request identity. Exact error enum/status/lookup protocol
необходимо закрыть до RED. Render failure не является selection failure,
поскольку renderer не участвует в этом command.

## 5. Observable no-application contract

Selection не меняет actualStart/opening snapshot, accepted originals,
эффективные интервалы назначений и historical checklist attribution.
Case/selection revision и selection audit могут измениться согласно command;
current work state и effective crew не меняются.

Обязательная матрица публичных наблюдений:

| Initial state | Action | Expected |
|---|---|---|
| Нет распоряжения | Сохранить selection | Есть selectable identity; нет PDF/original/opening |
| Есть applicable order A | Сохранить pending selection B | A остаётся текущим для directory/inspection |
| Selection сохранена | Renderer unavailable при optional template action | Selection пригодна для direct upload |
| Состав уже выбран | Same-request replay | Те же identities, no duplicate audit |
| Два разных выбора одного expected revision | Concurrent commands | Один accepted, другой conflict, no mixed snapshots |

Fictional worked preservation example: applicable A/version1, installer7001,
engineer73; pending B/version2, installer7002, engineer74. До и после selection
B public directory сохраняет A→7001 и прежний assigned/free summary;
inspection scope/attribution сохраняет engineer73/installer7001. Member rows B
не должны скрывать A через общий MAX(version) или применять 7002/74.
Это target invariant, не approval legacy registered predicate.

## 6. Handoff и verification boundaries

Original command получает существующий order/composition identity и проверяет
его своим approved reader. UI не генерирует скрытый шаблон и не вызывает
private persistence ради создания identity. Optional template использует
сохранённый snapshot; дата template и original date остаются разными facts.

До Gate 1 независимо проверить candidate capability mapping и pre-original
replacement policy; закрыть source predicates, complete DTO/result/errors,
schema/persistence manifest, correction version semantics, physical date/hash
compatibility и effective-projection ownership. Test seams должны наблюдать
real command и public projections, с независимыми expected literals и
deterministic concurrency/unknown-outcome construction.

После approved Gate 1: demonstrated RED → fresh independent Gate 3 → minimal
GREEN → relevant regression/architecture → fresh independent Gate 5. Затем
real HTTP direct-upload+optional-template parity и full verify. Ни этот draft,
ни отдельно сохранённые fixture rows не являются release evidence.

## 7. Closed command/result candidate

Следующий exact contract уточняет candidate fields разделов 2–4; он требует
independent Gate 1 и не означает ready persistence integration.

```php
namespace FMonitor2\AssignmentOrderComposition;

enum AssignmentOrderCompositionMode: string
{
    case NEW_ORDER = 'new_order';
    case REPLACE_PENDING = 'replace_pending';
}

final readonly class SelectAssignmentOrderCompositionCommand
{
    /** @param list<int> $installerTabIds */
    public function __construct(
        public string $requestId,
        public AssignmentOrderCompositionMode $mode,
        public int $installationObjectId,
        public int $actorUserId,
        public array $installerTabIds,
        public ?int $controlEngineerUserId,
        public int $expectedSelectionRevision,
    ) {}
}

interface AssignmentOrderCompositionApplication
{
    public function selectAssignmentOrderComposition(
        SelectAssignmentOrderCompositionCommand $command,
    ): AssignmentOrderCompositionResult;
}

interface AssignmentOrderCompositionResult
{
    /** @return array<string, int|string|bool|null> */
    public function toArray(): array;
}
```

Result имеет keys ровно в порядке:
`status, reasonCode, retryable, requestId, caseId, assignmentOrderId,
assignmentOrderVersion, selectionRevision, compositionIdentity,
compositionSha256, selectionDate, selectedAt`.
У SELECTED/REPLAYED reasonCode null, retryable false, остальные fields non-null.
У REJECTED/CONFLICT/FAILED все fields после requestId null.

| Status | reasonCode | retryable |
|---|---|---|
| SELECTED, REPLAYED | null | false |
| REJECTED | INVALID_COMMAND, AUTHORIZATION_DENIED, OBJECT_NOT_FOUND, INSTALLER_REQUIRED, CONTROL_ENGINEER_REQUIRED, INSTALLER_NOT_IN_CATALOG, INSTALLER_NOT_EMPLOYED, CONTROL_ENGINEER_NOT_ELIGIBLE, OBJECT_HAS_PTO_ACT, OBJECT_COMPLETED, NO_CHANGES | false |
| CONFLICT | REQUEST_ID_CONFLICT, STALE_SELECTION, ORIGINAL_ALREADY_ACCEPTED, PENDING_SELECTION_EXISTS, SELECTION_NOT_FOUND | false |
| FAILED | PERSISTENCE_FAILURE, PERSISTENCE_OUTCOME_UNKNOWN, DEPENDENCY_UNAVAILABLE | true |

`caseId/orderId/version/selectionRevision` — positive int. compositionIdentity
равна `composition-<orderId>-v<orderVersion>`. selectionDate — Moscow calendar
date единственного accepted clock instant, selectedAt — тот же instant UTC
RFC3339 second. selectionDate не является date оригинала или датой будущего
optional render; physical `order_date` compatibility отдельно проверяется
task 1.3 и не считается разрешённым изменением original contract.

Composition hash — SHA-256 compact JSON с exact key order
`caseId,compositionIdentity,engineerUserId,installers,orderId`, installer IDs
unique numeric-sorted. Worked expected input, вычисленный без production:

```json
{"caseId":4512,"compositionIdentity":"composition-81-v1","engineerUserId":73,"installers":[7001],"orderId":81}
```

SHA-256: `5c405e5761854b6de09ff2f06f1d72e38203081052b8409cf23fa8d4447fe93a`.
В isolated fixture с next orderId=81, no selection, clock
`2026-09-05T09:00:00Z` первый accepted Result содержит version=1,
selectionRevision=1, selectionDate=`2026-09-05`, selectedAt exact clock и этот
hash; same-request retry сохраняет все fields кроме status=REPLAYED.

## 8. Exact candidate precedence

1. Shape: canonical request UUID, positive object/actor IDs, nonnegative expected
   revision, list<int> installers без duplicates/nonpositive IDs; invalid даёт
   INVALID_COMMAND до dependent reads. Пустой list и null engineer допустимы
   для следующей business validation, не считаются отсутствующей DTO shape.
2. Active actor/role/local grant/process capability. Denial до replay и object
   lookup; unavailable authority — DEPENDENCY_UNAVAILABLE.
3. Accepted request lookup. Exact stored actor/object/canonical command tuple
   match даёт REPLAYED до текущих object/clock/catalog checks; mismatch —
   REQUEST_ID_CONFLICT без evidence disclosure. FAILED не кэшируется terminal.
4. Object/context lookup; absent — OBJECT_NOT_FOUND, unavailable — dependency
   failure. Completion, затем PTO запрещают new selection соответствующим code.
5. Expected revision против latest selection revision (0 если истории нет):
   mismatch STALE_SELECTION. REPLACE_PENDING без selection даёт
   SELECTION_NOT_FOUND, с accepted original — ORIGINAL_ALREADY_ACCEPTED.
   NEW_ORDER при latest selection без accepted original даёт
   PENDING_SELECTION_EXISTS; иначе допускает новый prospective выбор, не
   применяя его. Таким образом новая selection поверх applicable order
   разрешена, но accepted composition не исправляется задним числом.
6. Empty installers — INSTALLER_REQUIRED; null engineer —
   CONTROL_ENGINEER_REQUIRED. Получить один clock instant/selectionDate;
   unavailable clock — DEPENDENCY_UNAVAILABLE. Numeric-sorted installer lookup: первый missing
   candidate даёт INSTALLER_NOT_IN_CATALOG, не employed на selectionDate —
   INSTALLER_NOT_EMPLOYED. Active engineer eligibility затем проверяется из
   canonical directory. Один clock instant используется для всего accepted fact.
7. REPLACE_PENDING с тем же составом — NO_CHANGES. Новый состав
   создаёт новую immutable identity/revision с сохранённой replacement link.
   NEW_ORDER допускает такой же состав как у previous accepted order: это
   новый prospective документ, не semantic replay предыдущего selection.
8. Atomic commit selection/order/member snapshots + request result + один
   selection audit. CAS loser — STALE_SELECTION. Uncertain commit требует fresh
   lookup той же request identity: found match REPLAYED, proven absent
   PERSISTENCE_FAILURE, unavailable PERSISTENCE_OUTCOME_UNKNOWN.

Первое сохранение audit: event `assignment_order_composition_selected`,
actorId, occurredAt, payload с requestId/orderId/orderVersion/selectionRevision/
previousOrderId и compositionSha256. PreviousOrderId null только без прежней
selection; no renderer filename/bytes/path либо original date. Rejected/conflict
safe-audit persistence shape и public observation API остаются частью task 1.3,
не заменяются fake success или direct private table oracle.
