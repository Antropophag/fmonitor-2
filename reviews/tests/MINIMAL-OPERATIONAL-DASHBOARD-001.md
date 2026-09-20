# Gate 3 review — MINIMAL-OPERATIONAL-DASHBOARD-001

- Verdict: **CHANGES_REQUESTED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`
- Reviewed exact source: `b595eb0a8a7d93721a95bdff22d01e5aa05b70978578571c3398d4f9925c61fc`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T210513Z-5949b6e212/package.json`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789938305475190000-7f400422003c40f0b36caa0dfa3a60e6.json`
- RED test blob: `02a5eff81f0a111f53feb4cb100585ce5530d259d3a6220900598f4ce6c5f510`
- RED outcome inspected: `INTENDED_RED`, exit `255`, at the absent public owner `YiiOperationalDashboard`; this is an intended missing-production-behavior failure, not an environment/setup failure.

## Findings

1. **[BLOCKING] Acceptance L is asserted in prose but has no executable witness.** The test creates only a small fixture. It does not create or otherwise exercise 30,000 accessible objects, measure constant query count, prove aggregate correctness at that scale, bound materialized list rows to ten at the public read seam, or check that the HTML contains no hidden full dataset. This leaves the central bounded-read requirement and the design's performance risk untested.

2. **[BLOCKING] Acceptance K is incomplete.** The test fingerprints facts around one authorized GET and one subsequent HEAD, but does not perform a repeated GET, compare response/data determinism, or issue concurrent GET/HEAD reads. Therefore replay and concurrency behavior required by the normative spec and OpenSpec scenario are not covered.

3. **[BLOCKING] Acceptance F (canonical status parity) is absent.** The fixture checks one `working` case and one completed case but never enumerates all canonical queue states or compares dashboard classification with the existing object-queue projection. A duplicated/divergent status expression could pass this test, despite the explicitly identified design risk.

4. **[BLOCKING] Authentication/no-disclosure coverage is incomplete.** The anonymous assertion checks only `303` and `/pilot/login`; it does not prove that `/pilot/dashboard` is preserved as the safe return path. The forbidden response checks two body strings but does not prove the Dashboard navigation item is absent. Thus B and C are only partially traced.

5. **[BLOCKING] Public navigation seams are under-tested.** The test does not verify exact card links for every rendered upcoming/overdue row, does not activate a row and prove it reaches the matching existing object card under that card's server-side authorization, and does not establish a link for each of the four metrics. Merely finding the generic registry and active-filter hrefs cannot cover the full H/list-navigation contract.

6. **[BLOCKING] `shlz-ui` provenance and public-export constraints are not proved.** The static check only searches for two class-name substrings in the view and local CSS. It provides no source path/version/hash witness against the public `../shlz-ui` export, does not check status/link/button/empty-state contracts, and cannot detect copied private exports or private imports. This does not satisfy M or task 3.1.

7. **[BLOCKING] The real-browser witness covers only the successful populated page and weak geometry.** It checks overall document width, counts, and a one-column computed grid at 1440/390, but does not exercise empty/error states at 390, verify the heading/cutoff/formula text/list identities and links remain visible and operable, or detect clipped/overlapping content inside containers. The required narrow-screen semantic coverage is incomplete.

8. **[MAJOR] The test is not sensitive to several required formula/link regressions.** It checks the combined `11/2/1/8` result, but does not independently exercise documentary-closeout as active, unknown finish exclusion from overdue, exact overdue strict-before cutoff, or all four metric explanatory texts and destinations. The normative worked example `5/2/1/1` is not constructed as such. A number of plausible off-by-one/status/formula defects could remain masked by the mixed fixture.

9. **[MAJOR] Cutoff determinism is coupled to wall-clock time.** The test derives `today` itself instead of fixing the request cutoff through the planned public clock/factory seam, and it does not assert the exact displayed Moscow date. A midnight boundary between fixture setup and request handling can make the test flaky and it does not prove one cutoff is captured once per request.

10. **[MAJOR] Error and empty assertions do not cover the whole contract.** Empty verifies only `total=0`, not all four zero metrics and the absence of fabricated rows. Failure verifies a few strings, but not absence of every metric/list/object identity or a unified state in browser output. Partial-number regressions can pass.

11. **[MAJOR] Verification input collapses A–N into one coarse acceptance ID.** `A-N-complete-dashboard-slice` maps one test path but does not provide per-acceptance traceability or identify the browser helper as an executable dependency. Given the uncovered axes above, the manifest overstates complete A–N coverage and does not let the planner distinguish required witnesses.

12. **[MINOR] The browser helper accepts screenshots as evidence without checking their existence/metadata in its result.** It writes only `{ok:true}`; viewport-specific geometry and screenshot paths are not retained in the JSON evidence, weakening later independent review and exact reconstruction.

## Gate decision

The scope and normative specification are coherent, remain read-only, use the correct `objects.read` public seam, define deterministic ordering/formulas, preserve the landing route, and prohibit new state/DDL/dependencies. The recorded RED is correctly classified as intended missing behavior. However, the executable contract does not substantiate its claimed A–N coverage on the highest-risk boundaries (canonical parity, 30k bounded queries/materialization, replay/concurrency, auth return/no-disclosure, public `shlz-ui` provenance, and complete 1440/390 behavior). Gate 3 is therefore **CHANGES_REQUESTED**; executor implementation must not begin from this test package.

---

# Gate 3 rereview after correction — 2026-09-21

- Verdict: **CHANGES_REQUESTED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`
- Reviewed exact source: `017eb8904bcc62d7c9d6799139019ee7075028fae3fad53bb7f4833a1ed12db7`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T210846Z-6827224ee6/package.json`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789938505307488000-5d2257971f5245858f4dc49aa7cf4c37.json`
- RED test blob: `c459dfdc7ca1ab31eeab05afb7f7254851caf7e8afe92bb0ff388c50b1956029`
- RED outcome inspected: `INTENDED_RED`, exit `255`, at the absent public owner `YiiOperationalDashboard`; still an intended missing-production-behavior failure, not setup failure.

## Complete current findings list

1. **[BLOCKING] Acceptance K still lacks concurrent-read coverage.** The correction adds two sequential owner reads and compares them, resolving the repeat/determinism portion, but it never issues simultaneous GET/HEAD or owner reads. It therefore cannot detect connection-sharing, transaction, temporary-state, or read-side race defects required by the explicit concurrent-read contract. Fact fingerprinting also remains limited to the sequential HTTP calls.

2. **[BLOCKING] Acceptance F (canonical status parity) remains absent.** No correction enumerates all canonical queue states or compares dashboard `active`/completed semantics with the existing queue projection. The fixtures still cover only a `working` case plus one completion path, so a divergent duplicated classifier remains able to pass.

3. **[BLOCKING] Acceptance L remains incomplete despite the new 30k owner-read probe.** The correction now proves a full aggregate count, `<=5` observed queries, a coarse memory delta, and `<=10` returned rows. It does not request/render the dashboard after adding 30,000 rows and therefore does not prove that public HTML/DOM omits a hidden full dataset. It also asserts only `total` at scale rather than independently checking all aggregate values. The memory delta is measured on a warmed PHP process and is only an indirect materialization signal, not the specified public-seam/HTML witness.

4. **[BLOCKING] Public navigation remains only partially tested.** Exact hrefs are now checked for rows that happen to render, resolving the static row-link portion. The test still never activates a row, proves that the exact object card opens, or proves the card's ordinary server-side authorization remains effective. It also still checks destinations for only `total` and `active`, while the normative spec says each of the four metrics has a link.

5. **[BLOCKING] `shlz-ui` provenance remains incomplete.** The correction pins/hash-checks `reporting-dashboard.css` and checks that its bytes appear in the local asset, which resolves Dashboard/Chart Widget provenance. Status/link/button/empty-state are still accepted by mere class-name substring presence in both files; there is no public upstream source/version/hash witness for those contracts and no check excluding private exports/imports. The complete M/task 3.1 claim is therefore still unsupported.

6. **[BLOCKING] The browser helper is unchanged and still provides insufficient 1440/390 evidence.** It exercises only the populated success state and checks document width, item counts, and grid-column count. It does not exercise empty/error states at 390, verify cutoff/formula/list text and exact links remain visible/operable, or detect clipping/overlap inside containers. Its result remains only `{ok:true}` without viewport geometry or screenshot paths/hashes.

7. **[MAJOR] Formula sensitivity remains insufficient.** The correction does not add documentary-closeout as a distinct active witness, an unknown-finish overdue exclusion, an object finishing exactly on cutoff to prove strict-before semantics, the normative isolated `5/2/1/1` example, or assertions for all four explanatory texts/destinations. The combined `11/2/1/8` fixture can still mask plausible classification/formula regressions.

8. **[MAJOR] Cutoff determinism remains wall-clock coupled.** Although the direct owner is constructed with a fixed closure after the HTTP response, the HTTP path itself is still seeded from `today`, the displayed Moscow cutoff value is not asserted, and no injected HTTP clock proves that one cutoff is captured once. A midnight transition can make setup and request disagree.

9. **[MAJOR] Empty/error completeness remains unchanged.** Empty checks only `total=0` plus copy, not all four zeros and absence of rows. Failure checks three forbidden strings, not the absence of every metric/list/object identity or the unified rendered/browser state. Partial-data regressions can still pass.

10. **[MAJOR] Verification-input traceability remains unchanged and overstated.** It still collapses A–N into `A-N-complete-dashboard-slice`, omits the browser helper from `tests`, and gives the planner no per-acceptance mapping. That is especially material while F, K, L, M and navigation witnesses remain incomplete.

11. **[RESOLVED] Anonymous return-path and forbidden-nav assertions were added.** The correction now attempts the saved `/pilot/dashboard` return and explicitly rejects `href="/pilot/dashboard"` in the forbidden response. These close the earlier B/C assertion omissions, subject to eventual GREEN execution.

12. **[RESOLVED] Sequential repeat and basic 30k/query/row-bound witnesses were added.** These are useful partial corrections but do not close findings 1 and 3 above.

## Rereview decision

The correction materially improves safe-return/no-disclosure assertions, exact static row hrefs, sequential replay, large-fixture aggregate/query/row bounds, and Dashboard CSS provenance. The new RED remains correctly attributable to missing production behavior. Seven unresolved blocking/major test-design gaps still prevent the executable contract from supporting the claimed complete A–N matrix. Gate 3 remains **CHANGES_REQUESTED** and production implementation must remain paused.

---

# Gate 3 third review after complete correction — 2026-09-21

- Verdict: **CHANGES_REQUESTED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`
- Reviewed exact source: `595d5474be127f7addf16dae6f2d2a5db39d99573367343363ef437208f8b538`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T211449Z-158925e18c/package.json`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789938880972807000-4649e07bafac47bebe0a9d8eb9a58b8a.json`
- RED test blob: `48efd59d10397bbca832af885846d99d838f2d25a046ea734694e4d4fb06cbd6`
- RED outcome inspected: `INTENDED_RED`, exit `255`, at the absent public owner `YiiOperationalDashboard`; the observed RED remains an intended missing-production-behavior failure rather than setup failure.

## Complete current findings list

1. **[BLOCKING] The corrected test will produce a false RED after production exists because its fact fingerprint crosses author-owned fixture mutations.** `$before=$f->facts()` is captured before the first HTTP request. Later the test inserts six boundary objects and their detail rows, but after that it asserts `assertSameValue($before,$f->facts(),'GET HEAD repeat no facts')`. Those six deliberate writes make equality impossible regardless of dashboard behavior. This violates the required distinction between intended RED and test/setup failure and prevents a trustworthy GREEN transition. Capture a fresh fingerprint immediately around each read-only request group, or exclude only explicitly identified fixture setup from the comparison.

2. **[BLOCKING] Acceptance F still does not prove parity across all canonical queue statuses.** The test now compares the dashboard active total with the queue's `installation + document_closeout` filters and confirms one completed row. This is valuable, but it does not construct/compare `needs_assignment_order`, `ready_to_open`, and `needs_assignment_change` (nor all queue classification branches) to prove they are classified consistently and excluded/included according to the dashboard semantics. The normative acceptance explicitly says all canonical queue statuses.

3. **[MAJOR] Cutoff determinism remains coupled to the wall clock on the public HTTP seam.** Fixtures and expectations use `new DateTimeImmutable('today', Europe/Moscow)`, the displayed exact cutoff is never asserted, and the HTTP factory clock is not controlled. The direct owner clock closure does not control the controller requests. A Moscow-midnight transition can make fixture dates and request cutoff disagree, and the test does not prove the controller captures one date once per request.

4. **[MAJOR] Formula sensitivity still misses the strict overdue cutoff boundary.** The third correction provides the normative `5/2/1/1`, documentary-closeout, completed exclusion, unknown dates, `+13/+14`, and all metric destinations. It still has no unfinished object whose planned finish equals cutoff, so an implementation using `<= cutoff` instead of `< cutoff` can pass. This is a direct stated formula boundary.

5. **[MAJOR] Verification input remains a coarse and incomplete traceability claim.** It still maps all A–N to one `A-N-complete-dashboard-slice` entry and lists only the PHP wrapper, not the browser helper whose populated/empty/error 1440/390 behavior is essential evidence. Per-acceptance mapping is needed so the planner and later reviewer can distinguish exact witnesses and avoid calling missing coverage complete.

6. **[MINOR] The large-HTML hidden-dataset assertion is narrower than its claim.** It rejects only the last synthetic label (`Scale 29999`) plus counts `data-dashboard-object` markers. A hidden serialization using another attribute/script representation, or a truncated hidden subset not containing that last label, could pass. A bounded response-size/absence-of-scale-prefix or parsed-DOM/script-data assertion would directly prove no hidden dataset. This is not independently blocking because owner memory, query count, returned-row bound, HTTP marker count, and 30k aggregate assertions now provide substantial L coverage.

7. **[RESOLVED] Concurrent/replay/read-only coverage is now present.** Two simultaneous curl GET/HEAD requests are joined, the GET body is compared with the prior stable response, failures are rejected, and the fact fingerprint is compared around the concurrent group. Sequential owner equality and ordinary HEAD are also covered. The separate stale fingerprint bug is finding 1.

8. **[RESOLVED] The normative formula example and most boundary sensitivity are now present.** The isolated first response asserts `5/2/1/1`; documentary closeout, completion, unknown dates, `+13/+14`, stable top-five, full totals, and all four metric destinations are exercised. Only the strict overdue equality boundary remains in finding 4.

9. **[RESOLVED] 30k owner and public HTTP coverage is materially complete.** All four aggregate values, bounded query count, memory delta, returned rows, HTTP status, marker count, and a hidden-data sentinel are asserted. Finding 6 records the remaining defense-in-depth weakness.

10. **[RESOLVED] Exact row links and existing-card authorization are covered.** Every rendered row gets an exact href check, the matching authorized card returns 200, and the same card returns 403 for the unprivileged actor.

11. **[RESOLVED] Five public `shlz-ui` exports and private/dependency exclusions are covered.** Dashboard/Chart Widget, status badge, link, button, and empty-state upstream files have fixed hashes and byte inclusion checks; view/local CSS are screened for private path/import and alternative chart dependencies.

12. **[RESOLVED] The browser matrix now covers populated, empty, and error states at 1440 and 390.** It checks status, overflow/clipping, state semantics, visible/focusable exact row link, and retains viewport geometry plus screenshot path/hash/size for independent evidence.

## Third-review decision

The third correction closes most substantive gaps from the first two reviews and the recorded RED is still correctly located at missing production behavior. Gate 3 nevertheless remains **CHANGES_REQUESTED** because the test currently contains a guaranteed post-implementation false failure from its stale fingerprint, and canonical parity is still narrower than the explicit all-status acceptance. The public-cutoff and strict-overdue-boundary gaps should be corrected in the same test revision before executor handoff.

---

# Gate 3 fourth review — 2026-09-21

- Verdict: **APPROVED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`
- Reviewed exact source: `10e430179989efa220f2e2ba330cb6baf03da470ded59f5613d88107cb078acb`
- Role package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260920T211805Z-1e0dfb90e8/package.json`
- RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789939077594772000-f3a19131dd284584a3b6892ce3b7f8a9.json`
- RED test blob: `3f96040da5f43c7a7ae4d64084e6032042d55e22884d4ff0a38dc6eeb18a1ce9`
- Browser-helper blob: `79d2031e31024bc92cc63b91abfbf0acda518e1903727ed55a33753acfb22701`
- RED outcome inspected: `INTENDED_RED`, exit `255`, at the absent public owner `YiiOperationalDashboard`; this is the intended missing-production-behavior boundary and not a setup failure.

## Complete findings list

No unresolved findings.

1. **[RESOLVED] Read-only fingerprints are now scoped around the operations they judge.** The stale pre-fixture fingerprint was replaced with a fresh snapshot immediately before repeat GET/HEAD. Anonymous, authorized sequential, and concurrent reads each have an appropriate before/after fact comparison; author-owned fixture writes no longer create a guaranteed false RED.

2. **[RESOLVED] The public HTTP cutoff is fixed and observable.** `UserAccessFixture::start` accepts bounded extra environment, the server is started with `FMONITOR_NOW=2026-09-20T12:00:00+03:00`, fixtures use the same Moscow date, and the response must display `20.09.2026`. The test no longer depends on wall-clock midnight.

3. **[RESOLVED] Formula sensitivity includes the normative example and material boundaries.** The first response independently asserts `5/2/1/1`; fixtures cover documentary closeout, completed exclusion, unknown dates, cutoff, `+13`, `+14`, and an unfinished object finishing exactly on cutoff, which must not increase overdue. All four metric destinations are exact.

4. **[RESOLVED] Canonical queue parity covers every supported status filter and active overlap.** Fixtures make `needs_assignment_order`, `ready_to_open`, `installation`, `document_closeout`, `completed`, and `needs_assignment_change` non-empty. Dashboard active is compared with the canonical `installation + document_closeout` totals before and after the overlapping `needs_assignment_change` case, while completed/overdue exclusion is independently witnessed.

5. **[RESOLVED] Stable bounded lists and navigation are sensitive.** More than five equal-date rows prove top-five ordering and full aggregate retention; every rendered row has an exact object-card href; the authorized card returns 200 and the same card returns 403 to the unprivileged actor; dashboard navigation order and all metric links are asserted.

6. **[RESOLVED] Replay/concurrency are executable.** Sequential owner reads and HTTP responses are compared for equality. Concurrent authenticated GET/HEAD processes are joined, failures are rejected, GET content is deterministic, and fact fingerprints remain unchanged.

7. **[RESOLVED] The 30k path is covered through owner and public HTTP seams.** All aggregate values, bounded query count, bounded memory delta, at most ten returned rows, public response status, at most ten rendered row markers, and absence of the final scale sentinel are checked. Together these provide a sufficiently sensitive no-full-materialization witness.

8. **[RESOLVED] Authentication and disclosure behavior are covered.** Anonymous redirect and saved safe return path are asserted; forbidden access returns 403 without metric/object/navigation disclosure; permitted navigation order remains stable; root landing behavior remains a stated non-goal and is not changed by the test package.

9. **[RESOLVED] Empty and failure states are complete at HTTP and browser levels.** Empty asserts four zeros, no fabricated rows, and honest copy. Schema failure asserts 503, safe copy, no metrics/lists/object identities/internal details. Both states, plus populated state, run at 1440 and 390.

10. **[RESOLVED] Browser evidence is reconstructible and semantically useful.** Each mode/viewport records geometry and screenshot path/hash/size; the helper checks horizontal overflow/clipping, required text/state counts, and visible/focusable exact row navigation.

11. **[RESOLVED] Public `shlz-ui` provenance is pinned.** Dashboard/Chart Widget, status badge, link, button, and empty-state public source files each have an expected SHA-256 and byte inclusion check. The local view/assets reject private paths/imports and alternative chart dependencies.

12. **[RESOLVED] Demo and scope remain honest.** Required metric explanations, customer questions, and future-idea separation are checked; the normative specification continues to prohibit chart/trend scope, new domain facts/DDL/cache, landing changes, and `rapid-pilot/` changes.

## Fourth-review decision

The specification, verification input, executable RED, helper, and recorded evidence form a coherent A–N Gate 3 contract. The single PHP acceptance entry is acceptable because it is the registered public wrapper that directly launches and validates the browser helper and retains its artifacts. All previous blocking findings are resolved, and the observed RED fails solely at the deliberately absent public dashboard owner. Gate 3 is **APPROVED** for handoff to the separate executor; this approval does not claim implementation GREEN, CI GREEN, Gate 5 approval, merge, or deployment.

---

# Consolidated controlling Gate 3 test/plan delta approvals — 2026-09-21

- Verdict: **APPROVED**
- Reviewer: Codex, `gpt-5.6-sol`, reasoning `low`
- Current exact candidate inspected as input: `caaab4f807450efdd9ef6370699958545a8d9789dc46b105bece58fbdaf81ccb`
- Historical pre-implementation RED record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789939258501904000-31f1fdf2a3a944429196c2d89f0d2252.json`
- Independence: this section persists the reviewer's already-rendered independent decisions; the reviewer authored none of the specification, executable tests, production implementation, or reviewed deltas.

## Complete findings list

No unresolved Gate 3 test-design, verification-plan, or ownership findings.

The following root-authored post-approval test deltas are individually and collectively **APPROVED**:

1. `UserAccessFixture` exposes fixture-only `emails[18]` and `password` aliases that copy the existing `Yii2AuthFixture` credentials. They make the real-browser login setup reachable without creating another actor, changing permissions, bypassing HTTP authentication, or weakening any assertion.

2. The materialized-list assertion uses the normative `count(upcoming) + count(overdue) <= 10` rather than incorrectly requiring exactly ten. Exact top-five ordering, overdue identity, 30k list bounds, HTTP marker bounds, and full aggregate totals preserve sensitivity.

3. Query counting uses the supported Yii logger path on the explicitly retained dashboard connection, counts only category `yii\db\Command::query` at `Logger::LEVEL_INFO`, and requires `queries > 0 && queries <= 5`. Profiling begin/end messages cannot inflate the count, while silent instrumentation failure cannot pass.

4. Full aggregate totals use DTO keys `overdueCount` and `upcomingCount`; bounded list arrays remain separately verified through `overdue` and `upcoming`.

5. Concurrent HEAD uses native curl `--head` rather than custom `-X HEAD`, avoiding a false transfer-length failure for a compliant bodyless response. GET remains ordinary; both processes launch before join and both exit statuses are checked.

6. The 30k concurrent read-only checkpoint uses a bounded DB-side digest over every `SHOW TABLES` table, excluding only the pre-existing documented `fm2_pilot_auth_attempts` noise exactly as the former fixture fingerprint did. Each table contributes `COUNT` plus non-null `CHECKSUM TABLE EXTENDED`; null checksum is a setup failure, keys are sorted, and no full table rows enter PHP.

7. Private path checks (`shlz-design-source`, `node_modules`, `packages/`) apply to the dashboard consumer view/import surface rather than rejecting legitimate provenance comments in the pre-existing generated standalone CSS. Exact hashes and byte inclusion remain required for all five public exports; runtime `@import` and alternative chart dependencies remain prohibited across view and CSS.

8. Sequential and concurrent HTML replay canonicalize only the masked value of the `_csrf` hidden input on both compared responses. All other HTML bytes and dashboard semantics remain exact, owner DTO replay remains exact, and fact/digest checks remain unchanged. This permits normal Yii per-render CSRF masking without requiring a production security bypass.

9. Safe-return coverage requires the first successful login to consume and redirect to saved `/pilot/dashboard`, then requires a subsequent authenticated `/pilot/login` to use the established default `/pilot/objects`. This proves one-time return handling and preserves landing semantics.

10. The unused optional factory clock argument was removed. The controller/request clock remains fixed in the HTTP test through `FMONITOR_NOW`, the exact displayed Moscow cutoff is asserted, and the owner continues to receive the explicit cutoff through `read`.

The verification-input and ownership scope delta is also **APPROVED**:

11. The verification input lists the actual compatibility scope: `MariaDbYiiObjectCard.php`, `MariaDbYiiObjectCardProjection.php`, `MariaDbYiiObjectQueue.php`, `AuthController.php`, `PreopeningResources.php`, and `ViewSupport.php`, in addition to the dashboard paths. `MariaDbYiiObjectCardProjection.php` belongs to the existing `yii-object-read-presentation` capability owner with the unchanged object-queue/object-card verifiers. The Yii controller/resource/support paths remain covered by the existing application-code ownership and governance/unit selection.

12. The selected Gate 3 obligations remain sufficient: the dashboard acceptance test exercises the real auth return, queue parity, exact card 200/403, populated/empty/error browser journey, replay/concurrency, bounded 30k read, and public UI provenance; focused object-card and object-queue regressions cover their ownership frontier; governance/unit checks and exact-source full CI provide integration closure. The inspected planner package reported no missing tests.

## Controlling decision

The current controlling Gate 3 executable contract and verification/ownership plan are **APPROVED**. These approvals supersede the earlier per-delta message decisions for recording purposes and preserve the original A–N expectations without weakening the public seams. The historical RED record is provenance for the pre-implementation missing-owner boundary; current post-implementation evidence must be bound separately by the harness.

This decision does **not** approve production correctness, Gate 5, exact-source CI, PR readiness, merge, deployment, or settings changes.
