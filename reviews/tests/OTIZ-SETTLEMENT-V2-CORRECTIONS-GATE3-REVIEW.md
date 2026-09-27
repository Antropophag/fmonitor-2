# OTIZ settlement v2 corrections — Gate 3 review

- Verdict: `APPROVED`
- Candidate source: `49f3671d965f4e6430c84928fe96325c594d49d84277bb527f21731bd3899b8e`
- Verification plan: `afdc760795a10865416029cf5c901fb96e3f012e8bbee27b1488872111bd501e`
- Reviewer: `/root/otiz_correction_gate3`
- Gate: `gate3`
- Recorded at: `2026-09-27T07:49:39Z`

## Findings

No blocking findings remain in the complete RED candidate.

The review verified:

- a literal independent money oracle from the canonical 65,000,000-cent fund through 30% recognized gross, Kss 0.5, saved projection, obligations, HTTP export and relationship-resolved workbook cells;
- Kss 1, 0.5 and 0, including recognition without a fictitious zero-value obligation;
- stable entitlement identity across canonical retraction/recompletion, stale-baseline rejection, genuine incremental progress and independent-connection contention;
- a separate replacement-draft lifecycle, including preview/delete, preserved manual facts, incomplete and competing draft denial, admission change before acceptance, atomic acceptance, paid denial and HTTP redirect/history behavior;
- persisted shared #257 admission chronology across acceptance, current payment export, payment, historical export and accepted replacement;
- exact 21-field workbook values, stable-recipient aggregation, zero exclusion, distinct common/personal deductions, decisions, metadata, layout, and style-ID-to-number-format resolution.

This is approval of the executable RED candidate only. Implementation, GREEN evidence, exact-source CI and independent final review remain separate lifecycle obligations.

---

## Complete-candidate rereview — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Candidate source: `f96c966bb7f452edef8d8535a8bd64bc677aeee1d7aa1bc221cf0a2f66232547`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `47a3de0c01b59aedca9103329765dbd8b79f5f77b08406e20dc96e5f740f6db5`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

### Findings

1. **BLOCKER — U06/M20 register history is not specified by a regression.** Add a public register test proving that History includes cancelled/replaced calculations and accepted calculations with zero remaining obligation (including a fully reduced zero-obligation acceptance), excludes an accepted unpaid calculation merely waiting for payment, and treats an erroneous paid marker followed by reversal according to its current payable state. The present tests do not reject the implementation's `EXISTS(any payment fact)` history predicate.
2. **BLOCKER — the register row contract is incompletely exercised.** Add assertions for the required author and payment-stop reason, and for the old-year/default waiting route and blocked-obligation visibility. The browser currently checks only that the filter links and New calculation action exist; it would accept a list that omits both required fields and misroutes a blocked obligation.
3. **BLOCKER — the documentary allocation oracle contradicts the canonical input contract.** `specs/OTIZ-EXCEL-INPUTS-001.md` §§7–8 requires documentary progress to retain the proven positive attributed-contribution weights and forbids invented equal weights. Replace the equal `2437500/2437500` expectation in `settlement_v2_corrections_integration_001_test.php` with the independently derived mixed checklist/documentary oracle: contributions A/B `1500/2500`, documentary payable `4875000`, obligations A `1828125`, B `3046875`. Also assert the builder's saved attribution basis/weights so `$hasDocumentary ? 1` cannot self-prove.
4. **BLOCKER — the browser journey does not verify the required editing previews and recovery.** It submits a decision and deduction directly, but does not assert exact before/after per-object and per-recipient cents, revision/DB neutrality before save, invalid-preview input restoration, persisted decision/reason after reload, exact deduction removal, or that decision/deduction actions work from each grouping. Add scoped grouping assertions: switch to the intended grouping, expand its target, and assert the cross-linked action/result within that visible grouping.
5. **BLOCKER — payment confirmation and navigation scope are under-tested.** Before confirmation, assert the immutable full multi-object calculation scope, calculation number/date/total, and the explanation that FMonitor records an external payment rather than performing it. Also assert the admission-checked payment XLSX route remains distinct from the historical download and cover the economy-to-obligation/payment path without permitting an object filter to narrow a package payment.

The 13 captured checks are credible evidence for the behavior they actually cover, including stable per-work attribution, corrected-item-only movement, unchanged neighboring accepted calculation, blocked replacement preservation, object contributions `1500000/2500000` versus package remaining `4000000`, and cross-actor locking. They do not close the findings above. Gate 3 remains failed until the complete corrected RED candidate is independently rereviewed.

---

## Consolidated rereview — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Candidate source: `eaec0b39aeaa0ad295053211671ed61bef1ed535e800a6baf83e8e397bb856a8`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `82a65f1f472fadfd7e5d1ef484f24c89ca59b3f891a95c36a9beff919e07285d`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

### Prior findings

Findings 1–5 from the preceding rereview are materially addressed: the candidate now covers register business-state history and old-year waiting, author/block reason, neutral authenticated previews and recovery, canonical documentary contribution weights with exact `1828125/3046875` obligations, persisted/removable editing facts and scoped grouping crosslinks, whole-package payment confirmation, distinct payment/history exports, and the economy link. It also correctly rejects unsupported `financial` reversal without facts and retains only the explicit erroneous-mark path.

### Remaining findings

1. **BLOCKER — U01/U06 root table sort and pagination are still not demonstrated.** The browser assertion sends `pageSize=1&sort=amount_desc`, then checks only that one parent exists and a matched parent's children remain complete. It never proves which parent is first, that descending and ascending sorts change root order, that a real root pagination control/page 2 exists and preserves the query/sort/grouping, or that the required project table semantics are rendered. Add an independently ordered two-or-more-root oracle, navigate the real pagination link, and scope assertions to the visible shlz table/grouping. A server that ignores `sort`, truncates arbitrarily to one card, and emits no usable pagination/table would currently pass.
2. **BLOCKER — the UI does not pin the only permitted reversal ceremony.** The browser checks that no `financial` input exists and that an “Отменить отметку выплаты” button exists, but never opens it. Assert that the confirmation explicitly says the mark was erroneous and no real transfer occurred, requires a reason, posts `kind=erroneous_mark`, and does not offer a generic refund/financial-storno choice. Then submit it and verify the calculation returns to waiting while original payment and reversal history remain visible. The domain rejection test alone cannot prevent an ambiguous or unusable UI.

The 14 mapped exact checks (`7 GREEN`, `7 INTENDED_RED`) are accepted as valid evidence for their asserted behavior, but the complete RED candidate remains incomplete until these two UI scenarios are added and independently rereviewed.

---

## Final consolidated Gate 3 rereview — 2026-09-27

- Verdict: `APPROVED`
- Candidate source: `5a4f88377e6922b507569ed6744cbb22e28315f660e607eab4385a21c8611e3d`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `d7a9baa3e29caddc325ebeb7f2f18b465ac64dba15bbcfa04fd830f747857e4e`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

### Findings disposition

No blocking findings remain in the complete RED candidate.

- Prior findings 1–5 remain fixed as recorded in the preceding rereview.
- Root table behavior is now pinned through semantic tables for both groupings, independently ordered object and recipient totals, descending/ascending assertions, and navigation of the real next-page link with `page=2`, `sort=amount_desc`, and `pageSize=1` preserved. The second page contains the next whole object parent rather than split children.
- The reversal journey now opens the real confirmation, requires a reason and explicit no-transfer acknowledgement, fixes `kind=erroneous_mark`, offers no financial-refund route, submits the command, preserves the original payment and reversal reason in history, and returns the same calculation to the waiting queue.

The complete matrix was reconsidered after two returns. The 14 bound checks (`7 GREEN`, `7 INTENDED_RED`) are accepted for Gate 3 at the exact candidate source above. This approves the executable test candidate only; implementation, exact-source GREEN/CI, and independent final review remain required.

---

## Supplemental concurrency-helper disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Support/otiz_settlement_v2_concurrency_worker.php` only
- Blob SHA-256: `928df63711c84ea30f89122707711a4bf088eaa57c79e6a74a91afeac20bb614`
- Git blob: `d9c029c1e858f97dbdbb5d2f6a56af33f2953eeb`
- Reviewer: `/root/acceptance_review`

The reverse worker now passes the explicit fifth argument `erroneous_mark`. This matches the approved M13/A05 concurrency intent: race an erroneous payment-marker cancellation against payment while preserving one coherent serial result. It does not exercise or authorize unsupported financial reversal, and it changes no actors, scheduling, isolation, operations, or expected outcomes.

No findings for this bounded test-helper delta. This disposition is bound only to the exact helper blob above; it is not approval of concurrently changing production or of a later full candidate, which must be rebound and reviewed when frozen.

---

## Review3 supplemental test-delta disposition — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Scope: test-only blobs listed below; no whole-candidate decision
- Reviewer: `/root/acceptance_review`
- `tests/Otiz/settlement_v2_corrections_integration_001_test.php`: SHA-256 `c3d30b7f7a8c4e2b5fb5cb6a208d064386910644008d9f07b3a9c6cb5840cc7e`, Git blob `28271298ddd1401154d2ebbc425db57db14886ea`
- `tests/Otiz/settlement_v2_rights_flow_001_test.php`: SHA-256 `00f3e62ddd0edf415113956d01628862f62be7c6976f984fa328c1cd311697ad`, Git blob `7c1532c84f6948ada38cd0ac9d4ed5eff9a86869`
- `tests/Yii2/yii2_otiz_v2_acceptance_001_test.php`: SHA-256 `dde02e641a50d12502b9e9a06ec8719a23ec6e259fb3e543c9fa166ba3ea5df7`, Git blob `4963e8c14c9c35de7817b70f8880e3e190555a72`
- `tests/Yii2/otiz_v2_acceptance_assertions.mjs`: SHA-256 `95fc0a085baeebd79d70b1c6691cac5101d8dc790dc1be788ae42688b8996405`, Git blob `24d31a9b13159308e63fc86c27a3b2c385c779d5`

### Accepted portions

- Review3 T08 is excluded because the owner expressly withdrew that assertion; it was not used as evidence.
- The T05 integration oracle correctly rejects historical-minimum/blind-old Kss behavior: current canonical Kss is `10000`, contribution weights remain `1500/2500`, documentary obligations are `3656250/6093750`, the corrected replacement is `9750000/9740000` after the `10000`-cent deduction, and the old accepted `9750000` snapshot remains historical.
- The real-source T03 scenario accepts replacement old rights plus the unclaimed 1% B right, preserves the neighboring accepted calculation, and proves the next builder has no duplicate right.
- The real HTTP T07 scenario returns a Russian no-new-work result, links the original unpaid calculation, exposes no internal exception code, and persists no empty calculation.

### Blocking findings

1. **Gate 1/spec mismatch for T05.** `specs/OTIZ-SETTLEMENT-V2-001.md` still normatively states the superseded documentary example with Kss `5000` and obligations `1828125/3046875`. That directly conflicts with the corrected test oracle and the canonical current-Kss rule now established from `PremiumCalculationV2`/`OTIZ-EXCEL-INPUTS-001`. Amend the normative contract first to state current canonical Kss `10000` and `3656250/6093750`, explicitly rejecting historical minimum and blind old-coefficient copying while preserving old accepted history.
2. **The exact browser-helper blob still tests a common deduction, not the claimed personal deduction.** It never selects or posts `employeeId=A`; at that point B is `do_not_pay`, and the asserted A `900000→800000` with total `1400000` is compatible with a common object deduction. Add an explicit stable-recipient selection/hidden value assertion for A and independently require A `540000→440000`, B unchanged at `360000`, then delete that exact deduction identity and prove the original amounts are restored. This is necessary to prevent a common-deduction implementation from satisfying the personal-deduction journey.

Production was changing concurrently, so this supplemental verdict makes no statement about implementation or a later frozen source. Rebind the corrected test blobs and the complete candidate before final review.

---

## Review3 corrected supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: the three frozen spec/test blobs below; no whole-candidate decision
- Reviewer: `/root/acceptance_review`
- `specs/OTIZ-SETTLEMENT-V2-001.md`: SHA-256 `f7f9c35ce352440f5d86c59e71eddfe32246d9a988ceb4817c90eb83bb8af178`, Git blob `35e2eaa8be568975197691530007574e2396c6d1`
- `tests/Yii2/otiz_v2_acceptance_assertions.mjs`: SHA-256 `46ed32051c5f5be2257fde66f957c186cc405f300dfe4013d87f89366eb87c5e`, Git blob `2256d060f3615169932f06709b1aea464c23d9f6`
- `tests/Otiz/settlement_v2_corrections_integration_001_test.php`: SHA-256 `65f2dbb4336073a83ae7069d67c1141d99329d0a9bc0594587b5ec0ec035776a`, Git blob `3fe918e4341e2dc41a4670e7033734e745047c76`

No blocking findings remain in this bounded correction.

- The normative contract now states current canonical Kss `10000`, documentary obligations `3656250/6093750` on proven `1500/2500` contributions, explicitly rejects historical-minimum Kss and blind copying of an old erroneous coefficient, and preserves the old Kss `5000` snapshot without rewriting it.
- The browser helper explicitly selects stable `employeeId=A`, asserts that selection, proves the personal preview changes A `540000→440000` while B remains `360000`, removes the saved deduction by its addressable identity, and uses a neutral follow-up preview to prove A/B restore to `540000/360000`.
- The re-completion integration assertion now compares the sorted complete logical right-key set before/after and independently fixes total gross at `26000000`. This preserves identity and money without incorrectly granting whole-object capacity to `entitlements[0]` or coupling the contract to physical per-item claim granularity.

This supplemental approval supersedes only the two blockers in the immediately preceding review3 disposition. The unchanged accepted T03/T07 portions remain accepted. Review3 T08 remains excluded as expressly withdrawn. A later frozen full candidate still requires exact-source rebinding and review.

---

## Final additive Gate 3 review — 2026-09-27

- Verdict: `APPROVED`
- Candidate source: `42a03628440de7756efb89f09c4ec16260874d825afeead31d702ee746a23dc3`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `f317997ee02fb8384d1c970633dfc0b81b704a26794820ed91c2f24e2e18da20`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

### Decision

No blocking findings remain in the complete executable test candidate. All earlier approved corrections and supplemental dispositions are retained.

- Source freshness is exercised through the real HTTP/checklist path: a selected work's A→B attribution change after draft preparation rejects acceptance with `STALE_CALCULATION`, writes no claims, and leaves the draft editable; restoring the fact and explicitly refreshing permits acceptance. Independent later 20% B work does not invalidate the earlier payment basis, while a later correction to an owned work blocks current payment export with `SNAPSHOT_REPLACEMENT_REQUIRED`.
- The freshness contract preserves the more specific `STALE_ENTITLEMENT_BASELINE` for already occupied rights and states that later dismissal does not erase an accepted obligation. It does not manufacture admission: absent/failed evidence remains fail-closed, and the final implementation review must distinguish a healthy empty admission store from an unavailable producer/schema.
- The Kss chronology now independently proves old accepted Kss `0.5`, current corrected Kss `1.0`, immutable historical export, and rejection of the stale current-payment basis after the normative change.
- The real browser journey pins exactly two top-level OTIZ navigation links, a hidden-until-open acceptance confirmation with calculation identity/full amount, and entered-reason confirmations for delete/cancel/replace. Escape closes each dialog without mutation. Existing payment/reversal, editing, grouping, responsive and workbook assertions remain intact, with additional preview/table screenshots.
- The re-completion oracle compares the complete sorted logical right-key set and exact total gross rather than assigning whole-object capacity to one physical entitlement row.

Exact reviewed delta blobs:

- `specs/OTIZ-SETTLEMENT-V2-001.md`: SHA-256 `31692e6cda9a0f194372d71afb7e955e230e0a72daa1bb7f23da0671d2bb7646`, Git blob `7d2831eca1aebb60aa572c0ba8c15c7cce533ea2`
- `tests/Otiz/settlement_v2_rights_flow_001_test.php`: SHA-256 `2ea487ebef71d2bc4186859269630bc9f1236e2077d357ae05498c43ad86c0e5`, Git blob `7f41b72ca262b071a6d2cef7fdb6249729ec9db7`
- `tests/Otiz/settlement_v2_corrections_integration_001_test.php`: SHA-256 `4cfe323b3e2dd8be5edd81d170bc215d636f20026420a4cee5ea5ee75a601fee`, Git blob `52c97b76f21d590d5ff4296bd8c5ab28aec3ac2c`
- `tests/Yii2/otiz_v2_acceptance_assertions.mjs`: SHA-256 `3f34841b0e7124ccf8f5605afed10074bfc75666f3f6a1378c70244945f9c035`, Git blob `c102d61ca65c81b65850e760b111ccebf0fcf0d5`
- `tests/Yii2/otiz_settlement_v2_browser.mjs`: SHA-256 `4b2644dbac1352da0f32fd7a2404e7d7847fdb8e0e791b7a1f9baf600031d4e7`, Git blob `1623302cae730985f4e01c57ca1e46017d650291`

The 14 exact prepared checks (`11 GREEN`, `3 INTENDED_RED`) are accepted for Gate 3 at this source. Implementation of the remaining RED behavior, final exact-source verification/CI, and independent final review remain required.

---

## T05 normative-change supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: frozen specification and rights-flow test blobs only; no whole-candidate decision
- Reviewer: `/root/acceptance_review`
- `specs/OTIZ-SETTLEMENT-V2-001.md`: SHA-256 `8099ce95b0131ad3bee7f61b54626c8db4702d1e0c7ad1453be84e095e741e99`, Git blob `f3f8f28543057dda68cac7d8bf8082f40fbef875`
- `tests/Otiz/settlement_v2_rights_flow_001_test.php`: SHA-256 `9bb6e1d9b331460636784b54f5219ffa80d6e6a906a2f1e74632e1272735906a`, Git blob `f876e63e90c19216d05e5cac2f2965e42188f410`

No blocking findings in this bounded T05 delta.

The expected values are independently derived from `NativePremiumNorms`, not from the draft-builder implementation: passenger 630 kg uses the second capacity band; 15 floors gives `845 × 100000 = 84500000` cents and 2 floors gives `520 × 100000 = 52000000` cents. With Ksh=Kss=1 and owned attribution A=8%/B=13%, the exact obligations are respectively A=`6760000`, B=`10985000`, then A=`4160000`, B=`6760000`.

The test correctly separates a normative correction from new work: ordinary build must expose the proven current fund but zero new entitlement money; explicit replacement must apply the current higher or lower norm to exactly the source calculation's owned 21%, accept both directions, and leave the neighboring accepted 20%-at-old-norm calculation byte-equivalent. This prevents both false accrual of a norm delta and an `INCOMPLETE_REPLACEMENT` rejection of a valid downward correction.

The retained evidence record is honest: higher correction currently passes, lower correction fails with `INCOMPLETE_REPLACEMENT`, and the unrelated pending freshness assertion also remains RED. This approval covers the regression contract only; production was changing concurrently and must be rebound with the final candidate.

---

## Main-navigation test synchronization — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Yii2/yii2_main_navigation_001_test.php` only
- SHA-256: `7ad0be8c8d83aec2109ba11d05bc197e4bfcdce9df068e76cd69b4ce07b774eb`
- Git blob: `cbc08021190218de87104f437e00c846a3e95a61`
- Reviewer: `/root/acceptance_review`

No findings. The expectation now matches the already approved §12.5 navigation contract: the only top-level OTIZ links are “Экономика объектов” and “Расчёты”; the obsolete visible “Выполнение расчёта” and separate “Архив расчётов” entries are removed. Coverage is not weakened: the same test still requests `/pilot/otiz/history`, requires HTTP 303 to `/pilot/otiz/payments?filter=history`, and therefore protects the legacy history deep link while avoiding a duplicate archive section.

The retired unused three-tab browser delegate remains unchanged as historical evidence and is not part of this disposition. The final frozen candidate must include the focused main-navigation check in its exact-source verification.

---

## Canonical rounding supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Otiz/settlement_v2_rights_flow_001_test.php` only
- SHA-256: `b1385bbe18f8689b67e65b3168945740617faf8e1e298c72df414c338adeee77`
- Git blob: `4b7f804e21533db54e358c5405fd1854c3786e7d`
- Reviewer: `/root/acceptance_review`

No findings. The oracle is independently derived from existing canonical sources:

- `NativePremiumNorms`: passenger 630 kg at 15 floors is `84500000` cents; brick code `86` applies Ksh `11500`, so canonical half-up basis-point rounding gives fund `97175000` cents.
- A newly completed 1%-weight item gives exact gross `971750` cents.
- Report 2026-09-05 versus plan 2026-09-04 gives one late day and Kss `9900`; `PremiumCalculationV2::roundBasisPoints(971750, 100)` is `intdiv(97175000 + 5000, 10000) = 9718` cents. Therefore payable is exactly `971750 - 9718 = 962032` cents.

The test isolates the established project rounding rule and exposes the current one-cent truncation (`9717`/`962033`) without introducing a new formula or norm. The retained run is correctly classified `INTENDED_RED`; the other 19 rights-flow scenarios being GREEN does not mask it.

This approves only the exact regression-test blob above. The production rounding fix and final frozen candidate still require exact-source verification and independent final review.

---

## Saved-basis and visible-explanation supplemental disposition — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Scope: the two frozen test blobs below; no whole-candidate decision
- Reviewer: `/root/acceptance_review`
- `tests/Otiz/settlement_v2_rights_flow_001_test.php`: SHA-256 `8920a36ed566ccf590d5e1604c4240921d30f6a31f858e036a6f638c07031380`, Git blob `7a1f82dcfad622502f3e1c6315be88cc460dc56d`
- `tests/Yii2/otiz_settlement_v2_browser.mjs`: SHA-256 `7cfd3eb09b4e71d6fc024c0b375e3957bf5672721a2da396daf712e130aa24e4`, Git blob `4f2259366d11a24068ad8e9a2e6a07713dd92a19`

### Accepted portions

- The real 20%-prior/40%-confirmed scenario independently fixes the saved basis at `acceptedBp=2000`, `confirmedBp=4000`, `newBp=2000`, with selected entitlement gross exactly `13000000` cents. This correctly prevents historical work from being presented as new money and prevents the selected-right set from being discarded after projection.
- The expanded object view now requires the distinct explanations “Учтено ранее”, “Подтверждено”, “Объём этого расчёта”, “До уменьшений”, “За сроки”, “Общее удержание”, and “Личные удержания”. Existing domain tests continue to own the underlying monetary arithmetic.

### Blocking finding

1. **X01/X04 saved basis is not verified at the workbook seam.** The request explicitly requires saved selected rights to populate the XLSX basis rather than the exporter’s empty-right fallback, but neither changed test downloads/parses this scenario's workbook or asserts the “Основания” row. Existing workbook tests do not close the gap: their object fixtures contain no entitlements, and the current integration XLSX assertions do not inspect that sheet. Add a real HTTP XLSX assertion for this accepted/new-20% calculation proving the selected right kind/source/version/date and `13000000` cents appear in “Основания”, and that the `unknown` empty-loop fallback is absent. This must be derived from the saved calculation, not reconstructed from current checklist facts.

The retained RED records are accepted for the projection and UI-label failures they demonstrate, but they do not demonstrate the required workbook propagation. Rebind the corrected test blob before implementation completion.

---

## Saved-basis workbook rereview — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Scope: `tests/Otiz/settlement_v2_rights_flow_001_test.php` only; previously reviewed browser-label blob unchanged
- SHA-256: `655cc7c74f2f824dc3cc9e58046c542b0367e2ea9bd0e7eba554d4f8f26d0ac9`
- Git blob: `a08c879eb0c5b9cb8859fd989cd0fd120f10d6c7`
- Reviewer: `/root/acceptance_review`

### Accepted portions

- The helper independently resolves the “Основания” worksheet through workbook relationships rather than assuming a sheet filename.
- The real HTTP draft export is constrained to the exact 11 newly selected item identities `[39,40,41,1,2,3,4,5,6,7,9]`; each row must be `progress`, use the source revision read from the saved calculation `input_json`, and retain recognition date `2026-09-03`.
- Basis rows sum to the independent `13000000`-cent B increment. A later historical export of the accepted neighboring calculation must equal the complete previously captured basis rows after attribution and normative-source corrections, preventing reconstruction from current facts and excluding the unknown fallback by exact identity comparison.
- The retained GREEN result is accepted; the earlier XML namespace/parser failure is not treated as a product failure.

### Remaining finding

1. **X06 numeric cell typing is not actually asserted.** `rightsWorkbookBasis()` reads the cell `t` attribute only to choose inline-string text versus `<v>`, then returns plain values. The subsequent decimal regex therefore passes both a numeric cell and an `inlineStr` containing the same digits. Return the cell type (or otherwise retain it) and assert the amount cell is numeric—no `t="inlineStr"`/shared-string type—before applying the exact decimal/cents check. This closes the explicit typed-XLSX requirement without changing any money expectation.

The saved-basis propagation finding is otherwise resolved. Rebind the single corrected test blob for the final supplemental decision.

---

## Saved-basis workbook final supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Otiz/settlement_v2_rights_flow_001_test.php` only
- SHA-256: `d6266b8843ed372efed37c520c1feffaf28443ecc32054db435fb5848de2285f`
- Git blob: `7c6d75413ed3b66934a957b8425fd358ef7ed3b8`
- Reviewer: `/root/acceptance_review`

No findings. The independent OOXML reader now retains every cell's `t` attribute in `_types`. The “Основания” amount column explicitly accepts only the two numeric encodings used by OOXML here—omitted `t` or `t="n"`—and therefore rejects `inlineStr` and shared-string numeric lookalikes before applying the exact decimal grammar and `13000000`-cent sum oracle. Historical row equality also retains these types.

This resolves the sole blocker from the saved-basis workbook rereview. The previously accepted exact identities, saved source revisions, recognition dates, basis total, historical immutability, and browser explanation labels remain unchanged. Approval is limited to this exact test blob; final whole-candidate review still requires the frozen exact source.

---

## Mandatory-reason public-seam disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Otiz/settlement_v2_editing_001_test.php` only
- SHA-256: `30a0c29cc1e8ed3e5ae1ea26a186663df2102814a4db973256267742f023277d`
- Git blob: `f5395def25a41a8a201c7a373353a61f8f417f61`
- Reviewer: `/root/acceptance_review`

No findings. The regression exercises the public application owner rather than relying on HTML `required`: a personal deduction with whitespace-only reason and a dismissed-recipient `do_not_pay` decision with empty reason must each reject as `REASON_REQUIRED`. The pre/post fingerprint covers calculation revisions, deductions, payment decisions, events, operation receipts, entitlement claims, and recipient obligations, so either rejection writing a partial financial fact, audit event, receipt, or revision fails the test.

This is the existing H01/P02 mandatory-reason rule, not a new financial behavior. The captured failure is a genuine intended RED at the approved seam. Approval is limited to this exact test blob; implementation and the frozen full candidate remain subject to final exact-source verification and Gate 5 review.

---

## Complete seven-finding correction Gate 3 review — 2026-09-27

- Verdict: `APPROVED`
- Candidate source: `fc8227db0aa05ef1182d27195def2ed6516b83fa2cf8d3b2921ead4745f9f05d`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `324ed3ee31486265ff047fa15c7eb1fe5df8d7d55a36fd17c73a30ae232645eb`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

No blocking findings remain in the complete correction test candidate. The 14 bound checks (`10 GREEN`, `4 INTENDED_RED`) are accepted at the exact source above.

### Final-review findings coverage

1. Included-object diagnostics are exercised through a real native missing-plan issue saved in the draft and a relationship-resolved HTTP XLSX assertion requiring `DEADLINE_EVIDENCE_ABSENT` plus object identity in “Контроль”.
2. Raw server-rendered HTML—not post-JavaScript DOM—must contain exactly one delete/cancel/replace mutation form inside the corresponding closed dialog, with a visible required reason and no fabricated hidden reason. Existing browser Escape/no-facts assertions remain applicable.
3. Zero deduction preview and save both require `INVALID_MONEY`; the full financial/audit/receipt fingerprint must remain byte-equivalent.
4. Register rows independently require localized “Черновик/Утверждён/Отменён”, state-aware preliminary versus approved money labels, and absence of raw lifecycle codes.
5. Main and basis dates must be numeric OOXML serial dates that independently round-trip to the saved ISO day. The workbook must include a Normal/default style and an auto-filter covering the full data body, while retaining the prior amount typing, formatting, saved-basis and historical immutability assertions.
6. Direct owner and authenticated HTTP payment commands reject empty, impossible, non-padded, timestamp and non-date inputs as `INVALID_PAYMENT_DATE` with no facts; the HTTP fixture establishes the matching legacy admission snapshot first, so these failures cannot be falsely satisfied by an unrelated admission rejection. Future-date behavior remains separately covered.
7. The canonical three-recipient path uses a real selected crew, original/opening flow and HTTP checklist completion. It independently observes the `67/67/66` basis-point contribution split and requires draft, accepted DB obligations and real HTTP XLSX to equal `435500/435500/429000`, rejecting the current money-equal `433334/433333/433333` behavior.

The optional `InspectionFixture::open()` installer parameter preserves its prior default, so existing fixtures retain their original semantics. The added specification text records existing canonical rules and final-review findings; it does not introduce a new money or admission policy. The two nonblocking Gate 5 maintainability notes are intentionally not expanded into an unrelated architecture refactor.

Approval is for the executable correction candidate only. Implementation, complete exact-source GREEN evidence, rebinding of the frozen full candidate, CI, and independent Gate 5 rereview remain required.

---

## Final workbook and export-mode correction Gate 3 review — 2026-09-27

- Verdict: `APPROVED`
- Candidate source: `1ecf9145f46f0cd75a45267810a5a5eb433fa3c0c4fd6699e6e0fd4d4edd7465`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `a14e1a469ee1c68e490b2324150573d2bb401d6e3cce4c6eeade0cab371f1b16`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

No blocking findings remain in this complete correction candidate. The 14 bound checks (`10 GREEN`, `4 INTENDED_RED`) are accepted at the exact source above.

### Coverage accepted

- A real native included-object issue must appear exactly once in HTTP XLSX “Контроль”. The unit oracle also proves that the same issue supplied through object and normalized collections deduplicates for one object while the same code on a different object remains a distinct row with both identities preserved.
- The first visible row of “Расчёт ОТиЗ” must carry the exact draft/history/payment mode label and span `A1:U1`. The field header remains the unchanged 21-column second row; freeze and print titles cover rows 1–2; auto-filter starts at row 2 and includes the full body. Existing exact cents, coefficient, recipient, metadata, basis and appendix assertions are shifted only by the banner row and remain unchanged.
- Payment-mode export now has an explicit real HTTP state matrix: positive accepted-unpaid returns XLSX; already-paid and accepted-zero return `409 PAYMENT_EXPORT_STATE_INVALID`; both retain historical XLSX access and all reads are fact-neutral. A zero obligation visibly says “Суммы к выплате нет” and exposes neither payment button nor payment-mode link.
- The zero-acceptance cross-actor metadata check correctly uses historical export because no payable obligation exists; creator/accepter evidence remains independently verified.

The export-mode restriction is the original current-payment versus historical-read contract, not a new financial rule. Prior seven-finding corrections and all earlier Gate 3 dispositions remain intact. Approval is for tests/specification only; implementation, full exact-source GREEN rebinding, CI, and Gate 5 rereview remain required.

---

## Payment-versus-cancel/replacement race supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Otiz/settlement_v2_concurrency_001_test.php` only
- SHA-256: `4a4d37d78ee8ae586018e1199c26f765b2febb0669ec339edad43edf9ec93f86`
- Git blob: `f2b10451955320e4168789101bce8e99b1f7b474`
- Reviewer: `/root/acceptance_review`

No findings. This is a real Yii/MariaDB REPEATABLE READ interleaving at the public application seam, not the withdrawn review3 T08 assertion.

The second actor's transaction first establishes its consistent-read snapshot through the candidate read. Its custom Yii command invokes the hook immediately before executing the settlement-object lock statement. The independent first connection then commits the full payment command. Only after that commit does actor B acquire the object lock and continue cancellation or replacement acceptance. The hook is one-shot and its exact invocation count is asserted, so neither path can pass without the intended schedule.

For both commands the business invariant is correct: a payment committed before the conflicting object lock makes the calculation paid and therefore immutable to ordinary cancellation/replacement. Expected `PAID_CALCULATION` is coupled to external-audit assertions that the original remains `accepted`, its active claim and exact `100000`-cent payment remain, actor B writes no operation receipt, and the replacement preview remains `draft`. These observations distinguish stale-snapshot corruption from setup failure or a harmless serialization order.

The captured result is a genuine `INTENDED_RED`: both current paths incorrectly cancel the paid original. Approval is limited to this exact concurrency test blob; production correction, complete exact-source GREEN evidence, and whole-candidate Gate 5 remain required.

---

## Zero-obligation accepted lifecycle supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Scope: `tests/Yii2/yii2_otiz_v2_acceptance_001_test.php` only
- SHA-256: `1322ca5e83365ea9c784fe85d7abf7479bce981991faefe4790da58c69e27168`
- Git blob: `2273cbc832e5da21085f7f40a9cbf2c835e165d8`
- Reviewer: `/root/acceptance_review`

No findings. The real HTTP zero-obligation accepted case now pins both sides of the contract:

- it remains explicit that there is no payable amount, no “Отметить выплату” action, and no current-payment export link, while historical export remains available and reads remain fact-neutral;
- it retains exactly one cancel trigger and one replacement trigger, each pointing to the exact calculation's real POST form inside the corresponding closed server-rendered confirmation dialog.

The existing accepted-unpaid SSR helper independently proves those shared dialog forms have visible required reasons and no fabricated hidden reason. Together these assertions prevent the implementation from suppressing payment by erasing the true accepted lifecycle state. This is the inherited M10 plus R01/R02 behavior, not a new policy.

The captured missing-cancel result is a genuine `INTENDED_RED`. Approval is limited to this exact test blob; production correction, exact-source GREEN rebinding, and final Gate 5 rereview remain required.

---

## Full-CI three-failure correction supplemental disposition — 2026-09-27

- Verdict: `APPROVED`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

No blocking findings remain in the complete three-failure correction candidate.

### Exact unchanged recovery tests

- `tests/Yii2/yii2_otiz_settlement_form_recovery_browser_001_test.php`: SHA-256 `39778c28544e708b178d3f85fa593efb509c38295924f3ad883ab153fd5c5cd7`, Git blob `0a87c524d707ef2aca7bfee941a59da824261326`
- `tests/Yii2/otiz_settlement_v2_form_recovery_browser.mjs`: SHA-256 `d54220be0ad4e713cfedf5c6c920d4f33893493674baf6fabfb0676a7428816d`, Git blob `29a9a68994f3fe8016d4cbeaa151fb3c6a1df179`

The previously approved public recovery expectation is unchanged. Restoring the two pre-existing `data-recovery-deduction` / `data-recovery-decision` hooks is the bounded production correction; it must make the retained real browser journey reach and verify the 422 recovery behavior rather than alter the test.

### Exact corrected test blobs

- `tests/Support/yii2_production_web_cutover_contract.php`: SHA-256 `c301f5b9123f404adb733510fea6ca4bd9ab4e46b82ab44d1806db4aa446b098`, Git blob `8aabbeca1c4e711048610965aba79af902097622`
- `tests/Yii2/yii2_otiz_settlement_001_test.php`: SHA-256 `9ef3517beba91d67cc1b74cd857dd8642e3bf25340423ec841d8b2ed43ddeb3a`, Git blob `8d81aacb3128ee3d9525718ad8c35eaf82297b0a`

The cutover contract changes only the two literal asset hashes, and both independently equal the reviewed `pilot.css` and `otiz.js` bytes. MIME/cache/security expectations and every other asset remain unchanged.

The historical OTIZ compatibility test removes only the obsolete third visible `/history` tab requirement. It strengthens the replacement contract by requiring exactly two OTIZ navigation links and a real GET `/pilot/otiz/history` → HTTP 303 `/pilot/otiz/payments?filter=history`. Historical snapshot content, XLSX readability, retired legacy-writer rejection, guest authorization, and no-facts assertions remain intact. Both corrected tests are GREEN in the retained focused records.

This is not expectation weakening and does not reuse the withdrawn T08 hypothesis. Approval covers the complete known CI failure inventory: the two test-contract synchronizations plus the already approved recovery production-hook restoration. After implementation, run the focused recovery/main-browser/HTTP checks, rebind the exact full source, and complete final review/CI handling without treating this approval as merge or deployment authorization.

---

## Owner-bounded U01/U02 Gate 3 review — 2026-09-27

- Verdict: `APPROVED`
- Candidate source: `9504464353001ebc0c1d94280fa2d2f45ee156459d0cbb223d28c0a9c70bf9e3`
- Snapshot: base `0b4c0aaf5d82129e16a7d487c20a8d5dcb537bd3`, patch SHA-256 `b8a421451a61ecda325fc3b5150f19c0cd6bd81cf1ac384419255ed60bc29a2c`
- Reviewer: `/root/acceptance_review`
- Gate: `gate3`

No blocking findings remain in the complete bounded U01/U02 candidate. The 17 mapped checks (`14 GREEN`, `3 INTENDED_RED`) are accepted at the exact source above.

### U01 — preview versus saved approval

- Real HTTP deduction preview proves the separate preview changes `2000000→1900000` while the main basis, acceptance confirmation, and saved A/B recipients remain `2000000` and `1200000/800000`; preview writes no facts.
- Real HTTP `do_not_pay` preview proves redistribution is confined to the preview block and does not replace the saved recipient table or confirmation even when total money is unchanged.
- Unsaved previews can be followed by acceptance, and the resulting owner read/DB obligations match only the saved `2000000` revision.
- Explicit deduction save advances revision exactly once and persists `1900000` with A/B `1140000/760000`; old revision acceptance returns 409 with a full facts fingerprint unchanged, while the fresh revision accepts.
- The real browser repeats both unsaved and saved paths, inspects the actual confirmation dialog and recipient table, submits stale and fresh approval requests, and the PHP wrapper independently checks persisted obligations, deduction count, absence of preview decision facts, and screenshots.

### U02 — documentary allocation freshness

- The fixture exercises the real selection/original/opening/checklist HTTP path for all 41 items and independently observes canonical 85% contributions `5100/3400` before accepting that neighboring checklist calculation.
- A real PTO HTTP fact creates a documentary-only `6500000`-cent draft allocated `3900000/2600000`. Ten real attribution corrections move 34 percentage points and the fresh builder independently yields contribution `1700/6800` and money `1300000/5200000`, while the document rows remain byte-equivalent.
- Acceptance of the stale documentary draft must return `STALE_CALCULATION` without financial facts; refresh then accepts the fresh split and DB/history XLSX reproduce it.
- A foreign-object change and an attribution event outside the selected report cutoff do not invalidate the accepted basis. A later in-cut correction of used work blocks both owner and real HTTP payment export/payment with `SNAPSHOT_REPLACEMENT_REQUIRED`, while historical XLSX remains the saved 20/80 result and failures are fact-neutral.
- Explicit replacement captures the restored current 60/40 basis, preserves the neighboring accepted checklist snapshot, restores payment eligibility, and remains independently subject to #257 `COMPOSITION_MISMATCH` admission.

The expectations implement only the owner's current U01/U02 scope and existing freshness/revision/admission contracts. They do not reopen previously approved money, UI, workbook, recovery, CI, or architecture work. Approval is for the executable tests/specification only; production implementation, exact-source GREEN rebinding, final review, and the new full CI remain required.
