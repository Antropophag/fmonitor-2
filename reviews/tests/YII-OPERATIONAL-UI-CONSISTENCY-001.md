# Gate 3 review — YII-OPERATIONAL-UI-CONSISTENCY-001

- Date: 2026-09-20
- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the reviewed scope, specification, tests, or RED evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T182338Z-e73b587ea9/package.json`
- Candidate source: `5ce833c28dcafa03dbf042fbc89cce78cad724a4113f255ef0bdccd4748df897`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Contract: `specs/YII-OPERATIONAL-UI-CONSISTENCY-001.md`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — A4 is not tested through an executable Yii or browser seam.** `tests/Yii2/yii2_shlz_select_001_test.php` only searches PHP source files for marker strings and `ViewSupport::select`. An implementation containing dead code or literal marker comments would pass. The test never renders objects, installers, users, or OTIZ; never proves preserved form names/values or submission; never exercises trigger/listbox/option state, keyboard behavior, `aria-expanded`/`aria-selected` transitions, selected/open styling, or the no-JavaScript native fallback. This does not cover the contract's central regression (the grey opened status selector) or its explicit interaction and fallback scenarios. Add real HTTP DOM assertions for every in-scope screen and a browser interaction/submission check using the public SHLZ behavior.

2. **HIGH — A1/A2 do not prove calendar events are sourced or placed correctly.** The calendar delta asserts the three row labels and merely checks that each tone occurs somewhere in the document. It does not assert that object `4512` produces a planned-start event on `2026-10-15`, a planned-finish event on `2026-11-03`, or that each event is in the correct row/cell with the required type text/agenda entry. It does not exercise the `plan_finish_date` fallback, nor prove null/zero planned dates create no event. A view with three empty rows and unrelated tone markers could satisfy the new assertions. Add DOM assertions tying object, date, row, event type, and tone together, plus explicit fallback and absent-date cases.

3. **MEDIUM — A3's overflow/full-value requirement has no regression witness.** `yii2_object_queue_001_test.php` checks the short labels, literal `0`, and one fixture engineer string, but never supplies a long registration number/address or asserts bounded wrapping and access to the complete value. The normative long-values scenario can regress while this suite remains GREEN. Add a deliberately long fixture and DOM/style contract assertions that distinguish contained wrapping from page-wide horizontal expansion while preserving the full accessible value.

4. **MEDIUM — A5's single-owner/no-post-render-mutation rule is not enforced.** The navigation HTTP matrix usefully covers visible membership, order, and several permission combinations, but equivalent final HTML can still be produced by the prohibited Shell regex/DOM insertion or reordering. There is no ownership/architecture assertion that Yii views call only `MainNavigation::render()` and that the legacy decorator is absent from the Yii render path. Add a bounded source/architecture witness for this explicit requirement while retaining the real HTTP matrix.

## RED evidence assessment

All four retained commands are exact-source, drift-free intended RED runs and fail on the expected missing behavior: calendar rows, short object labels, SHLZ Select markers, and navigation order. Their fixtures reach the real Yii HTTP seam where applicable, and the navigation test runs in the declared browser profile. However, each command is fail-fast, so the retained output does not establish sensitivity for the uncovered scenarios above; in particular the SHLZ command fails before any view inventory assertion and never has an interaction seam at all.

## Disposition

Return to Gate 2. Correct the four bounded coverage gaps, capture fresh exact-source intended RED evidence, regenerate the package/plan if the changed test inventory requires it, and obtain a new independent Gate 3 review before implementation. Gate 4 is blocked against this package.

`CHANGES_REQUESTED`

## Gate 3 correction rereview — 2026-09-20

- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the corrected tests or evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T183113Z-b67af242dc/package.json`
- Candidate source: `38cd0260ed9cd57b01fda71a8c85a4ab4daffa93ac2884e5f4540104ba61c56d`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **A4 executable interaction — partially resolved.** The new Playwright test reaches authenticated `/pilot/objects`, proves a real RED at the missing SHLZ root, and specifies click/keyboard ARIA transitions plus a hidden value. The no-JavaScript and submission requirement remains incomplete as described below.
2. **A1/A2 event binding — partially resolved.** Planned start/finish and inspection now have exact row/object/tone assertions, and missing planned dates have a negative witness. The finish fallback assertion remains insensitive as described below.
3. **A3 long values — resolved for Gate 3.** A 120-character registration/factory fixture proves full values remain in the identity element and the test requires a dedicated wrapping rule. Actual responsive appearance remains a required Gate 4 visual check.
4. **A5 ownership — resolved for Gate 3.** The existing full HTTP/RBAC matrix is now paired with a bounded prohibition on view-owned primary navigation and client insertion/reordering primitives.

### Remaining findings

1. **HIGH — planned-finish fallback can pass without any fallback event.** In `yii2_calendar_003_test.php`, the fallback assertion only searches for the string `calendar-row-planned_end calendar-day-2026-11-04`. That string is the `headers` relationship of the ordinary grid cell and exists whether or not object `4512` is rendered there. It does not require `data-object-id="4512"`, `data-event-type="planned_end"`, or `data-tone="warning"`. Replace it with the same exact row/date/object/type/tone DOM binding used for the primary finish source, and assert the superseded date has no finish event for that object.

2. **HIGH — the no-JavaScript fallback is not shown to be usable or submit the selected value.** `shlz_select_browser.mjs` only asserts `select[name="status"]` has count one with JavaScript disabled. A hidden or disabled native select, or one whose value is ignored by the GET form, passes. The normative scenario says the user can submit a valid value. Assert the fallback is visible and enabled, choose a non-default status, submit the form, and verify both the resulting query/value and server-rendered selected state. The JavaScript path likewise currently changes only the hidden input and never proves preserved server submission semantics.

3. **MEDIUM — the calendar repeated-read/no-write assertion was accidentally made tautological.** After the new fixture mutations the test executes `$before=$f->facts(); assertSameValue($before, $f->facts(), ...)`, with no request between the two snapshots. It therefore cannot detect a write by `GET /pilot/calendar/`. Capture the pre-request facts immediately before the repeated GET, issue the request, then compare the post-request facts. Keep fixture setup mutations outside the observed interval.

### RED evidence assessment

The five retained records are exact-source and drift-free. The new browser record is a valid intended RED at `full SHLZ Select root`; the other four fail at the expected missing calendar rows, short object labels, shared renderer marker, and navigation order. The evidence is reconstructible and no environment/setup failure is being mistaken for RED. Fresh RED will still be required after correcting the assertions above because those expectations are not present in the reviewed candidate.

### Rereview verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Return to Gate 2 for the three bounded corrections, retain the resolved witnesses, capture fresh exact-source RED, regenerate the package, and request independent rereview.

## Gate 3 third review — 2026-09-20

- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the corrected tests or evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T183434Z-ee89597827/package.json`
- Candidate source: `4a62bc5d8a197820436d0f8c81c6f57636b3270fd7f1d510b6e1599d2a9c5965`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `CHANGES_REQUESTED`

### Previous blockers

All three findings from the second review are resolved:

- the `plan_finish_date` fallback is now bound to exact row/date/object/event type/tone and excludes the superseded finish date;
- the repeated-read assertion now brackets a real calendar GET with fact snapshots;
- JavaScript and no-JavaScript select paths now submit non-default values, and the native fallback is required to be visible, enabled, and server-selected after navigation.

### Remaining finding

1. **HIGH — A4's keyboard and non-disabled visual-state regression is still unprotected, and the keyboard witness regressed in this correction.** The contract explicitly requires keyboard navigation and an opened/selected state that does not look disabled; this is the user-reported grey-select defect. The previous package's Playwright script pressed `ArrowDown`/`Enter` and asserted one `aria-selected="true"` option. The current `shlz_select_browser.mjs` removed those steps and only clicks an option. Neither version asserts that the trigger is enabled/not `aria-disabled`, or that its computed open/selected text styling remains the normal SHLZ interactive state rather than the disabled/grey state. A click-only implementation with broken keyboard handling or the original grey opened appearance can pass. Restore an explicit keyboard selection path and add a bounded open/selected-state assertion against the public SHLZ enabled-state contract (plus absence of disabled semantics). Retain the newly added submission/fallback assertions.

### Evidence assessment

All five package records are exact-source, drift-free intended RED and fail at the expected missing behavior, not setup. The corrected calendar and submission expectations are present in source, but fail-fast occurs earlier as expected. Evidence quality is otherwise sufficient; the remaining issue is missing executable sensitivity, not record provenance.

### Third-review verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on the single bounded A4 correction above. Capture fresh exact-source browser RED and regenerate the package before the next independent Gate 3 review.

## Gate 3 fourth and final review — 2026-09-20

- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the reviewed artifacts, tests, or evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T183758Z-8df73db841/package.json`
- Candidate source: `43f79c4309fef5a23678ec84abb2ca7846ff38b5f41c769a7f837a5e1945d156`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `APPROVED`

### Final findings disposition

All prior findings are resolved. The corrected Playwright contract now requires an enabled, non-`aria-disabled` trigger; open-state ARIA; keyboard selection through `ArrowDown`/`Enter`; exactly one selected option; the selected trigger class; full opacity; a computed enabled text color distinct from the public placeholder token; hidden-value propagation; JavaScript GET submission; and a visible, enabled, submitted, server-persistent native fallback without JavaScript. The earlier calendar event/fallback bindings, genuine repeated-read no-write interval, long-value containment witness, and navigation ownership/RBAC matrix remain present.

### Traceability and RED evidence

The specification and OpenSpec delta stay within the owner-authorized UI slice and preserve the calendar icon, domain history, RBAC grants, and OTIZ formulas. Tests cover the real Yii HTTP seams, deterministic fixtures, permission rejection, malformed queries, bounded failures, exact event placement/types/tones, object presentation, active select inventory/interaction, and fixed navigation order/visibility. Expected values are fixture- or contract-owned rather than copied from implementation.

All five retained records are exact-source and drift-free. They fail at the expected absent behavior: three calendar rows, short object labels, shared SHLZ renderer, full SHLZ browser root, and the new navigation order. The Playwright failure occurs after successful authenticated navigation and is not an environment/setup failure. CI, implementation GREEN, final review, deployment, and stand verification remain future gates and are not implied by this approval.

### Final Gate 3 verdict

`APPROVED`

Gate 4 may proceed against exact reviewed source `43f79c4309fef5a23678ec84abb2ca7846ff38b5f41c769a7f837a5e1945d156`. Any subsequent specification or test change requires planner-consistent renewed review.

## Gate 3 test-delta review — official SHLZ behavior — 2026-09-20

- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the test or implementation delta
- Current root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T190254Z-d1b0b61c4e/package.json`
- Package candidate source: `77a8023720a2ae4da3097d27359ea964401387aa8dbb63bcc0541ae7dc843ea8`
- Current executable source evidenced by GREEN records: `fb423b7ef81ea47585126b916894ecf7a21c4c63620f317ce17b1d45928aaff7`
- Verdict: `APPROVED`

### Scope and evidence exception

This is a bounded review of the A4 test delta in `tests/Yii2/yii2_shlz_select_001_test.php` and `tests/Yii2/shlz_select_browser.mjs`. The prior complete Gate 3 approval remains the baseline. The harness cannot combine the historical RED and current GREEN into one reconstructed multi-test package, so this verdict explicitly binds the named package and the three external immutable records supplied by root; it does not treat the package's empty evidence list as proof by itself.

### Review

The static inventory now requires the production navigation module to import `enhanceSelects` from the served public `/pilot/assets/shlz-behaviors.js` export and invoke it on `document`. This catches the rejected local/custom behavior while retaining the shared renderer and all-screen inventory assertions.

The Playwright test remains independent of the implementation mechanism after bootstrap: it exercises authenticated Yii HTML and verifies enabled semantics, open ARIA transition, keyboard navigation with multiple `ArrowDown` operations and `Enter`, selected-option state, enabled selected styling, hidden form value, JavaScript submission, and visible/enabled native no-JavaScript submission with server-selected persistence. The correction strengthens official behavior provenance without weakening the previously approved interaction, accessibility, appearance, or fallback matrix.

Historical RED record `1789930916842952000-bec8731d179742a3aa800889c5381687` is exact and drift-free and fails specifically because official SHLZ behavior was not imported/initialized. Current records `1789931185655645000-de1eb27fb02c4e29b39d4bc3ed4dc152` and `1789931185658795000-0acd01facfc345d3b2676571295d1411` are exact, drift-free GREEN for the static provenance/inventory test and the real browser interaction/fallback test respectively. Their shared executable source matches, and neither record reports setup failure.

### Test-delta verdict

`APPROVED`

The prior complete Gate 3 approval remains valid with this official-behavior correction. This is test approval only; it does not approve the implementation at Gate 5, CI, publication, deployment, or stand verification.

## Gate 5-finding test-delta review — 2026-09-20

- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the corrective tests or implementation
- Current root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T191959Z-5fc3b58acf/package.json`
- Candidate source: `b0ad5c92801b4aab2f427632ff819fddec318d7142663e73e6e9851085988fd1`
- Executable source: `504b9968b04da22505adf09c0a28c8b13f8cb0a627c0e9f543e58774108dd8b6`
- Causal finding record: `reviews/code/YII-OPERATIONAL-UI-CONSISTENCY-001.md`, verdict `CHANGES_REQUESTED`
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — planned and combined calendar bounds are checked as implementation text, not behavior.** `yii2_calendar_003_test.php` requires counts of the literal strings `CALENDAR_ROW_LIMIT+1` and `count($rows)>self::CALENDAR_ROW_LIMIT`. This is coupled to one spelling of the implementation and does not create either a planned-only overflow or a mixed inspection/planned overflow. An implementation can contain those strings in dead/unrelated branches, apply the wrong threshold, count source rows instead of emitted events, or return partial HTML and still pass. The Gate 5 finding explicitly required overflow coverage driven by planned dates and by combined sources. Seed both cases through the fixture and assert HTTP 503, no partial calendar HTML, safe response, and unchanged facts/schema, parallel to the existing inspection-only overflow witness.

2. **HIGH — agenda typing/tone covers only one of the three required event types.** The new assertion binds `planned_start` to `accent`, but there is no exact agenda assertion for `planned_end`/`warning` or `inspection`/`success`. The rejected implementation hard-coded every agenda entry as inspection/success, and a partial correction that fixes only planned start passes the new test while leaving planned finish wrong. Assert exact object/schedule, event type, text label, and tone for all three agenda entries.

### Resolved portions

- Empty-string finish fallback is exercised through the real Yii HTTP calendar seam and exact fallback event binding.
- All four asset bundles that can deliver `navigation.js` are required to load modules, while the retained browser test proves the official behavior on a real screen.
- `object-card.php` is included in the shared-renderer/no-native-select inventory, and the retained object-card HTTP suite is GREEN on the same exact executable source.
- The four supplied GREEN records are source-consistent, drift-free, and have no setup failure. They establish current behavior but do not replace the two missing sensitivity cases above.

### Verdict

`CHANGES_REQUESTED`

Return only the two bounded test corrections above, retain the resolved witnesses, capture exact-source results, and request another independent test-delta review before final Gate 5 rereview.

## Gate 5-finding test-delta rereview — 2026-09-20

- Reviewer: independent agent `/root/gate3_ui_review`; authored none of the corrective tests or implementation
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T192232Z-f8870c18c3/package.json`
- Candidate source: `41160b4e0538055a6efa2b7ca51bfb9b806804e7debee44f04ed14a93d1e54b7`
- Executable source: `7843927b995c0565d1526a35c991b58324596e2888fe009edec7ee1b25219bdf`
- Verdict: `CHANGES_REQUESTED`

### Resolved portions

The limit tests now create 5,001 planned rows and a mixed 2,000 planned/3,000 inspection projection through the real HTTP seam. Both require HTTP 503, no partial calendar markup, and safe error output. This resolves the prior source-string coupling and proves planned-only and combined limit behavior. Agenda nodes are now bound to all three exact event identities/types/tones.

Both supplied GREEN records are exact-source, share one executable source, are drift-free, and report no setup failure.

### Remaining findings

1. **MEDIUM — agenda text assertions are not scoped to agenda entries.** After each exact agenda-node assertion, the test uses `str_contains($page['body'], 'Плановое начало')`, `str_contains($finishAgenda['body'], 'Плановое завершение')`, and `str_contains($page['body'], 'Инспекции')`. Those strings are guaranteed by the three calendar row headings even if every agenda item still renders the wrong hard-coded label. This is the exact class of Gate 5 defect being corrected. Assert normalized visible text on the matched agenda entry (or a child dedicated to its type label) for each of planned start, planned finish, and inspection.

2. **MEDIUM — new overflow no-write assertions cover only source-row counts.** The planned-only check compares only `fm_maintable` count, and the mixed check compares only `fm_maintable`/schedule counts. A failing GET that appends an audit/domain event or alters another table still passes. Bracket each request with the fixture's complete `$f->facts()` snapshot (and retain the source counts where useful), matching the contract's no domain/audit/schema write requirement and the existing inspection-overflow evidence style.

### Rereview verdict

`CHANGES_REQUESTED`

Correct only these two assertion-sensitivity gaps, retain the real overflow fixtures and exact identity/type/tone bindings, capture fresh exact-source calendar GREEN, and request a final bounded test-delta review.
