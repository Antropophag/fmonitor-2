# Gate 5 code and UI finish review — YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001

- Reviewer: Codex, independent Gate 5 reviewer; not an author of the specification, tests, or implementation
- Reviewed exact source: `3bd14ae8b16b75d3ac5e909ee9844c66a6caa5ae30a119fa6199eda9481a333b`
- Candidate commit: `5e542806e4b159b8f4bb1ac40325f80a7966ac31`
- Base: `193fa1ea5a6a8c26fc822f58d26dd150ec4623d4`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T085715Z-354ce7bf03/package.json`
- Contract: `specs/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md`
- Gate 3 record: `reviews/tests/YII2-OPERATIONAL-DASHBOARD-BAR-CHARTS-001.md`
- Focused GREEN record: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789980954715368000-8e2b29b20edf41e2b48dd45400b15fc2.json`
- UI evidence: `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-user-access-d891f0732c75/dashboard-bar-charts-populated-1440.png` and `/var/folders/yc/548th18156s39y3kx0xc05tc0000gn/T/yii-user-access-d891f0732c75/dashboard-bar-charts-populated-390.png`
- Additional UI evidence: computed tallest mark `116px`, zero mark `4px`; Impeccable detector result `[]`
- Verdict: **CHANGES_REQUESTED**

## Findings

1. **[MAJOR] The chart composition does not occupy the public chart-widget plot slot, leaving a large empty row in every chart and clipping the weekly series.**
   - Locations: `app/YiiRuntime/Views/dashboard.php:41-46`, `app/YiiRuntime/Assets/pilot.css:1894-1912`, and the public contract in `app/YiiRuntime/Assets/shlz.css:5106-5167`.
   - Evidence: `.shlz-chart-widget` defines three explicit rows (`auto auto minmax(240px, 1fr)`) and `overflow: hidden`, while each produced widget contains only a header followed directly by `.fm2-chart-bars`; it never uses `.shlz-chart-widget__plot`. The unoccupied third row therefore remains at least 240px high. Both supplied screenshots show the resulting large blank lower half in all three widgets and a needlessly long dashboard (2373px desktop, 4386px mobile). In the 1440 screenshot the weekly widget is only half-width, but its desktop grid requires twelve tracks of at least 42px plus eleven gaps and padding; the right-hand weeks are visibly cut at the card edge by the public widget's `overflow: hidden`. The browser check only rejects bars outside the *viewport*, so clipping inside a widget can false-GREEN. On mobile, the content is technically stacked and page overflow is absent, but the excess height materially weakens hierarchy and scanability.
   - Impact: operators cannot reliably scan the entire six-week series at desktop width; the visual hierarchy spends more space on empty surface than information, and the mobile task becomes substantially longer than the content warrants. This fails the finish/readability expectation in G and the public-slot intent documented by the design, despite the clean mechanical detector.
   - Required correction: place the bars in the public `.shlz-chart-widget__plot` slot (or explicitly provide the complete public row composition), remove the phantom row, and make all twelve weekly marks fit the paired desktop widget without clipping or overlap. A locally labelled chart viewport is acceptable only if implemented deliberately as allowed by the design; silent `overflow:hidden` clipping is not. Extend the browser geometry oracle to compare each bar/label rectangle with its owning widget/plot rectangle, not only with the page viewport. Re-capture 1440/390 populated evidence after the bounded correction.

2. **[MAJOR] The DTO validator enforces only counts and the stage sum, not the fixed keys/fields or the activity sum invariant required for atomic failure.**
   - Location: `app/InstallationProcess/YiiOperationalDashboard.php:29-38`.
   - Evidence: validation checks only `6/6/5` array counts and `sum(stages) === total`. It does not verify canonical stage/activity keys, the four required fields of every week, non-negative integer values, ordered week boundaries, or that the five activity buckets sum to the three active canonical stage counts. The Gate 3 malformed-sum witness exercises only an unsupported stage and therefore cannot detect this production omission.
   - Impact: a repository regression that returns six or five wrong-shaped entries, or silently loses/duplicates an activity category, can reach the view as a successful partial/mislabelled chart instead of the contract's atomic `503`. This is a maintainability and fail-closed gap in Acceptance F/I, not a current RBAC or write-safety defect.
   - Required correction: validate the exact canonical DTO shape and key order, integer/non-negative values, six contiguous server-derived weeks, and the activity sum against the canonical active-stage population before returning. Add a sensitive public-seam malformed activity/shape witness; keep the existing infrastructure and stage-sum failures.

## Acceptance A–L disposition

- **A — conditionally conforming:** the controller fixes one Moscow cutoff; existing authentication/`objects.read` is retained; repeat, concurrent GET/HEAD, and fact fingerprints are GREEN. No write seam was added.
- **B — conforming:** all six mutually exclusive stages share `MariaDbYiiObjectQueue::stagePredicates()` across aggregate and drill-down; the stage sum is validated.
- **C — conforming:** six Monday–Sunday windows and current deadline-certificate revision fallback are server-derived; start/finish drill-down uses the same cutoff and current deadline join.
- **D — conforming:** accepted checklist server time, non-revoked photo server time, and completion root/correction `recorded_at` are used; device time is excluded and boundaries are mutually exclusive.
- **E — conforming:** chart/bucket pairs are allowlisted; unknown, duplicate, extra, incomplete, and status-conflicting dimensions fail closed; search/page compose and the register exposes its own cutoff.
- **F — changes requested:** query count, PHP memory, DTO cardinality, and 23-mark DOM stay fixed at 30k, but the atomic DTO validator is incomplete as finding 2 describes.
- **G — changes requested:** native links, exact accessible names, visible values, legend text, focus and zero baseline are present; public shlz-ui bytes are used and detector output is clean. Widget-slot composition and weekly clipping fail the finish/readability review as finding 1 describes.
- **H — conforming on reviewed delta:** predecessor metrics/lists/navigation and ordinary queue semantics remain; no DDL, `rapid-pilot`, landing redirect, or state-changing owner changed. Planner-selected adjacent consumers remain obligations outside this single focused GREEN record.
- **I — partially conforming:** authorization and malformed filter rejections are safe; source failure and stage-sum failure are atomic. Wrong-shaped/activity-sum DTOs are not yet rejected by the production validator.
- **J — conforming:** all reviewed paths are reads over existing append-only/current projections and do not create audit/domain facts.
- **K — evidence is sensitive except for the two gaps above:** independently derived stage/week/activity cases, boundary values, 30k query/memory/DOM assertions, fingerprints, populated/empty/error browser states, and screenshot hashes are present. The internal-widget clipping oracle and malformed activity-shape oracle must be added.
- **L — not yet done:** exact-source focused GREEN exists, but Gate 5 is changes-requested and exact-source CI remains outside this review record/UNKNOWN until the corrected candidate is prepared and reviewed.

## Security, SQL, ownership, and maintainability

- RBAC remains at the existing public dashboard/queue owners; forbidden and guest paths do not disclose aggregate values or object identities.
- SQL parameters are bound for search, cutoff, and week limits. Chart and bucket values select only hard-coded expressions after allowlist validation; they are not interpolated as data. No client-supplied date range is trusted.
- Aggregate query count and returned DTO are fixed with object count. The implementation does repeat correlated activity subqueries, so production-plan cost should continue to be watched, but the supplied 30k evidence does not show an acceptance blocker.
- The dashboard and drill-down share the canonical stage predicate owner. The implementation introduces no writer, cache, DDL, deletion of production history, or reinterpretation of append-only facts.
- Public `shlz-ui` assets are consumed from the pinned exported CSS; no private import or chart runtime is present. Finding 1 is about using that public widget contract correctly, not provenance.

## Post-Gate-3 root test delta

**Disposition: APPROVED.** The sole late test change deletes only the 30,000 scale-only fixture rows from `fm2_pilot_object_details`, `fm2_installation_cases`, and `fm_maintable`, then releases the large DTO/HTTP variables before the subsequent empty/error fingerprints. It executes after the production 30k assertions have already required exact total/stage values, fixed `6/6/5` DTO shape, positive bounded query count (`<=12`), memory delta below 16 MiB, exactly 23 DOM marks, and absence of the scale sentinel from HTML. It neither changes production code nor weakens those assertions; it prevents scale-only rows from contaminating later full-table fingerprint and empty-state phases. The cleanup is test-fixture teardown, not evidence of production deletion semantics.

## Visual disposition

The desktop/mobile screenshots confirm the intended full-width stage widget, paired desktop widgets, single-column mobile flow, readable headings, visible values, native link semantics, and no page-level horizontal scrollbar. Focus styling and complete accessible names are covered by executable evidence; color is not the sole carrier of meaning. The computed height evidence confirms honest `4px` zero baselines and a `116px` tallest mark. However, the screenshots also expose the material empty-row and weekly clipping defects described in finding 1. Therefore the UI finish disposition is **CHANGES_REQUESTED** even though `impeccable detect` returned `[]`.

## Final decision

The candidate is read-only, permission-preserving, bounded in query/DTO/DOM cardinality, and substantially conforms to A–L. The late root test delta is acceptable. Gate 5 is nevertheless **CHANGES_REQUESTED** because the shipped chart-widget composition clips part of the weekly data and produces severe empty-space hierarchy on both target viewports, and because atomic DTO validation does not cover the full fixed-shape/activity-sum contract. Correct both findings, refresh exact-source GREEN/browser evidence, and request a bounded Gate 5 re-review of the delta.
