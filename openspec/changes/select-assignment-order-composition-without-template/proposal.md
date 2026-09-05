## Why

Владелец разрешил загрузку готового оригинала без шаблона. Independent inventory `docs/operations/assignment-order-direct-upload-seam-inventory-2026-09-05.md` показывает, что существующий public creator всегда вызывает renderer; original command требует уже сохранённый order/composition. Поэтому прямой пользовательский путь пока отсутствует.

## What Changes

- Добавить public application command сохранения выбранного состава одного распоряжения без вызова renderer и без generated artifacts.
- Проверять кадровые/объектные prerequisites и ровно одного инженера до atomic persistence immutable composition identity.
- Возвращать identity, пригодную для existing original upload; отдельное формирование шаблона остаётся необязательным действием.
- Сохранить append-only history, exact replay и concurrency protection. Выбор состава не применяет назначения, не открывает работы и не создаёт signed original.
- Сохранить observable текущие назначения, availability counters и inspection attribution при появлении новой неподписанной selection; запись новых rows не должна скрывать прежний applicable order через общий MAX(version).
- Зафиксировать exact command API, capability mapping, statuses/audit и draft correction semantics в executable Gate 1 до RED; planning не утверждает неизвестные legacy outcomes.

## Capabilities

### New Capabilities

- `pilot/assignment-order-composition-selection`: подготовка immutable выбранного состава без обязательного PDF render.

### Modified Capabilities

Нет изменений main capabilities; integration с existing prepare path требует отдельного exact Gate 1 disposition.

## Impact

Behavior slice: `ASSIGNMENT-ORDER-COMPOSITION-SELECT-001`. Actors — сотрудник и Руководитель ФКР в approved direct-original workflow. Источники: PRODUCT, pilot spec и owner original decision; legacy creator используется только для анализа coupling. Target seam candidate: `selectAssignmentOrderComposition(command)` внутри production application module. Release value — direct upload возможен без renderer/storage template dependency.

В scope: selection persistence и handoff original command. Вне scope: original bytes processing, read grants, opening, применение состава во времени, 1С ДО, изменение protected PILOT-E2E-FLOW-001, новая domain logic в rapid-pilot. Existing prepare/render не удаляется до reviewed integration.

Planning gaps для exact Gate 1: authorization selection vs existing prepare/upload capabilities, техническая immutable version identity и physical date compatibility с existing composition hash. Политика append-only replace_pending уже утверждена владельцем 2026-09-05, exact record `owner-e2e-admission-and-pending-selection-approval-2026-09-05-1842Z.md`. Они не заполняются догадками об observed legacy behavior; NEEDS_GRILL только если потребуется новое продуктовое решение сверх approved original workflow.

## Storage drafting decision — 2026-09-05

Separate dateless selection ledger is the selected candidate. A canonical shared
identity registry/allocator must preserve existing order IDs/case versions and
serve both new selection and legacy preparation. This expands the exact
integration manifest to include legacy writer cutover and original-reader source
resolution. It does not change original PDF processing or effective applicability.
Detailed requirements are in the appended design; exact schema/version and
executable ports remain Gate1 work, not an approved implementation.

Combined-release clarification 2026-09-05: dateless storage requires exact
cross-source pending/current rules and a same-identity optional-render public
path. Legacy preparation's selected-case guard does not complete that path or
permit an easier direct-only release. These dependencies are part of the
consolidated executable Gate1/integration package, with no Done claim from
storage or selector alone.

## Technical correction v0.5 — 2026-09-05

Executable candidate v0.5 закрепляет closed result/lookup factories,
проверку malformed dependency payload, typed stage/audit receipts вместо
придуманных AUTO_INCREMENT IDs и полную legacy prepared/registered × original
normalization. Это технические уточнения утверждённого two-mode workflow.
Product policy REPLACE_PENDING закрыта; её не нужно пересогласовывать.
Independent v0.4 review и writer/reader cutover inventory сохраняют P0 blockers:
exact migration/backfill/receipt, совместимость всех writers, original reader
и same-identity optional render. Ни одна ветвь ещё не допущена к RED.

## Technical flow correction v0.6

По independent v0.5 rereview один invocation-owned clock читается lazily перед
первым необходимым audit/terminal fact; full matching replay clock не читает.
Clock failure до persistence даёт dependency_unavailable. Callback/UoW передают
closed rollback cause; stage не владеет commit/rollback. Полная таблица
stage→decision→UoW→public outcome закреплена в executable v0.6, включая
request race, invalid generated receipt и unknown acknowledgement. Это technical
уточнение прежних outcomes; Gate1 и P0 release dependencies остаются открыты.
