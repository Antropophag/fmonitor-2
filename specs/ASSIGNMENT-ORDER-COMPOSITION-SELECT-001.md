# ASSIGNMENT-ORDER-COMPOSITION-SELECT-001 — выбор состава без шаблона

Версия 0.1, 2026-09-05. **DRAFT / GATE 1 NOT APPROVED**.

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

Command candidate fields: `requestId`, `installationObjectId`, `actorUserId`,
`installerTabIds`, `controlEngineerUserId`, `expectedSelectionRevision`.
UUID requestId — canonical lowercase; object/actor/engineer IDs positive;
installer IDs — непустой unique numeric-sorted набор положительных identities;
expectedSelectionRevision — nonnegative integer, 0 означает отсутствие selection.
Input не содержит PDF/filename/storage path, documentDate original или
произвольные кадровые snapshots. Actor в HTTP приходит только из session.

Exact selection capability и local/process grant mapping требуют Gate 1
решения. Inherited actor intent разрешает подготовку direct-upload пути для
двух ролей ФКР, но не автоматическое разрешение read-only ОТиЗ, инженеру или
administrator-only. Prepare permission и original-upload process capability
не считаются взаимозаменяемыми. Unauthorized attempt не читает confidential
selection и не создаёт order/snapshot; safe audit не раскрывает лишние inputs.

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

Исправление выбранного состава до original сохраняет историю; exact relation
selection revision ↔ immutable assignmentOrderId/version требует Gate 1 решения.
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

До Gate 1 закрыть capability/source predicates, complete DTO/result/errors,
schema/persistence manifest, correction version semantics, physical date/hash
compatibility и effective-projection ownership. Test seams должны наблюдать
real command и public projections, с независимыми expected literals и
deterministic concurrency/unknown-outcome construction.

После approved Gate 1: demonstrated RED → fresh independent Gate 3 → minimal
GREEN → relevant regression/architecture → fresh independent Gate 5. Затем
real HTTP direct-upload+optional-template parity и full verify. Ни этот draft,
ни отдельно сохранённые fixture rows не являются release evidence.
