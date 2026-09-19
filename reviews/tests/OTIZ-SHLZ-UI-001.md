# Test review: OTIZ-SHLZ-UI-001

- Reviewer: Codex independent Gate 3 reviewer `/root/issue196_gate3` (gpt-5.6-sol / low); authored neither the specification nor the tests
- Test author: root agent, per `docs/operations/current-delivery-goal.md`
- Reviewed source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T205653Z-36963b4b69/snapshot/source.patch`, SHA-256 `13ca4207f20e59e74af72e4bdf4d6c03a62c4a92bd134466414f2fbab312a56c`; candidate source `1c68d0aa927800ab7274d77859a8c99c21b9004ae8854b29974c9a3d667d05d7`
- Agreed review scope / prior findings disposition (for rereview): first review of the complete prepared Gate 3 package; no prior findings
- Specification: `specs/OTIZ-SHLZ-UI-001.md` (`57b40195bca93b98970dc9f5d88813f0b7c5614d9e243dbf62c771c47d1bc8a2`)
- Public seam: authenticated Yii2 `/pilot/otiz/**` HTTP DOM/browser routes plus existing OTIZ publication/settlement HTTP/browser/domain seams selected by the verification plan
- Red command and intended failure: `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php` — source-bound record `1789764996691053000-b7420971172c4c7cbc9ced11d893b1ac.json`, exit 255 at `otiz_shlz_ui_browser.mjs:25`, `shared authenticated shlz shell`, actual 0 / expected 1. This is an intended missing-presentation failure, not setup failure.
- Companion evidence: `php tests/Otiz/settlement_owner_001_test.php` GREEN in record `1789764996691037000-ab21a4fa6ac64ac8855e47b180707b37.json`; `php tests/Yii2/yii2_otiz_settlement_browser_001_test.php` GREEN in record `1789764996691057000-0e5b3813b1894c82b5ffcf862308ddd5.json`. All three records bind candidate source `1c68d0aa927800ab7274d77859a8c99c21b9004ae8854b29974c9a3d667d05d7` and executable source `91c6c55188f2ec0e93f95cf10d798f289da8575915954e038ef0b8033a03de53`.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — the presentation test is mapped as complete A1–A7 evidence but omits material required contexts and states.** `specs/OTIZ-SHLZ-UI-001.md:15,23,31,35,39` requires recognizable current subnavigation across overview/register/payments/history/snapshot, primary/secondary/danger action semantics with navigation remaining links, 200% zoom, workflow-order keyboard traversal, JS-off behavior, and the enumerated empty/status/permission-limited edge states. `tests/Yii2/otiz_shlz_ui_browser.mjs:24-90` exercises one accepted snapshot, one register visit, four viewport widths, one initial `Tab`, one custom motion token, and JS-off only on the snapshot. It never applies or measures 200% zoom; never verifies tab order or keyboard activation; never checks current-tab state; never distinguishes secondary/danger commands or navigation element semantics; and does not cover empty register/history, draft/paid, blocked, absent issues/allocations/closures, or permission-limited action removal. A production implementation can fail each of those explicit acceptances while this test passes. The generic viewport predicate at lines 47-50 also checks only off-screen interactive elements, not overlap between content/actions as claimed by A5.

2. **HIGH — A8 and its verification-matrix publication seam are not completely mapped.** The contract at `specs/OTIZ-SHLZ-UI-001.md:43,61` requires existing publication and settlement HTTP/browser regressions, authorization-before-validation, GET/HEAD read-only behavior, unchanged replay/concurrency outcomes, and no snapshot/closure/operation/event facts for rejected, forbidden, or invalid commands. `openspec/changes/refresh-otiz-shlz-ui/verification-input.json` maps only the settlement browser and direct settlement owner. The browser test supplies a useful invalid-closure before/after checkpoint, but it does not submit exact replay or a forbidden command through the refreshed HTTP forms and does not establish authorization-before-validation or GET/HEAD fact stability. The direct owner test cannot detect controller/view changes to HTTP routes, methods, CSRF, payloads, or return paths. The existing `tests/Yii2/yii2_otiz_publication_browser_001_test.php` is expressly called for by the spec matrix but is absent from the plan. Its presently reported `PreopeningFixture` HTTP 503 is pre-existing and by itself is not evidence that the new presentation test is malformed; however, it does not become GREEN or dispensable. The scoped Gate 3 remains blocked by the missing mapping/coverage, and the 503 must be diagnosed or replaced with equivalent stable public-seam evidence before approval rather than silently excluded.

3. **MEDIUM — reduced-motion sensitivity proves only an implementation token, not the observable contract.** `tests/Yii2/otiz_shlz_ui_browser.mjs:78-80` asserts that `--fm2-motion-duration` is `0s`/`0ms`. An implementation can set that token while leaving transitions or animations active through another duration/property, and the test does not compare content, state, available actions, or tab order with the normal-motion context as required by `specs/OTIZ-SHLZ-UI-001.md:35`. Inspect computed `transition-duration`/`animation-duration` on the affected composition (or otherwise prove no optional motion remains) and compare the required semantic/action/order observations across contexts.

Expected-value independence is otherwise sound for the exercised paths: the fixture seeds fixed literal amounts and identities, while the PHP settlement assertions independently inspect persisted rows and exact literal money outcomes rather than copying rendered values. Isolation and determinism are also adequate: random private database/artifact names prevent collisions, domain dates and fixture values are fixed, loopback services are bounded, and cleanup is owned. The RED is source-bound and fails at the intended missing shell assertion. Those strengths do not close the missing acceptance rows above.

## Required changes

- Extend the mapped browser/HTTP evidence to cover the omitted A1–A7 contexts and edge states, including a real 200% zoom observation, meaningful keyboard order/activation, current navigation state, complete action hierarchy, representative empty/status/permission-limited cases, and JS-off/reduced-motion equivalence at the contract breadth.
- Complete A8 mapping at the public HTTP/browser seam: include stable publication evidence and assertions for forbidden/invalid precedence, read-only GET/HEAD, replay/no-duplicate facts, unchanged forms/routes/return paths, and before/after counts for all named fact classes where applicable. Diagnose the pre-existing publication-browser HTTP 503 or provide equivalent mapped stable evidence; do not label the failure GREEN.
- Capture fresh intended-RED/companion evidence and prepare a new exact-source reviewer package after the test and verification-plan changes.

---

## Correction round 1 — 2026-09-19

- Reviewer: Codex independent Gate 3 reviewer `/root/issue196_gate3` (gpt-5.6-sol / low); still independent of specification and test authorship
- Reviewed correction source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T210143Z-459b555e8e/snapshot/source.patch`, SHA-256 `4503c6428f697adc1486d9faa17c31b526fb544b69d53d450362854452036730`; candidate source `a40009a47d0678b383dbe39e6c7582e3d7099bc05e2d2b8620ce22c634f69f7f`; executable source `35dc4e1c7e1eb15603d43b378a82831580bba288795b14305f2f528e35c5d30c`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T210143Z-459b555e8e/delta.patch`, SHA-256 `b5883696f81a6cc69ec1760bd45efd875ae27f2136401791a506736d70a717d6`
- Verification plan: SHA-256 `3a89bfd4b9bf45e3a66154b0f7133a3476949417dcb56d696827fda322004e27`; `missing_tests=[]`
- Fresh RED: `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php`, record `1789765264504403000-7d3c745a83a54e7d8eef9804b806a99a.json`, intended failure at the absent shared authenticated shell
- Fresh mapped GREEN evidence: `yii2_otiz_workflow_001_test.php`, `yii2_otiz_commands_001_test.php`, `snapshot_publication_001_test.php`, `settlement_owner_001_test.php`, `settlement_concurrency_001_test.php`, `object_register_paging_http_001_test.php`, and `yii2_otiz_settlement_browser_001_test.php`; exact record paths are retained in the prepared package
- Current verdict: `CHANGES_REQUESTED`

### Prior finding disposition

1. **Prior HIGH (A1–A7 breadth): PARTIALLY RESOLVED.** The delta now checks current states for four financial tabs, primary/danger/secondary element semantics, collisions, blocked/draft and empty collection states, broader JS-off routing, skip-link activation, disclosure keyboard activation, and fresh RED evidence. It still does not cover the complete acceptance:
   - `Emulation.setPageScaleFactor(2)` at `tests/Yii2/otiz_shlz_ui_browser.mjs:66-71` is visual/pinch scaling, not 200% browser layout zoom/reflow. It leaves the CSS layout viewport (`documentElement.clientWidth`) unchanged, and the assertion only requires a nonzero primary rectangle, not containment in the reduced visual viewport. A layout that overflows or hides actions at actual 200% browser zoom can pass.
   - Lines 85-88 prove the skip link and one programmatically focused disclosure, but do not traverse or assert that keyboard tab order follows DOM/workflow order as required by A5. Programmatic `focus()` bypasses the order under test.
   - The navigation/JS-off matrices omit `/pilot/otiz` overview; the test does not assert a workflow header on payments; and the draft fixture asserts only absence of settlement forms, not the available accept action or exactly one primary action. A7's permission-limited presentation is still absent: mapped command denial proves server authorization, but no browser assertion proves forbidden actions are absent.

2. **Prior HIGH (A8 mapping): PARTIALLY RESOLVED.** The plan now maps seven exact-source GREEN regressions, including stable publication domain evidence, Yii route/command mapping, concurrency, settlement browser facts, and object-register HTTP behavior. This legitimately avoids treating the pre-existing `yii2_otiz_publication_browser_001_test.php` fixture 503 as GREEN and substantially improves replay/concurrency/rejection sensitivity. However, the required public-seam matrix remains incomplete. The mapped command test checks invalid cases while authorized and a valid command after permission removal; it never sends an invalid payload while unauthorized, so authorization-before-validation can regress while all mapped tests pass. No mapped assertion exercises HEAD and proves fact stability, and no positive publication/accept browser flow traverses the refreshed form while checking its exact method/route/CSRF/payload/return path. The direct publication owner test cannot detect those controller/view regressions.

3. **Prior MEDIUM (reduced motion): RESOLVED.** Lines 98-102 compare workflow text, visible actions and DOM order before/after reduced-motion emulation and inspect effective computed transition/animation durations throughout the OTIZ composition. This is sensitive to active motion outside the custom token.

### Current complete findings

1. **HIGH — actual 200% browser zoom/reflow and workflow keyboard order remain untested.** Replace the page-scale simulation with a layout-equivalent browser zoom/reflow observation (for example, an effective halved CSS viewport or a supported browser zoom mechanism) and assert page/action containment against that effective viewport. Traverse the natural tab sequence and assert the relevant workflow-order targets, rather than focusing a later target programmatically.

2. **HIGH — presentation edge/state coverage remains incomplete.** Add the overview route to the shell/current-context and JS-off matrices, assert the payments workflow header, assert the draft accept action/one-primary hierarchy, and use a permission-limited authenticated browser actor to prove forbidden actions are absent. These are explicit A1/A7 observations and cannot be substituted by domain authorization tests.

3. **HIGH — A8 still lacks authorization-before-validation and complete positive HTTP form preservation.** Add a public HTTP case combining absent permission with malformed query/payload and assert the authorization outcome plus unchanged named facts; add HEAD/read-only before/after evidence; and map a stable positive publication/accept form journey (or equivalent HTTP browser evidence) that checks exact method, route, CSRF, fields, redirect/return path, replay result and no duplicate facts through the presentation seam.

Expected-value independence, deterministic fixture data, private isolation/cleanup, and the intended missing-presentation RED remain acceptable. No production code was reviewed or changed in this Gate 3 round.

### Required changes for correction round 1

- Resolve the three current findings above, capture fresh exact-source RED/GREEN evidence, and prepare another reviewer package.

---

## Correction round 2 — 2026-09-19

- Reviewer: Codex independent Gate 3 reviewer `/root/issue196_gate3` (gpt-5.6-sol / low); authored neither specification nor tests
- Reviewed correction source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T210546Z-b3e382f496/snapshot/source.patch`, SHA-256 `a87a734eeb983f01137cd14ed2fcf599d9d47b5a380fdee8b82fc7e36c70638b`; candidate source `ef8e56622153f702826949d6995cb0545c2248cdf55425fe67fd574d656bc3c9`; executable source `cd69390fa312b1fbe05839594c55823bab575a05ddb62289a8d1925143f20a97`
- Verification plan: SHA-256 `70cdf747f4cc012316064f55a7b47adcaaf0d1f0dc81be548dbbaa2dc3201704`; `missing_tests=[]`
- Fresh RED: `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php`, record `1789765521598765000-131ee7663e514849b368d21635572c60.json`, intended absent-shell failure at `otiz_shlz_ui_browser.mjs:25`
- Fresh mapped GREENs: records `1789765521606272000-c6113baf29a940fe94cf39121a88d3d5`, `1789765521605333000-077e2e5d1eb04ec58a93c1c8b23f2a94`, `1789765521602836000-e012c711de1f4e809cb63416d174542e`, `1789765521625525000-bbbeb31aa1e54dd08d70749ea389d1f1`, `1789765521654158000-047e8b02f5b742fc91af1826ba0ec400`, `1789765521673049000-57938d390f1245dea2e96b9efc7029b1`, and `1789765521679659000-a8cf156688df46c68f8e3a15b8dc54bd`
- Final verdict for this package: `CHANGES_REQUESTED`

### Remaining-finding disposition

1. **Round-1 finding 1 (zoom and keyboard): RESOLVED.** The 384-CSS-pixel context over a 768-pixel screen is a valid 200% layout/reflow equivalent, explicitly proves the 2:1 ratio, page containment and a visible primary action, and is complemented by the 320/768/1024/1440 matrix. The skip-link assertions plus natural `reportDate` → primary submit tab transition and keyboard activation of disclosure/accept action provide sensitive representative workflow-order and activation evidence.

2. **Round-1 finding 2 (presentation states): MOSTLY RESOLVED, one bounded omission remains.** Overview/current-tab, payments workflow/one-primary, ready-draft accept, permission denial/action absence, blocked/empty collections and paid behavior across the mapped settlement browser are now covered. However the JS-disabled matrix at `tests/Yii2/otiz_shlz_ui_browser.mjs:106-111` still omits `/pilot/otiz`, despite adding that route to the normal-navigation matrix and despite A6 requiring authenticated SSR behavior on the GET screens. This is a small but foreseeable hole: overview can acquire a JS-only dependency while every asserted JS-off route remains healthy.

3. **Round-1 finding 3 (A8): PARTIALLY RESOLVED.** The correction proves authenticated HEAD success plus a complete eight-table fact fingerprint after GET/HEAD, exercises unauthorized + malformed input with an exact 403, proves a permission-limited response contains no actions, and drives a positive accept form by keyboard through its success redirect/status. The broader mapped GREEN set independently covers invalid/forbidden no-fact outcomes, publication replay/concurrency and settlement form facts. Two sensitivities are still missing at the changed presentation seam:
   - The unauthorized-malformed request at `otiz_shlz_ui_browser.mjs:114-116` has no before/after fact checkpoint. The existing command test proves no facts for authorized-invalid requests and for a different valid-denied request, but a controller can incorrectly append an operation/audit fact specifically on the combined malformed-denied branch while returning 403 and all tests pass.
   - The new positive accept form assertion checks its action and successful native submission, but never asserts the `_csrf` field name/value or the full form payload, and does not replay the rendered command. Removing the accept CSRF field while weakening/bypassing enforcement, or changing hidden payload fields, would not necessarily fail this test. The stable contract explicitly preserves CSRF names/values, form fields and replay/no-duplicate outcomes through the updated forms.

### Current complete findings

1. **HIGH — the combined unauthorized-malformed public request is not tied to no-fact evidence, and accept-form preservation remains partial.** Capture the fact fingerprint immediately before and after the denied malformed request; assert the rendered accept form's method, exact action, `_csrf` name/nonempty value and relevant hidden fields; then exercise the applicable repeated submission/replay outcome with before/after operation/event/snapshot facts. If accept is intentionally non-replayable, map the exact immutable repeated outcome and prove it appends nothing; otherwise use the existing replayable rendered command that the contract intends.

2. **MEDIUM — JS-off overview remains unmapped.** Include `/pilot/otiz` in the disabled-JavaScript route matrix and assert its workflow context plus permitted native navigation/forms, not only a generic nonempty body.

Expected-value independence, deterministic setup, isolation/cleanup, effective reduced-motion detection, responsive strategies, edge-state fixtures, and fresh exact-source evidence are otherwise satisfactory. The pre-existing publication-browser 503 remains neither mislabeled GREEN nor independently blocking now that stable publication/domain and positive accept HTTP evidence are mapped; the blockers above concern the current test assertions themselves.

### Required changes for correction round 2

- Close the two current findings and capture a fresh exact-source package before executor dispatch.

---

## Rebuilt complete-matrix review — 2026-09-19

- Reviewer: Codex independent Gate 3 reviewer `/root/issue196_gate3` (gpt-5.6-sol / low); authored neither specification nor tests
- Reviewed source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T210857Z-d18767e2db/snapshot/source.patch`, SHA-256 `f705a06cfe8a7fd1c23cf856ace83afb3b3ae6cf6740bec9b21db449a9489c13`; candidate source `7daf31a0dcf0cbe4095e616e2405751cebb5b0ce6b01beadc9697a850f4cfd4f`; executable source `baaa48e1bcd46f20f56a459fdf85d789232b03dd4af43f1833efb9d3546b4bfd`
- Correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T210857Z-d18767e2db/delta.patch`, SHA-256 `e71d958423d0dacbc3c2f8ae909a8df73ff5fbc56962f0800ffe4ff64da87acb`
- Verification plan: SHA-256 `2fd80fd620a369b4028dd0ada21e176f2b77d0c7e6fc3208fe8819666c7d5073`; `missing_tests=[]`
- Fresh intended RED: `php tests/Yii2/yii2_otiz_shlz_ui_001_test.php`, record `1789765712471921000-064f4e03b4c6413fbac935bab6508a8d.json`, exit 255 at the absent shared authenticated shell
- Fresh exact-source GREEN companions: `yii2_otiz_workflow_001_test.php` (`1789765712468123000-84f8d0f602e24de2a14058178feeb107`), `yii2_otiz_commands_001_test.php` (`1789765712468477000-650c004448ab45de828ef122fc9c9d89`), `snapshot_publication_001_test.php` (`1789765712474154000-aba28e2ce29f4e419b1116838e3feeb5`), `settlement_owner_001_test.php` (`1789765712513916000-3b0631100d574aeda21fdc63be57e3ca`), `settlement_concurrency_001_test.php` (`1789765712507817000-86422b729ed942499f6a4c53574c62f8`), `object_register_paging_http_001_test.php` (`1789765712504513000-e2fbc37bb0844d0988a4d68542eb6ebe`), and `yii2_otiz_settlement_browser_001_test.php` (`1789765712517275000-97a155e7b153424b9b4d5169c9770d8a`)
- Verdict: `APPROVED`

### Remaining finding disposition

1. **Round-2 HIGH: RESOLVED.** The parent fixture now captures the eight-table OTIZ fact fingerprint before revoking permission and compares it after the combined malformed POST and denied GET. The ready-draft form asserts the exact sole input name (`_csrf`), a nonempty current token and exact accept action, then performs the native keyboard submission. A repeated POST with the captured form token must redirect to the immutable outcome, and a second synchronized full fact fingerprint proves the repeat appends or rewrites no snapshot, object, allocation, issue, closure, event, publication or settlement-operation fact.

2. **Round-2 MEDIUM: RESOLVED.** The disabled-JavaScript matrix now includes `/pilot/otiz` and all scoped financial GET screens, asserts route-specific SSR content and native tab navigation on each, and retains snapshot-specific object, complete-payment form and export-link assertions.

### Complete review result

No findings remain. The complete matrix now has traceability to A1–A8, exercises the real authenticated Yii2 HTTP/DOM/browser seam, is sensitive to missing shell/workflow/object/action/table/responsive/zoom/keyboard/coarse-pointer/reduced-motion/JS-off/edge-state behavior, and maps stable publication, settlement, replay, concurrency, denial and paging regressions. Expected values are independently derived from fixed fixture/domain literals; rejection and immutable-repeat paths prove exact outcomes and no-fact behavior; setup uses isolated random databases/private artifacts/loopback services with owned cleanup; and the captured RED fails for the intended missing presentation rather than setup.

The pre-existing unmapped publication-browser `PreopeningFixture` HTTP 503 is not relabeled GREEN. It does not block this Gate 3 package because equivalent stable mapped evidence now covers publication domain invariants, Yii command/route behavior, a positive accept form through the changed presentation seam, repeated immutable no-fact behavior, and settlement browser forms. Gate 5 and exact-source CI remain separate future gates and are not implied by this approval.

### Required changes

None.

---

## Post-Gate-3 root-authored test delta — 2026-09-19

- Reviewer: Codex independent Gate 3 reviewer `/root/issue196_gate3` (gpt-5.6-sol / low); authored none of the reviewed corrections
- Review scope: test-expectation delta only. Production implementation is explicitly excluded and remains Gate 5 work.
- Historical approved source/evidence: rebuilt complete-matrix approval above; intended RED record `1789765712471921000-064f4e03b4c6413fbac935bab6508a8d.json`
- Current exact source: base `af4e2ddb72a194ecfb114820f4134a34b20fdb39` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260918T212952Z-f3ffec83c0/snapshot/source.patch`, SHA-256 `0398fefe69d14d05604413bf876c80413d50fe41e1eff2367498bf3ed3fe2e12`; candidate `eee6f1caa7df7050ad80a03ad22aed132dc54153bafd87f51097f2dd4ff9ba2f`; executable source `8f54889a6493c5de476f88b533c0096ba4ec28085815daab7258e4d9e893c7ea`
- Verification plan: SHA-256 `f9da066d121d933b93bcd1502748847dcbd452add95a57299c8bfbd2feabcfff`; `missing_tests=[]`
- Current mapped GREEN records: target `1789766853881113000-8426f92d90994e889602089897af61ad`; workflow `1789766853819458000-5a8ca0e93d6c441cb8f8b4ed08f231f7`; commands `1789766853883979000-d98557a7964e41d886829787b832b939`; publication `1789766954348320000-130ce15e3ba1413e8eb738b44c646393`; owner `1789766853812085000-9e2ecc3ba32e448f864fea1ae67fd8b1`; concurrency `1789766853839294000-82b5ee49cf04473b8501466a7b2a3f79`; register `1789766853984842000-a13acc382ac1439cac3afb8f32dd51b9`; settlement browser `1789766853888089000-b9b33da2f75d42ec9cc450b0632aeb8e`. Each record is GREEN and binds candidate/end source `eee6f1caa7df7050ad80a03ad22aed132dc54153bafd87f51097f2dd4ff9ba2f` and executable source `8f54889a6493c5de476f88b533c0096ba4ec28085815daab7258e4d9e893c7ea`.
- Delta verdict: `APPROVED`

### Correction assessment

1. **Available total `900,00`: approved.** The fixture independently seeds accrued/fund/remaining `1 000,00` and an existing discipline closure of `100,00`; E1 explicitly defines the available payment as `900,00`. Correcting the header expectation from gross `1 000,00` to available `900,00` increases financial sensitivity and matches the worked example rather than copying an implementation value.

2. **Existing HEAD 404 plus read-only fingerprint: approved.** A8 requires GET/HEAD to remain read-only, not that every GET route has a successful HEAD twin. Asserting the established 404 outcome while retaining the synchronized eight-table before/after fingerprint preserves both route compatibility and no-write sensitivity.

3. **Dynamic hidden-value normalization: approved.** Replacing truncated `outerHTML` strings with ordered structural tuples of tag, name, type, href, owning form action and text removes volatile CSRF/value bytes while continuing to detect changes to content, action inventory and DOM/tab order. It does not weaken the separate exact `_csrf` name/nonempty-value and form-payload assertions.

4. **Incomplete draft outcome and no-fact repeat: approved.** Snapshot 503 uses a synthetic fixed content hash rather than a publication receipt/canonical complete manifest, so the existing contract outcome is `?error=incomplete`, not acceptance. The correction retains the rendered primary accept form, keyboard submission, alert role, exact repeat redirect and synchronized complete fact fingerprint. It therefore tests a legitimate A7 draft/rejection state without inventing acceptance semantics. Positive publication/acceptance, replay and append-only behavior remain independently covered by the mapped publication/domain and settlement suites; the corrected target need not manufacture a valid publication fixture.

5. **One-or-more empty markers: approved.** The contract requires readable empty register/history structure, not exactly one marker across independently empty semantic regions. Requiring at least one route-specific marker remains sensitive to loss of the empty state while allowing multiple correctly scoped messages.

### Complete delta result

No findings. The corrections are independently justified by the stable specification and seeded facts, preserve the public-seam assertions and rejection/no-fact controls, and remove only false assumptions or volatile representation details. The historical RED remains valid evidence that the target originally failed for the missing shell; the current GREEN is implementation-era evidence and does not rewrite that history. This approval is limited to the test delta and does not approve production code, CI, deployment or merge readiness.

### Required changes

None.

## CI regression test delta — 2026-09-19

- Reviewer: independent `/root/issue196_ci_test_delta` (gpt-5.6-sol / low); authored neither production nor tests.
- Source: commit `42cbee4d` plus the bounded delta in `tests/Yii2/yii2_main_navigation_001_test.php` and `tests/Support/yii2_production_web_cutover_contract.php`; source digest `c2bf36dc…`.
- Evidence: focused records `1789812455029602000-a419d1bedbf241ad8da9f52b8055e75e` and `1789812461907868000-2b132bd2199646d9bee678ada6375784` are GREEN.
- Verdict: `APPROVED`; findings: none.

The navigation correction preserves the exact single shared MAIN requirement and now asserts `/pilot/otiz` as the sole current item on all internal OTIZ routes. The asset correction pins the exact SHA-256 of the intentionally changed served `pilot.css`. No expectation was removed without an equivalent accepted contract.
