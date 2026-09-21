## Context

См. `proposal.md` и capability spec. Текущие object identity/ordinary details приходят из immutable legacy mirror и versioned object-detail snapshot; карточка и другие readers читают их напрямую, history projection ограничивает выдачу восемью событиями. Процессные данные уже имеют самостоятельные owners и append-only storage. Изменение пересекает InstallationProcess, OTIZ/ERP/Bitrix consumers, IdentityAccess, Yii runtime, canonical migrations и recovery inventory, поэтому требует отдельной native ownership boundary, а не UPDATE legacy rows или универсальной формы.

## Goals / Non-Goals

**Goals:**

- Один глубокий application owner для typed patch, authorization, revision/replay, atomic override+event и effective-value read model.
- Явная field registry, используемая командой, form projection, diff rendering и consumer mapping без принятия произвольных JSON keys.
- Сохранение исходных import bytes/hash и исторических published artifacts при применении overrides только к текущим reads/новым outputs.
- Минимальный Yii editor на существующей карточке и совместимая общая chronology с pagination.

**Non-Goals:**

- Generic EAV/form builder/event-sourcing framework, запись в legacy mirror/import snapshot, редактор интеграционных или process-owned данных.
- Внешний sync/network call при Save, backfill старых документов/расчётов, изменение нормативной таблицы Кшах.
- Новый top-level экран, redesign карточки, Vue/SPA или перенос domain ownership в controller/view/JavaScript.

## Decisions

### 1. Owning module and public seam

`InstallationProcess` владеет `ObjectDetailsEditApplication` (точное имя executor может согласовать с существующей vocabulary) и пассивной command DTO. Owner получает actor authorization port, current object/effective projection, field registry, transaction-capable repository и server clock. Yii controller выполняет transport parsing/CSRF, вызывает owner и отображает стабильные outcomes. OTIZ, Bitrix, ERP и queue зависят только от public effective-value reader, не от override tables.

Альтернатива — UPDATE `fm_maintable`/snapshot из controller — отвергнута: ломает import repeatability, provenance, history и единственного владельца mutation.

### 2. Fixed typed field registry

Registry перечисляет 11 безусловных полей и два условных поля исходного плана, их wire names, scalar/reference types, normalization, validation, display label/unit и consumer semantics. `shaftBp` отсутствует: OTIZ вычисляет Кшах из effective `pitmaterial`. Registry не принимает ambient columns и не становится runtime-конструктором.

Справочные поля хранят подтверждённое canonical значение/code и display snapshot события. Числа преобразуются decimal/integer logic без binary float. Номера остаются strings. Условие редактирования исходного плана читает реальные immutable document/process facts и transfer-certificate priority, а не непустой текст даты.

Альтернатива — loose JSON/EAV без registry — отвергнута из-за mass-assignment, type drift и невозможности доказать consumer coverage.

### 3. Separate current overrides plus append-only events

Additive schema создаёт current override aggregate на object/case identity с monotonic revision и typed validated JSON (либо эквивалентные фиксированные columns, если frontier этого требует), append-only events с immutable diff payload и request replay table/identity. Transaction locks aggregate/current revision, validates the whole normalized patch, writes changed overrides and exactly one event, then commits. Event stores old/new typed/raw/display snapshots; current reads need not recalculate history.

Альтернатива — только events с fold каждого reader — отвергнута как избыточная новая event-sourcing platform; только current JSON без event — не удовлетворяет audit/history.

### 4. Effective-value overlay and consumer frontier

Shared reader overlays manual values on immutable source details field-by-field and returns value plus provenance/evidence. Corrupt base snapshot remains corrupt where required inputs/evidence cannot be proven; override одного поля не blesses весь source. Existing consumers migrate explicitly:

- card and queue/search/filter/pagination;
- applicable calendar/aggregate/report/document inputs;
- Bitrix link lookup and ERP candidate/matching for `zavnumber`;
- OTIZ native premium inputs for current technical values and derived Кшах.

Existing published snapshots/documents/payments retain embedded values and evidence. On factory-number change, historical ERP facts remain linked to prior key and current matching requires fresh/non-ambiguous evidence.

Альтернатива — поправить только displayed card — отвергнута: SQL filtering/matching and calculations would disagree with UI.

### 5. Authorization and Yii UI

Registry adds `objects.details.edit`; local role sync grants it to exact `fkr_operator` and `manager`. Owner separately checks active identity, capability and object scope. Canonical route is `POST /pilot/objects/<id>/details` with `requestId` and `expectedRevision`; GET remains card read.

Card markup changes only by one compact pencil icon in the existing header. A native `<dialog class="shlz-modal">` uses public `shlz-ui` modal/field/input/button components and two fieldsets. Failed validation re-renders submitted values and field errors; Cancel/backdrop/Escape write nothing. The shared close control receives an app-level accessible icon treatment (40px target, consistent strokes) applicable to every `shlz-modal`, without changing `../shlz-ui` source.

### 6. Unified paginated chronology

Projection merges process and object-detail events into one stable descending chronology with `(occurred_at, stable_id/type)` cursor semantics. Initial page preserves current density; «Показать ещё» fetches/returns older events without hiding prior results. Projection authorization is identical to object/card access. Diff strings are escaped at render time; snapshots remain structured data.

### 7. Migration, verification and architecture impact

Canonical migration version increments additively and updates current-image backup inventory/auto-increment profile. Runtime account receives only exact DML. Architecture checks SHALL keep Yii free of direct domain SQL, enforce one owner and route allowlists, and include new asset in CSP-controlled asset routing. `rapid-pilot/` remains unchanged except existing fixtures may consume public projections; no new domain logic goes there.

Verification uses table-driven owner/DB tests for every allowed field and rejection class, canonical Yii HTTP/browser tests, importer repeatability, consumers/matching/OTIZ evidence, migration/recovery and focused architecture checks. The planner selects lane/reviews; this stateful persistence/financial-input change is expected to require Gate 3 and final independent review, not inferred FAST.

Quality Graph получает минимальный exact capability ownership entry `object-details-editing`: только новые owner/registry/persistence/schema files, существующий canonical object-card verifier и зарегистрированные consumer chains для legacy import, Yii reads, ERP facts и OTIZ norms. Это не меняет lane/classifier/admission rules; entry нужен, чтобы protected domain owner не оставался без проверяемого владельца.

## Risks / Trade-offs

- [Risk] Consumer missed and shows/filter old source value → Mitigation: explicit frontier inventory, addressable tests per real consumer and reviewer checklist.
- [Risk] JSON shape permits accidental mass assignment → Mitigation: fixed registry validates exact top-level keys and typed values before transaction; mixed payload fails entirely.
- [Risk] Factory key correction falsely reuses ERP evidence → Mitigation: evidence remains bound to observed key/source run; changed key requires fresh unambiguous match.
- [Risk] Plan edit bypasses transfer process → Mitigation: owner checks immutable fixing facts and certificate priority; UI visibility is not enforcement.
- [Risk] History pagination becomes unstable on equal timestamps → Mitigation: compound stable cursor and deterministic ordering.
- [Risk] Large cross-cutting slice increases review cost → Mitigation: vertical checkpoints by public seam, complete field matrix, no unrelated refactor, exact-source CI once.

## Migration Plan

1. Apply additive canonical schema migration and exact DML grants; update backup/restore inventory before enabling route.
2. Deploy owner/read overlay and migrate internal consumers while editor capability remains unavailable.
3. Sync capability to approved business roles and enable canonical route/UI in the same candidate.
4. Verify existing import, process commands, published history and restore on exact candidate; no data backfill or external sync is run automatically.
5. Rollback web image may stop exposing editor but MUST leave additive schema/events intact; forward image reads them again. Schema/data deletion is not a rollback action.
