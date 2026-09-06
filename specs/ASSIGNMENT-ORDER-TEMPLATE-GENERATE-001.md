# ASSIGNMENT-ORDER-TEMPLATE-GENERATE-001

Версия0.1, 2026-09-06. Candidate Gate1.

## Простыми словами

ФКР получает PDF-шаблон для сохранённого текущего состава с сегодняшней датой.
Файл и его версии не хранятся. После успешного формирования остаются дата и audit;
это не original, не применение состава и не открытие работ.

## 1. Authority и public seam

Authority: selection-template-no-storage-owner-approval-2026-09-06.md,
fresh-launch-owner-scope-2026-09-06.md, SELECT-001 v0.11. Оба selection modes
поддерживаются через одну immutable identity. Actor — active ФКР/Руководитель ФКР.
Техническое permission mapping — existing assignment_order.composition.select:
формирование является действием над выбранным составом, новых grants не вводится.
Admin/read-only без этого exact capability не получает действие.

Namespace FMonitor2\AssignmentOrderComposition:

```php
interface AssignmentOrderTemplateApplication {
    public function generateAssignmentOrderTemplate(int $caseId,int $orderId,int $actorId): array;
}
interface AssignmentOrderTemplateDateReader {
    public function find(int $caseId,int $orderId): array;
}
ProductionAssignmentOrderTemplateFactory::create(\mysqli $db,string $prefix=''): AssignmentOrderTemplateApplication;
ProductionAssignmentOrderTemplateFactory::dateReader(\mysqli $db,string $prefix=''): AssignmentOrderTemplateDateReader;
AssignmentOrderTemplateVerificationFactory::create(\mysqli $db,string $prefix,SelectionClock $clock,\Closure $render): AssignmentOrderTemplateApplication;
```

Render closure соответствует existing renderAssignmentOrder(array):array; production
использует ProductionPdfAssignmentOrderRenderer, без StoringAssignmentOrderRenderer.
Verification меняет только clock/renderer; SQL и transaction реальные. Native SQL
остаётся в MariaDb adapters того же модуля. Никаких HTTP/runtime DDL.

Result exact keys: status,reasonCode,assignmentOrderId,templateDate,filename,
mediaType,bytes. Success status=generated, reasonCode=null, exact requested orderId,
Moscow date, filename=Распоряжение о закреплении монтажников.pdf,
mediaType=application/pdf, nonempty PDF bytes. На отказе все payload fields null;
status rejected/conflict/failed и reasonCode из таблицы ниже. IDs1..PHP_INT_MAX;
invalid prefix вне ASCII[0..25] — InvalidArgumentException до I/O.

| Case | status/reasonCode |
|---|---|
| invalid ID | rejected/invalid_command |
| denied capability | rejected/authorization_denied |
| missing case/identity либо other-case identity | rejected/order_not_found |
| requested selection не latest | conflict/target_not_current |
| completed/PTO | rejected/object_completed / object_has_pto_act |
| unavailable/malformed source/clock | failed/dependency_unavailable |
| renderer throws/invalid result | failed/render_failure |
| confirmed persistence rollback | failed/persistence_failure |
| uncertain commit/rollback | failed/persistence_outcome_unknown |
| proven generated event ID overflow | failed/allocation_capacity_exhausted |

## 2. Generation transaction

Shape → exact authorization → owned transaction с exact case row lock. Нет ambient
transaction ownership: чужую работу нельзя commit/rollback. Under lock проверить
case/identity ownership, latest selection, completion/PTO и immutable composition
по approved registry/selection rules; legacy source не усыновляется. Source failures
не становятся absence. Затем получить один UTC clock instant, вывести Moscow date,
прочитать текущий object snapshot и сохранённые engineer/member snapshots.

Renderer получает version, assignmentOrderDate=Moscow date, organizationType по
числу members, immutable names/IDs/positions и object address/entrance/regnumber/
planned dates. Current HR не переписывает выбранный snapshot. Необходимые renderer
fields должны быть валидны; отсутствующие/невалидные object данные — dependency_unavailable.

Рендер выполняется в памяти под case lock, затем записывается audit и commit.
Lock удерживается до завершения, поэтому pending replacement/original не пересекают
generation target. Generated PDF не проходит отдельный storage path. Bytes выдаются
только после подтверждённого commit. Каждый повторный вызов — новая генерация и
новый audit с текущей датой; request-key replay/PDF caching здесь не обещаются.
Renderer failure не создаёт audit успеха и не меняет дату последнего формирования.
Отказы не создают generation facts; existing transport security audit остаётся
своим owner. Неопределённый commit не повторяется автоматически и bytes не выдаёт.

## 3. Единственный persisted fact и read projection

Existing fm2_process_events получает event_type=assignment_order_template_generated,
installation_case_id, actor_user_id, occurred_at=тот же UTC instant и payload_json
с exact keys assignmentOrderId,assignmentOrderVersion,compositionIdentity,
compositionSha256,templateDate. JSON canonical UTF8 без final LF. Никаких bytes,
filename/storage identity/template version в БД. Event id lossless1..PHP_INT_MAX
проверяется до commit. Старые events/selection/original/assignment/opening не меняются.

Date reader вызывается owning application/HTTP только после своей authorization
(selection либо original capability); как registered composition reader, он не
присваивает себе новый grant. Проверяет case/order ownership и читает generation
facts этой identity. Result exact keys status,date: found+YYYY-MM-DD, not_found+null,
unavailable+null. Другой case/нет identity/нет generation → not_found. Malformed
matching event или query failure → unavailable. Последний успех — greatest event ID,
а не MAX(date); корректность date/time/identity/hash проверяется. Files не читаются.

## 4. Evidence и Done

Real native selection81 (7001/73), clock2026-09-05T21:30:00Z → напечатанная дата
2026-09-06, один generation event и date projection2026-09-06. Повтор с
2026-09-06T21:30:00Z →2026-09-07, та же composition, два audit facts. Existing PDF
renderer проверяется real PDF text/visual QA; его bytes не подменяются fake header.

RED/Gate3: missing public generator; success/date/audit/no-storage; repeat current
date; renderer failure сохраняет предыдущую дату и direct-original возможность;
replaced target; authority/other-case/completion/PTO; native rollback and projection
integrity. No new schema/migration version. Gate4 минимальная implementation,
focused selection/original/render regression, architecture, independent Gate5.
Parent task3.2 закрывается только после этих evidence. HTTP/PDF download wiring и
полный portal VERIFY_OK/deploy остаются следующими пакетами.
