# YII2-OBJECT-QUEUE-001 — test review

## Author preparation — root,2026-09-10

Owner #76 autorizes autonomous compatibility migration of current filters,
route/query contracts and scheduling owner; new cadence/cancel policy not inferred.
Normative matrix specs/YII2-OBJECT-QUEUE-001.md; OpenSpec yii2-object-queue contains
scope/dependency impact/verification input. Root authored all tests; implementation
absent. Independent Gate3 and explicit ADR0004 architecture approval pending.

RED on canonical isolated v24 DB, DML runtime principal, real public/yii.php:
- php tests/Yii2/yii2_object_queue_001_test.php →255; expected guest303/login,
  actual404. /tmp/76-queue-http-red.log.
- php tests/Yii2/yii2_inspection_planning_001_test.php →255;
  INTENDED_RED planning owner absent. /tmp/76-yii2_inspection_planning_001_test.php.red.log.
- php tests/Yii2/yii2_inspection_planning_concurrency_001_test.php →255;
  planning owner absent before independent process barrier. /tmp/76-queue-race-red.log.
- php tests/Yii2/yii2_object_queue_browser_001_test.php →255; real browser login
  succeeds, then INTENDED_RED Yii queue missing. /tmp/76-queue-browser-red.log.
- php tests/Yii2/yii2_object_queue_lineage_001_test.php →255; queue owner absent.
  /tmp/76-yii2_object_queue_lineage_001_test.php.red.log.
- php tests/Yii2/yii2_queue_readiness_001_test.php →255; planning owner absent.
  /tmp/76-yii2_queue_readiness_001_test.php.red.log.

Root independently checked canonical checklist/completion setup and existing public
selection→original→application fixture; /tmp/76-queue-fixture-check-green.log:
QUEUE_FIXTURE_CANONICAL_OK, QUEUE_NATIVE_LINEAGE_FIXTURE_OK. The first fixture-only
probe found a missing original runtime require; it was corrected in the lineage
test before review, retained /tmp/76-queue-fixture-check.log. No tested operation
was replaced with fixture SQL; SQL only seeds/audits/fault-injects private DB.

Inventory15/15 GREEN /tmp/76-queue-inventory.log. CI policy first found appended
E2E ordering expectation (not missing test); correction preserves all prior entries,
15/15 GREEN /tmp/76-queue-ci-policy-green.log; original failure retained
/tmp/76-queue-ci-policy.log. Syntax/diff checks GREEN. Full CI belongs to final
implemented candidate, not this RED checkpoint. No Gate3 APPROVED inferred.

Complete coverage groups: real HTTP queue/login/HEAD/assets/CSRF/permission/errors;
owner schedule exact facts+event/replay/denials/clock/rollback; independent concurrent
connections; lineage/filter/status/retract and original through public commands;
readiness old public predicate versus new owners on structural faults; browser
keyboard/dialog/filter/desktop/mobile and independent DB observation.

## Independent Gate 3 review — sol/low, 2026-09-10

Reviewed reconstructible source snapshot at base
`907cb0a3b05ff5248ee097dd3a271b385c3dcc7b`, snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-queue-gate3`,
patch SHA-256
`0fc18ab69b8c783309748a176d42bae519ac56bacc6279cd8ad01dd3baeebd33`,
restored as `/private/tmp/fmonitor-76-queue-gate3`. Root authored the specification
and tests; the reviewer authored neither. The restored tree has the recorded base
and manifest digest. An ignored `vendor` symlink to the supplied source checkout
was used only to run tests and is not part of the reviewed artifact.

### Findings

1. **HIGH — application-owner authorization is not tested for queue reads.**
   Location: `tests/Yii2/yii2_object_queue_001_test.php:17,31-42` and
   `tests/Yii2/yii2_object_queue_lineage_001_test.php:8-44`. The only negative
   `objects.read` case enters through HTTP. Every direct `YiiObjectQueue::read`
   call uses authorized actor 9101 (or actor 18). An implementation that enforces
   permission only in the controller and leaves the public application owner
   unrestricted passes this candidate, contrary to queue matrix item 1 («Owner
   также проверяет право») and the shared invariant that every entry point
   enforces authorization. Correction: add direct owner cases for missing actor,
   inactive account/activation, inactive role and near/missing exact
   `objects.read`, asserting the denial contract and exact unchanged facts.

2. **HIGH — the required HTTP result matrix is incomplete.** Location:
   `tests/Yii2/yii2_object_queue_001_test.php:9-30`. The suite does not exercise
   guest POST scheduling, HTTP `409` for absent/ineligible cases (including the
   indistinguishable response), or a past date over HTTP. Thus a controller can
   bypass native guest behavior or map owner results incorrectly while the owner
   unit checks remain green. Correction: cover guest POST return/login behavior,
   past-date `422`, and representative missing/ineligible `409` responses with
   unchanged process facts and generic/no-store responses.

3. **MEDIUM — several explicit queue/read and browser observables have no
   sensitive assertion.** Locations: `tests/Yii2/yii2_object_queue_001_test.php:13-15,28-43`,
   `tests/Yii2/yii2_object_queue_lineage_001_test.php:9-19`, and
   `tests/Yii2/object_queue_browser.mjs:13-27`. Missing checks include empty
   mandatory source requisites and an unsupported process state returning 503;
   reset and pagination links preserving `q/status`; a new search dropping the
   old `page`; rendered total/empty state; default Moscow date and `min=today`;
   absence of the schedule button on every non-working displayed state; and
   desktop page-overflow. Marker presence and screenshots do not make these
   failures observable. Correction: add focused owner/HTTP assertions for the
   malformed rows and link/query semantics, and browser assertions for these
   controls and both viewport overflow conditions.

The RED behavior was reproduced independently after attaching the supplied
ignored dependencies: all six focused commands exited 255 for the intended
missing route/owner behavior. Queue HTTP failed at guest expected 303 versus 404;
planning, concurrency, lineage and readiness failed on the explicit absent-owner
guards; browser login succeeded and failed at the missing queue route. No full
suite was run. The retained author evidence for canonical fixtures/oracle and
verification inventory is consistent with the reviewed source, but does not fill
the behavioral omissions above.

**Gate 3 verdict: CHANGES_REQUESTED.** Return to Gate 2, correct the complete
candidate, capture a new reconstructible snapshot and request independent review
of the changed tests.

### Explicit architecture design review — ADR 0004

**APPROVED for exactly**
`InstallationProcess\\YiiInspectionPlanning::scheduleInspection(actorId, objectId, inspectionDate)`
as the single state-changing owner of authorization, current-case/latest-engineer
eligibility, schedule and append-only event in one Yii connection/transaction.
The method name states the intent and must be registered explicitly after this
approval. This approval permits only that public seam: it grants no hotspot, SQL,
dependency or second-connection allowance, no baseline entry for another method,
and no cadence/cancel/reassignment policy. HTTP remains form/CSRF/result mapping;
the rapid route remains the preserved oracle/stand adapter until the separate
cutover. ADR 0004 may move from `proposed` to `accepted` when this exact approval
is recorded in the final candidate.

## Root correction after first return —2026-09-10

Все три группы исправлены одним delta: прямые read-owner denial cases (missing,
blocked/invited/inactive account, inactive role, near/missing exact capability) с
проверкой ACCESS_DENIED и неизменных фактов; guest POST, HTTP past422 и одинаковые
missing/ineligible409; mandatory requisites/unsupported state503; настоящий
filtered page2 с сохранением q/status, clean GET search без page, total/empty/reset;
кнопка только для working среди всех status buckets; Moscow default/min и desktop
page-overflow, browser new-search с actual page2 и reset. Public read-denial
convention уточнена в normative spec. ADR0004 accepted по отдельному architecture
approval reviewer, test Gate3 пока не объявлен approved.

Fixture-only drift check также GREEN: /tmp/76-queue-readiness-fixture-check.log,
все6 missing/column/type/index/CHECK/FK controls корректно восстанавливают canonical
readiness. Root не менял production-код и не ослаблял старые expectations.

## Independent Gate 3 re-review — sol/low, 2026-09-10

Reviewed the complete corrected candidate from reconstructible snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-queue-gate3-corrected`,
base `907cb0a3b05ff5248ee097dd3a271b385c3dcc7b`, patch SHA-256
`e7bf5002f6e425a5d88b1cd637db07f5bf2ab88d1541e1cbfb10812e8ef68d00`,
restored at `/private/tmp/fmonitor-76-queue-gate3-corrected`. The delta closes
the three prior finding groups: direct owner denials are observable at the
specified `ACCESS_DENIED` seam; HTTP guest/date/ineligible mappings are covered;
and the requested malformed-row, query/pager, visibility, date and overflow
assertions were added. The exact architecture approval for
`YiiInspectionPlanning::scheduleInspection` remains valid and ADR 0004 accurately
records it.

### Complete findings

1. **HIGH — the new browser page-2 scenario has no page-2 fixture.** Location:
   `tests/Yii2/object_queue_browser.mjs:23` and
   `tests/Yii2/yii2_object_queue_browser_001_test.php:6-13`. The browser wrapper
   constructs `ObjectQueueFixture`, which seeds only object 451201, and does not
   add the 50 objects needed for a second page. The new script then opens
   `/pilot/objects?page=2`. Normative queue matrix item 2 requires an out-of-range
   page to return 503, so a conforming implementation cannot reach the following
   search assertion and this test cannot become GREEN. The 51-row setup in
   `yii2_object_queue_001_test.php` belongs to a separate private database and
   cannot supply the browser test. Correction: seed at least 51 admitted rows in
   the browser test's own fixture before starting the server (with object 451201
   still discoverable after the search), then retain the page-2 → new-search →
   page-parameter removal assertions and reproduce intended RED.

The corrected HTTP and lineage commands were independently reproduced as RED 255
at the intended missing route/owner guard. The browser command could not be
reproduced in the restored directory because its default Playwright module path
resolves outside the supplied checkout; the retained root RED evidence covers
the missing-route stage. This environment limitation does not affect the static,
deterministic page-count contradiction above. No full suite was run.

**Gate 3 verdict: CHANGES_REQUESTED.** Correct the browser fixture, capture one
new complete reconstructible snapshot and request delta re-review. No other
finding remains from the previous complete list.

Root также устранил до Gate4 противоречие fixture composition и architecture:
fixtures/worker теперь вызывают единый public YiiRuntime\InstallationProcessFactory,
который внедряет persistence в owners. db-only Component setup больше не заставляет
InstallationProcess конструировать concrete MariaDb adapter. Behavioral seams,
все expected outcomes и matrix неизменны; factory присутствует в planned paths.

## Independent Gate 3 final re-review — sol/low, 2026-09-10

Reviewed the complete prepared source from reconstructible snapshot
`/Users/antropophag/.local/state/fmonitor2/review-snapshots/76-queue-gate3-composed`,
base `907cb0a3b05ff5248ee097dd3a271b385c3dcc7b`, patch SHA-256
`67a44277411854c551025c901e9f27126365b03613b21445720227d6d8082170`,
restored at `/private/tmp/fmonitor-76-queue-gate3-composed`.

### Correction of the preceding re-review

The preceding page-2 finding and its `CHANGES_REQUESTED` verdict were reviewer
error and are superseded by this review; they remain above only as review history.
`tests/Yii2/yii2_object_queue_browser_001_test.php:7` already creates 50 additional
admitted objects before starting the server, in addition to object 451201 created
by `ObjectQueueFixture`. Its SHA-256 is
`0768637b1db1ee4938af221ab71efb96f8ffbcf2738310b886f3a56628f3349f` in the
corrected snapshot, composed snapshot and source checkout. I had inspected the
changed JS line and the delta against the first snapshot, then incorrectly inferred
fixture cardinality without checking the unchanged wrapper line. Page 2 is valid
with the actual 51-row fixture, so the scenario is deterministic and can become
GREEN.

### Complete findings

None. The candidate closes all three findings from the first review. The final
composition delta routes fixture, worker and native-lineage construction through
`YiiRuntime\InstallationProcessFactory::queue/planning`. This is an appropriate
composition root: it injects persistence into the application owners, leaves
InstallationProcess independent of YiiRuntime and concrete construction, uses the
same planned factory for production configuration, and changes no behavioral seam
or expected outcome. The factory path is included in verification input.

All six focused commands were independently reproduced on the final snapshot with
the supplied vendor and explicit Playwright module path. Each exited 255 for its
intended missing implementation: queue HTTP at expected guest 303 versus actual
404; planning, concurrency and readiness at the absent planning-owner guard;
lineage at the absent queue-owner guard; browser after successful native login at
the missing Yii queue route. No full suite was run. Retained canonical fixture,
readiness comparator and original-oracle GREEN evidence remains applicable because
their source and obligations did not change.

**Gate 3 verdict: APPROVED.** The tests are traceable to the normative contract,
exercise the public owners and real Yii HTTP/browser seams, use independently
observed persisted facts, cover authorization/rejections/replay/concurrency and
fail deterministically for missing behavior. Gate 4 may proceed from this exact
prepared source. The earlier exact architecture approval for
`InstallationProcess\YiiInspectionPlanning::scheduleInspection` remains unchanged;
the composition factory adds no public mutation seam or architectural allowance.

## Root checkpoint binding

Root сверил code/spec/test/OpenSpec bytes с восстановленным approved snapshot
67a44277 перед checkpoint. Дополнительно изменяются только task/goal status и
этот review record, не поведенческие ожидания. Private browser fixture audit
через existing oracle подтвердил51 admitted row и настоящую page2:
/tmp/76-queue-browser-fixture-count-green.log.
