# OBJECT-DETAILS-EDITING-001 — исправление реквизитов объекта с историей

## Простыми словами

Сотрудник ФКР или руководитель сможет открыть существующую карточку, нажать компактную кнопку редактирования и одной формой исправить локальные идентификационные и технические реквизиты. Поправки не переписывают legacy/import source, не превращают процессные или интеграционные данные в свободные поля и всегда сохраняются вместе с понятной append-only историей. Кшах не вводится: он вычисляется из актуального материала стен шахты.

Срез не создаёт универсальный конструктор полей, не меняет процессные команды, не запускает внешнюю синхронизацию при Save и не пересчитывает старые документы, snapshots или выплаты.

## 1. Actor, public seam и результат

Actor: активный local user с capability `objects.details.edit` и доступом к объекту. Нормативное назначение capability: бизнес-роли `fkr_operator` и `manager`; access administrator/superadministrator без бизнес-роли права автоматически не получает.

Единственный state-changing public application seam принимает:

- positive object identity;
- trusted actor identity;
- UUID v4 `requestId`;
- non-negative integer `expectedRevision`;
- непустой object patch с exact wire names из раздела 2.

Canonical HTTP seam: `POST /pilot/objects/<positive-id>/details` через Yii auth/CSRF/method boundary. Controller не владеет domain SQL или фактами. Success возвращает canonical `303` на карточку; stable validation/authorization/conflict/unavailable outcomes следуют существующим Yii conventions и не раскрывают SQL, schema, raw credentials или stack.

Успешный non-no-op одной транзакцией создаёт/обновляет current overrides, увеличивает revision ровно на один и добавляет ровно одно immutable событие `object_details_changed`. Отказ/no-op/replay не создаёт новых фактов.

## 2. Полная карта существующих реквизитов

### 2.1 Editable — идентификация и размещение

| Wire name | Source fallback | Тип и правило |
|---|---|---|
| `address` | `fm_maintable.ordadr_address` | trimmed non-empty string, bounded действующим card contract |
| `entrance` | `fm_maintable.entrance` | trimmed non-empty string |
| `regnumber` | `fm_maintable.regnumber` | nullable string; ведущие нули сохраняются |
| `zavnumber` | `fm_maintable.zavnumber` | nullable string ≤120 bytes; ведущие нули сохраняются; `0` не ERP candidate |

### 2.2 Editable — классификация оборудования

| Wire name | Source fallback | Тип и правило |
|---|---|---|
| `floors` | technical payload `fields.floors` | integer в подтверждённом предметном диапазоне |
| `weight` | `fields.weight` | integer kg в подтверждённом диапазоне |
| `speed` | `fields.speed` | exact справочное/decimal значение; legacy code не выдаётся за м/с |
| `pittype` | `fields.pittype` | допустимый справочный code/value, coherent raw/display |
| `pitmaterial` | `fields.pitmaterial` | допустимый справочный code/value, coherent raw/display |
| `lift_type` | `fields.lift_type` | допустимый справочный code/value, coherent raw/display |
| `paired` | `fields.paired` | явный boolean/supported source value; пустое не становится `false` |

`shaftBp`/Кшах отсутствует в allowlist и форме. Для нового расчёта он выводится действующей `NativePremiumNorms`-нормой из effective `pitmaterial`; изменение таблицы норм не входит в срез.

### 2.3 Conditionally editable — исходный план

`workdatestart` и `plan_finish_date` допустимы только до фиксации соответствующего исходного срока реальным immutable документным/процессным фактом. Непустой текст даты сам по себе не является фиксацией. После распоряжения/opening/другого утверждённого snapshot обычная правка запрещена. `workdatestartadjusted`, `workdateendadjusted` и срок действующей справки о переносе не являются отдельными свободными полями; certificate deadline сохраняет приоритет и меняется только существующей командой справки.

В первой UI модалке условные даты показываются только когда owner/projection подтверждает editable state; сервер проверяет условие независимо от UI.

### 2.4 Integration-owned — только владельцы интеграции

- ERP equipment readiness/first/full shipment, source, run/status/timestamps — `EquipmentFacts`/hourly ERP sync.
- Bitrix technical-document links — Bitrix document-link integration.
- workforce identity/status/dates/positions — hourly workforce catalog.
- пустое значение, source outage или выключенный worker не передаёт ownership ручному editor.

### 2.5 Process-owned — только существующие commands

- ПТО и declaration date/details/file/corrections;
- actual opening/completion, actor/time;
- assignment orders/originals, composition/assignments/control engineer;
- inspections, checklist, photos, progress;
- transfer certificates и effective transferred deadlines.

Эти поля запрещены даже если пусты и actor имеет `objects.details.edit`.

### 2.6 Derived/system — не поля editor

Case state/readiness, computed progress, organization form, Кшах, premiums и другие calculations; local/legacy IDs, revisions, hashes/provenance, published snapshots, payments и technical audit identities.

## 3. Typed normalization и whole-command validation

Patch MUST быть JSON/form-map с exact уникальными string keys и scalar values; lists/nested objects/duplicate/unknown keys запрещены. Mixed allowed+forbidden payload отклоняется целиком.

Numbers проверяются без binary float; допустимая decimal запятая/точка нормализуется в canonical representation. Справочник проверяет code/value и выдаёт coherent raw/display. Пропущенный key означает «не менять». Пустой соседний input не сбрасывает существующий override и не подставляет `0`/`1`; явное очищение допустимо только для nullable field через однозначный wire representation. Любая ошибка даёт field-aware stable validation result и ноль writes.

Пример: `regnumber=00042`, `zavnumber=00123-А` после reload остаются точными строками. `zavnumber` >120 bytes отклоняется без truncation. `shaftBp`, `ptoactdate`, ERP dates, process state/progress или system ID отклоняют весь request.

## 4. Authorization, idempotency и concurrency

Owner проверяет active identity, exact capability и object scope до mutation. Actor/time берутся только из trusted runtime/server clock.

Fingerprint включает command kind, actor, object identity, expected revision и canonical normalized patch. Exact replay возвращает первоначальный outcome/revision/event identity без нового event. Тот же `requestId` с другим fingerprint даёт conflict. Stale revision даёт conflict и ноль writes. Параллельные команды к одной revision дают максимум одного winner; loser не пишет override/event/replay success.

Normalized no-op возвращает no-op на текущей revision, без event. Отмена modal, GET, validation failure и transport rejection ничего не пишут.

## 5. Persistence, provenance и история

Overrides, accepted-request identity и events хранятся production-owned additive schema отдельно от `fm_maintable` и `fm2_pilot_object_details`. Legacy mirror/payload/content hash byte-for-byte не меняются ручной правкой. Идентичный повтор importer не стирает override, не создаёт `MIRROR_CONFLICT`/`DETAIL_CONFLICT` из-за него и не меняет историю.

Одно accepted Save создаёт одно event со всеми изменениями. Event хранит object/case identity, revision, actor ID+display snapshot, server time и для каждого поля field identity/label, old/new typed/raw/display и unit/reference label. История восстанавливается из event, не из нынешнего object/справочника. Следующая правка не изменяет прежний event. Injected failure между override/event приводит к полному rollback.

## 6. Effective-value readers и consumer frontier

Shared public projection возвращает fallback source + override field-by-field вместе с truthful provenance. Override одного поля не объявляет corrupt source целиком исправным.

Текущие consumers MUST использовать effective values там, где поле является их текущим input:

1. `MariaDbYiiObjectCard`, `MariaDbYiiObjectCardProjection` и card view.
2. `MariaDbYiiObjectQueue` search/filter/pagination и применимые calendar/aggregates/reports.
3. Новые assignment-order/document snapshots; уже выпущенные документы неизменны.
4. `MariaDbBitrixOrderDocumentLinks`/technical document lookup для effective `zavnumber`.
5. `MariaDbErpEquipmentFactsCandidates`, `MariaDbEquipmentFacts` и local matching для effective `zavnumber`.
6. `MariaDbNativePremiumInputs`/`MariaDbNativePremiumInputsValues` для новых technical operands; Кшах выводится из effective `pitmaterial` с override-specific evidence/hash.

Save не делает network call и финансовую operation. При смене заводского номера старые ERP facts не выдаются за свежие подтверждения нового ключа; неоднозначность fail closed. Старые documents, accepted/published snapshots, payments и process facts не пересчитываются.

## 7. Yii card/modal и общая хронология

Существующая компоновка карточки не меняется. В header добавляется только компактная доступная pencil icon-button. `shlz-modal` использует публичные `shlz-ui` modal/field/input/select/button exports и ровно две группы:

1. «Идентификация и размещение».
2. «Классификация оборудования», включая числовые характеристики.

Кшах отсутствует. Failed validation сохраняет submitted values и показывает field errors. Cancel/backdrop/Escape writes nothing. Shared `shlz-modal__close` имеет понятный consistent icon, accessible name/focus и click/touch target не менее 40×40 px; исправление применяется ко всем app modals без изменения `../shlz-ui`.

После success/reload показаны effective values и manual provenance. В общей вкладке «История» событие «Данные объекта изменены» показывает accepted UI timezone, actor ID/display и все old → new; отсутствие — «Не указано». Значения HTML-escaped. Initial chronology page остаётся bounded; deterministic compound cursor/«Показать ещё» делает доступными ранние process и detail events, включая записи за пределами прежнего `LIMIT 8`. History access не шире card/object access.

## 8. Schema/recovery/deployment

Canonical additive migration покрывает fresh/repeat/exact-upgrade/compatible-partial/incompatible/concurrent states, не выполняет destructive repair и не удаляет прежние rows/history. Runtime не выполняет DDL и получает только exact DML. Current-image backup/restore inventory включает новые tables/auto-increment; restore сохраняет overrides/events/replay и допускает следующую revision.

Rollback web image скрывает editor, но не удаляет additive schema/data. Реальный import, внешние calls, merge и deployment не выполняются этой поставкой.

## 9. Acceptance matrix

| ID | Observable acceptance |
|---|---|
| A1 | Каждое безусловно editable поле заполняется из missing и исправляется независимо; provenance соседей не меняется. |
| A2 | Conditional plan разрешён до real fixing fact и запрещён после snapshot/opening/certificate priority. |
| A3 | Unknown/forbidden/`shaftBp`/mixed payload и invalid typed/reference values дают zero writes. |
| A4 | Leading zeros, 120-byte boundary, decimal normalization и null semantics сохраняют точный смысл. |
| A5 | Active+capability+scope required; missing capability/inactive/out-of-scope rejected. |
| A6 | Exact replay/no-op не дублируют; request-content conflict/stale/parallel loser не пишут. |
| A7 | Multi-field accepted Save атомарно пишет effective values и один полный immutable event; injected failure rolls back. |
| A8 | Второе исправление сохраняет первый event; >8 chronology entries доступны deterministic pagination. |
| A9 | Identical import сохраняет source bytes/hash, overrides и events без новых import conflicts. |
| A10 | Card/queue/search/filter/doc inputs используют effective values без изменения old snapshots. |
| A11 | Factory correction достигает Bitrix/ERP matching, не делает network call и не переименовывает старые ERP facts. |
| A12 | Effective material достигает нового OTIZ calculation; Кшах вычислен нормой и evidence не приписан old payload hash. |
| A13 | Canonical Yii POST проверяет auth/CSRF/method/outcomes; browser modal имеет две группы, unchanged card+icon, input retention/cancel/reload. |
| A14 | Shared modal close control доступен и визуально пригоден во всех app modals. |
| A15 | Migration/recovery lifecycle сохраняет data/history/replay и следующий write. |

## 10. Evidence and non-goals

- Owner issue #222 and approved local prototype decisions, 2026-09-21.
- `PRODUCT.md`, `CONTEXT.md`, pilot spec/data model.
- `MariaDbYiiObjectCard*`, legacy import/snapshot, OTIZ native inputs/norms, deadline certificate and ERP/Bitrix readers on `main` `80130fbb`.

Не входят mass edit, create/delete object, new business fields, EAV/form/RBAC/reference editor, process-rule changes, bidirectional sync, formula redesign, historical document/payment edits, new audit platform, SPA/card redesign or harness redesign.
