# Gate 3 test review — YII2-OBJECT-CARD-PRESENTATION-001

- Date: 2026-09-21
- Reviewer: independent agent `/root/gate3_object_card`; authored none of the reviewed specification, tests, or RED evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T215602Z-1baf36bbb5/package.json`
- Candidate source: `8813869fb1c9f6691e8d15643f2fd1db785070bdf54cc6ca78700ba1cd17e626`
- Base: `f145e3e00f25644f5c4e32f7c2f3e8bba4f624a3`
- Specification: `specs/YII2-OBJECT-CARD-PRESENTATION-001.md`
- Public seam: authenticated Yii `GET|HEAD` object-card, related composition screens, installer directory, and desktop/mobile browser rendering
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789941378251147000-30bbd08fb49743828763da339960c215.json`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **CRITICAL — the registered test does not exercise the declared public seam.** `tests/Yii2/yii2_object_card_presentation_001_test.php:6-44` reads PHP, CSS, and JavaScript files and searches for strings. It performs no authenticated `GET` or `HEAD`, renders no fixture, and inspects no user-facing DOM. Dead code, comments, unused CSS, mismatched dynamic markup, or escaped/conditional output could satisfy it. Replace the source-string presentation assertions with deterministic authenticated HTTP fixture assertions for the card and every adjacent route. Retain only narrowly justified architecture/source checks for constraints that cannot be observed at HTTP.

2. **CRITICAL — the browser test is orphaned and therefore provides no executable acceptance coverage.** `tests/Yii2/object_card_presentation_browser.mjs` has no PHP launcher/fixture, is not registered in `tools/verification/suites.tsv`, and is absent from the verification input's `tests` and the package's selected commands. The only selected command is the static PHP test. Consequently none of the click/keyboard/responsive assertions runs in Gate 2 or planned CI. Add a registered PHP browser test that starts the Yii fixture, authenticates an actor, supplies a real object URL and Playwright module, executes the script with bounded timeout/cleanup, and records the result; map it in the verification input and regenerate the plan/package.

3. **HIGH — A1–A6 coverage is materially incomplete and several assertions are insensitive.** The suite does not bind address/status/registration/passport to the rendered composition or reject the duplicate summary; does not prove matching tab/panel ids, click, ArrowLeft, Home, End, roving focus, hidden panels, or the no-JavaScript first panel; does not cover planned/corrected/actual dates, manufacture/delivery date/completeness, or unknown-value behavior; does not prove each installer has name, employee number, and current status, or role/name/phone separation; and does not exercise document mappings for DOC/DOCX, XLS/XLSX, images, unknown fallback, full names, metadata, actions, preserved URLs, and authorization. Literal-token checks at `yii2_object_card_presentation_001_test.php:11-24` can pass with unrelated or nonfunctional markup. Add fixture-owned values and exact rendered/interactive assertions, including negative/unknown cases.

4. **HIGH — A7 preservation, authorization, rejection, and determinism have no witness.** No test snapshots complete domain/audit/schema facts around GET, HEAD, repeat GET, page load, or tab switching; no read-only actor is checked for absence of mutation controls; no permission rejection is exercised; and empty, corrupt, or unavailable projections are not tested for existing safe statuses/messages. Add before/after complete-fact snapshots around the actual requests/interactions, read-only and unauthorized actors, repeated-response assertions with stable normalization, and explicit safe degradation fixtures.

5. **HIGH — the privacy assertions do not establish the user-visible prohibition across the promised inventory.** `yii2_object_card_presentation_001_test.php:27-36` searches only four exact PHP array/string spellings and four installer-directory tokens. Equivalent output through variables/helpers, different case or spelling (`legacy`, `provenance`, integration-chain names, department fields), or data-origin values supplied by fixtures can pass. It also never verifies rendered HTML. Seed hostile provenance/position/department values and assert their absence from authenticated rendered HTML for the card, selection, original, execution, and directory while positively proving that position remains visible only in the directory.

6. **MEDIUM — A8 responsive and layout assertions are too weak even if the orphan browser script were run.** The browser script checks global horizontal overflow before and after resizing, but does not prove production sidebar presence, compact desktop work width, reachable tabs on mobile, or registration-number wrapping without truncation. The CSS source check explicitly requires `overflow-x: auto`, which can mask page/component overflow and does not derive the expected result from the contract. Use deliberately long fixture values and assert full accessible registration text, bounded card/page widths, visible/reachable tabs, production sidebar, and no document/page horizontal overflow at both declared viewports.

## RED evidence assessment

The retained record is source-consistent and fails at the first missing tab label, so it is a genuine intended RED rather than a setup failure. It does not validate the orphan browser script or sensitivity for later assertions because the PHP test is fail-fast. The RED is therefore necessary but insufficient for Gate 3 approval.

## Required changes

Return to Gate 2 and replace the static presentation oracle with authenticated HTTP fixtures plus a registered browser launcher covering the complete A1–A8 matrix above. Capture fresh exact-source intended RED evidence for every selected test, update `verification-input.json`, regenerate the verification plan/package, and request a new independent Gate 3 review before implementation.

`CHANGES_REQUESTED`

## Gate 3 correction rereview — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; authored none of the corrected tests or evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T220148Z-2c294dbf1f/package.json`
- Candidate source: `84068fa1bf7f08fc25d9077d5dcc5f2f322e701599d815504c05a7ee0f219764`
- Executable source: `b42ebca2b5a900323a20c62990a08e5e73ff64cb669ca4499d74f3f8be852005`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789941794084726000-65b871159cee40ceaf9dfff309dff07e.json`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Public seam — resolved.** The PHP test now starts `PreopeningFixture`, authenticates actors, and requests the real Yii object-card and installer-directory routes. Its presentation checks operate on rendered response HTML rather than view source.
2. **Orphan browser test — resolved.** The selected PHP acceptance test now launches `object_card_presentation_browser.mjs` against the fixture server with bounded cleanup. The browser path logs in and reaches the real card, so a separate suite entry for the helper script is unnecessary.
3. **A1–A6 completeness — open.** The correction moves the existing assertions to the real seam but does not add most of the missing acceptance cases described below.
4. **A7 preservation/authorization/determinism — partially resolved.** Complete facts now bracket GET/repeat GET/HEAD and a read-only actor is checked for two mutation controls. Deterministic output, rejection, safe degradation, and browser-interaction no-write remain uncovered.
5. **Privacy inventory — partially resolved.** Card and directory checks now inspect rendered HTML, but adjacent routes and hostile fixture values remain absent.
6. **Responsive/layout — partially resolved.** The browser now proves page-level no-overflow at both viewports and scrolls the fourth mobile tab into view, but the remaining desktop/long-identity assertions are absent.

### Remaining findings

1. **HIGH — A1–A6 remain substantially under-specified by the executable tests.** `yii2_object_card_presentation_001_test.php:10` checks only loose tokens anywhere in the page. It does not bind address/status/registration to the left/right identity composition, prove a separate persistent passport and one workspace, or reject a duplicate upper summary. Tabs are not checked for matching ids/`aria-controls`/`aria-labelledby`, initial `aria-selected`/roving `tabindex`/`hidden`, click, ArrowLeft, Home, End, or a usable first panel without JavaScript. There are no fixture-owned assertions for planned/corrected/actual dates, readiness, manufacture/delivery/delivery-date/completeness, unknown values without invented “Готов” or technical timestamps, installer name/employee-number/status per person, or engineer/responsible/foreman role-name-phone separation. Document coverage is only the presence of one row and `file-pdf`; DOC/DOCX, XLS/XLSX, images, unknown fallback, full names, metadata, separate actions, unchanged URLs, and authorization are untested. Add exact DOM/interaction assertions for these contract-owned outcomes.

2. **HIGH — adjacent-screen and hostile-value privacy coverage is still missing.** The test never requests `/selection`, `/original`, or `/execution`, although A4/A5 and the declared public seam cover them. It also does not seed distinctive position, department, provenance, `legacy`, or integration-chain values; searching ordinary card/directory output for a few literals cannot prove those projection values are suppressed. Seed hostile recognizable values, render every in-scope route, assert position/department and provenance are absent where prohibited, and positively prove the directory alone retains the seeded position while omitting provenance/update-origin output.

3. **HIGH — the remaining A7 cases can regress while the suite passes.** `yii2_object_card_presentation_001_test.php:12` compares only repeat/HEAD status and the empty HEAD body, not deterministic GET subject output. It does not exercise an unauthorized actor/rejection, empty/corrupt/unavailable projections and their safe messages/statuses, or snapshot facts around the Playwright page load and tab interactions. The read-only assertion names only two controls and does not establish absence of all mutation routes exposed by the card. Add normalized repeated-body equality, explicit unauthorized and degraded-projection cases, a complete mutation-control inventory, and a browser checkpoint so complete facts are compared before and after page load/tab switching.

4. **MEDIUM — A8 still lacks the long-identity and desktop composition witness.** `object_card_presentation_browser.mjs:27-31` establishes global no-overflow and last-tab visibility, but it does not use a deliberately long registration number, prove that its full text remains accessible and wraps without truncation, assert the production sidebar is present, or bound the compact workspace width at 1440×1000. Add fixture-owned long identity data and measurable assertions for those outcomes at the declared viewports.

### RED evidence assessment

The fresh record is exact-source and drift-free: candidate and end source are `84068fa1bf7f08fc25d9077d5dcc5f2f322e701599d815504c05a7ee0f219764`. It reaches the authenticated Yii card successfully and fails at the expected absent «Сроки и готовность» rendered label, not setup. Because the combined test is fail-fast before the later HTTP and browser assertions, this record does not establish RED sensitivity for the four remaining coverage gaps.

### Rereview verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Return to Gate 2 for the four bounded corrections, retain the resolved real-seam/launcher work, capture fresh exact-source intended RED evidence, regenerate the package if bound inputs change, and request another independent rereview.

## Final bounded visual rereview — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; authored none of the narrowed specification, corrected tests, or evidence
- Owner direction: faster visual-only presentation scope; existing domain and safe-degradation regressions are preserved rather than duplicated here
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221204Z-79069ab3c4/package.json`
- Candidate source: `39fbd73626c235ef5d210c871b13aceb7d96b5f8b018e842dc7015ce57c1e4ee`
- Executable source: `8f3b21f17c27ff3b7e6f8681e7093b67ca3107a574403287724eae4827500995`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789942326134368000-f4c2d600f5f9440f9aa7a9a3e2bf5dd7.json`
- Verdict: `CHANGES_REQUESTED`

### Resolved scope

The correction validly aggregates HTTP, source-inventory, and browser failures instead of stopping at the first missing label. It exercises authenticated Yii rendering, GET/HEAD and browser no-write, a bounded read-only control inventory, click/ArrowRight/Home/End, desktop/mobile overflow, production sidebar/canvas, mobile tab reachability, document-row presence, and a no-JavaScript request. The owner-directed exclusion of duplicated domain/safe-degradation scenarios is accepted for this rereview.

### Final complete findings

1. **HIGH — the narrowed normative contract and OpenSpec delta are not coherent.** `specs/YII2-OBJECT-CARD-PRESENTATION-001.md` narrows A3, A6, A7, and A8, but `openspec/changes/refine-yii-object-card-layout/specs/runtime/yii-object-card-presentation/spec.md:18-27,55-75` still normatively requires unknown-value behavior, every document-format mapping and unchanged URL, deterministic repeated rendering, and the broader preservation matrix. The current test intentionally omits those requirements. Update the delta to the owner-approved visual-only scope (without weakening retained external regression ownership), or restore executable coverage for the still-written requirements; Gate 3 cannot approve two conflicting acceptance contracts.

2. **HIGH — A2's explicit accessibility contract is still not sensitive.** The browser test covers click, ArrowRight, Home, and End, but never ArrowLeft. Neither HTTP nor browser assertions verify matching tab/panel ids with `aria-controls`/`aria-labelledby`, roving `tabindex`, inactive-panel `hidden`, or that the no-JavaScript *panel content* is available; the no-JS assertion only finds the tab label «Сроки и готовность», which can remain visible while its panel is hidden or empty. A tabs implementation with broken reverse navigation, mismatched relationships, multiple tabbable tabs, or unusable no-JS content would pass. Add exact relationship/state assertions, ArrowLeft, and a panel-content visibility/source witness with JavaScript disabled.

3. **MEDIUM — A4's department prohibition and per-installer composition are not enforced.** The rendered card checks generic `Табельный` and `Работает` tokens, while the adjacent source inventory checks only exact `['position']` and `['provenance']` spellings. It never checks department/division fields or binds a fixture installer's FIO, employee number, and current status to one rendered team item. Seed or use a known installer and assert those three values within its item; add the bounded department-field prohibition to the card and adjacent inventory. This remains within the visual-only scope and directly covers the narrowed A4 text.

4. **MEDIUM — A6 proves the container/action classes but not the promised file presentation.** The test requires `shlz-document-row`, visual, and actions tokens, but does not assert a public format icon, the complete available filename, available metadata, or that the action preserves the source URL. A blank row with an unrelated action passes. Bind the fixture PDF row to its expected icon/type, full name, metadata, action, and unchanged href. This is the single available-format case in the narrowed contract; a multi-format matrix is no longer required if the delta is reconciled.

### RED evidence assessment

The aggregate RED is exact-source and drift-free. It reaches both authenticated Yii HTTP and launched Playwright, reports the expected missing tabs/document rows/privacy changes/no-JS panel label, and is not a setup failure. Its aggregation materially improves evidence quality. Fresh RED is still required after adding the missing A2/A4/A6 expectations and reconciling the bound acceptance artifacts.

### Final rereview verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on these four bounded visual-contract corrections. No broader domain or safe-degradation test expansion is requested.

## Exact-correction rereview — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; authored none of the correction
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221446Z-1f288f7360/package.json`
- Candidate source: `ed5c5b380333402f8010cfa576ba5a62c4fbfab0b7dde1e2cb99ec2993c3211a`
- Executable source: `492c19b6c7b1cbf17073d9ec19682d1bb13b6589eaabe40ae561cb6c0383eced`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789942488714256000-abc94c553cd54481984918275a592699.json`
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Contract coherence — resolved.** The OpenSpec delta now matches the narrowed presentation scope and delegates existing safe-degradation coverage instead of retaining the removed broad matrix.
2. **A2 — partially resolved.** Exact initial relationships/state, ArrowLeft, and no-JavaScript first-panel content are now present. The interaction-state consistency gap below remains.
3. **A4 — partially resolved.** One installer's FIO/tab/status is bound and adjacent sources prohibit the exact department field. The object-card department escape remains unguarded.
4. **A6 — resolved for the narrowed scope.** The fixture PDF is bound to public row/icon/title/meta/action markup, full source filename, and its object download href. A multi-format matrix is no longer required.

### Final remaining findings

1. **HIGH — A2 checks `tabindex` and `hidden` only before any interaction, not after the required transitions.** `object_card_presentation_browser.mjs:2` verifies initial roving state, then click/ArrowRight/ArrowLeft/Home/End assertions check only `aria-selected`. The normative scenario requires `aria-selected`, `tabindex`, `aria-controls`, and `hidden` to remain consistent when each key activates a tab. An implementation that flips `aria-selected` but leaves focusability on the first tab and every inactive panel visible (or the active panel hidden) passes. After each activation, assert the active tab has the roving focus state and its controlled panel is visible/not hidden, while the previously active tab/panel become untabbable/hidden. `aria-controls`/`aria-labelledby` relationships may be established once because their values are static.

2. **MEDIUM — A4's department prohibition still excludes the object card itself.** `yii2_object_card_presentation_001_test.php:15` checks `['department']` only in `selection.php`, `original.php`, and `execution.php`; the rendered fixture has no distinctive department value, and `object-card.php` is absent from the source inventory. A future team-row implementation can render `$installer['department']` and still pass. Include `object-card.php` in the bounded source prohibition (or seed a department marker and assert it is absent from the rendered team item) while retaining the positive FIO/tab/status binding.

### RED evidence assessment

The new aggregate record is exact-source and drift-free. It reaches authenticated HTTP and Playwright, reports all currently absent presentation groups including the new installer/document expectations, and is not a setup failure. The two remaining issues are assertion-sensitivity gaps, so fresh aggregate RED is required after adding those expectations.

### Verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked only on these two visual-test corrections.

## Final Gate 3 approval — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; authored none of the reviewed specification, tests, or RED evidence
- Package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T221644Z-9ab5ff7a6a/package.json`
- Candidate source: `5c04267eb9056515ef73ef70a437452415c966863caf8fe67bc08d0334158741`
- Executable source: `dad6a1b0d66b2b8e49d762d556b0cc129d47f0f43f87ee7afdc2dcd2938fbecd`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789942605684974000-6ef8f484518b422aa7f92edfdab6a968.json`
- Verdict: `APPROVED`

### Final findings disposition

All prior findings are resolved for the owner-approved bounded visual scope.

- The browser `state` helper now verifies `aria-selected`, roving `tabindex`, panel `hidden`, and actual visibility for all four tabs after initial render, click, ArrowRight, ArrowLeft, Home, and End. Static `aria-controls`/`aria-labelledby` relationships and the no-JavaScript first-panel content are also covered.
- The bounded source inventory now includes `object-card.php` alongside selection, original, and execution views for position, department, and provenance field suppression, while the HTTP assertion binds the fixture installer's FIO, personnel number, and employment status in one team item.
- The narrowed normative spec and OpenSpec delta are coherent; PDF document-row icon/title/meta/action/href, authenticated HTTP, read-only controls, no-write, desktop/mobile layout, sidebar/canvas, and aggregate browser execution remain covered.

### RED evidence

The retained aggregate run is exact-source and drift-free: candidate and end source are `5c04267eb9056515ef73ef70a437452415c966863caf8fe67bc08d0334158741`. It reaches authenticated Yii HTTP and launched Playwright and reports the expected absent presentation behavior, including object-card field suppression and browser tabs/document/no-JavaScript failures. The record is an intended RED, not an environment or setup failure.

### Gate 3 verdict

`APPROVED`

## Mock-fidelity visual test-delta review — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: passport/workspace geometry, technical-fact placement, SHLZ asset URLs, fixture speed, and `PilotAssetController` verification boundary
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **HIGH — the new technical-fact placement directly contradicts the unchanged A3 contract.** Both `specs/YII2-OBJECT-CARD-PRESENTATION-001.md:21` and the OpenSpec delta requirement/scenario still say the «Сроки и готовность» tab SHALL show/group technical characteristics. The corrected test now requires floors, capacity, and speed in `.fm2-static-passport` and explicitly requires those labels to be absent from `#object-panel-readiness`. Either result necessarily violates one side. Record the owner-approved mock decision in both normative artifacts: A3 should limit the readiness panel to planned/factual dates, process readiness, and the missing-delivery statement, while A1 (or an explicit passport requirement/scenario) should require the available technical characteristics in the narrow left passport and the wider workspace on its right at desktop width. Then the existing XPath and Playwright assertions become traceable rather than contradictory.

### Resolved test quality

- The fixture-owned speed value complements floors and capacity, and exact `dt`/sibling-`dd` assertions inside `.fm2-static-passport` are sensitive to both location and value.
- The Playwright geometry assertion materially distinguishes the owner-approved narrow-left-passport/wide-right-workspace composition at the desktop viewport.
- Requiring `/pilot/assets/shlz-file-types/file-pdf-default.svg` and `/pilot/assets/shlz-icons/download.svg` is a bounded executable witness for the public PDF and download assets. Adding `PilotAssetController` to planned paths correctly declares the serving boundary; this does not review its future implementation.

### Verdict

`CHANGES_REQUESTED`

Return only the normative main/delta alignment for the approved passport placement and desktop geometry. The test mechanics and verification-input boundary are otherwise approved.

Gate 4 may proceed against exact reviewed source `5c04267eb9056515ef73ef70a437452415c966863caf8fe67bc08d0334158741`. Any subsequent specification or test change requires planner-consistent renewed review. This approval does not imply implementation GREEN, final review, CI, deployment, or publication readiness.

## A6 expectation test-delta rereview — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; authored neither the test delta nor production implementation
- Scope: the one-line A6 filename expectation in `tests/Yii2/yii2_object_card_presentation_001_test.php`; production implementation was explicitly excluded
- Reviewed test SHA-256: `063ab9a6b67d24386331b62504db1109457014a4c68ee30586d0a2f86eae987d`
- Verdict: `APPROVED`

The correction from upload input name `signed.pdf` to the projection-owned available name `Подписанный оригинал.pdf` is required by A6's “full available name” contract. `MariaDbYiiObjectCardProjection` and the applied object-card reader expose that canonical filename, and existing HTTP regression tests independently expect the same user-visible value. The surrounding assertion retains the PDF icon, metadata, and preserved object download href bindings; only the incorrect expected title changed.

No finding. The prior Gate 3 approval remains valid with this bounded expectation correction. This verdict reviews no production code and implies neither implementation GREEN nor final approval.

`APPROVED`

## Gate 5 correction test-delta rereview — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production correction was explicitly excluded
- Scope: A2 public Tabs tabindex, A4 current workforce status, A3 specification/expectation alignment, and verification-input boundary
- Verdict: `CHANGES_REQUESTED`

### Findings

1. **HIGH — the revised A3 acceptance is not executable beyond the missing-delivery message.** The main and delta specifications now require the first tab to group *available planned and factual dates, process readiness, and technical characteristics*. The test adds only `Сведения о поставке не переданы`; its browser no-JS check retains only `Плановое начало`. An implementation that renders that one planned label plus the absence message, but omits the fixture's remaining available dates, process state, and technical facts, passes. Add fixture-owned rendered assertions for each category A3 now names (at minimum the available planned date pair, the available factual/process state applicable to the chosen fixture, and one distinctive technical characteristic), scoped to the first tab/panel. If the fixture deliberately has no factual date, assert the contract-owned absent-state presentation rather than silently leaving the category untested.

   The delta scenario at lines 21–23 should also say “process readiness or technical characteristics,” matching the revised requirement, rather than retaining the old “readiness or equipment” wording.

### Resolved portions

- A2 correctly follows the public `shlz-ui` contract by expecting active `tabindex="0"` and inactive `-1` across every interaction state. This is an independent public-contract expectation and does not preserve the implementation shim.
- A4 deterministically changes installer 7001's current workforce status to `dismissed` after composition selection, then binds FIO, personnel number, and rendered `Уволен` in one team item. This is sensitive to a projection incorrectly using the historical selection snapshot.
- Adding `app/InstallationProcess/MariaDbYiiObjectCardProjection.php` to planned paths correctly declares the production boundary required to expose current status; it does not itself approve that future production delta.

### Verdict

`CHANGES_REQUESTED`

Return only the bounded A3 expectation/scenario correction for rereview. The A2, A4, and verification-input changes are approved.

## A3 correction rereview — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: revised A3 delta scenario and executable expectations only
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **HIGH — the values are asserted globally, so the required first-tab grouping remains untested.** `yii2_object_card_presentation_001_test.php:11-12` applies independent `str_contains($body, ...)` checks to the entire page. Generic values such as `9`, `630`, `Принят`, the process status, and both dates can be rendered in the passport, header, documents/history, hidden duplicate markup, or unrelated navigation and still pass. A3 specifically requires these facts to be grouped in the «Сроки и готовность» panel. Bind the four categories to that exact panel: extract or regex-match the panel controlled by the «Сроки и готовность» tab, then assert the planned start/date, planned finish/date, process readiness/original state, technical label/value pairs, missing-delivery message, and absence of invented factual start within that panel. Prefer label/value pair assertions so bare `9` and `630` cannot match unrelated content.

### Resolved portion

The OpenSpec scenario wording now matches the revised requirement, and the chosen fixture-owned facts plus explicit absent factual start are appropriate expected values. Only their DOM scoping/sensitivity remains open.

### Verdict

`CHANGES_REQUESTED`

Return only the bounded first-panel binding correction. Previously approved A2, A4, A6, verification-input, and A3 wording remain approved.

## Final A3 correction approval — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: A3 first-panel DOM binding correction only
- Verdict: `APPROVED`

The test now resolves the exact `#object-panel-readiness` node through `DOMDocument`/XPath and binds each fixture-owned `dt` label to its sibling `dd` exact value: both planned dates, process readiness, accepted-original state, floor count, and load capacity. The missing-delivery message and absence of an invented factual start are evaluated only inside that panel. This closes the prior false-positive path through header, passport, documents, history, or unrelated page text.

No findings. The revised A3 scenario wording and the previously approved A2, A4, A6, and verification-input corrections remain valid.

`APPROVED`

## Mock-fidelity specification correction approval — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: main/delta ownership of passport technical facts and desktop passport/workspace composition
- Verdict: `APPROVED`

The main contract and OpenSpec delta now agree with the approved executable expectations: A1 owns the narrow left passport, its available technical characteristics, and the wider right workspace; A3 owns planned/factual dates, process readiness, and the explicit missing-delivery statement, while excluding technical characteristics from that tab. The delta opening scenario makes the same left/right width relationship observable.

This resolves the sole finding from the mock-fidelity test-delta review. The previously approved exact passport label/value assertions, readiness exclusions, desktop geometry, public SHLZ PDF/download asset URLs, fixture speed, and `PilotAssetController` verification boundary remain valid.

No findings.

`APPROVED`

## Public SHLZ asset-seam test-delta review — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: unauthenticated canonical SVG GET/HEAD and rejection expectations
- Verdict: `APPROVED`

The acceptance test reaches the real unauthenticated Yii HTTP seam for both public assets referenced by the object card. Expected bodies are independently read from the public `../shlz-ui` distribution exports and compared byte-for-byte. Each GET is bound to exact SVG MIME, `public, max-age=3600` (therefore no `immutable`), `nosniff`, and same-origin CORP. Each HEAD must return 200 with an empty body and a content length equal to the canonical asset byte length.

The negative matrix distinguishes a simple unknown filename from encoded traversal and requires 404 without leaked SVG content. These checks catch local copies, stale/wrong assets, authenticated-only serving, permissive path resolution, incorrect cache/security policy, and GET/HEAD divergence. `PilotAssetController` and asset configuration are already declared production boundaries in the verification input.

No findings.

`APPROVED`

## Documents secondary-action micro-test review — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: history/correct visual action assertions only
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **MEDIUM — the assertions are neither documents-panel scoped nor guaranteed to select an anchor.** `yii2_object_card_presentation_001_test.php:113-115` runs a regex over the entire response body. It can pass if an identically labelled SHLZ button appears outside the Documents panel. Because the pattern begins at a `class` attribute rather than `<a`, it also does not prove the element carrying `shlz-button` is the opening anchor that closes at `</a>`. Reuse the parsed DOM/XPath: locate the exact Documents tabpanel, then require exactly one `.//a` for each label whose class token contains `shlz-button` (and whose `href` is the appropriate history/correction route). This directly distinguishes the required action anchors from bare text links inside the correct panel.

### Verdict

`CHANGES_REQUESTED`

Return only this bounded XPath/scoping correction. The document-row and all earlier approved expectations remain valid.

## Documents secondary-action correction approval — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: corrected history/correct action assertions only
- Focused evidence: reported GREEN for `php tests/Yii2/yii2_object_card_presentation_001_test.php`
- Verdict: `APPROVED`

The corrected assertions resolve the exact `#object-panel-documents` node and require exactly one `<a>` for each action label. XPath class-token matching binds `shlz-button` to the anchor itself, while the distinct `/originals/history` and `/originals/submit` href suffixes prevent the two actions from satisfying each other's expectation. Bare text links or buttons outside the Documents panel no longer pass.

No findings. The document row and all earlier approved expectations remain valid.

`APPROVED`

## Technical display mapper test-delta review — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: numeric technical-enum presentation expectations and mapper boundary
- Verdict: `APPROVED`

The fixture deliberately supplies numeric-looking `raw` and `display` values for three distinct technical fields, preventing an implementation from passing by echoing the projection display unchanged. Exact `dt`/sibling-`dd` assertions inside `.fm2-static-passport` bind `pittype=40` to «Глухая», `pitmaterial=41` to «Железобетон», and `paired=39` to «Первая». The negative raw-token checks independently reject the current direct-echo failure shape in the response HTML.

The expected names are owner-approved presentation outcomes, and the verification input declares the named `app/YiiRuntime/ObjectCardTechnicalDisplay.php` presentation boundary. The test changes no importer/read-model facts and remains scoped to rendering.

No findings.

`APPROVED`

## Final passport-field test-delta review — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: speed/lift/shaft passport fields and exclusion of work sequence
- Verdict: `CHANGES_REQUESTED`

### Finding

1. **HIGH — two new expectations have no normative owner in the current contract.** A1 requires available permanent technical characteristics and names floors, capacity, speed, and shaft characteristics, so the exact speed and shaft label/value assertions are aligned. It does not say that lift type is part of the required passport set, nor that `paired`/«Очередность» is a workflow value that MUST NOT appear there. The new test therefore both requires an additional field and rejects an otherwise contract-permitted available characteristic without a written acceptance decision. Amend main A1 and the delta opening requirement/scenario to include available lift type and explicitly exclude work sequence/`paired` from the technical passport. If `44 → 1,0` is an owner-approved presentation mapping rather than merely test fixture knowledge, record that worked example in the contract or design so the expected value is independent of the pending mapper implementation.

### Test sensitivity

The mechanics are otherwise strong: exact passport-scoped `dt`/sibling-`dd` pairs bind speed, lift type, shaft type, and shaft material; browser assertions cover their visible labels and exclude «Очередность»; raw numeric tokens are rejected from HTML. The `paired=39` fixture makes the negative expectation causal rather than vacuous.

### Verdict

`CHANGES_REQUESTED`

Return only the bounded normative alignment/worked example. No production review is requested.

## Final passport-field specification approval — 2026-09-21

- Reviewer: independent agent `/root/gate3_object_card`; production implementation was not reviewed
- Scope: passport field inventory, `paired` exclusion, and speed-code worked example
- Verdict: `APPROVED`

Main A1 and the OpenSpec delta now explicitly own the exact field inventory exercised by the tests: floors, capacity, speed, lift type, shaft type, and shaft material. Both exclude `paired` as work sequence rather than a passport characteristic and define the independent worked expectation `44 → 1,0 м/с`. The design records the same decision and fail-closed suppression of unknown numeric reference codes.

This resolves the sole contract-traceability finding. The previously reviewed exact passport pairs, visible browser labels, causal `paired=39` exclusion, and raw-code rejection remain aligned and sensitive.

No findings.

`APPROVED`
