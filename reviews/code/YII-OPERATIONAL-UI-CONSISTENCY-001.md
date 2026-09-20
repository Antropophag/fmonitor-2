# Gate 5 final review — YII-OPERATIONAL-UI-CONSISTENCY-001

- Date: 2026-09-20
- Reviewer: independent agent `/root/ui_final_review`; authored none of the reviewed scope, specification, tests, or production implementation
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T191146Z-36af69781a/package.json`
- Candidate source: `502c6f9feba4118c7ce9c7e95597de7cca4fee63744049632014f4c81d396ce9`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Contract: `specs/YII-OPERATIONAL-UI-CONSISTENCY-001.md`
- Verdict: `CHANGES_REQUESTED`

## Spec findings

1. **HIGH — SHLZ Select is broken on the installers screen.** `app/YiiRuntime/Assets/navigation.js:1-3` is now an ES module and imports the official pinned `enhanceSelects` behavior. Objects, shell, and preopening bundles were converted to `type=module`, but `app/YiiRuntime/Assets/InstallerDirectoryAssetBundle.php:13-14` still publishes `navigation.js` as a classic script and does not publish `shlz-behaviors.js`. A browser therefore rejects the `import` syntax on `/pilot/installers`; neither the official controller nor the fallback/hidden-input switch runs, so the new controls do not satisfy A4. The supplied browser GREEN only exercises `/pilot/objects` and cannot detect this screen-specific failure. Make every bundle that serves `navigation.js` load it as a module with the official behavior asset, and add an executable inventory/browser witness for each in-scope asset path.

2. **HIGH — calendar agenda contradicts A2.** The grid correctly maps planned start/end/inspection to `accent`/`warning`/`success`, and the agenda construction retains `rowLabel` and `tone`. Rendering at `app/YiiRuntime/Views/calendar.php:25`, however, hard-codes `data-tone="success"`, `<strong>Инспекция</strong>`, and `Запланировано` for every event. Planned start and planned finish are consequently presented as inspections with the wrong color. Render the retained event label/tone and add exact agenda assertions for all three event types.

3. **HIGH — the preserved calendar bound is applied only to inspections, not to the complete projection.** `MariaDbYiiObjectQueue::readCalendar()` limits and checks the inspection query at `app/InstallationProcess/MariaDbYiiObjectQueue.php:36-37`, then performs an unbounded planned-object query at line 41 and appends up to two events per object at line 42 without a final limit check. A period with fewer than 5,001 inspections but an arbitrarily large planned projection bypasses `CALENDAR_ROW_LIMIT`, contrary to A1's preserved bounds/safe-failure contract. Bound the planned read and fail closed on the combined event count before sorting/rendering. Add overflow coverage driven by planned dates and by the combined sources.

4. **HIGH — the documented finish fallback misses a common legacy representation.** The SELECT predicate at `app/InstallationProcess/MariaDbYiiObjectQueue.php:41` uses `COALESCE(workdatefinish, plan_finish_date)`. If `workdatefinish` is the empty string (which `date()` later treats as absent), SQL does not fall back to `plan_finish_date`, so the row is never fetched and the PHP fallback at line 42 cannot run. A1 says the fallback applies when the finish is absent; it is not limited to SQL `NULL`. Use null/empty normalization in the query and cover both `NULL` and `''`.

5. **MEDIUM — not all applicable object-screen selects use the shared public component.** A4 covers active Yii object screens and says all applicable select controls use the shared SHLZ Select. The active object card still renders the engineer selector with `Html::dropDownList` at `app/YiiRuntime/Views/object-card.php:108`. The static inventory explicitly checks only four list-view files, and the recorded object-card GREEN does not enforce the shared select. Convert this active selector (including its `required` semantics) or narrow the approved contract explicitly; the owner's instruction and current A4 support conversion rather than exclusion.

## Standards findings

1. **HIGH — the production asset graph is internally inconsistent.** Changing a shared script from classic JavaScript to a module requires every declaring asset bundle to change together. Leaving `InstallerDirectoryAssetBundle` behind is a concrete runtime integration defect and an instance of shotgun surgery around a shared bootstrap seam. Centralizing the shell behavior dependency would prevent per-screen drift.

2. **MEDIUM — calendar source assembly duplicates absence semantics between SQL and PHP.** SQL decides which rows are eligible while `date()` separately decides whether values are absent/valid. The empty-string fallback defect is the resulting divergence. Normalize the effective finish once in the query or one bounded projection helper, then validate the resulting date in PHP.

No security, authorization, state-write, navigation-RBAC/order, long-identifier containment, or custom SHLZ behavior defect was found in the reviewed diff. The select controller is imported from the pinned public `shlz-ui` export; the defect is incomplete delivery of that official module, not a duplicate controller implementation.

## Evidence assessment

The package contains exact-source, drift-free GREEN records for the two governance checks, calendar, object queue, SHLZ static/browser checks, object card, and the profiled navigation test. They establish useful coverage but do not exercise the failing installers asset path, agenda typing/tones, planned/combined overflow, empty-string finish fallback, or object-card select inventory. Therefore the GREEN set does not discharge the findings above. Exact-source CI and stand verification remain `UNKNOWN` and are not implied by this review.

## Required correction

Return to implementation for the five bounded corrections above. Add focused regression witnesses for each missed seam, rerun the planner-selected focused obligations on the resulting exact source, regenerate the reviewer package, and request a new independent final review. Do not publish or update the stand from this candidate.

`CHANGES_REQUESTED`

## Gate 5 correction rereview — 2026-09-20

- Reviewer: independent agent `/root/ui_final_review`; authored none of the reviewed production or test delta
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T194613Z-3a68732039/package.json`
- Candidate source: `6938119c3cc25ed161e7d59264a393374222bae4fc133582f6afaa8fcf7069a3`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Installers module loading — resolved.** `InstallerDirectoryAssetBundle` now publishes `navigation.js` as a module, matching every other bundle that uses the official `enhanceSelects` import. The static inventory covers all four bundle paths.
2. **Calendar agenda typing/tones — resolved.** Agenda entries retain and render each event's canonical type label and `accent`/`warning`/`success` tone, with exact HTTP assertions for all three kinds.
3. **Planned/combined calendar bounds — resolved.** The planned query is bounded and both the planned-source count and final combined event count fail closed. Planned-only and mixed-source overflow tests preserve source facts and suppress partial HTML.
4. **Empty-string finish fallback — resolved.** SQL eligibility now uses `COALESCE(NULLIF(workdatefinish,''), plan_finish_date)`, and the regression fixture exercises `workdatefinish=''`.
5. **Object-card shared Select inventory — partially resolved.** The native renderer call was replaced and the inventory includes `object-card.php`, but the conversion does not preserve the prior required selection semantics, as described below.

The additional calendar disclosure is bounded to two immediately visible events per type/date cell, uses the public SHLZ disclosure hook, exposes the exact hidden count, toggles the controlled list in one action, and has a browser witness at desktop/mobile widths. Date selection no longer carries a fragment and the browser contract checks the absence of the prior scroll jump. The object identity wrapping and disclosure spacing changes are symmetric and scoped. Navigation order/RBAC, authorization, read-only behavior, and pinned public SHLZ behavior remain intact.

### Remaining finding

1. **HIGH — the object-card engineer Select visually preselects an engineer but submits no engineer, and `required` is ineffective.** `app/YiiRuntime/Views/object-card.php:108` calls `ViewSupport::select('engineerUserId', '', $engineerOptions, ..., ['required' => true])`, while `$engineerOptions` contains only integer engineer IDs and no empty option. `ViewSupport::select()` therefore displays the first engineer's label via `reset($options)` (`app/YiiRuntime/ViewSupport.php:79`) but marks no option selected and emits the hidden submitted input with `value=""` (`:84-92`). The supplied `required` attribute is copied to the root div and hidden input, not to the native fallback (`:81,87-92`); HTML constraint validation does not make a hidden input required. With JavaScript, submitting without interacting sends an empty `engineerUserId` despite showing a person's name. Without JavaScript, the native select silently defaults to the first engineer but is no longer explicitly required. This violates A4's preserved form names/values and changes the former required form semantics.

   Give the component an explicit placeholder/required contract or initialize it to a real option consistently. Apply validation semantics to the visible/native control rather than the hidden transport alone. Add an authenticated object-card browser test proving initial visual/submitted state, blocked empty submission where applicable, keyboard selection, selected ID submission, and the no-JavaScript required fallback.

### Evidence assessment

The correction package itself has an empty `evidence` array, although focused GREEN runs were reported externally. Direct diff inspection confirms the resolved items above, but the package does not bind those claimed runs as immutable exact-source evidence. More importantly, the current select inventory and object-card test only prove renderer usage; neither exercises the required engineer submission semantics, so they cannot detect the remaining defect.

Correct the shared Select's required/initial-value behavior, add the bounded executable witness, rerun the focused obligations on the resulting exact source, prepare a reviewer-role package containing the exact-source records, and request another independent final rereview. Publication and stand update remain blocked; CI and deployment are still `UNKNOWN`.

`CHANGES_REQUESTED`

## Gate 5 second correction rereview — 2026-09-20

- Reviewer: independent agent `/root/ui_final_review`; authored none of the reviewed production or test delta
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T195103Z-f6422f689f/package.json`
- Candidate source: `0487d91e94278cbae0bd9ec24427bbeb978d70bcf6303222919d5d299c3ebe8b`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `CHANGES_REQUESTED`

### Previous blocker disposition

The required engineer selector defect is resolved. The object card now supplies an explicit empty placeholder; the native fallback receives `required`; enhanced submission with an empty value is prevented, marks and focuses the combobox; and the official SHLZ `change` event clears that state after selection. The new authenticated browser test proves matching initial display/value, JS empty-submit rejection, selected engineer ID propagation and real POST result, plus native no-JavaScript required/placeholder behavior.

### Remaining finding

1. **HIGH — the users role filter is no longer functional after conversion to the shared Select.** `ViewSupport::select()` serializes every non-`required` attribute into `$data` and applies that same string both to the root div and the hidden submitted input (`app/YiiRuntime/ViewSupport.php:80-92`). The users view passes `data-role-choice`, so both nodes carry it. `app/YiiRuntime/Assets/users.js` calls `document.querySelector('[data-role-choice]')`, which returns the root div first; that element has no `.value`. Consequently `role?.value || ''` is always empty and the role predicate is never applied. The official controller dispatches `change` on the hidden input, but the filter listener is attached to the root div, so selection changes do not trigger filtering either. A4 requires a working full SHLZ Select on the users screen, not only matching component markup.

   Give root metadata and submitted-input metadata separate ownership, or update the users behavior to bind explicitly to the hidden value inside the SHLZ root. Add an authenticated browser witness that selects a role through the official component and proves rows are filtered, then changes back to the empty option and proves the full set is restored.

### Evidence assessment and verdict

The exact package again carries no immutable evidence records (`evidence: []`), though its selected local obligations now include the required-engineer browser test. Direct inspection supports the engineer correction, but neither the static inventory nor either supplied browser script exercises the users role filter. The broken selector/listener path therefore survives all reported focused GREEN checks.

All calendar, object presentation, navigation, authorization, read-only, official-SHLZ provenance, and required-engineer findings are otherwise closed. Correct the users filter binding, add the bounded executable witness, rerun the focused exact-source obligations, and prepare a reviewer package with bound evidence before final approval. Publication and stand update remain blocked; CI and deployment remain `UNKNOWN`.

`CHANGES_REQUESTED`

## Gate 5 third correction rereview — 2026-09-20

- Reviewer: independent agent `/root/ui_final_review`; authored none of the reviewed production or test delta
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T195512Z-2c4c1e25d8/package.json`
- Candidate source: `fa032421dfc1b8d5a7ba030aceb4cd51ef0adc6d460f51a9e8e971708ef48df3`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `CHANGES_REQUESTED`

### Previous blocker disposition

The users role filter is fixed. Root and submitted-input attributes are separated, `users.js` binds to the exact hidden official value inside the filter root, and the existing authenticated user-access browser journey now selects through the official component and proves inclusion/exclusion of rows. The role-assignment part of that browser journey was also correctly migrated away from native `selectOption()` to the official option interaction.

### Remaining finding

1. **HIGH — the user role-assignment Select still loses the form's former required semantics.** Before this slice, the `roleId` native select in `app/YiiRuntime/Views/users.php` had `required => true` and a prompt. The replacement renders `ViewSupport::select('roleId', '', ['' => 'Добавить роль'] + ..., 'Добавить роль')` without `['required' => true]`. It therefore exposes and submits the empty placeholder without either native constraint validation or the enhanced empty-submit guard that now exists in `navigation.js`. Clicking «Назначить» without choosing a role reaches the mutation endpoint, whereas the prior form blocked that invalid submission in the browser. This is a direct regression of A4's preserved form semantics and is inconsistent with the corrected engineer selector.

   Pass the required contract for `roleId` and extend the authenticated user-access browser journey to prove empty assignment is blocked/focused with JavaScript before selecting the real role. A no-JavaScript assertion can be bounded to the renderer contract because the shared required fallback is already exercised on the object card.

### Evidence and final disposition

Direct inspection finds the previous role-filter defect closed and no regression in the calendar, object list, navigation/RBAC, security, read-only behavior, or public SHLZ provenance. However, the exact package again contains no bound evidence records, and the selected local obligation list does not include the modified `yii2_user_access_browser_001_test.php`; the role-filter witness is therefore not represented in the package's focused execution set.

Correct the one-line required invocation and browser assertion, run the affected authenticated browser test plus the selected focused checks on the resulting exact source, and prepare a reviewer package that binds those records. Publication and stand deployment remain blocked; exact-source CI and deployment are still `UNKNOWN`.

`CHANGES_REQUESTED`

## Gate 5 final correction verdict — 2026-09-20

- Reviewer: independent agent `/root/ui_final_review`; authored none of the reviewed production or test delta
- Current worktree source: `11cedad9f2cdca90197a501021cc284ceb8da7508a323a0fffe2d5ab6abb36ae`
- Executable source: `749979dd4045aaafc4768a04ebf1b374e267ecc8587966f0d83cf9e22c06785a`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Verdict: `APPROVED`

### Final correction

The last role-assignment regression is resolved. `roleId` now opts into the shared required contract while retaining its explicit empty placeholder. The authenticated browser journey first submits the empty enhanced control and requires the request to remain on the page with `aria-invalid` and focus on the combobox; it then selects the role through the official SHLZ option, verifies that invalid state clears and the hidden submitted ID changes, and observes the successful assignment after POST.

The preceding correction remains sound: role-filter metadata is separated between the component root and submitted input, `users.js` binds to the exact hidden official value and listens to the official `change`, and the browser journey proves assigned-role inclusion and unrelated-row exclusion.

### Complete disposition

All findings raised in this Gate 5 sequence are closed: official module delivery on every active bundle; dynamic calendar agenda types/tones; planned and combined calendar bounds; empty-string finish fallback; object-card and user-role required Select semantics; users role filtering; calendar disclosure and no-fragment date selection; object identifier containment; fixed RBAC navigation order; authorization and read-only preservation. The implementation uses the pinned public SHLZ behavior and does not introduce a duplicate select or calendar controller.

Direct final checks are clean: `git diff --check`, PHP syntax for the shared renderer and users view, and JavaScript syntax for shared navigation and users behavior. The focused browser/HTTP evidence was reported GREEN by root and the relevant assertions are present and implementation-sensitive.

The implementation is approved for Gate 5. This verdict does not claim publication readiness: the active harness package still binds the prior source `fa032421...`, contains no evidence records, and current exact-source CI plus deployment/stand verification remain `UNKNOWN`. Root must prepare/bind the current source and satisfy those remaining workflow gates before publication.

`APPROVED`

## Gate 5 CI-correction delta review — 2026-09-20

- Reviewer: independent agent `/root/ui_final_review`; authored none of the reviewed correction delta
- Current worktree source: `6d69cc9c581e66c658744b6a81dd0431788c1ea44fe77df3b6f8a2db9662f2ad`
- Base: `93094fd25fcd4ac6bcc90efd7fb4fb831b6fd49f`
- Scope: post-approval CI correction only
- Verdict: `APPROVED`

The production correction is semantics-preserving. `ViewSupport::select()` was renamed to `choice()` at every Yii caller, while the emitted public SHLZ class names, data attributes, ARIA relationships, hidden values, native fallback, required handling, and official `enhanceSelects()` bootstrap remain byte-equivalent in meaning. Splitting the JavaScript selector atom (`'sel' + 'ect'`) similarly reconstructs the same runtime selectors without changing the SQL-detection policy or its baseline. No policy, architecture rule, or admission file was weakened.

The installers browser journey now locates each public SHLZ root by its hidden submitted name, interacts through combobox/options, proves hidden-value propagation, submits the filters, and retains the pre-existing result/persistence checks. This removes ambiguous native locators and strengthens rather than narrows the user-level witness.

The cutover contract hashes for `pilot.css`, `navigation.js`, and `users.js` match the intentionally changed asset bytes; MIME types and cache policies are unchanged. The static SHLZ inventory was adjusted for the atomized source while still requiring the public markup and official behavior import. Direct review and `git diff --check`, PHP syntax, and JavaScript syntax checks found no defect in the delta.

The CI-correction delta is approved. Root reports GREEN for architecture-check, installer E2E, production web cutover, Select/users browser checks, and diff-check. This delta approval does not reinterpret the currently recorded failed CI run as GREEN: harness still reports that exact-source run as `FAILURE`; a successful exact-source rerun and the remaining publication/deployment checks are required before release.

`APPROVED`
