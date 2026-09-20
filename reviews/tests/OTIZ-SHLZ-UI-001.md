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

---

## Stand-feedback correction Gate 3 — 2026-09-20

- Reviewer: Codex independent Gate 3 reviewer `/root/otiz_gate3_review`; authored neither the specification nor the tests and made no implementation changes.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T112754Z-64fcda4378/snapshot/source.patch`, SHA-256 `de6602b99cb650af087962ebc847c3e09a0f857681e71aed00c2a2712f8872d7`; candidate source `24ee49a73de45f8010649fa80249938ce44a10bc3310067813d9b3cadf79d1fd`; executable source `e1ad8c2a65d51f3521e60676f3f3da110e1f9de63e2d0f9a97db56efcb97eb4f`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T112754Z-64fcda4378/verification-plan.json`, SHA-256 `b6926e2e9ee63c3711462fafad3e2c1fd56a7d860fd28d1416cd291feb3c6ea3`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]` in the prepared package.
- Fresh intended REDs: `yii2_otiz_shlz_ui_001_test.php` record `1789903563352955000-3c72f1eff84b4075bdd0793c59c56938.json` (missing nine-column register); `yii2_shared_pagination_001_test.php` record `1789903573534878000-4743ee27a9354ad79e31e76cdc39f526.json` (missing shared renderer); `object_register_paging_001_test.php` record `1789903578648811000-cddaf980c46846fe85aafd7b3fae10f9.json` (legacy code `86` remains unresolved). All three failures are deterministic, occur after fixture setup, and identify missing target behavior.
- Existing exact-source GREEN evidence mapped in the package: workflow, commands, settlement-browser, snapshot-publication, settlement-owner, settlement-concurrency, and object-register HTTP records. These preserve the established A3–A8 financial/history/authorization baseline, but do not fill the A9–A12 gaps below.
- Verdict: `CHANGES_REQUESTED`.

### Complete findings

1. **HIGH — A10 is not tested across the promised codes and both consumers.** `tests/Otiz/object_register_paging_001_test.php:18-26` exercises only code `86` through `ObjectRegister`, plus one unknown code. It does not exercise the separately specified known codes `112` (`11500bp`) and `123` (`12500bp`), does not drive `MariaDbNativePremiumInputs`, and does not assert canonical display value or provenance from the public legacy reference source. An implementation that hardcodes `86` only inside `ObjectRegister`, never fixes premium inputs, and supplies no reference provenance would pass. Add deterministic public-seam cases for all three proven codes and an unknown code through both register and premium-input reading, with exact independent coefficients/funds, canonical material/provenance, unchanged detail snapshot/business facts, and `missing_norm` for the unknown mapping.

2. **HIGH — A9 does not detect invented zeroes or incorrect row economics.** The browser delta in `tests/Yii2/otiz_shlz_ui_browser.mjs:75-88` asserts the nine headings, page row counts, one global fund total, and a pager transition, but never asserts the nine values of a known row or the rendered unknown-material row. The domain test proves nullable values, yet the current view already demonstrates the risk by formatting `fund_cents ?? 0`; an implementation can keep presenting an unknown norm as `0,00 ₽` and still pass every new assertion. Add authenticated rendered assertions for independently seeded known and unknown rows covering progress, fund, Кшах, earned, paid, penalties, remaining fund, state, and explicit non-zero/non-money unknown presentation. Keep the global summary assertions distinct from row values and prove it remains global under both paging and filtering.

3. **HIGH — A12's renderer and four-consumer contract is substantially unobserved.** `tests/Yii2/yii2_shared_pagination_001_test.php` renders only a middle-page synthetic call and then accepts a consumer when its source merely contains `ViewSupport::pagination`; a comment, dead branch, or unused call satisfies that check. It does not assert one `li` per item, `.shlz-pagination__item` on every destination, non-link disabled directions, ellipsis, public icon usage, summary/page-size context, or first/last states. It also does not render the objects, installers, construction-control, and OTIZ HTTP surfaces to prove their purpose-specific accessible names, preserved surface filters, keyboard destinations, or absence of page-level overflow. No existing GREEN records for those three adjacent non-OTIZ consumers are mapped in this package. Add renderer-level first/middle/last and compact-range cases for the complete markup/state/query contract, plus public HTTP/DOM (and bounded browser geometry/keyboard where required) assertions for all four actual consumers. Map their existing focused regressions so the shared replacement cannot silently change their read/filter behavior.

### Review assessment

The specifications clearly identify the public seams, sensitive financial/legacy semantics, no-write boundary, and independently derived numeric examples. The captured REDs fail for intended missing behavior and the fixtures are isolated. A11's 51-row paging boundary and the existing object-register HTTP evidence are credible. Gate 3 nevertheless cannot approve implementation because the current tests permit foreseeable partial implementations of every newly sensitive A10/A12 boundary and omit A9's explicit unknown-value presentation rule.

### Required changes

- Close all three findings with root-authored assertions and fresh exact-source intended-RED evidence.
- Regenerate the verification plan/package after the test delta and return the complete candidate for independent Gate 3 review before executor implementation.

---

## Stand-feedback correction Gate 3, round 2 — 2026-09-20

- Reviewer: Codex independent Gate 3 reviewer `/root/otiz_gate3_review`; authored neither specification nor tests and made no implementation changes.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T113459Z-b0cd442a56/snapshot/source.patch`, SHA-256 `f7529a1af19e98e874b6e57fe2c75b9e9081fb2436d4593f49ff47c3e19dd125`; candidate source `d9ec5766fd4889d8b23c71a1c9e439db331aa70d80191ab2e1658bf6defd7eb7`.
- Verification plan: SHA-256 `b02b14e701deb2a13e71fd9b7e7d4a500d7f4e118ba6d9907a6f052d449a49ef`; lane `CRITICAL`; required reviews `gate3`, `final`; prepared package reports no missing tests.
- Fresh intended RED records: OTIZ browser `1789903962505812000-b7b36bfa53364a89a9d14da0c3b9b336`; shared renderer `1789903971670344000-0cecd52016f84f25a9d52180a2370d31`; register legacy mapping `1789903976222209000-4c9fa7128f4746f086ea166f10defe22`; premium-input legacy mapping `1789903983144623000-85e3a2858c5e4fa3a56c76b0ddf83778`; object queue `1789904001368920000-91d634335fde4d28b9786a1196e0ceb7`; installer directory `1789904013473827000-27c490fc270a4112879309a7c1995df4`; construction control `1789904021175251000-f5dbc5d1827b450e9a3553a965fbd026`. Each is source-bound and fails at the intended absent behavior after successful setup.
- Verdict: `CHANGES_REQUESTED`.

### Prior finding disposition

1. **Prior A10 finding: MOSTLY RESOLVED; snapshot-immutability sensitivity remains.** The correction now covers `86`, `112`, `123`, and unknown `999` through both `ObjectRegister` and `MariaDbNativePremiumInputs`, with independently stated coefficient/fund expectations and exact original-card locator/content hash for premium operands. The unknown premium input is blocked with `SHAFT_COEFFICIENT_UNRESOLVED`. However, the numeric-card loops update or restore the detail row between reads and never compare the detail/business-fact fingerprint immediately before and after each numeric-code read. A reader that resolves the coefficient correctly while rewriting the immutable detail snapshot to a canonical display value would pass: its mutation is overwritten by the next fixture update or final restore before the later generic read-only assertion.

2. **Prior A9 finding: PARTIALLY RESOLVED.** The correction adds a known row, an unknown row, explicit unknown fund `—`/not `0,00 ₽`, correct global summary, second-page cardinality, and summary stability under filtering. But the known row assertion searches the row's combined text for a set of literals. It does not bind values to their nine labelled cells, does not require the correct multiplicity of repeated values such as `0,00 ₽`, and contains no distinct assertion for every financial column. An implementation can swap fund/remaining, put the single zero in the wrong paid/penalty cell, or omit/misrender one of earned/paid/held while all current text-presence checks pass.

3. **Prior A12 finding: RESOLVED.** The renderer test now covers middle/first/last states, compact ellipsis, public item classes, one-child list items, current link, non-link disabled directions, public icons, visible range summary, no buttons, and query preservation. Actual HTTP tests bind the shared markup and purpose-specific labels for objects, installers, and construction control; the OTIZ browser binds the fourth consumer and keyboard page navigation. Existing consumer assertions retain their filter/query and row behavior, and the fresh REDs prove each legacy local composition is reached rather than accepting source inspection alone.

### Current complete findings

1. **HIGH — A9 known-row economics are not column-bound.** Parse the known row by `td[data-label]` (or an equivalent accessible table mapping) and assert the exact expected value for each of Progress, Fund, Кшах, Earned, Paid, Held, Remaining, and State. Assert repeated zero/unknown values in their own cells rather than once in aggregate row text. Keep the current unknown-fund and global-summary checks.

2. **HIGH — A10 does not prove numeric legacy resolution leaves the detail snapshot unchanged.** For both public consumers, capture the exact detail row/content hash and relevant business-fact fingerprint immediately after seeding each numeric code and compare immediately after the read, including the unknown case. This must fail an implementation that writes a canonical label or any derived value back into `fm2_pilot_object_details` while otherwise returning correct operands.

### Review assessment

The correction materially improves traceability, expected-value independence, real-consumer coverage, and RED sensitivity. A12 is now complete for Gate 3, and the known/unknown legacy coefficient matrix reaches both required readers. Approval is still blocked because the two explicit sensitive promises—correct per-column money presentation and no rewrite of imported detail facts—can regress while the tests remain green.

### Required changes

- Close the two remaining findings with root-authored assertions and fresh exact-source evidence.
- Regenerate the reviewer package and return the complete candidate for another bounded Gate 3 round before implementation.

---

## Stand-feedback correction Gate 3, round 3 — 2026-09-20

- Reviewer: Codex independent Gate 3 reviewer `/root/otiz_gate3_review`; authored neither specification nor tests and made no implementation changes.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T113938Z-95e43a35da/snapshot/source.patch`, SHA-256 `c7577a0ad26d75d01cfde6c3ef0cd6fd7629c49290e59757dd585d4e475fb036`; candidate source `018fcfb0a8ca6167b7ab01ccbee9409e9b07591c5a105f0eed04802d0623dadf`; executable source `b6d21b138436b463dff2100d2b5375ede56e3fea9f1714229139a32b87a6c44d`.
- Verification plan: SHA-256 `4e622ac509c100e0ae14ccf9f73e3b664952fb8a2c2b6ec326f603956ccdd0c9`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]`.
- Fresh exact-source evidence: seven intended REDs for the OTIZ browser, shared renderer, both legacy-material readers, and the three adjacent pagination consumers (records `1789904238572506000-6b7421056a5d4375a5dfc7d3f41e043c`, `1789904247724007000-06a0c4d298cf494caeecfba39d9c36dd`, `1789904252593508000-9d14c5ff6ab94e0d89944d79c00997a7`, `1789904267970625000-1ef35f28d072451f9581a4c586f9560d`, `1789904277496312000-4489e28c436f4b5fbf8a07d59b53363e`, `1789904288100058000-19d8b2c21b60406b852d87ccf7e53c09`, `1789904303948629000-f9d04062effa4c0699ee55ee77f44ed1`). Seven mapped A3–A8 regressions remain GREEN on the same source.
- Verdict: `APPROVED`.

### Remaining-finding disposition

1. **Round-2 A9 finding: RESOLVED.** The browser test now selects every known-row cell by its exact `data-label` and independently asserts Object, Progress, Fund (including base), Кшах, Earned, Paid, Held, Remaining, and State. Repeated zero values are checked in their own cells, so swapped, omitted, or duplicated financial presentation fails. The unknown row separately requires `missing_norm` wording and a Fund cell containing `—` but not `0,00 ₽`; page cardinality and global summary stability across paging/filtering remain covered.

2. **Round-2 A10 finding: RESOLVED.** For codes `86`, `112`, `123`, and unknown `999`, both `ObjectRegister` and `MariaDbNativePremiumInputs` now capture a complete fact fingerprint immediately after fixture seeding and compare it immediately after the public read. The premium-input cases retain exact card locator/content-hash provenance. A resolver that rewrites the detail payload/hash, records a derived fact, or otherwise mutates any fixture table will fail before the next fixture update can conceal it.

3. **Round-2 A12 resolution: RETAINED.** No weakening is present. Renderer first/middle/last states, compact structure, icons, destinations, disabled states, summaries and query preservation remain asserted, as do the four rendered Yii consumers and their purpose-specific labels/filter behavior.

### Complete review result

No findings remain. The complete A9–A12 matrix now exercises the authenticated rendered register, both public legacy-material readers, the reusable renderer, and all four pageable Yii consumers. Expected monetary/coefficient values are independently fixed by the contract and legacy oracle; known and unknown presentation is column-sensitive; legacy reads prove exact provenance and no-write behavior; server paging/filter context and global totals are deterministic; and every fresh RED fails at the intended missing behavior rather than fixture setup.

This approval authorizes Gate 4 implementation against the reviewed tests only. It does not approve production code, Gate 5, CI, deployment, publication, or merge readiness.

### Required changes

None.

---

## Post-Gate-5 row-balance test delta — 2026-09-20

- Reviewer: Codex independent Gate 3 reviewer `/root/otiz_gate3_review`; authored neither implementation nor the reviewed test delta.
- Review scope: only the new sensitive row `remaining_fund` expectations. Production code and the prior Gate 5 verdict are not approved by this round.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T121007Z-58c4150e83/snapshot/source.patch`, SHA-256 `271ab4798f10612ebe72860ab7cf664c14acb495c4288bc39eb7849086224cd5`; candidate source `57247157fa978533b94a202f2b66c260029128c3c68dab84c6800c97130cb768`.
- Verification plan: SHA-256 `3705c3b2ab874f9caca5c79cf4f58d9971ae47a215ba50f9e13e07baaf9778a4`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]`.
- Fresh evidence: `yii2_otiz_shlz_ui_001_test.php` record `1789906043375062000-5a504e4de18943b0a8127b7501f3d7b4` is intended RED only at actual `597 999,70 ₽` versus expected `597 999,40 ₽`; `object_register_paging_001_test.php` record `1789906051890057000-01fd2e79916140178034e38baeac5696` is intended RED only at actual `51,999,970` versus expected `51,999,940`. The shared renderer, premium inputs, all three adjacent pagination consumers, and seven mapped A3–A8 regressions are GREEN on the same source.
- Verdict: `APPROVED`.

### Delta assessment

1. **Domain row expectation is independently correct.** The fixture's object fund is `52,000,000` cents, paid is `0`, discipline is `10`, stored deadline is `20`, and the saved formula trace independently yields deadline penalty `350 - 300 = 50`. The stable contract explicitly characterizes row balance as `max(0, fund - paid - discipline - trace_deadline)`, so the expected row value is `52,000,000 - 0 - 10 - 50 = 51,999,940` cents. The prior `51,999,970` behavior incorrectly used the stored deadline.

2. **Authenticated rendered expectation is the same contract, not a new formula.** The browser fixture has fund `59,800,000` cents, paid `0`, discipline `10`, and trace deadline `150 - 100 = 50`. Therefore held is `0.60 ₽` and remaining fund is `597,999.40 ₽`. Both values are bound to their exact `data-label` cells, so the test distinguishes the trace-derived row balance from the stored-deadline result `597,999.70 ₽`.

3. **Global summary characterization remains unchanged.** `OTIZ-OBJECT-REGISTER-PAGING-001` deliberately keeps global balance on stored deadline while row balance uses trace deadline. The existing summary assertions execute and pass before the new intended-RED assertion: global penalties remain discipline plus trace deadline, while global balance remains the per-object clamp using stored deadline. No summary expectation was edited, removed, or reinterpreted.

### Complete delta result

No findings. The two assertions expose one consistent implementation defect at the public domain and authenticated-rendered seams, use deterministic fixture arithmetic, and preserve the established global-summary distinction. The intended REDs fail for that exact mismatch rather than setup or an unrelated regression.

This approval applies only to the root-authored test delta. Production correction requires a fresh GREEN candidate and a new independent final/Gate 5 review; prior final approval cannot cover the post-review behavioral correction.

### Required changes

None to the test delta.

---

## Owner-confirmed material dictionary and table visual Gate 3 — 2026-09-20

- Reviewer: Codex independent Gate 3 reviewer `/root/otiz_gate3_review`; authored neither specification nor tests and made no implementation changes.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T123723Z-825aee4411/snapshot/source.patch`, SHA-256 `ebaa5de6fc8ba18f4900b862f6ffb5d19c19db8796868771f492d885ee686f90`; candidate source `f4736922aec964783a134492b5b36116fde61f03376e1e4d50ce08089706b873`.
- Verification plan: SHA-256 `b7ffd6e77c87d0dfce639ff768b7c2f5f76a1bd5932f1e446ccfe9a5491f52a5`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]`.
- Source oracle: owner-confirmed read-only `fm_fields_values(field_id=13)` dictionary: `41` = «железобетон» (`10000bp`), `72` = «Железобетон и металл» (`10000bp`), `86` = «кирпич» (`11500bp`), `112` = «кирпич и металл» (`11500bp`), `123` = «Металлокаркас и сетка» (`12500bp`). The existing `Integration.php:1612–1622` coefficient branches independently corroborate `86`, `112`, and `123`.
- Fresh evidence: intended RED browser record `1789907684570411000-1896bc104769477dba443581ba00b99d`, register record `1789907692902292000-e88c21f9737044b29bb5f0c928df65e6`, and premium-input record `1789907699884597000-43d6f87cf45946f080962e580335e353`. Shared pagination and its adjacent consumers plus the mapped A3–A8 regression set are GREEN on the same source.
- Verdict: `CHANGES_REQUESTED`.

### Complete findings

1. **HIGH — code `72` is absent from the premium-input reader matrix.** `ObjectRegister` exercises all five confirmed codes, unknown `999`, exact coefficients/funds, and immediate no-write fingerprints. `MariaDbNativePremiumInputs` uses `41` as its initial fixture and loops over `86`, `112`, and `123`, but never presents code `72` to the reader. An implementation that maps `41` correctly and omits or mis-maps `72` only in premium inputs passes. Add `72 → 10000bp` to the premium-input public-reader cases with exact card provenance and an immediate full-fact equality check, matching the protections already applied to the other numeric codes.

2. **HIGH — A10a does not assert numeric classes on body values.** The browser test proves `thead.shlz-table__head`, nine scoped header cells, seven numeric header classes, 50 `tr.shlz-table__row`, 450 generic body cells, and white page/wrapper surfaces. The normative contract also requires numeric *values* to use `shlz-table__cell--numeric`, but no assertion counts or identifies numeric `td` cells. Production could align only the headers and omit numeric alignment from every value while the test remains green. Assert the seven exact numeric `td[data-label]` cells per rendered row use `shlz-table__cell--numeric` (350 on the first 50-row page), and assert Object and State do not receive the numeric modifier.

### Review assessment

The owner-confirmed dictionary is now stated precisely in both stable contracts. The register test is deterministic and complete for all five codes plus unknown/no-write behavior. The presentation test correctly reaches the authenticated 320px browser seam and sensitively checks public table structure and computed white surfaces. The two omissions above are bounded but directly permit partial implementations of explicit sensitive acceptance statements, so Gate 3 cannot yet authorize implementation.

### Required changes

- Add the missing premium-input `72` case and body numeric-cell assertions.
- Capture fresh exact-source RED evidence and prepare the complete candidate for independent re-review.

---

## Owner-confirmed material dictionary and table visual Gate 3, correction — 2026-09-20

- Reviewer: Codex independent Gate 3 reviewer `/root/otiz_gate3_review`; authored neither specification nor tests and made no implementation changes.
- Reviewed source: base `7784d3f800dfe53fecf9fb3ee8a8bce7daa31192` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T124203Z-79836a132c/snapshot/source.patch`, SHA-256 `4600b90214987330b61f09fdfde66c7faf79505dd4c1ac0b52cd4f12fd3c1b47`; candidate source `80b8bef70b75c88f0ed3494bdbe13b91be19f00791f15f828c11b219f88ec8fd`; executable source `59fe7cfa6dabc7a1ef130e73a3e825de8157e25d19df1ba92f2a177bc233a3fb`.
- Verification plan: SHA-256 `aab717a434a04deace1ab61b9d8b64d2ac18615cbbed17ff8ac142105f9ee801`; lane `CRITICAL`; required reviews `gate3`, `final`; `missing_tests=[]`.
- Fresh intended REDs: browser `1789907952262184000-c7f2d437c24e4c049fdf144b42cd363e` at the absent public shlz table head; register `1789907960491436000-bbbef0390692479cb1f222258e604735` at unresolved code `41`; premium inputs `1789907983287257000-b03b72351bc443f48ee67ee90c33ff5a` at unresolved initial code `41`. Shared pagination, all three adjacent pagination consumers, and seven mapped A3–A8 regressions remain GREEN on the exact source.
- Verdict: `APPROVED`.

### Prior finding disposition

1. **Missing premium-input code `72`: RESOLVED.** The public premium reader now receives an explicit code `72` card with the owner-confirmed canonical name «Железобетон и металл», requires `10000bp` in both the row and operand, binds provenance to the exact detail locator and content hash, and compares the complete fact fingerprint immediately before/after the read. The initial code `41`, looped `86`/`112`/`123`, and unknown `999` cases retain their coefficient, provenance/no-write, and refusal coverage. Although the current RED stops first at unresolved `41`, the deterministic sequential assertions ensure `72` becomes the next independently sensitive case once preceding mappings exist.

2. **Missing numeric body-cell contract: RESOLVED.** On the first 50-row rendered page the browser now requires exactly 450 public body cells, exactly 350 numeric-modifier cells (seven per row), and zero numeric modifiers on the Object and State cells. Together with the retained seven numeric scoped headers, row classes, computed white page/wrapper surfaces, and exact per-column value assertions, this detects header-only or overbroad numeric styling.

### Complete review result

No findings remain. The expanded five-code owner-confirmed dictionary is covered across both readers with unknown/no-write safeguards, and the public table/white-surface contract is observable at the authenticated browser seam. The expected values come from the confirmed legacy dictionary and stable premium norm, the checks are deterministic, and the fresh REDs fail for missing target behavior rather than setup.

This approval authorizes implementation against the reviewed test candidate only. It does not approve production code, final/Gate 5 review, CI, deployment, publication, or merge readiness.

### Required changes

None.
