# Gate 5 code review: YII2-INSPECTION-PLANNING-UI-255

- Review date: 2026-09-25.
- Reviewer: independent agent `/root/final_review`; authored none of the reviewed specification, tests, or production implementation.
- Base: `b81b08d91ae5639f08413628b411587ae28176df`.
- Reviewed commit: `d1d83c2f6f77a50d109b37a94922d766a9b4b8ea` (clean worktree at review start).
- Harness source: `4725a08d7309ba08427e7dc6a47f9b36e2acdc42791c69ed2355d8962cae488f`; executable source `a86f091c4460f4caf370aaaf895ec74d1e4fc0ad448837bd13b49bc8306be068`.
- Implementation commits reviewed: `9518603f`, `9ae948a8`, `d1d83c2f`; Gate 3 history through the overflow, browser-focus, and calendar-cardinality approvals was reviewed.
- Verdict: **CHANGES_REQUESTED**.

## Standards

1. **BLOCKING — the calendar publication does not preserve server-side object scope.** `CalendarController.php:42-45` checks only the global `objects.read` capability and calls `MariaDbYiiObjectQueue::readCalendar()` without the actor (`:77-82`). `MariaDbYiiObjectQueue.php:28-53` consequently reads all current schedules without an actor/object-scope predicate. This violates the repository rule that every entry point preserves authorization and the contract requirement that engineers and Руководитель ФКР see plans only in their effective server-side scope. The focused role test covers an engineer's `ownership=mine` queue and out-of-scope POST, but never an engineer calendar read. Pass actor and effective scope through a canonical read seam and add a negative calendar visibility oracle.

2. **BLOCKING — queue and calendar duplicate domain event interpretation instead of using the canonical projection seam.** `MariaDbYiiChecklistRead.php:78-93` and `MariaDbYiiObjectQueue.php:38-53` independently parse `inspection_scheduled`/`inspection_rescheduled`/`inspection_cancelled` payload JSON and derive current state. This breaches the repository's public-application-seam rule and the reviewed design decision that controllers/views do not read events and both publications consume one canonical current-plan result. It is also a concrete **Duplicated Code** smell: the two SQL fragments already differ in join type and fallback behavior. Move the bounded, actor-scoped bulk read into the supplied planning projection/store and make both consumers use it.

No forbidden shared asset, #258 workforce/person/picker surface, migration, assignment writer, checklist/progress writer, ОТиЗ, invitation, outcome, violation, notification, or new persistence change was found in the production diff.

## Spec

1. **BLOCKING — out-of-scope plans can be disclosed by calendar.** The normative requirement says: “Engineer и Руководитель ФКР SHALL видеть и выполнять действия только в действующем server-side scope.” The current calendar path has no actor-bound scope at all, so an assigned engineer can receive inspection plans for unrelated objects. This is security-visible missing behavior, not merely an internal design preference.

2. **BLOCKING — the promised canonical object-keyed current-plan read is absent.** The contract requires the server to obtain the canonical current plan from the object-keyed read seam and queue/calendar to publish the same plan. The design further says `view/controller не читает events` and calendar consumes the same bulk result. Two independent event-table projections do not meet that requirement and can diverge from `YiiInspectionPlanning::currentPlan` as its semantics evolve.

3. **MAJOR — one Moscow date per request is not preserved across HTTP publication.** `CalendarController::now()` honors `FMONITOR_NOW`, but `MariaDbYiiObjectQueue::readCalendar()` discards it and recomputes wall-clock `now`; construction-control controller/read/view also obtain the date independently. Near Moscow midnight, bounds, current-plan filtering, marker, ordering, and the dialog's default date can disagree inside one response. Inject the controller's Moscow date through the shared read and render path. The existing rollover test exercises a directly constructed queue read, not these HTTP publications.

No unrelated product scope creep was found.

## Evidence

- `git diff --check`: PASS.
- PHP syntax for `ChecklistController.php`, `MariaDbYiiChecklistRead.php`, and `MariaDbYiiObjectQueue.php`: PASS.
- `php tests/Yii2/yii2_inspection_planning_ui_255_contract_test.php`: PASS.
- `php tests/Yii2/yii2_inspection_planning_today_priority_255_test.php`: PASS.
- Full local `make test` / `make verify` was not run, per owner decision.
- Harness reviewer preparation/admission remains unavailable because retained evidence coverage is incomplete (`action_authorized=false`; review results missing; GitHub/CI UNKNOWN). This limitation is explicitly **not** treated as approval, GREEN, or the basis of this verdict. Exact-source GitHub CI is still required after corrections and a new final review.

## Decision

Gate 5 is closed. Correct the actor-scoped calendar read, replace both direct event interpretations with one canonical current-plan projection, and propagate one request-scoped Moscow date. Add the missing calendar-scope and HTTP-midnight regressions, obtain any test-delta review required by the recomputed plan, and request a new independent final review of the corrected exact source before CI/publication.
