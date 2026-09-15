# YII2-CONTROL-ENGINEER-ASSIGNMENT-001 — самостоятельное закрепление инженера

Status: `ACCEPTED_FOR_GATE_2`
Owner decision: issue #52, уточнение 2026-09-15
Actors: active FMonitor user; mutation requires exact permission `control_engineer.assign`
Public seams: native application command; `POST /pilot/objects/{objectId}/control-engineer-assignment`; `GET|HEAD` карточки и preparation; existing native composition/application flow

## Простыми словами

Текущий инженер объекта становится самостоятельным append-only операционным фактом. Его явно назначают или заменяют из карточки, а подготовка распоряжения только показывает актуального инженера и сохраняет его исторический snapshot. Старые документы и applications не меняются. До первого самостоятельного факта допустим только read-only bootstrap из последнего подтверждённого native application; legacy и случайный пользователь не являются fallback.

## 1. Current assignment read

Reader принимает positive `objectId` и возвращает ровно один outcome: `found` с object/case/revision, engineer snapshot и provenance; `missing`; `not_found`; либо `unavailable` при schema/cardinality/linkage/lineage corruption.

При standalone history authoritative является единственная coherent строка с максимальным `assignment_sequence`; provenance `standalone`, revision равна sequence. Reader проверяет append-only lineage: case/object, previous assignment ID/engineer, sequence и snapshots. Applications после первого standalone fact не смешиваются.

Если standalone rows нет, допускается только единственная latest по `application_sequence` coherent `fm2_assignment_order_applications` row case. Engineer snapshot берётся из `selected_snapshot_json.selectedEngineer`, согласованного с `control_engineer_user_id` и composition hash; provenance `native_application_bootstrap`, revision `0`. Read не создаёт standalone fact. Zero applications даёт `missing`; malformed/ambiguous latest — `unavailable`.

`fm_maintable.responsstroicontrol`, `fm2_assignment_orders` без подтверждённого application, каталог пользователей и rapid-pilot не являются fallback.

## 2. Append-only application command

Command: lowercase UUIDv4 `requestId`, positive PHP-int `objectId`, `engineerUserId` и `actorUserId`, `expectedRevision` 0..2147483647. Невалидный typed command бросает `InvalidArgumentException` до owner, SQL и clock.

Result status: `assigned|replayed|rejected|conflict|failed`; safe reasons: `authorization_denied`, `object_not_found`, `engineer_not_eligible`, `no_changes`, `assignment_changed`, `request_id_conflict`, `dependency_unavailable`, `persistence_failure`. Только assigned/replayed имеют assignment payload.

Owner проверяет idle compatible schema, unique case/object, active local actor/role с exact permission `control_engineer.assign`, active local engineer с active exact role `construction_control_engineer`, current assignment и expected revision внутри одной transaction после case lock. Иные permissions или administrator name право не дают.

Immutable row хранит assignment ID, case/object, sequence, new engineer ID/FIO/position snapshot, nullable previous assignment ID/engineer, bootstrap application provenance, actor ID, UTC timestamp, request ID/fingerprint. Previous rows never UPDATE/DELETE; отдельный generic event framework не создаётся.

Первый standalone fact имеет sequence1. После bootstrap previous assignment ID null, previous engineer ID и bootstrap application ID сохраняются. При missing оба previous значения null. Следующие rows ссылаются на previous standalone row/engineer. Тот же engineer → `rejected/no_changes` без write.

Fingerprint: SHA-256 canonical UTF-8 JSON `[requestId,objectId,engineerUserId,expectedRevision,actorUserId]`. Matching authorized replay возвращает прежний payload без второй строки; другой fingerprint → `conflict/request_id_conflict`. Разные commands с одной expectedRevision сериализуются case lock: максимум одна assigned, следующая → `conflict/assignment_changed`. Любой наблюдаемый persistence failure rollback-ится и не выдаёт assigned.

## 3. Yii2 card mutation

Карточка GET/HEAD с `objects.read` показывает current engineer и provenance. Actor с exact `control_engineer.assign` видит form с engineer select, request ID и expected revision. `POST /pilot/objects/{id}/control-engineer-assignment` принимает urlencoded UTF-8, CSRF и вызывает owner command; route допускает только POST.

Assigned/replayed →303 на canonical card. Invalid transport →400/413/415; unauthorized →403; missing object →404; conflicts →409; ineligible/no changes →422; unavailable/persistence → sanitized503 with `Retry-After: 60`. Отказы не создают assignment facts.

## 4. Preparation and immutable snapshot

`GET|HEAD /pilot/objects/{id}/assignment-order/selection` показывает found engineer справочно. DOM не содержит engineer select/radio, `controlEngineerUserId` или `controlEngineerConfirmed`; read не создаёт facts. Missing показывает «Сначала закрепите инженера в карточке объекта» без enabled submit; unavailable → sanitized503.

Composition POST использует installers, existing request/mode/selection revision и показанную form current-assignment revision. Legacy/forged engineer fields могут быть приняты как inert compatibility input, но игнорируются и не влияют на authority. Selection owner под case lock перечитывает current assignment; missing → `control_engineer_required`, несовпадение с form assignment revision → `assignment_changed`. Для старых native callers без этого transport field owner использует current revision в момент команды, не client engineer ID. Success сохраняет current engineer ID/FIO/position как immutable existing selection/order snapshot. Последующее original/application копирует snapshot; replacement его не переписывает.

## 5. Worked regression A–H

Fixture: object4512/case6101; authorized actor18; unauthorized actor95; engineers73=A,74=B,75=C; application81 sequence1 snapshot A; installer7001.

A. No standalone → read A/revision0/`native_application_bootstrap`/application81, zero writes.

B. request `52525252-0001-4525-8525-000000000001`, expected0, B, actor18 → assigned sequence1; previous=A/application81; current=B.

C. Application81/snapshot/hash remain byte-equivalent and project A.

D. Preparation shows B read-only, no engineer selector/confirmation, GET no writes.

E. New composition/order/application stores immutable B snapshot; later replacement does not rewrite it.

F. actor95 → authorization_denied/HTTP403, zero writes.

G. expected1 B→C creates sequence2 referencing sequence1; current=C; history reconstructs bootstrap A → B → C; application81 remains A.

H. #40 current-engineer projection uses C without changing opening/application history. #38 installer directory continues using latest application installer snapshots; standalone engineer replacement changes neither installer composition nor application history.

## 6. Scope and verification

Allowed: one additive canonical table with indexes/FKs/checks, existing migration catalogue and explicit fixture/deployment permission registration. Runtime DDL and implicit grants forbidden.

Out: rapid-pilot read/change, legacy backfill/writer, district rules, #20/#45/#14/#131/#141, calendar, checklist/offline, generic frameworks, order/application redesign, historical reprojection, unrelated refactor.

Gate2 covers A–H plus replay/concurrency/authorization/fail-closed/read-only. Required witnesses: #40 construction-control preopening/opening and #38 installer-directory native assignments. Planner focused checks, architecture check, independent reviews and one exact-source CI are mandatory; full local suite forbidden.
