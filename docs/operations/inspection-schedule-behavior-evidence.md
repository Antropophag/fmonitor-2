# Inspection scheduling behavior evidence

Date: 2026-09-01  
Scope: discovery only; current rapid-pilot oracle, not an approved scheduling policy.

## Sources inspected

- `PRODUCT.md`, `CONTEXT.md`, `docs/fmonitor-2-pilot-spec.md`, and `docs/fmonitor-2-pilot-data-model.md`.
- PB-06 in `docs/operations/pilot-behavior-inventory.md`, GRILL-001 in `docs/operations/migration-backlog-and-grill.md`, the proposed module map, and the runtime-DDL migration plan.
- `rapid-pilot/InspectionSchedule.php`, `rapid-pilot/inspection-schedule.js`, `rapid-pilot/ObjectQueue.php`, and the dispatch/wiring in `rapid-pilot/router.php`.
- `rapid-pilot/Calendar.php`, `rapid-pilot/calendar.js`, `rapid-pilot/verify-calendar-projections.php`, and `app/PilotHttp/ConstructionControlView.php`.
- `app/PilotHttp/AccessPolicy.php`, `app/RapidPilot/LocalRoleCatalog.php`, and `rapid-pilot/UserAccessView.php`.

## Current public seam and actor

The state-changing pilot seam is `POST /pilot/objects/{positive-decimal-id}/inspection-schedule`, dispatched directly to `RapidPilotInspectionSchedule::handle(...)`. The request carries `csrfToken` and `inspectionDate`; actor identity comes from `FMONITOR_AUTH_USER_ID`. There is no application-module command behind this endpoint: the HTTP-oriented rapid-pilot class owns validation, SQL, transaction handling, redirect, and runtime DDL.

An actor is accepted when the local pilot account and all joined local roles are active and at least one active role grants the exact string permission `inspection.schedule`. The built-in construction-control role currently grants it. The actor need not be the object's assigned control engineer and no relationship between scheduler and engineer is checked. The object queue displays the button only to an actor with this permission and only on rows rendered as `В работе`, but the endpoint itself is authoritative and independently checks state.

The target seam candidate remains `InspectionPlanning::scheduleInspection(installationCaseId, inspectionDate, actorId)` (with a current-assignment read dependency). This is a proposed ownership boundary, not approval of the pilot rules described below.

## Exact accepted behavior

The endpoint accepts one calendar date when all of the following hold:

1. the method is POST and the route contains a positive non-zero decimal object id;
2. submitted and authenticated-session CSRF tokens are both non-empty and compare equal;
3. the actor id is a positive integer and currently has `inspection.schedule`;
4. `inspectionDate` is exactly a valid Gregorian `Y-m-d` string;
5. the date is today or later in the `Europe/Moscow` calendar;
6. an installation case exists for the legacy object and its state is exactly `working` or `needs_assignment_change`;
7. the highest `version_no` assignment order for that case is `registered` and has a positive `control_engineer_user_id`.

On first acceptance of the tuple `(installation_case_id, current_control_engineer_user_id, inspection_date)`, one schedule row is inserted with redundant case/object/engineer identities, actor id, and server-generated `scheduled_at`. In the same database transaction, one event is appended with:

- `event_type = inspection_scheduled`;
- schedule and installation-case ids;
- actor id and the same server time;
- JSON payload containing `scheduleId`, `inspectionDate`, and `controlEngineerUserId`.

The response is `303` to `/pilot/objects?inspectionScheduled=<date>` with `Cache-Control: no-store`. The queue then renders a presentation-only success notice. The same redirect is returned for an exact duplicate, so the HTTP result does not distinguish creation from a no-op.

`FMONITOR_NOW`, when parseable, controls both the Moscow “today” boundary and `scheduled_at`; otherwise the pilot silently falls back to actual current time. `scheduled_at`/`occurred_at` are `DATE_ATOM` strings, not database timestamps.

## Rejections and failure boundaries

- Non-POST: `405`; malformed/non-positive route: the router does not select this handler, while direct handler invocation reports `404`.
- Missing/mismatched CSRF: `403` before actor, date, or object lookup.
- Missing/invalid actor or missing permission: `403`.
- Empty, whitespace-padded, non-zero-padded, impossible, or otherwise non-exact `Y-m-d`: `422`.
- A valid date before Moscow today: `422`.
- Missing case, any state other than `working|needs_assignment_change`, no assignment order, latest order not registered, or non-positive engineer id: one undifferentiated `409`.
- SQL/encoding/transaction errors: rollback attempt, generic `503`, and server-side error log. A failure before `begin_transaction()` still enters the rollback path harmlessly.

Exact Russian error strings and status-code grouping are pilot presentation behavior, not proposed product semantics.

Important edge behavior:

- today is accepted; there is no upper date bound, opening-date bound, planned-finish bound, completion/cadence rule, holiday rule, or requirement that the selected date be within seven days;
- selection uses the order with numerically greatest `version_no`, not “latest registered”; a newer prepared/cancelled order blocks scheduling even if an older registered order exists;
- multiple future dates for the same case/engineer are accepted, including dates out of chronological insertion order;
- the same case and date is accepted again after the current engineer changes because engineer id is part of the uniqueness key;
- no route reschedules, cancels, completes, or supersedes a schedule, and no schedule status is stored;
- tables have indexes but no foreign keys, so relational validity is enforced only by this request path.

## Duplicate, idempotency, and concurrency

The database unique key is `(installation_case_id, control_engineer_user_id, inspection_date)`. `INSERT IGNORE` makes an exact repeat a successful no-op: no second schedule, no second event, and no mutation of the original actor/time. There is no caller-supplied idempotency key, so this is value identity rather than request identity.

Concurrent exact repeats are serialized by the unique index: at most one insert reports one affected row and only that transaction appends an event. Which actor becomes `scheduled_by_user_id` is unspecified. The other request returns the same success redirect.

The case/order lookup occurs before the transaction and does not lock either row. A concurrent reassignment can therefore leave a newly inserted schedule referring to the engineer observed before the reassignment committed. If two callers observe different current engineers, both same-date rows may exist. This stale-assignment race is current pilot behavior and must not be promoted.

The schedule insert and its event are otherwise atomic within one transaction. `INSERT IGNORE` is broader than an explicit duplicate handler and can suppress database warnings unrelated to idempotency; that mechanism is implementation behavior, not a target contract.

## Observable projections

- `RapidPilotCalendar` projects every stored schedule in its bounded date window as a separate `inspection` / `scheduled` event, linked to the object. Calendar access requires `objects.read`, not assignment to the schedule's engineer. It does not consume the schedule-event table.
- `RapidPilotInspectionSchedule::enhanceControl(...)` selects schedules for Moscow today, moves matching object rows to the front, and adds `Запланировано на сегодня`. It matches only legacy object id, not the viewing user or stored engineer; future and past schedules do not affect this queue decoration.
- The base construction-control queue's “last inspection” value comes from checklist activity, not scheduling. Scheduling does not create an inspection/evidence fact and does not advance checklist progress.
- `verify-calendar-projections.php` characterizes bounded/deterministic calendar rendering but seeds no schedule and therefore does not verify schedule creation, duplicate behavior, event atomicity, authorization, or date rejection.

## Persistence inventory

Mutated:

- `fm2_pilot_inspection_schedules`;
- `fm2_pilot_inspection_schedule_events`.

Read for eligibility and authorization:

- `fm2_installation_cases`;
- `fm2_assignment_orders`;
- `fm2_pilot_users`;
- `fm2_pilot_user_roles`;
- `fm2_pilot_roles`;
- `fm2_pilot_role_permissions`.

Read by projections: schedule rows plus legacy `fm_maintable`. The schedule-event table currently has no read consumer. Both schedule tables are still created at runtime by `ensureSchema(...)`, including from request/read paths and bootstrap; canonical DDL ownership is a separate release-critical migration concern.

## Classification

- **ACCEPTED product direction:** inspection planning is assigned work distinct from inspection evidence; state changes require an explicit application seam and exact capability; audit/history must be append-only; calendar and queues are read models only.
- **PILOT_ONLY current oracle:** manual free-date insertion; today/future validation; value-tuple duplicate no-op; assignment to the engineer from the highest-version registered order; success redirect; current event shape; unrestricted number of dates; queue decoration only for today; broad `INSERT IGNORE`; stale-assignment race; runtime schema creation.
- **UNKNOWN / GRILL-001:** first and recurring due-date formula, seven-day boundary and holiday semantics, who may choose/change a visit date, relationship between scheduler and assigned engineer, whether multiple pending visits are valid, reschedule/cancel/overdue behavior, reasons/audit for changes, reassignment handling, and whether a planned visit is closed by an inspection fact.

Nothing marked `PILOT_ONLY` or `UNKNOWN` is an accepted target requirement.

## Recommended narrow characterization slice

Create `CHARACTERIZE-INSPECTION-SCHEDULE-DUPLICATE-001` as an explicitly `PILOT_ONLY` oracle characterization, without approving cadence, reschedule, or cancellation policy:

1. use the existing POST public seam against an isolated MariaDB prefix and fixed Moscow clock;
2. seed an authorized scheduler, a `working` case, and a highest-version registered order with one engineer;
3. submit the same valid future date twice and prove both HTTP outcomes are successful redirects;
4. prove exactly one schedule and exactly one matching `inspection_scheduled` event exist, with unchanged first actor/time and matching payload;
5. prove invalid date, past date, missing permission, and ineligible-case requests cause zero schedule/event mutation;
6. optionally race the exact same tuple through two processes and assert one schedule/one event without asserting the winner.

Do not assert a seven-day rule, automatic next date, maximum pending schedules, target authorization roles, exact error copy, reschedule/cancel semantics, stale-assignment behavior, or that scheduling completes/creates an inspection. The target `INSPECTION-SCHEDULE-001` slice remains blocked by GRILL-001; this characterization only records a narrow existing oracle and can support canonical schema ownership independently.
