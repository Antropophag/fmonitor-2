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

---

## Gate 5 shared-scope final rereview — 2026-09-25

- Reviewed commit: `d5a1320686fe870c9ce79cf6e79655f87259e81f` over prior review commit `72e82ca9`.
- Harness source: `c841ae8186c1cd3a1a0a47bba0e33bbbd6a338a07e1aa639d4a44b490286c88d`; executable source `dd21da790aec54e6915fe5721e9f704d1420018f8b4fa6bec7cd39fabe6af937`.
- Root test commits reviewed: `5a3d12bf`, `b17d3f07`, `eddaf470`, `a2e31a56`, `eaa3d831`, together with their independent recorded corrections/approvals.
- Production correction reviewed: `d5a13206`.
- Verdict: **APPROVED**.

### Findings disposition

1. **Resolved — one shared actor/object scope policy.** `MariaDbYiiInspectionPlanning` now owns `actorGlobalScopeSql`, `actorAssignedScopeSql`, `actorScopeSql`, and `actorAnyScopeSql`. The command seam, construction-control queue, current inspection calendar rows, and planned calendar rows compose those same fragments rather than maintaining separate role logic.

2. **Resolved — mixed-role precedence.** An active `manager` or `fkr_operator` role plus `objects.read` grants global scope even when the actor also has an engineer role; otherwise visibility falls back to the latest current engineer assignment. The real HTTP regression proves a mixed manager/engineer can read the unassigned object in queue, create its plan, and see it in calendar.

3. **Resolved — exact global authorization.** Global scope requires an active user, an active manager/FKR role, and `objects.read` carried by an active role. `construction_control.read` alone no longer opens the queue: `actorAnyScopeSql` fails closed before publication, and the real HTTP test requires exact `403` from both queue and calendar with no DML.

4. **Resolved — calendar composition.** Current inspection rows use `actorScopeSql` against the canonical case identity. Planned legacy rows use the identical global predicate or latest-assignment predicate through a matching native case. The global branch remains independent of case existence, preserving manager/FKR legacy planned rows that have no native case.

5. **No new scope or standards finding.** The correction remains within the canonical planning/read boundary; no persistence, migration, shared asset, #258 surface, assignment writer, outcome, violation, notification, checklist/progress, ОТиЗ, or invitation change was introduced.

### Independent bounded evidence

- `git diff --check`: PASS.
- PHP syntax for `MariaDbYiiInspectionPlanning.php`, `MariaDbYiiObjectQueue.php`, `MariaDbYiiChecklistRead.php`, and the new scope-policy test: PASS.
- `php tests/Yii2/yii2_inspection_scope_policy_255_test.php`: PASS.
- `php tests/Yii2/yii2_inspection_planning_ui_255_test.php`: PASS.
- `php tests/Yii2/yii2_calendar_003_test.php`: PASS.
- `php tests/Yii2/yii2_construction_control_active_queue_001_test.php`: PASS.
- Full local `make test` / `make verify` was not run, per owner decision.

### Decision

No Gate 5 finding remains for exact source `d5a13206`. The issue #255 candidate is independently **APPROVED** for final exact-source CI. This review does not assert CI GREEN or PR-ready by itself: harness CI remains `UNKNOWN`, publication readiness is false, and the mandatory exact-source GitHub run must still pass before PR-ready can be reported.

---

## Gate 5 post-CI-correction rereview — 2026-09-25

- Reviewed exact HEAD: `28199194bb502dd5ffe42c712724d31b84ac7fae`.
- Production implementation remains the approved `d5a1320686fe870c9ce79cf6e79655f87259e81f`; no production file changed after that approval.
- Reviewed correction commits: `7eac9fee`, `893c0390`, `3dcf5704`, `86c3b305`, plus independent review records through `28199194`.
- Current harness admission candidate source: `4719100d067aad205c6577565495025cb5fdd36466017cf42244d97202a1658f`.
- Verdict: **APPROVED**.

### Assessment

1. **Global fixture authority remains explicit.** The affected construction-control, calendar, preopening and inspection fixtures now assign an active `manager` role whose active role set carries `objects.read`; they do not bypass the shared production scope policy or grant an anonymous fixture exception.

2. **Mixed-role revocation remains real.** Adding the manager role to inspection actor `73` would otherwise preserve `inspection.item.complete`; the journey now revokes that capability from both active roles before asserting safe denial and an unchanged fact snapshot. The negative oracle is stronger, not weakened.

3. **Navigation and direct-route admission remain separate.** Navigation membership continues to follow `construction_control.read`, preserving its established permission contract. After `objects.read` revocation the link may remain visible, but the real construction-control route must return exact `403`, while the shared fact snapshot proves no DML. The matrix no longer incorrectly expects that denied route to return `200`.

4. **Asset publication contract is exact.** The updated SHA-256 `89f0968f61169acc5527a229c2affcac4dccc303694095c0cf6c58d1ccdab8c4` matches the reviewed `inspection-schedule.js` bytes.

5. **Verification ownership correction does not drop the changed implementation boundary.** `MariaDbYiiChecklist.php` is only the unchanged composition wrapper; the actually changed `MariaDbYiiChecklistRead.php` remains registered in the policy and verification input. Removing the wrapper's erroneous capability ownership avoids unrelated bootstrap ownership failures without excluding the modified reader.

6. **No specification expectation was weakened.** Security, no-DML, mixed-role, direct-route, append-only, browser asset and current-plan requirements retain executable assertions. The reported affected focused inventory is GREEN, including the eight-test registered bootstrap run (342 seconds).

### Independent bounded evidence

- `git diff --check`: PASS.
- PHP syntax for every changed PHP fixture/contract: PASS.
- Exact inspection asset digest: PASS.
- `php tests/Yii2/yii2_main_navigation_001_test.php`: PASS.
- Full local `make test` / `make verify` was not run.

### Decision

No Gate 5 finding remains at exact HEAD `28199194`. The post-CI corrections are independently **APPROVED** and preserve the production verdict. This record confirms review only; final PR-ready status still requires the root delivery record to bind the exact pushed SHA to the authoritative GitHub CI result.

---

## Gate 5 post-main integration review — 2026-09-25

- Reviewed exact merge HEAD: `bdd859bbe98bea964d34ea779e66c6d7e85e0259`.
- Parents: approved #255 source/review `0c2f91256f91a9e4a62feba0f8abb4328c5866a4`; current `origin/main` `99bd0974150617a01e195cec28f7d886f1ede761` containing merged #258 PR #264.
- Exact harness source: `35ae08998497d328a462ab3e4f92ed5f2c8e3db9b913f809f753c9840948db6b`.
- Verdict: **APPROVED**.

### Integration assessment

1. The merge has only two semantic conflict resolutions. `current-delivery-goal.md` correctly retains #255 as the active delivery goal while recording #258 stage one as merged and preserved. The cutover contract retains both independently required asset digests: #255 `inspection-schedule.js` `89f0968f…` and #258 `preopening.js` `6fc7bb97…`; both match the exact local bytes.

2. Shared additive files were preserved. `config/yii/web.php` contains both installer-utilization routes and the inspection-plan POST/fallback routes. Verification policy and suite registration contain both feature families and all #255 scope/HTTP/browser tests. No #258 workforce, directory, person-card, selection behavior, or artifact was removed or edited by the #255 side.

3. The #255 production implementation and its approved security semantics are byte-preserved from the first parent. No new direct event interpretation, alternate persistence, scope bypass, shared-asset edit, or forbidden product expansion was introduced by the merge.

4. Merge-parent inspection shows #258's main-side delta was incorporated rather than selectively reconstructed. The two combined-diff resolutions are consistent with both parents' intended changes; no conflict marker or malformed registration remains.

### Independent bounded evidence

- `git diff --check`: PASS.
- Exact SHA-256 for both conflicted asset-contract entries: PASS.
- `php tests/Yii2/yii2_inspection_planning_ui_255_contract_test.php`: PASS.
- `php tests/Yii2/yii2_inspection_scope_policy_255_test.php`: PASS.
- `php tests/Yii2/yii2_main_navigation_001_test.php`: PASS.
- Reported post-merge bounded checks: OpenSpec GREEN; change verification 18/18 GREEN; issue contract, shared-scope HTTP, and main navigation GREEN.
- The local production-cutover run's shlz-ui external icon HTTP 404 is recorded only as an environment dependency diagnostic. Exact local asset files/digests exist and the same external-icon contract is present on `origin/main`; this review does **not** relabel that run GREEN.
- Full local `make test` / `make verify` was not run.

### Decision

No integration finding remains at exact HEAD `bdd859bb`. The post-main candidate is independently **APPROVED** for exact-source GitHub CI. Harness CI remains `UNKNOWN` and publication readiness false at review time, so this verdict alone is not PR-ready evidence.
