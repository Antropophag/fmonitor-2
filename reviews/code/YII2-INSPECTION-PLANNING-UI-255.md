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

---

## Gate 5 correction rereview — 2026-09-25

- Reviewed commit: `c91f8c517d9cfd6a1130ebf9d7ed61aa049ec6e4` over prior verdict commit `34d41b6b`.
- Harness source: `a5900a5f877fd1433b8fcd5e65cdb21070eccbbf7c1e0830655a828e9d2d0051`; executable source `efb9fbe398a32dce156bb88265c36ed4ab7415430e2729782828c95aad4027a3`.
- Test corrections reviewed: `1b8c3687`, `aa213f00`; independent delta approval: `aa497fdc`.
- Production correction reviewed: `c91f8c51`.
- Verdict: **CHANGES_REQUESTED**.

### Prior findings disposition

1. **Calendar actor scope — partially resolved.** The real HTTP matrix now requires successful engineer and FKR calendar responses and proves exclusion/inclusion of an out-of-engineer-scope plan. `CalendarController` passes the authenticated actor and the reader scopes both inspection and planned-date events.

2. **Canonical projection — resolved.** Both queue and calendar call the single `MariaDbYiiInspectionPlanning::currentProjectionSql()` definition. Neither consumer independently decodes event payloads now.

3. **One request Moscow snapshot — resolved.** Calendar passes its controller snapshot through the read, and construction-control passes one instant through queue projection and rendering. The view no longer reacquires the clock in the normal controller path.

### Remaining findings

1. **BLOCKING — mixed-role scope precedence differs between commands and reads.** `MariaDbYiiInspectionPlanning::actorHasObjectScope()` grants global scope whenever an actor has role `manager` plus `objects.read`, even if the same actor also has active `control_engineer`. Both queue and calendar instead make any active `control_engineer` role take precedence and restrict the actor to current assignments (`MariaDbYiiChecklistRead.php:74-77`, `MariaDbYiiObjectQueue.php:39-42`). Therefore a `manager + control_engineer` actor can successfully create a plan on an unassigned object and then be unable to see it in either required publication. Use one shared scope policy for command, queue, and calendar, with the explicitly selected mixed-role precedence, and add a real HTTP create/read assertion for that combination.

2. **BLOCKING — construction-control global read does not require `objects.read` or an approved global role.** `MariaDbYiiChecklist::queue()` requires only `construction_control.read`; its new `$globalScope` is simply the absence of an active `control_engineer` role. Consequently any non-engineer role carrying `construction_control.read`, even without `objects.read` and without `manager`/`fkr_operator`, receives every object's current plan. Calendar correctly gates at the controller on `objects.read`, and the command seam requires that permission for global FKR/manager scope, so the three surfaces are inconsistent. Bind global queue visibility to the same active-role plus `objects.read` policy and test revocation/missing permission at the real queue route.

The old compatibility overload `readCalendar(string $first, string $last)` intentionally retains an unscoped actor `0` for existing internal consumers. No production HTTP caller uses it; this compatibility path must not become an alternate user entry point.

### Focused evidence

- `git diff --check`: PASS.
- PHP syntax for the three corrected projection/read files: PASS.
- `php tests/Yii2/yii2_inspection_planning_ui_255_contract_test.php`: PASS.
- `php tests/Yii2/yii2_inspection_planning_002_test.php`: PASS.
- `php tests/Yii2/yii2_inspection_planning_ui_255_test.php`: PASS.
- No full local suite was run. Exact-source CI remains `UNKNOWN`, and publication readiness is false.

### Decision

Gate 5 remains closed. Centralize the effective actor/object scope policy alongside the canonical projection, make command, queue, and calendar apply identical mixed-role and `objects.read` rules, add the two missing authorization regressions, obtain the required test-delta approval, and request another exact-source final review.
