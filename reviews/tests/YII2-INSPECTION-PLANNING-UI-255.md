# Gate 3 test review: YII2-INSPECTION-PLANNING-UI-255

- Review date: 2026-09-24.
- Reviewer: independent agent `/root/gate3_review`; authored none of the reviewed specification, lifecycle artifacts, fixtures, or tests.
- Test author: root agent.
- Reviewed source: committed HEAD `2641d31a7ea2932c778738e44c6e669838e82cd3` plus the current correction to `tests/Yii2/yii2_inspection_planning_ui_255_browser_test.php` (`git hash-object` `c409bfffaab44afe5597166465e01efd6bda5165`).
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T205129Z-d262d21c4e/package.json`; its plan selects `CRITICAL` with required reviews `gate3` and `final`. The package predates the browser-fixture correction and must be regenerated after the test matrix is corrected.
- Specification: `specs/YII2-INSPECTION-PLANNING-UI-255.md` and `openspec/changes/inspection-planning-ui-255/specs/runtime/inspection-planning-ui/spec.md`.
- Verdict: **CHANGES_REQUESTED**.

## Findings

1. **BLOCKING — Moscow-day ordering and pagination have no executable behavioral oracle.** `tests/Yii2/yii2_inspection_planning_ui_255_contract_test.php:19-20` only searches source text for `Europe/Moscow`/`:today` and an `ORDER BY` regular expression. No test creates more today's objects than one page, applies the active authorization scope and filters, asserts COUNT and page membership, verifies stable within-group ordering, crosses Moscow midnight with an injected clock, or proves the read performs no DML. An implementation can contain matching dead text while ranking after LIMIT, use the wrong instant, or mutate a marker and pass. Add real Yii queue reads with independently seeded today/future/unplanned objects across multiple pages, active filters/scope, deterministic identity tie-breaks, two clock instants straddling Moscow midnight, and before/after fact snapshots.

2. **BLOCKING — authorization and object scope are not covered for either required role.** The HTTP and browser fixtures convert one role to `manager` and exercise one visible object (`tests/Yii2/yii2_inspection_planning_ui_255_test.php:6-16`, browser wrapper `:7-12`). They never exercise a construction-control engineer, never establish that `manager` means the required Руководитель ФКР capability, and never attempt reads or create/reschedule/cancel against an out-of-scope object. Static method-name checks cannot prove the application seam repeats capability and object-scope enforcement. Add positive matrices for both required actors and negative missing-capability/out-of-scope cases at the real POST and queue/calendar seams, with zero facts on denial.

3. **BLOCKING — replay/conflict, rejection classification, retained context, and unknown outcomes are untested.** The journey covers one stale cancel and only checks status `409` plus no new facts (`yii2_inspection_planning_ui_255_test.php:14`). It omits identical replay, reused request identity with different intent, invalid date, transport/server uncertainty, and the observable distinction between confirmed rejection and unknown outcome. It also never verifies that date, object, action, plan/version context survive either class of failure or that no hidden second command is emitted. The contract test merely searches for rejection strings and absence of `fetch` (`contract_test.php:14-17`). Add public-seam requests and browser assertions for each outcome, exact event counts/request identities, retained form values/context, classified copy/status, and a command-call witness proving one attempt.

4. **BLOCKING — canonical current-plan agreement and fail-closed reads are incomplete.** Calendar agreement is asserted only after create (`yii2_inspection_planning_ui_255_test.php:12`); after reschedule and cancel only the queue is read (`:13-15`). There is no expired-plan read, multiple-current overflow, projection/schema outage, partial-HTML exclusion, or zero-write assertion for ordinary queue/calendar GETs. Add queue and calendar assertions after every action, exclude superseded/cancelled/past plans in both surfaces, and inject/read the projection failure and ambiguity cases with exact fail-closed responses, no partial current-plan HTML, and unchanged facts.

5. **BLOCKING — browser coverage does not exercise the promised user actions or failure return path.** `inspection_planning_ui_255_browser.mjs:3` opens only the create dialog, types a date, closes it, and reopens it. It never submits create, opens reschedule, invokes cancel, observes queue/calendar convergence, checks explicit submit/cancel controls, or verifies retained data after a server rejection/unknown outcome. Thus broken action wiring or separate create/reschedule dialogs can satisfy it. Extend the real browser flow through create → calendar/queue → reschedule → cancel on desktop and narrow viewports, include keyboard opening/closing and focus return for create and reschedule, assert textual actions remain visible, and include at least the rejected return path with retained input and context.

6. **BLOCKING — expected-value independence and RED evidence are insufficient for the sensitive matrix.** The principal structural test derives expectations from implementation tokens and a permissive regex rather than observable behavior. The bounded RED reproduced during review is valid but reaches only the first missing method-name assertion; it provides no retained evidence that the currently absent authorization, pagination/midnight, replay/unknown, or projection-failure branches are present and fail for the intended reason. After findings 1–5 are addressed, regenerate the exact-source plan/package and retain a complete RED run in which all independent scenarios are reachable (without one early assertion standing in for the matrix).

## Positive assessment

The normative specification cleanly limits the slice, names the canonical command/read seam, preserves #258 boundaries, and explicitly excludes persistence, assignment, outcome, checklist/progress, and shared-asset changes. The current tests already establish useful foundations: real Yii POST/redirect happy path, CSRF and method rejection, append-only event sequence, a stale rejection, local no-fetch asset intent, and desktop/narrow focus/Escape behavior. The corrected browser wrapper now grants the required read permissions before server start and uses a valid fixture email.

## Independent bounded verification

Syntax checks passed for all three PHP tests and the browser module. `git diff --check` passed. Running `php tests/Yii2/yii2_inspection_planning_ui_255_contract_test.php` exited `255` at the intended first RED, `INTENDED_RED: real Yii route calls createInspectionPlan`; setup and parsing succeeded.

Gate 4 is not authorized. Correct the complete test matrix as one root-authored revision, retain fresh RED evidence, regenerate the source-bound harness package, and request independent Gate 3 rereview before implementation.

---

## Gate 3 correction rereview — 2026-09-25

- Reviewer independence: unchanged; this reviewer authored none of the corrected artifacts.
- Corrected HEAD: `5c3b9c03810b63a2eea547d2375d0759005af568`.
- Corrected candidate source: `7787ed68a99293b83c66d9b6d24832f540b20334a9260bed5620c74fe44717b2`.
- Corrected package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T210141Z-3e6329b74e/package.json`; verification-plan SHA-256 `1f5a2b283759fa20ae25bc7f11fd3da94e14f204b11189e22f861bf96e1e4207`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Verdict: **CHANGES_REQUESTED**.

### Prior findings disposition

1. **Partially resolved; filtered COUNT/pagination behavior remains open.** The new `yii2_inspection_planning_today_priority_255_test.php:5-10` seeds 51 today and four future plans, reads two pages through the public queue owner, proves today's rows cross the page boundary in numeric order, crosses Moscow midnight, and snapshots facts around both reads. This materially closes the clock, multi-page ordering, tie-break and no-DML portions. However, both reads use `ownership=all` and an empty search. No executable case applies an existing ownership/search filter and independently verifies the filtered total/count and page membership. `yii2_inspection_planning_ui_255_contract_test.php:21-22` remains a source-token assertion for COUNT and ORDER-before-LIMIT, so an implementation can count an unfiltered set or apply today rank outside a filter subquery while satisfying it. Add at least one active filter/scope case whose expected filtered count and two-page membership are independently asserted.

2. **Partially resolved; application authorization is covered, real Yii-route authorization is not.** Adding existing `yii2_inspection_planning_002_test.php` to the acceptance mapping supplies strong canonical seam coverage for engineer, FKR, missing capability, revoked scope and zero facts. The issue-specific Yii HTTP test still logs in only the manager fixture and never POSTs as the assigned engineer or attempts an out-of-scope/missing-capability route. Because the slice adds a controller boundary which must pass the authenticated actor and object unchanged, the lower application test cannot catch a controller that substitutes a privileged actor or bypasses route scope. Add real-route positive engineer and FKR cases plus at least one denied out-of-scope/capability case with no event.

3. **Partially resolved; replay/conflict improved, but unknown outcome and retained command context are not validly proven.** `yii2_inspection_planning_ui_255_test.php:13` now exercises identical replay and changed-intent conflict at HTTP, including unchanged facts and retained date/object. That closes those portions. The browser test's unknown case aborts the request in Playwright (`inspection_planning_ui_255_browser.mjs:6`) and then waits for application copy on the pre-navigation page. A native form POST with no fetch/retry has no server response from which the application can classify or render an unknown outcome; the test neither injects a server-side uncertain result nor observes the canonical route's classification. It also checks only date, not action/object/plan/version context, and contains no confirmed-rejection browser return path. Replace the browser-network abort with a deterministic server/command uncertainty fixture at the real POST seam, assert unknown versus confirmed rejection separately, assert the complete retained context, exact event/request counts, and explicit user retry as the sole second attempt.

4. **Partially resolved; current-plan agreement improved, calendar fail-closed remains open.** The corrected journey reads calendar after create, reschedule and cancel; the inherited planning test covers expired plans, and the new priority test injects multiple-current overflow with zero repair. The outage assertion at `yii2_inspection_planning_ui_255_test.php:19` checks only construction-control. No calendar request is made while the projection table is unavailable or ambiguous, so calendar can still publish partial/stale legacy events while queue fails closed. Exercise both queue and calendar for schema outage and ambiguity, asserting exact fail-closed responses, no partial plan/object event HTML, and no facts.

5. **Partially resolved; the browser journey still misses required keyboard and cross-surface assertions.** The browser now submits create, reschedule and cancel and asserts one dialog plus textual action locators in both viewports. Reschedule/cancel are mouse-opened; focus, Escape/close, focus return, and retained draft are still tested only for create. It never visits calendar after any action, so the promised real `create → calendar/queue → reschedule → cancel` route is not browser-tested. It also lacks the confirmed-rejection return path noted above. Extend the journey with keyboard reschedule, focus/close restoration, visible submit/cancel controls, and calendar observations after each state transition.

6. **Open — no fresh complete retained RED demonstrates the corrected matrix.** The new package reports executable RED `PENDING`. Independent syntax checks and `git diff --check` pass, and the static contract test still reaches its first intended missing-route failure. Direct runs of the three fixture-backed tests stop before assertions because this clean worktree has no `vendor/autoload.php`; therefore they provide setup failures, not RED evidence for the new scenarios. Retain a prepared, dependency-complete run after the remaining corrections where all scenario groups are reachable and fail for absent production behavior rather than setup or one early static assertion.

### Assessment

The correction is substantial: replay/conflict, queue/calendar state transitions, projection outage for queue, multi-page today ordering, Moscow midnight, overflow and a longer browser action flow are now represented. It does not yet close the complete six-finding matrix promised by the normative contract. In particular, the sensitive controller authorization boundary, filtered COUNT behavior, server-originated unknown result, calendar fail-closed behavior and keyboard/cross-surface browser path remain capable of regressing while the suite passes.

Gate 4 remains unauthorized. Rebuild these remaining cases together, regenerate the source-bound package, retain dependency-complete RED evidence, and request another independent rereview.

---

## Gate 3 second correction rereview — 2026-09-25

- Reviewer independence: unchanged.
- Corrected HEAD: `faf2608be1486bf140b16eb7260157211e41595c`.
- Exact candidate source: `a637ecc57e4301f0d7de93347365573b501f6bc21e7593686ef1ef3fe25e6c73` (clean worktree).
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T210543Z-0560223e7e/package.json`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Verdict: **CHANGES_REQUESTED**.

### Closed findings

1. **Filtered count/page membership — resolved.** `yii2_inspection_planning_today_priority_255_test.php:8` now applies a nonempty search, independently asserts the filtered total before combining two pages, and retains the 51-row today boundary, stable numeric ordering and future-row position. Midnight and no-DML witnesses remain.

2. **Calendar outage closure — resolved for schema outage.** `yii2_inspection_planning_ui_255_test.php:20` now requests both queue and calendar while the event projection table is unavailable, requires `503` from both, rejects partial row/event HTML and restores the fixture table in `finally`.

3. **Keyboard/cross-surface browser journey — resolved.** The browser matrix now opens reschedule by keyboard, checks date focus, Escape and trigger focus restoration, and observes the canonical object link in calendar after create/reschedule and its absence after cancel in desktop and narrow viewports.

### Remaining blocking findings

1. **BLOCKING — the real Yii authorization matrix still does not execute an allowed command as either required actor.** The new HTTP prelude proves an assigned engineer can GET `ownership=mine`, an out-of-scope engineer POST returns `404` without facts, and an FKR actor can GET the global queue (`yii2_inspection_planning_ui_255_test.php:8`). All successful create/reschedule/cancel requests remain under the original fixture actor `9101`, whose role was renamed generic `manager`. Neither engineer `9403` nor FKR `9401` performs an allowed POST. A controller that denies all engineer writes, or maps FKR reads correctly but command identity incorrectly, still passes. Add at least one successful real-route command for each required actor, assert the recorded exact actor and scoped object, and retain the denial witness.

2. **BLOCKING — the unknown-outcome browser case still bypasses the server application and cannot prove the specified retained return path.** Replacing `route.abort` with Playwright `route.fulfill({status:503, body:'Service unavailable.'})` does not exercise the Yii route or its uncertainty classification. It replaces the entire server response with plaintext, so a native form navigation should leave the application page; no production response supplies the expected `Результат … не подтверждён` copy or preserved dialog. The test's subsequent expectation therefore either fails for browser mechanics or requires an unrelated client interception layer, contrary to the declared native-form/no-fetch design. Introduce a deterministic failure at the command/server boundary and let the real Yii POST render the classified unknown response with full action/object/date/version/request identity; then assert no automatic second POST and one explicit retry. Add the confirmed-rejection browser return path as previously requested.

3. **BLOCKING — multiple-current ambiguity is not exercised through both publication surfaces.** The priority test injects overflow and calls only `MariaDbYiiChecklist::queue`, expecting an exception. The HTTP closure covers only a missing-table outage. Calendar can still tolerate or publish one of multiple current plans while queue fails closed. With the overflow fixture active, request real queue and calendar routes and require both to fail before partial HTML and without DML.

4. **BLOCKING — dependency-complete retained RED evidence is still absent.** Harness state for the exact clean source reports executable RED `PENDING`; direct fixture runs in the preceding review stopped at missing `vendor/autoload.php`. This correction adds no retained run showing that the sensitive route, projection and browser scenarios reach intended missing behavior. PHP/Node syntax checks and `git diff --check` pass, but those are not Gate 2 RED evidence. After correcting the three behavioral gaps above, retain one prepared dependency-complete run that reaches the whole scenario matrix and fails only for missing production behavior.

### Decision

Three material parts of the prior return are now closed, but controller command authorization, server-originated unknown handling, calendar ambiguity closure and executable RED evidence remain open. Gate 4 remains unauthorized. The next handoff should correct these as one complete matrix and regenerate the source-bound package before rereview.

---

## Gate 3 third correction rereview — 2026-09-25

- Reviewer independence: unchanged.
- Corrected HEAD: `c940fbeaa15a80b925eafcbb322a023c03480d79`.
- Exact candidate source: `8a4309995f82e2bb57e61a6652b17cfd6e0b65005fc32134d3ea8f50f72d362c` (clean worktree).
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T211844Z-b7109174ac/package.json`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Verdict: **CHANGES_REQUESTED**.

### Closed findings

1. **Real-route actor coverage — substantively present.** The HTTP test now performs successful create/cancel pairs through the real Yii route as assigned engineer `9403` and FKR actor `9401`, in addition to the out-of-scope engineer denial. The retained HTTP RED reaches the allowed engineer POST and receives current-production `404`, proving the barrier is live rather than static.

2. **Server-originated uncertain outcome — present at HTTP seam.** A database trigger makes the real command fail during event append; the route must return classified `503`, retain date/object/action copy and leave all facts unchanged. This is an appropriate deterministic unknown-commit witness at the public seam.

3. **Queue/calendar ambiguity — present.** Two current roots/events are injected and both real publication routes must return `503`, publish no row/event HTML and perform no repair or DML.

4. **Dependency-complete RED — present.** Four exact-source harness records are retained for contract, HTTP, today and browser commands. They share candidate source `8a4309995f82e2bb57e61a6652b17cfd6e0b65005fc32134d3ea8f50f72d362c` and executable source `5ddb9f7b3ff764138f1b06ed78e80b521b5d4f8a2354d3aabe9aa98dfaf71046`. HTTP fails at the missing allowed engineer route (`303` expected, `404` actual); today reaches the missing today marker (`51` expected, `0` actual); browser reaches the missing create trigger after successful setup/login; contract reaches the missing canonical route call. These are intended missing-behavior failures, not dependency/setup failures.

5. **Today fixture connection — corrected.** `MariaDbYiiChecklist` now receives `$f->db`, so queue and `currentEngineerAssignment` use the same isolated fixture database rather than an unrelated process-global connection. The base object also has a valid sequence-one assignment.

### Blocking test defects

1. **BLOCKING — the HTTP test cannot become GREEN because its fact baseline predates the newly added successful role commands.** `$before` is captured on `yii2_inspection_planning_ui_255_test.php:7`; engineer and FKR create/cancel pairs then append four legitimate events on `:9`. The following GET/HEAD assertion on `:11` still compares current facts with the pre-command `$before`, and the CSRF assertions do the same. Once the new routes return the expected `303`, those assertions necessarily fail even if GET/HEAD and rejected CSRF requests write nothing. Refresh a baseline after the role matrix (or snapshot immediately around each no-write action) while retaining explicit assertions for the role events and actors.

2. **BLOCKING — the final append-only oracle excludes facts deliberately created by this same test.** The last assertion expects exactly three event types, `[scheduled, rescheduled, cancelled]`. Before it runs, the test has legitimately created four engineer/FKR schedule/cancel events and manually inserted two ambiguity events, in addition to the main three-event journey. Therefore it can never equal the three-element array. Scope the main-journey assertion by request identities/schedule root, or assert the complete independently expected history including role and fault-control facts. Also assert exact actor IDs for the successful role commands, as promised by the authorization scenario.

3. **BLOCKING — browser `route.fulfill(503, text/plain)` still does not test the real server-retained unknown response.** The HTTP trigger case now correctly proves server classification and retention, but the browser test still replaces that response with raw `Service unavailable.` and then expects application copy and the existing dialog to remain. That response bypasses Yii entirely and a native form navigation cannot derive `Результат сохранения не подтверждён` or preserved form state from it. Either drive the browser through the same deterministic real-server fault mechanism, or make the browser assertion observe the real classified HTML `503`; do not synthesize an unrelated plaintext response and expect application-rendered state. The added invalid-date path is useful confirmed-rejection coverage, but does not repair this unknown branch.

### RED evidence checked

- Contract: `1790284497485326000-74f542be47d24cc59d6de318e96da184`.
- HTTP: `1790284508032680000-73823630aed34822a93bdc1fa23c4acc`.
- Today: `1790284533649860000-d0aa32b9f0034786aa37d5ca24b58b41`.
- Browser: `1790284558495133000-2852b3006ad74472b227372914cf9fe2`.

The retained records are valid RED evidence for their first reached barriers, but they cannot reveal the two later impossible history assertions because execution stops earlier. Correct the test-only baselines/history oracle and replace the synthetic browser unknown response, retain fresh exact-source RED, regenerate the package, and request rereview. Gate 4 remains unauthorized.

---

## Gate 3 fourth correction rereview — 2026-09-25

- Reviewer independence: unchanged.
- Corrected HEAD: `adaa85b04a4774aecd2d2a3735850e742637e788`.
- Exact candidate source: `fb52d7621edadfd23098db6327ef897f29810487510a4d16050384b04c758510` (clean worktree).
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T212127Z-7f3d323714/package.json`; lane `CRITICAL`, required reviews `gate3` and `final`.
- Verdict: **CHANGES_REQUESTED**.

### Prior blockers disposition

1. **Resolved — fact baseline.** The test refreshes `$before` after the engineer/FKR create/cancel matrix, so subsequent GET/HEAD, CSRF and triggered-rollback no-write assertions compare against the correct state.

2. **Resolved — main history oracle.** The final assertion filters by the three main-journey request identities before requiring scheduled/rescheduled/cancelled order, excluding the deliberately separate role and ambiguity events.

3. **Resolved — real browser unknown response.** The wrapper installs the database failure trigger before launching the browser. The browser observes the actual Yii `503` classified HTML and retained dialog context, writes a private release marker only after those assertions, and the parent drops the trigger before explicit retry. No request interception or synthetic response remains. The narrow viewport is exercised separately after the stateful desktop journey.

### Remaining blocking findings

1. **BLOCKING — successful route authorization still does not prove the controller forwards the authenticated actor.** The new role matrix asserts statuses and reads the resulting plan, but never inspects the four role-command events. A controller that authorizes the correct session yet invokes the canonical command as actor `9101` would still produce the expected plan/status and pass. The normative contract requires the current actor to reach the command, and the prior review explicitly required exact recorded actors. Filter events by the four `eeee…002/003` and `ffff…001/002` request identities and assert actor IDs `[9403, 9403, 9401, 9401]` together with their event types. This is a small but security-material route-boundary oracle.

2. **BLOCKING — retained RED is not exact for the corrected test candidate.** The four records cited in the preceding review bind source `8a430999…` / HEAD `c940fbea`. The current package binds source `fb52d762…` / HEAD `adaa85b0`, and harness state still reports executable RED `PENDING`. The changed files are tests and browser harness, including the real-503 mechanism; predecessor RED cannot demonstrate that this corrected candidate parses, boots and reaches its intended first barriers. Run and retain the four prepared focused commands against the current exact source after adding the actor assertion. Expected first RED barriers may remain the same, but their records must bind the reviewed bytes.

### Decision

All behavioral matrix gaps and the three previously impossible oracles are otherwise resolved. Approval now requires only the exact-actor event assertion and fresh exact-source retained RED for the resulting candidate. Gate 4 remains unauthorized until those two items are complete.

---

## Final Gate 3 rereview — 2026-09-25

- Reviewer independence: unchanged; the reviewer authored no specification, test, fixture, or production source.
- Approved test HEAD: `503f791adfaaa3136fb16aef2e87e2314ea21275`.
- Candidate source: `3b17ac7932fee404d5ccd263cd5f704ce9c241a07dbf81b11383c63f54983673`; executable source: `ef545ed9090a76cb20a014c3fb7c2f689f694a8f67e7878fe1d874f78673c286`.
- Verdict: **APPROVED**.

### Final blockers disposition

1. **Resolved — exact authenticated actors.** The HTTP test filters the four engineer/FKR command events by their opaque request identities and independently requires event types create/cancel/create/cancel and actor IDs `9403, 9403, 9401, 9401`. Controller substitution of the privileged fixture actor can no longer pass.

2. **Resolved — fresh exact-source RED.** All four retained records bind candidate source `3b17ac7932fee404d5ccd263cd5f704ce9c241a07dbf81b11383c63f54983673` and executable source `ef545ed9090a76cb20a014c3fb7c2f689f694a8f67e7878fe1d874f78673c286`. Container-backed records identify Git SHA `503f791adfaaa3136fb16aef2e87e2314ea21275` and terminate at the intended missing behavior:

   - contract `1790285178734808000-b3f772cb33bf453dbff7a5d26ae7b1a9`: canonical Yii command route absent;
   - HTTP `1790285178737781000-2df8376178b045649c62c885fa67de51`: assigned engineer real POST returns `404`, expected `303`;
   - today `1790285178758032000-fb152b169a4a4131bf20e94cf069d0f5`: 51 seeded current-today objects yield zero today markers;
   - browser `1790285178751474000-fcfbcfcf8daf4772b56c7af2a3bad8f4`: authenticated page lacks the create action, so the real trigger locator times out.

### Final assessment

No Gate 3 findings remain. The executable matrix now covers the canonical public command/read seam; exact engineer/FKR authority and object scope; CSRF and read-only methods; replay, request conflict and stale version; classified rejection versus server-originated unknown outcome with retained context and no hidden retry; queue/calendar agreement after every action; past-plan exclusion; projection outage and multiple-current fail closure; filtered COUNT and Moscow today priority before pagination; stable ordering, midnight recomputation and no DML; and one accessible dialog across desktop/narrow, keyboard focus/return and the real browser journey. The tests preserve the #258 and shared-asset exclusions.

Gate 4 may proceed against the approved test candidate. Test, specification, verification-input, registration, or fixture changes require applicable independent delta review. Final review and exact-source CI remain mandatory and outside this Gate 3 verdict.

---

## Gate 3 overflow-fixture delta review — 2026-09-25

- Reviewer independence: unchanged.
- Reviewed commit: `6a03b71d5dee2c315085cce94cb1b03ecdc4eb17` over approved test HEAD `503f791adfaaa3136fb16aef2e87e2314ea21275`.
- Scope: the sole committed change to `tests/Yii2/yii2_inspection_planning_today_priority_255_test.php`; dirty executor production files were excluded and untouched.
- Verdict: **APPROVED**.

The correction preserves the approved ambiguity oracle while removing an unrelated storage-uniqueness collision. The second immutable root now stores distinct `inspection_date=2026-09-12`, then receives its own version-one `inspection_scheduled` event whose canonical effective payload date is `2026-09-10`. Consequently the projection still observes two current roots for object `452000` on the tested Moscow day, but fixture setup no longer fails before the read seam because two root rows share the same storage date.

The event is internally bound to the inserted root and case, has a distinct opaque request identity, version one, deterministic payload and actor/time. The before/after fact snapshot remains after complete fault setup, so the assertion continues to prove that the failing ambiguity read performs no repair or DML. Earlier filtered COUNT, two-page order, midnight and no-DML expectations are unchanged.

PHP syntax and `git diff --check` pass. No Gate 3 finding is introduced; the test approval extends through commit `6a03b71d5dee2c315085cce94cb1b03ecdc4eb17`. Final production review and exact-source CI remain outside this delta verdict.

---

## Gate 3 browser-focus delta review — 2026-09-25

- Reviewer independence: unchanged.
- Reviewed commit: `e7803353a44976ce7b98804a945643bb04f1d289` over the approved test candidate.
- Scope: one-line browser-oracle change in `tests/Yii2/inspection_planning_ui_255_browser.mjs` only.
- Verdict: **APPROVED**.

The new `waitForFunction` waits until the already resolved date input is exactly `document.activeElement`, accommodating Chromium's asynchronous native-dialog focus settlement. It neither substitutes another focus target nor turns the requirement into a timeout/sleep; failure to focus the date input still times out and fails. The immediate equality assertion remains after the wait, and all Escape/trigger focus-return, reschedule-focus and narrow-viewport checks are unchanged.

Node syntax and `git diff --check` pass. No acceptance expectation is weakened, so Gate 3 approval extends through `e7803353a44976ce7b98804a945643bb04f1d289`. Production changes remain outside this verdict.

---

## Gate 3 calendar-cardinality delta review — 2026-09-25

- Reviewer independence: unchanged.
- Reviewed commit: `0a93fca343dbb70bd194bdb8e5607b10940e7b00`.
- Scope: two browser cardinality assertions only.
- Verdict: **APPROVED**.

The calendar intentionally presents the same canonical current-plan event in both the month grid and selected-day agenda. Requiring exactly one matching object-card link incorrectly constrained presentation multiplicity rather than domain-plan multiplicity. Requiring `count() > 0` after create and reschedule still proves that calendar publishes the current plan and its canonical object link, while the unchanged zero-count assertion after cancel proves that neither presentation retains the cancelled plan. Canonical single-current semantics remain independently covered at the read/application seams.

Node syntax and `git diff --check` pass. Gate 3 approval extends through `0a93fca343dbb70bd194bdb8e5607b10940e7b00`; production remains outside this delta verdict.

---

## Gate 5 finding test-delta review — 2026-09-25

- Reviewer independence: unchanged.
- Reviewed commit: `1b8c3687b37a6813728cf8f77ee88f29ee6ec197`.
- Scope: root-authored calendar scope/clock assertions only; dirty executor production was excluded and untouched.
- Verdict: **CHANGES_REQUESTED**.

### Finding

1. **BLOCKING — the negative engineer calendar oracle does not require a successful scoped read.** The fixture creates an object outside engineer `9403`'s assignment scope through the FKR route, then asserts only that the engineer response body lacks `/pilot/objects/451202`. A `403`, `404`, `500`, `503`, empty response, or globally broken engineer calendar would all satisfy that negative assertion. Require `engineerCalendar.status === 200` before asserting absence, and likewise require `fkrCalendar.status === 200` before asserting inclusion. This distinguishes correct row-level filtering from denying or failing the calendar surface.

The positive/negative object setup and FKR cleanup are otherwise well isolated and exercise the real Yii routes. The contract additions preserve actor ID plus a single `$today` value at the `readCalendar` call and bound direct wall-clock acquisition to at most one occurrence; existing Moscow-date requirements supply the timezone expectation. PHP syntax passes.

After adding the two exact status assertions, request a narrow rereview. The existing broader Gate 3 approval remains unchanged outside this delta.

### Scoped-calendar status correction — 2026-09-25

- Reviewed commit: `aa213f0025e8d5977a69d258648dc1025df803bc`.
- Verdict: **APPROVED**.

The added exact `[200, 200]` assertion closes the sole delta finding: both engineer and FKR calendar reads must succeed, while the existing assertions independently require exclusion of object `451202` for the engineer and inclusion for FKR. Although the status assertion is physically evaluated later in the same test, the complete test can no longer pass on a denied, unavailable or empty failed engineer response. PHP syntax and `git diff --check` pass. Production files were not reviewed or modified.

---

## Gate 5 shared-scope test-delta review — 2026-09-25

- Reviewer independence: unchanged.
- Reviewed commit: `5a3d12bf232c528e1a3a1932537adb32133f4c18`.
- Scope: root-authored HTTP tests and additive verification registration only; dirty executor production excluded and untouched.
- Verdict: **CHANGES_REQUESTED**.

### Finding

1. **BLOCKING — the existing journey now assigns the purported out-of-scope object to the engineer whose exclusion it tests.** In `yii2_inspection_planning_ui_255_test.php`, the delta inserts the current assignment for object `451202` with `engineer_user_id=9403`. Later the same test logs in `other.person@shlz.ru` as user `9403` and still requires that engineer calendar omit `/pilot/objects/451202` while FKR includes it. Under the approved assignment-scope rule, `451202` is now in that engineer's scope, so the expectation is contradictory and a correct implementation cannot pass. Seed a structurally valid assignment to a different engineer identity (the existing neutral fixture engineer `7301` is sufficient for manual snapshot reads), leaving `9403` assigned only to `451201`.

The new standalone `yii2_inspection_scope_policy_255_test.php` is otherwise coherent: actor `9101` carries manager plus control-engineer roles, the second object is assigned to someone else (`9403`), and real queue, command and calendar must retain manager-global scope. The separate user without `objects.read` must receive exact `403` from queue and calendar with a full fact snapshot proving no DML. Verification input and suite registration are additive, and PHP syntax/`git diff --check` pass.

Correct the single conflicting assignment and request narrow rereview. Broader approved tests remain unchanged outside this delta.

### Shared-scope fixture correction — 2026-09-25

- Reviewed commit: `b17d3f073532b28b3e0afe87e357e5a075957f1a`.
- Verdict: **APPROVED**.

Object `451202` is now assigned to neutral engineer `7301`, so engineer actor `9403` has only `451201` in scope and the existing calendar exclusion/inclusion matrix is coherent. The mixed actor `9101` still carries manager plus control-engineer roles and must retain global queue access, independently of the neutral assignment.

The legacy active-queue regression now logs in its existing FKR actor `18`, grants only the additive construction-control/checklist read capabilities to role `1`, and continues to exercise its intended global pagination surface. This aligns fixture authority with the approved shared scope policy without weakening its queue expectations. PHP syntax and `git diff --check` pass; dirty production files were untouched. Gate 3 approval extends through `b17d3f073532b28b3e0afe87e357e5a075957f1a`.

### Calendar global-fixture delta — 2026-09-25

- Reviewed commit: `eddaf4704a67cb68015ac003452290966d61b83d`.
- Verdict: **APPROVED**.

Actor `18` now explicitly receives active role `7`, whose fixture code is `manager` and whose established permissions include `objects.read`. This supplies the approved manager-global scope required by the existing calendar and overflow expectations without changing their assertions or granting a test-only production bypass. The pre-existing removal of ordinary role `5`'s `objects.read` remains intact. PHP syntax and `git diff --check` pass; dirty production files were untouched.

### Pure-engineer fixture delta — 2026-09-25

- Reviewed commit: `a2e31a56f15a5afb5231c064e6963a66694a89ad`.
- Verdict: **APPROVED**.

The fixture removes user `9403`'s incidental role `9210` before that shared role is converted to `fkr_operator`. User `9403` therefore remains a pure control-engineer actor whose successful and denied behavior is determined by current assignment scope, while user `9401` retains the intended FKR-global path. Mixed-role manager precedence remains independently covered by `yii2_inspection_scope_policy_255_test.php`, so this isolation does not remove that acceptance case. PHP syntax and `git diff --check` pass; dirty production files were untouched.

### Explicit calendar capability delta — 2026-09-25

- Reviewed commit: `eaa3d8313c9cce0ca89dcb873ec2bc224c4c1690`.
- Verdict: **APPROVED**.

The calendar fixture now explicitly grants `objects.read` to active manager role `7` alongside actor `18`'s role assignment. `INSERT IGNORE` keeps setup additive and deterministic, no expectation changes, and the subsequent removal from ordinary role `5` preserves the denied-user branch. PHP syntax and `git diff --check` pass; dirty production files were untouched.

---

## CI fixture/governance correction review — 2026-09-25

- Reviewer independence: unchanged.
- Reviewed commit: `7eac9fee4a69b15b4d89c262e519f4ea49a4d7b4`.
- Scope: tests, verification ownership/input and exact asset contract only.
- Verdict: **APPROVED**.

The correction is coherent with the shared scope policy and the reported full-CI failures:

- `MariaDbYiiChecklist.php` is removed from both the changed-path input and the `construction-control-checklist-ui` ownership pattern because this slice changes the read trait, not the composition owner; the remaining path mapping is unambiguous.
- Inspection fixture actor `73` and the preopening actor `73` explicitly receive active manager role `7`, which already owns `objects.read`; their established `ownership=all`, E2E and pagination expectations therefore retain intentional global authority. Assignment-specific checklist behavior remains guarded by its separate application rules.
- Main navigation no longer advertises construction control when `objects.read` is removed, and the real route must return exact `403`; the unchanged full fact snapshot proves the denied reads remain no-DML.
- The production cutover digest `89f0968f61169acc5527a229c2affcac4dccc303694095c0cf6c58d1ccdab8c4` exactly matches the current local `inspection-schedule.js` bytes.

JSON parsing, PHP syntax for all changed PHP files, digest comparison and `git diff --check` pass. Production files were not edited by this review.

### Follow-up fixture correction review — 2026-09-25

- Reviewed commit: `893c0390538e72843cd6fc778dc154740960da3d`.
- Verdict: **APPROVED**.

The journey revocation now removes `inspection.item.complete` from both active roles `2` and `7` held by actor `73`. The replay denial and full no-fact snapshot therefore continue to test genuine capability loss rather than being bypassed by the newly explicit manager role. No later scenario depends on restoring that capability.

The navigation fixture explicitly names role `9201` as `manager`, making its initial construction-control route consistent with the global-scope policy. The later removal of `objects.read` still produces the exact route `403`, navigation exclusion and no-DML snapshot, so the negative phase is preserved. PHP syntax and `git diff --check` pass; production was untouched.

### Navigation membership delta — 2026-09-25

- Reviewed commit: `3dcf570423fdb16547d93d8bb0b23d062488456b`.
- Verdict: **APPROVED**.

The navigation matrix again includes `/pilot/construction-control` while `construction_control.read` remains granted, preserving the established permission-based MAIN membership contract. The separate direct request still requires exact `403` after `objects.read` revocation, so the new server-side global-scope rule remains independently enforced without being conflated with link membership. The no-DML snapshot is unchanged. PHP syntax and `git diff --check` pass.
