# Test review: INSTALLER-UTILIZATION-OBSERVATIONS-001

- Gate: 3 — independent review of the root-authored specification and executable RED tests
- Reviewer: independent `gpt-5.6-sol/low` agent `/root/gate3_review`; authored neither specification/tests nor implementation
- Test/spec author: root delivery agent
- Reviewed source: base `99bd0974150617a01e195cec28f7d886f1ede761`, candidate source `bb0ff8c1561a91c03974d01e38c45101a37a5d51000f1366e326316b43e26357`, executable source `ed2c19af5c7a62bb02fdd19eea4212ce5b2a4087e002d3c610b86f6dd991da51`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T233608Z-8f3f6d6783/package.json`; plan SHA-256 `d0e1e570b33860e3e15ec70543b64e84fafa5baef00909bdcb958fcf19393051`
- Specification: `specs/INSTALLER-UTILIZATION-OBSERVATIONS-001.md`, SHA-256 `936b1111530dfe9b86abb6bf3482268a8f5a0920edee4aadb62e870423a78c98`
- Public seams: jobs scheduler/worker capture; `GET|HEAD /pilot/dashboard`; saved observation drill-down; installer directory/card presentation
- RED evidence: three package-bound `INTENDED_RED` records exist, with command blobs matching the reviewed files
- Verdict: `CHANGES_REQUESTED`

## Author response (pending independent re-review)

The root test/spec correction replaces class/reflection and source-substring gates with disposable MariaDB scenarios at the public capture/history/detail and HTTP seams. It adds fixed independent count/share examples, rollback/idempotency/immutability/unavailable regressions, full/denied/scoped GET/HEAD reads, and real browser assertions for grouped bars, saved navigation and card presentation. The contract now makes partial scope a 403, defines HEAD, saved-member fields/order, unknown coordinates and a 366-point bound. This note records the response only; it does not change the independent `CHANGES_REQUESTED` verdict.

## Findings

1. **BLOCKER — A–I have no executable behavioral test.** `tests/Workforce/installer_utilization_observations_001_test.php` only requires the future class, reflects four method names, and searches four contract literals. It creates no disposable data and never invokes `capture`, `currentSummary`, `history`, or `detail`. Therefore implementations can pass without proving the original → opening → PTO transitions (A), denominator/share/percentage-point comparison (B), retry/race idempotency (C), transactional rollback (D), missing-day behavior (E), immutable late-correction behavior (F), saved drill-down equality (G), one-point no-trend behavior (H), or fail-closed incomplete/ambiguous input (I).

2. **BLOCKER — authorization and read-only seams are asserted as source text, not behavior.** The Yii test merely finds `'installers.read'` in `DashboardController.php`; it does not request dashboard or direct detail routes as guest, denied, partial-scope, and fully authorized actors, nor prove response bodies contain no counts, people, object identities, or reasons on denial. It also does not exercise `HEAD`. No before/after storage/job/synchronization snapshots prove GET/HEAD never capture, enqueue, or synchronize (J/K). The background job type's explicit allowlist and execution authority are not tested either.

3. **BLOCKER — history persistence is not acceptance-sensitive.** There is no schema/migration execution, next-free migration-number assertion, catalogue/recovery check, immutable-owner boundary, unique MSK-date constraint, transaction failure injection, or two-worker race. Header/member atomicity, exact saved denominator and bases, replay receipts, and prohibition of UPDATE/DELETE can all be absent while the suite passes. The test also cannot distinguish a saved-detail read from recomputation against today's projection, which is the principal requirement of this stage.

4. **BLOCKER — the RED evidence does not reach the reviewed expectations.** Both PHP tests fail at the first missing class, so route, authorization, copy, and presentation assertions are unreachable in the recorded RED cycle. The browser command requires an external config argument but the planner-selected command supplies none; its recorded failure can therefore be caused solely by `configuration required`, before a browser or product route is exercised. These failures demonstrate missing scaffolding/input, not behavioral sensitivity to the stage-2 contract.

5. **HIGH — the current-summary and comparison rules are untested.** Nothing constructs the three mutually exclusive groups, proves each identity appears exactly once, or checks `without_current = awaiting_start + unassigned` and `without_next = unassigned`. No independent expected values validate real dates, per-date denominators, counts, shares, percentage-point delta, unavailable denominator handling, or the single-suitable-point state. An implementation with overlapping groups, today's denominator applied historically, count delta mislabeled as percentage points, or an invented trend can pass.

6. **HIGH — source completeness and the diagnosed production symptom lack regression coverage.** The suite has no fixture for active objects whose official native assignment/application facts are absent, no ambiguous identity, and no incomplete/malformed workforce or assignment source. It therefore does not enforce unavailable rather than zero/proven-free output, and does not protect the read-owner correction motivated by production. It also does not prove the stage-one owner is the sole classifier rather than allowing a dashboard/history reimplementation.

7. **HIGH — scheduler semantics are only prose.** No controlled-clock test proves one 03:17 `Europe/Moscow` slot after workforce synchronization, date selection around UTC/MSK midnight, permitted job payload/type, retry after failure, or no synthetic catch-up/backfill. Missing days, first post-deployment observation, and no capture from ordinary web reads need executable boundaries.

8. **HIGH — drill-down correctness and privacy are untested.** The route is checked only by a substring in `config/yii/web.php`. There is no click/direct-URL request for each bucket, count/list/reason equivalence to the selected saved bar, subset semantics (`without_next` versus `without_current`), saved object/document identities, hostile-text escaping, late-fact stability, unknown date/bucket rejection, or cross-observation access isolation.

9. **HIGH — UI and browser coverage does not prove L/M.** The browser script only checks that a heading exists, focuses the chart container, and compares page-level overflow at two widths. It neither verifies two adjacent stock `shlz-ui` bars per real date nor absence of a chart library/line/third block, labels/legend and real missing dates, clickable and keyboard-activated bars, visible focus, touch targets, local chart overflow, saved detail navigation, or comparison copy. It never opens the installer directory/card, so status-label rendering, removal of both provenance fields, and card panel gap/padding are not browser-tested. The PHP `shlz-status` substring can pass with an unrelated label.

10. **HIGH — the executable tests do not isolate setup or independent expected values.** No managed clock, disposable database, deterministic fixtures, or cleanup is present. Counts, shares, reasons and HTML projections have no independently specified oracle. The requested collision/repeat/failure and late-correction cases require deliberate state transitions and stable snapshots, not file-content inspection.

11. **MEDIUM — specification needs an explicit partial-scope outcome.** The contract says a partial object scope must not be presented as a complete workforce summary, but does not unambiguously choose denial versus a clearly marked scoped summary for dashboard/history/detail. Acceptance J says partial actors get authentication/403, which appears to choose denial; state this normatively for every public read seam so tests and implementation cannot diverge. Also specify whether `HEAD` on the detail route must mirror GET status/headers with an empty body.

12. **MEDIUM — saved evidence and retention bounds need a tighter contract.** “Minimal identities” and “bounded historical observations” do not define stable member identity fields, exact reason DTO fields, ordering, history window/pagination, or behavior when a referenced display entity later disappears. Without this, byte-equivalent late-correction checks and reproducible drill-down can be implemented incompatibly while each claims compliance.

## Required changes

- Replace structural reflection/text checks with disposable-database, managed-clock scenario tests that invoke the public observation owner and HTTP/jobs seams and cover every A–M row with independent expected values.
- Add schema/migration, transaction failure, immutable storage, repeat and real concurrency coverage, including one published point/date and identical race results.
- Add full authorization/no-leak and GET/HEAD no-write coverage for dashboard and direct saved-detail reads, plus explicit worker allowlist/authority tests.
- Add scheduler/timezone/order/no-backfill tests and unavailable-source/ambiguous-identity regression fixtures, including the production symptom where active objects lack official applied assignment facts.
- Add saved-versus-current drill-down snapshots, late PTO/correction immutability, missing-day and one-point comparison cases.
- Expand browser evidence to the real dashboard chart and installer directory/card at desktop and narrow widths, including stock grouped-bar structure, keyboard/touch navigation, visible focus, local containment, status labels, hidden provenance, and card spacing.
- Clarify the normative partial-scope and saved-detail field/order/history-bound contracts, then capture fresh exact-source intended RED that reaches the missing behavior and regenerate the reviewer package.

## Rereview 1 — behavioral fixture correction cycle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/gate3_review`
- Reviewed candidate source: `74a2578c41c667a4885cdea057cc8cf8e88db97444c9347d9a6a3d768e325ded`; executable source `892825a04dce0d97ca638835630cd4fa01e87a6b431910e2c408a06d74df0a67`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T234822Z-5336d1e595/package.json`; plan SHA-256 `282636fc351d3fb21cd08dac4ba7ca2fc3bd502d86f90b95e4542aecb9ed810f`
- Bound test hashes: domain `3217031ee8fc5e4a972a34994d3bb2de507debd3e9c4f953fdf5a6df6af65d8e`; HTTP `ea8ed5e13a00f7b626ec1c319b985ddfb85709c49a7e965347e399e4c0a91d69`; browser wrapper `ae2f0fa12cdd954b89708681c94a1d47533f91d5c92a8a3e581272b38df13885`
- RED evidence: all three mapped commands are exact-source `INTENDED_RED`; each currently stops at the absent schema class before later expectations
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

The correction materially improves the suite: it replaces reflection/text-only checks with disposable MariaDB fixtures and public method/HTTP calls; fixes the browser configuration path; adds fixed count/share/date expectations, failure rollback, repeat idempotency, missing-day handling, source-unavailable cases, full/denied/partial authorization, GET/HEAD no-write snapshots, unknown detail coordinates, a two-date grouped-bar browser route, keyboard navigation, narrow overflow/spacing, status label and hidden provenance checks. The specification now resolves partial-scope denial, HEAD semantics, saved member fields/order and the 366-observation history bound.

### Findings

1. **BLOCKER — acceptance A and the shared stage-one owner are bypassed by a test-only classification input.** Every capture supplies `fixtureStates` with already-decided `working|awaiting_start|unassigned` values. The fixture never creates a draft, accepted original, factual opening or PTO and never asks the stage-one `MariaDbInstallerUtilization` owner to classify those facts. Consequently an implementation may expose a parallel observation-only definition, ignore #264 entirely, or mishandle the required draft → original → opening → PTO transitions while passing. Remove classification injection from the public contract under test and construct the real source facts; independently assert draft unchanged, original changes only `without_next`, opening changes `without_current`, and PTO restores it.

2. **BLOCKER — acceptance C does not exercise a race.** Calling `capture` twice sequentially proves replay only. There are no two overlapping workers/connections, no barrier at the uniqueness boundary and no assertion that the loser returns the exact persisted receipt. Transaction/unique-key logic that is replay-safe but fails under concurrent insert can pass. Add a deterministic two-process/two-connection race and compare both semantic receipts plus header/member cardinality.

3. **HIGH — saved-detail reproducibility is under-tested.** The saved row has no fixture current/upcoming reasons, object/document identities, dates, ordering conflict or removed display entity. The alleged late-fact case only changes workforce `fio`; it does not apply late PTO/correction/composition facts. `without_current` detail is never compared to its clicked bar, and no hostile saved value is rendered. An implementation can persist only tab IDs/counts, recompute reasons from current facts, omit required evidence fields, or sort incorrectly while passing. Seed real bases and late mutations, then compare complete saved DTO/HTML before and after for both buckets.

4. **HIGH — current projection and unavailable presentation remain uncovered.** `currentSummary()` is never called. Incomplete/ambiguous/source-gap cases assert only that capture throws; no dashboard request proves the live summary is unavailable rather than zero/free, and no recovery case proves subsequent valid reads/capture. The production-symptom fixture is again expressed as the option `openedObjectsWithoutOfficialAssignments`, rather than real active-object/native-application facts, so it cannot catch a broken stage-one join.

5. **HIGH — scheduler/worker authority and ordering are incomplete.** The test validates time and job payload creation, but does not run the actual job handler, prove its explicit allowlist/authority, establish that workforce synchronization precedes capture, test a failed job retry, or assert the web actor cannot invoke capture. The scheduler's returned `skippedDays` is implementation-shaped and does not by itself prove that no synthetic observation/job was created for the missing date.

6. **HIGH — migration and immutability boundaries are not tested.** `Schema::apply` is invoked directly, without asserting the next-free migration number, canonical catalogue/recovery registration, production migration runner behavior, unique local-date schema constraint, or denial of UPDATE/DELETE through the normal observation owner/principal. The implementation could satisfy the happy fixture while remaining undeployable or mutable in the production path.

7. **MEDIUM — UI acceptance remains only partially sensitive.** The browser checks two bars per date, keyboard Enter and basic card padding, but not accessible legend/series labels, displayed first/last counts/shares/denominators/percentage-point delta, the one-point no-trend copy, touch activation, detail count/reasons after navigation, or local chart scrolling versus clipped bars. The HTTP test checks `shlz-status` anywhere in directory/card rather than binding the label to workforce status, and card separation/gaps are not asserted independently of panel padding.

8. **MEDIUM — the 366-point/history and saved-order clarifications have no executable oracle.** There is no 367-point fixture, date-order assertion beyond two points, equal-FIO tie, multi-reason ordering, or deleted-entity case. These newly normative rules could be implemented incorrectly without failing the suite.

### Required changes for rereview 2

- Drive capture and live summary from actual #264 workforce/original/opening/PTO facts; remove `fixtureStates` and production-symptom option shortcuts from the tested public seam.
- Add deterministic concurrent capture, complete saved-reason/late-fact snapshots for both buckets, and live unavailable/recovery HTTP coverage.
- Exercise scheduler → allowlisted worker execution and authority/order/retry boundaries; prove missed days create neither observations nor synthetic jobs.
- Add migration catalogue/recovery/next-free and immutable-owner checks.
- Strengthen browser/HTTP expectations for comparison values, one-point state, labels, touch/detail navigation and status-bound/card-gap presentation; cover the specified 366/order/deleted-entity boundaries.
- Capture fresh exact-source intended RED and prepare a new reviewer package.

## Rereview 4 — complete two-series comparison and no-leak closure

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/gate3_review`
- Reviewed candidate source: `42553467740b90288ccb2a03ad46ed8c9682ff9279b8b892cf9a68cee45b5da1`; executable source `46aeef88126c57a80224e77e5571bf1873abe41acac693c09b3679fe79e4999f`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T001356Z-490b8bf255/package.json`; plan SHA-256 `b9bec305cbfe55787a807e0a4644822d30f33b5dc70f635ba4cc682c3aa69fe4`
- Bound test hashes: domain `3c4481c60b97ff129008c676c318107fee25a353b2eb53f648beec46c973f434`; HTTP `27f3f7aea87cb1f1a2e4a323b98a731a35ab196e5a07d35200ccf38cae7e8ba2`; browser wrapper `4b4d0004aea866cb2f3503f845443e06b85189bcca8d36772ebd68d2efb63429`
- RED evidence: all three mapped commands are exact-source `INTENDED_RED`; the domain failure reaches the absent canonical v35 registration and HTTP/browser failures reach the absent observation schema class
- Verdict: `APPROVED`

### Prior findings disposition

1. **Both comparison series — RESOLVED.** The denominator-changing owner fixture now independently asserts first/last dates and denominators plus first/last counts, shares and percentage-point deltas for both `without_current` and `without_next`. Browser data binds the same complete value set for both rendered series.
2. **Guest/partial no-leak — RESOLVED.** Guest, denied and partial actors exercise dashboard and direct saved-detail GET/HEAD. Actual response bodies reject installer IDs, observation dates and business-series copy, while HEAD bodies are required empty.

### Complete assessment

The reviewed contract and suite now provide acceptance-sensitive coverage for A–M through the intended public seams:

- the merged stage-one utilization owner classifies real draft, accepted-original, factual-opening and PTO facts into three exclusive groups;
- current and historical counts, real per-date denominators, both shares and both percentage-point comparisons have independent expected values, including a one-point no-trend state;
- sequential replay, a deterministic two-process race, transaction failure and unique MSK-date persistence protect atomic idempotent observations;
- missing days are absent, history retains the newest 366 real observations, and saved members/reasons remain unchanged after late PTO, corrected original and display-fact edits;
- active objects without official assignment facts, incomplete workforce coverage and ambiguous identities fail closed rather than becoming zero or proven freedom;
- scheduler time/order, minimal job payload, system authority, worker dispatch/retry and no catch-up behavior are explicit;
- canonical migration/recovery registration and the immutable public owner boundary are exercised alongside the planner-selected integration frontier;
- authorized GET/HEAD remain read-only, while guest/denied/partial direct reads have status and no-leak coverage;
- grouped stock bars, accessible legend, exact comparison data, keyboard/touch saved-detail navigation, responsive containment, workforce status labels, hidden provenance and local card spacing are browser-observable.

Expected values come from explicit fixture facts and contract arithmetic rather than implementation output. Fixtures are disposable, clocks/dates are fixed, source mutations are deliberate, and RED records are bound to the reviewed candidate. Root test/spec authorship and independent reviewer separation are preserved.

### Required changes

None. Gate 4 implementation may proceed against exact candidate source `42553467740b90288ccb2a03ad46ed8c9682ff9279b8b892cf9a68cee45b5da1` without weakening or changing the approved expectations.

## Rereview 3 — numeric comparison and fail-closed correction cycle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/gate3_review`
- Reviewed candidate source: `1ab2e95d8166ce8ab68d83025886624a8431f3139d78f9df99f8e1dcd967a88f`; executable source `c79eae4aac942c265a20b4d7c61acda69908028953f2dc2074778fdb38a45d2b`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T000934Z-2adbe3c197/package.json`; plan SHA-256 `a69c10d31116cde8dcbed1cdc0569ef908c87611d9d6ba3d905216b91e00fc73`
- Bound test hashes: domain `b08840b67bdbdc8bba87c36dd0c1120119d81bb683d0604735d8febb82f1d7cf`; HTTP `1559c59b103bf017fa5bc06f16921912af22571457d207f5d7abd9bfb1d274fd`; browser wrapper `4b4d0004aea866cb2f3503f845443e06b85189bcca8d36772ebd68d2efb63429`
- RED evidence: all three mapped commands are exact-source `INTENDED_RED` at the missing stage-2 migration/schema boundary
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **B/H owner oracle — PARTIALLY RESOLVED.** A one-point history asserts real values and no trend. A denominator-changing 2→3 fixture asserts real first/last dates, denominators, latest counts/shares and `without_current` percentage-point delta.
2. **Production symptom and ambiguity — RESOLVED.** A real opened installation case without an official application and a duplicate installer identity in an official snapshot both fail closed through the stage-one owner.
3. **Detail GET/HEAD authorization — PARTIALLY RESOLVED.** Guest, denied and partial actors now exercise dashboard and direct detail with both verbs and correct statuses; denied bodies have explicit fixture-literal no-leak checks, but guest/partial GET bodies do not.
4. **Rendered comparison — PARTIALLY RESOLVED.** Browser data binds exact dates, denominators, `without_current` counts and its pp delta; the one-point HTTP state explicitly omits invented delta.

### Findings

1. **HIGH — the second required comparison series remains unverified.** Contract clause 7 requires count, share and percentage-point change for *each* indicator. The owner assertion checks `withoutNextShare` only for the latest observation, but never checks saved first/last `without_next` counts/shares or `withoutNextDeltaPercentagePoints`. The browser `data-utilization-comparison` assertion likewise binds only `withoutCurrentFrom`, `withoutCurrentTo` and `withoutCurrentPp`, and checks no share values for either series. An implementation can calculate/render «Из них без следующего назначения» from the wrong dates or denominator, or copy the first series' delta, while passing. Assert independently expected first and last count/share plus pp delta for both `without_current` and `without_next`, and bind the rendered comparison to those exact values.

2. **MEDIUM — partial-scope and guest GET no-leak assertions are ineffective.** For guest/partial loops, `assertSameValue('', $method==='HEAD' ? $r['body'] : '')` checks only HEAD; on GET it compares an empty literal to itself. Thus a partial actor may receive `403` with installer IDs, observation date, object/document identity or saved reasons in the body and still pass, contrary to acceptance J. Apply the denied actor's forbidden-literal checks to guest and partial GET bodies as well (including saved object/document/reason literals), while retaining empty HEAD assertions.

### Required changes for rereview 4

- Add complete independent owner and rendered comparison oracles for both chart series: first/last dates, denominators, counts, shares and percentage-point deltas.
- Make guest and partial GET no-leak assertions inspect the actual response bodies and reject saved installer/object/document/reason literals.
- Capture fresh exact-source intended RED and prepare a new reviewer package.

## Author response — Rereview 2 candidate

The root-delegated test/spec correction removes every `fixtureStates`, source-completeness and production-symptom option from the tested capture seam. The domain fixture now injects the merged stage-one `MariaDbInstallerUtilization` owner and drives public selection, accepted-original and opening requests before adding the canonical PTO fact. It also adds a transaction-only failure port, a two-process/two-connection race helper, exact saved reason/object/document assertions across late PTO/correction/edit facts, live unavailable/recovery behavior, scheduler/worker authority and retry, v35 catalogue/recovery/unique-date/immutable-owner assertions, and the 367-to-366 history/order oracle. HTTP/browser coverage binds the status label to the workforce row and checks comparison copy, legend, keyboard/touch detail navigation, saved members/reasons and independent card gap/padding.

Fresh bounded execution is recorded outside the checkout at `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/evidence/issue-258-rereview2-intended-red.md`. This author response does not alter the independent `CHANGES_REQUESTED` verdict; a fresh package and independent rereview remain required.

## Rereview 2 — real-owner, concurrency and worker-boundary cycle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/gate3_review`
- Reviewed candidate source: `3d59bd5bc09704d5099fe4c7724e2ed5e8053a100fa40512db079f04f814a6a3`; executable source `93e3eb3caf94570e1efe2822bfa05d72b9ca8fa3a38f9bc75f72eb072b925fcc`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260925T000440Z-cfd1991aa9/package.json`; plan SHA-256 `ff27cd46dc5683afc315258ab6532b782a9f3baa424396468047a646ecb1fe9b`
- Bound test hashes: domain `aae95c0ba54872ab48e165805c363b8a0eb31b9c400b3af8a3ed8d07793d9c17`; HTTP `22c1ed1dc59ef454295d82a87d7e3642da8c7c0ad1485bdce3e16f44c8bede8f`; browser wrapper `4b4d0004aea866cb2f3503f845443e06b85189bcca8d36772ebd68d2efb63429`
- RED evidence: all three mapped commands are exact-source `INTENDED_RED`; domain RED reaches the missing canonical v35 registration, while HTTP/browser wrappers reach the missing observation schema class
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Real stage-one owner and A transitions — RESOLVED.** The fixture injects `MariaDbInstallerUtilization`, drives public selection, original upload and opening, and adds the canonical PTO fact. Fixed independent counts distinguish every transition.
2. **Concurrent capture — RESOLVED.** Two independent processes/connections rendezvous before capture; receipts and persisted cardinality are compared.
3. **Saved detail/late facts — SUBSTANTIALLY RESOLVED.** Upcoming object/document/type evidence is asserted, and complete saved DTOs for both chart buckets are compared after PTO, corrected original and mutable display-fact edits.
4. **Current unavailable/recovery — PARTIALLY RESOLVED.** Public owner and dashboard now prove fail-closed/recovery for `source_unavailable`, but the distinct missing-official-assignment production symptom and ambiguous identity are not constructed.
5. **Scheduler/worker — RESOLVED.** Time, workforce-before-capture ordering, minimal job/actor, handler dispatch, retry classification, authority rejection and no catch-up job are covered.
6. **Migration/immutability — SUBSTANTIALLY RESOLVED.** Canonical v35/recovery/unique-date and lack of mutable public owner methods are asserted; planner-selected production runner/recovery checks provide the wider integration frontier.
7. **UI/browser — SUBSTANTIALLY RESOLVED.** Legend, adjacent bars, comparison vocabulary, keyboard/touch navigation, saved count/reasons, status-bound label and independent local card spacing are now exercised.
8. **History/order bound — PARTIALLY RESOLVED.** The 367→366 date bound and equal-FIO member tie are covered; multi-reason ordering/deleted display entity are indirectly protected by complete saved DTO equality but not separately constructed.

### Findings

1. **BLOCKER — acceptance B and H lost their executable value oracle.** The current suite never asserts any `withoutCurrentShare`, `withoutNextShare`, first/last denominator, comparison date, count delta, or percentage-point delta field. The later 367-point loop adds installer 7003, but only asserts length/date order and can pass if comparison math uses the latest denominator for every date, reports count differences as percentage points, or invents dates. Likewise, no history read immediately after a single suitable observation asserts that values remain visible while `trend`/delta is absent. Browser checks only the generic words “Первое наблюдение”, “Последнее наблюдение”, “Знаменатель” and “п. п.”, so arbitrary or incorrect numbers pass. Add a small, isolated managed fixture with denominator 3→4 and independently expected counts, shares, real dates/denominators and percentage-point deltas, plus the one-point no-trend state before the second capture.

2. **HIGH — acceptance I and the production SSH regression remain conflated with workforce-source unavailability.** The only fail-closed transition changes `fm2_workforce_catalog.reconciliation_state` to `source_unavailable`. It does not construct an ambiguous installer identity, malformed mandatory assignment/application facts, or the reported condition where active objects exist but official native assignments are absent. A reader that correctly rejects a stale workforce row yet silently classifies every installer free when native assignment coverage is missing can pass. Build the production-shaped active-object/missing-official-application fixture through real tables and assert unavailable/no capture/no PII-zero summary; add an independently ambiguous identity case.

3. **MEDIUM — HTTP no-leak coverage does not include HEAD denials or partial direct detail.** Authorized GET/HEAD is covered, but guest is checked only on dashboard GET, denied actors only use GET, and the partial actor is checked only on dashboard. The contract explicitly applies authentication/403 to dashboard and every saved-detail URL and defines HEAD parity. Add guest/denied/partial GET and HEAD for a direct detail URL, with empty/safe bodies and no saved member/object/document leakage.

4. **MEDIUM — exact first/last comparison and one-point presentation are absent from browser coverage.** This follows the missing domain oracle but is independently user-visible: the browser never asserts actual dates, counts, shares or denominators and never boots a one-observation dashboard. Once the domain DTO oracle is added, bind rendered values to the same independent fixture and assert the one-point explanatory copy contains no invented delta.

### Required changes for rereview 3

- Restore independent acceptance B/H assertions for a denominator-changing two-point comparison and a one-point no-trend state, at owner DTO and rendered browser/HTTP levels.
- Add real active-object/missing-official-assignment and ambiguous-identity fixtures; prove owner capture and dashboard fail closed rather than zero/free.
- Complete guest/denied/partial direct-detail GET/HEAD parity and no-leak checks.
- Capture fresh exact-source intended RED and prepare a new reviewer package.

## Current Gate 3 disposition

The latest independent decision is **Rereview 4: `APPROVED`** for candidate `42553467740b90288ccb2a03ad46ed8c9682ff9279b8b892cf9a68cee45b5da1`, recorded above with its exact package, hashes, complete assessment and no required changes. Earlier `CHANGES_REQUESTED` sections remain as append-only review history and are superseded only for that exact approved source.
