# OTIZ settlement v2 corrections — final implementation review

- Verdict: `APPROVED`
- Exact source: `a2714f687f828f717c335e7844040bf7f6fa13625e1d497f1b647e6b964da46f`
- Plan: `4f9f6a9591fa1236675501b3c9b315335a8e5340ecc83dd687e5fd17f629b5c1`
- Reviewer: `/root/otiz_correction_gate3`
- Recorded by harness: `2026-09-27T09:25:52Z`

## Findings

No blocking findings remain.

The final review verified F01–F06 end to end: literal Kss money propagation including zero payout; stable cumulative entitlement frontiers and serialized races; restart-safe v37 backfill/recovery; complete neutral replacement drafts with atomic acceptance and paid/dependent safeguards; append-only #257 admission generations shared by acceptance, payment export and payment; and relationship-resolved XLSX fields, aggregation, types, styles, layout and injection safety.

All eleven focused records are GREEN at the exact source. This approval does not replace the planner-selected exact-source CI obligation or authorize merge/deployment.

Nonblocking maintainability notes: the settlement owner and migration remain dense/minified, and mutable operation context plus replacement revision markers deserve future refactoring.

---

## Full PR final review — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Exact source: `d7295c4e5ba8d30c99f1e5ba61d3c55ed77a9ac03f5d1e2ff521b643c73c7670`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `cc56360c0e503a9be384eca7f7c8d9da0fa6ba3b12b33e4dc96e72861edddffa`
- Reviewer: `/root/acceptance_review`
- Gate: `final`

### Findings

1. **P1 — included-object issues disappear from XLSX.** `app/YiiRuntime/Controllers/OtizSettlementV2Controller.php:22` passes only `excludedObjects` as workbook `issues`, and `app/Otiz/OtizSettlementV2Workbook.php:9-11` populates “Контроль” only from that top-level array. Saved `objects[*].issues` for included objects—unknown/incomplete native inputs and other saved basis diagnostics—are silently omitted. This violates `specs/OTIZ-SETTLEMENT-V2-001.md:23,46` and X01/X04. Flatten saved included-object issues with object identity into “Контроль” in addition to exclusions, and add an HTTP XLSX regression proving they survive from the saved snapshot.
2. **P1 — destructive lifecycle forms still ship fixed hidden reasons and immediate submit actions.** `app/YiiRuntime/Views/otiz-calculation-v2.php:8,24` renders delete/cancel/replace as ordinary submit forms with `reason="Удалён/Отмена/Замена пользователем"`. `app/YiiRuntime/Assets/otiz.js:34` removes them and constructs dialogs only after JavaScript runs. Before initialization, or without JavaScript, one click submits a destructive command with a fabricated reason. This contradicts `specs/OTIZ-SETTLEMENT-V2-001.md:57` (“видимой обязательной причиной, не фиксированным hidden reason”). Render the real dialog/reason controls server-side or make the initial controls inert with no hidden reason; preserve explicit reason, calculation scope, Escape neutrality, and server validation in both paths.
3. **P2 — zero-value deductions persist.** `app/Otiz/MariaDbOtizSettlementV2.php:18,21` rejects only negative amounts, although H01 requires a positive exact amount. Both save and preview accept zero; save writes an audited deduction and revision with no monetary effect. Reject `amount <= 0` with a stable domain reason before mutation and add zero-value preview/save no-facts regressions.
4. **P2 — the unified register presents draft estimates as recognized accrual and exposes raw state codes.** `app/YiiRuntime/Views/otiz-v2-register.php:5` labels every projection total “Начислено”, including drafts, and renders `draft|accepted|cancelled` directly. The owner contract requires drafts to remain visibly preliminary/non-payment and user labels “Черновик/Утверждён/Отменён”. Use state-aware money wording (for example “Предварительно к выплате” for drafts and “Утверждено к выплате” for accepted rows) and localized lifecycle labels; add row-level assertions for each state.
5. **P2 — workbook dates are formatted strings, not typed dates.** `app/Otiz/OtizSettlementV2Workbook.php:14-15` wraps dates as strings with a date style, so the writer emits `t="inlineStr"`. Independent inspection of the exact-source `payment.xlsx` confirms main `A2='2026-09-26'`, `data_type='s'`. This violates the XLSX requirement that dates be typed. Emit Excel serial numbers (or another valid numeric date representation) with the date format and assert both type and displayed value through an independent reader. The openpyxl “no default style” warning should also be corrected or explicitly justified while touching styles.
6. **P2 — payment date is not validated as a real calendar date at the public seam.** `app/Otiz/MariaDbOtizSettlementV2.php:28` performs only lexicographic future comparison. Empty or malformed direct requests reach the DATE insert and depend on database/sql-mode failure rather than a stable domain rejection; malformed strings can also compare incorrectly. Parse exact `Y-m-d`, reject invalid/empty values before admission writes with a stable error, retain nonfuture enforcement, and add no-facts direct-owner/HTTP tests.
7. **P1 — checklist recipient weights bypass the canonical contribution split.** `specs/OTIZ-EXCEL-INPUTS-001.md:18,20` requires each item's basis points to be split among attributed installers with integer remainder in binary tab order and the resulting positive contribution to be the team weight. `app/Otiz/MariaDbNativePremiumInputsProgress.php` correctly produces that evidence (the existing adversarial oracle is 200 bp across three identities → `67/67/66`), but `app/Otiz/MariaDbOtizSettlementV2DraftBuilder.php:13-14` ignores it and calls `equal(installerTabIds, gross)` to split money equally. For a 1,300,000-cent work this yields `433334/433333/433333` instead of contribution-proportional `435500/435500/429000`, changing KTU/recipients while conserving only the total. Preserve per-work contribution weights in `works`, build `recipientWeights` from those canonical weights, and add a real three-recipient odd-remainder regression through saved obligations/XLSX. Do not infer correctness from the existing two-person fixtures.

### Standards axis (nonblocking after the product findings above)

1. **MEDIUM — duplicated preview/save rules have already drifted.** `app/Otiz/MariaDbOtizSettlementV2.php:17-21` duplicates decision/deduction validation and projection construction between save and preview; save requires reasons while preview does not. Extract shared pure proposal/validation functions so preview and commit cannot diverge. This is Duplicated Code/Divergent Change and conflicts with the cohesive application-operation target in `docs/architecture/audit-and-target-2026-09-07.md:58`.
2. **MEDIUM — HTTP owns settlement read-model logic.** `app/YiiRuntime/Controllers/OtizSettlementV2Controller.php:24,26` reconstructs recipient keys, before/after money rows, aggregation, filtering, sorting, and pagination from domain-shaped arrays. Move this to an OTIZ query/read-model service and return an explicit view model. This is Feature Envy/Data Clump/Primitive Obsession and weakens the documented HTTP → application/read-model boundary.

### Evidence and prior dispositions

All 14 mapped acceptance checks are GREEN at the exact source above. The same-source native Excel inputs, main navigation, runtime boundary, and `make architecture-check` records are also GREEN. The architecture check includes seven rules and the Pilot HTTP global qualification. The prior Gate 3 test blobs and every supplemental disposition remain valid on this candidate; their behaviors are present in the final package and no expectation was weakened.

The review independently reconfirmed the corrected money/right attribution, source freshness, ordinary and replacement flows, higher/lower norm correction, half-up rounding, neighboring-calculation isolation, admission fail-closed behavior, payment/export invalidation, erroneous-mark reversal, mandatory reasons, saved workbook basis, and real browser flows. Review3 T08 remains excluded as expressly withdrawn.

The #257 producer and live enforcement #107 remain honestly `UNKNOWN`: missing admission evidence remains `PRODUCER_UNKNOWN`, not allow/GREEN, and this review does not claim a full producer UI or live enforcement. These limitations do not replace the seven in-scope findings above.

Gate 5 does not pass. Correct the complete finding set, rebind the full frozen candidate, rerun the affected focused checks, and return the correction delta for independent final rereview. Exact-source CI remains a separate mandatory obligation and this verdict does not authorize merge or deployment.

---

## Seven-finding correction final rereview — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Exact source: `eca1e74250f713d0584c864bcffa2c2d0a5034fd7c903f284e74e9a6b3e75de1`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `6ebb15103c79e809eed0b3219b02ed8fac820a3dd46daa39319592217f7d805b`
- Reviewer: `/root/acceptance_review`
- Gate: `final`

### Findings

1. **P2 — included-object diagnostics are duplicated in XLSX “Контроль”.** `app/Otiz/OtizSettlementV2Workbook.php:9` now appends every `objects[*].issues` row directly. `app/YiiRuntime/Controllers/OtizSettlementV2Controller.php:22,28` also flattens those same object issues into top-level `issues`, and workbook line 11 appends the top-level list again. A saved `DEADLINE_EVIDENCE_ABSENT` therefore appears twice. Choose one ownership path: either let the workbook consume object issues plus top-level exclusions, or pass one normalized/deduplicated issue collection; retain object identity and add an exact row-count regression, not only `str_contains`.
2. **P2 — draft/non-payment mode is still absent from the main printable sheet header.** The original Excel requirement says a draft must be labeled “Черновик. Не основание выплаты” in the filename, header, and metadata. `app/Otiz/OtizSettlementV2Workbook.php:6,8,12` uses the label in metadata and the filename, but the “Расчёт ОТиЗ” sheet begins directly with the 21 field headers and contains no visible draft/non-payment banner. Add a merged or otherwise visible first-sheet title/banner carrying the exact mode, adjust print titles/area and filters accordingly, and independently assert it in the draft workbook. Do not rely on the metadata sheet as the required main header.

### Disposition of the seven returned findings

The prior seven Gate 5 findings are otherwise fixed:

- Included-object issues now reach “Контроль” with identity, subject to deduplication finding 1 above.
- Delete/cancel/replace forms are server-rendered inside closed dialogs with visible required reasons and no fabricated hidden reason; JavaScript only opens/closes them.
- Zero deductions and malformed payment dates fail at the owner seam without facts.
- Register state/money labels are localized and state-aware.
- Main/basis workbook dates are numeric Excel dates, Normal style exists, and auto-filter covers the body. Independent openpyxl inspection of the exact artifact reports real date types, no style warning, and `A1:U5`.
- Checklist allocation now first derives canonical per-work basis-point weights (`67/67/66`) and then proportionally distributes money, with exact DB/XLSX `435500/435500/429000` evidence.

All 14 mapped checks and the four additional native-input/navigation/runtime/architecture checks are GREEN at this exact source. The approved complete Gate 3 test candidate remains unchanged and valid. The two prior nonblocking maintainability notes remain nonblocking and were correctly not expanded into a framework refactor.

Gate 5 remains failed only for the two workbook conformance findings above. Rebind and return their combined correction; exact-source CI and merge/deployment authorization remain separate.

---

## Workbook/export/race correction final rereview — 2026-09-27

- Verdict: `CHANGES_REQUESTED`
- Exact source: `08872c2cb1c73c09b39cabeb2744c110e17574e365d3bec73c86b70f0709bd04`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `6c1e405a819fb1d46993abd5e82bbac074f13950a57effeddedcc00b2e9f2ca9`
- Reviewer: `/root/acceptance_review`
- Gate: `final`

### Finding

1. **P2 — accepted zero-obligation calculations lose their valid cancel/replace lifecycle in the UI.** `app/YiiRuntime/Views/otiz-calculation-v2.php:3-4` computes `$zeroAccepted=$accepted&&!$hasPayable&&$paymentId===null` and then assigns `$accepted=false`. This correctly suppresses payment-mode export and “Отметить выплату”, but it also suppresses the accepted lifecycle block at line 26, including “Отменить расчёт” and “Заменить расчёт”. A zero result from full proven reduction is still an accepted, unpaid calculation with recognized claims; the owner permits ordinary cancellation/replacement when there is no payment/dependency, and the general R01/R02 lifecycle has no zero-obligation exception. Keep the true accepted state, gate only payment controls/export on `$hasPayable`, show “Суммы к выплате нет”, and retain cancel/replace. Add a raw-HTTP/browser assertion that the zero accepted result has cancel/replace but no payment button/payment-mode link.

### Corrected findings disposition

The two prior workbook findings and the subsequently exposed payment-export/race risks are fixed:

- “Контроль” deduplicates by object identity plus issue code, preserves the same code for different objects, and exact row counts are covered.
- Main workbook mode banners are visible/merged in row 1; row 2 retains all 21 headers; freeze/print titles and filters use the corrected rows without changing cents or coefficient bindings.
- Positive accepted-unpaid payment export remains available, while paid and accepted-zero payment mode reject with `PAYMENT_EXPORT_STATE_INVALID`; historical exports remain available and reads are fact-neutral.
- Cancellation/replacement payment guards use locking current reads. The approved deterministic MariaDB RR race is GREEN: a concurrently committed payment preserves the accepted original/claim/payment, writes no losing receipt, and leaves replacement draft intact.
- Erroneous-mark reversals remain the only reversal kind that can reopen payment/correction paths; genuine paid history remains immutable.

All 14 mapped checks and four additional exact-source checks are GREEN, including browser artifacts, native inputs, main navigation, runtime boundary, and architecture/HTTP qualification. Approved Gate 3 tests remained unchanged. The external #257 producer and live enforcement #107 remain honestly `UNKNOWN`, not silently allowed or claimed complete.

Gate 5 remains failed only for the zero-obligation lifecycle presentation finding above. Correct that bounded view/test delta, rebind, and return for final rereview. Exact-source CI and merge/deployment authorization remain separate.

---

## Final zero-obligation lifecycle rereview — 2026-09-27

- Verdict: `APPROVED`
- Exact source: `0f2c91038ea83778bb6ca5073eade48bc621bac3ef238b0d712a53027b6647f4`
- Snapshot: base `5dd42b8d03faa9b8d3d01906c2234b69220ec5e2`, patch SHA-256 `96063c4326a01715cef5fbbdb13b09cb8136f6a856b3875ecfee5bf1ba347785`
- Reviewer: `/root/acceptance_review`
- Gate: `final`

### Findings

No blocking findings remain.

The sole prior finding is fixed. An accepted zero-obligation calculation still displays “Суммы к выплате нет”, offers no payment-mode link or “Отметить выплату”, and exposes historical export; it now also retains the valid cancel and replacement triggers backed by exact closed server-rendered POST dialogs with required visible reasons. Adjacent states remain unchanged: drafts retain refresh/delete/accept flows, positive accepted-unpaid calculations retain payment plus cancel/replace, paid calculations retain only historical export and erroneous-mark reversal, and reversed erroneous marks return the obligation to the waiting/payment path.

The approved supplemental test blob is unchanged (`1322ca5e83365ea9c784fe85d7abf7479bce981991faefe4790da58c69e27168`; Git blob `2273cbc832e5da21085f7f40a9cbf2c835e165d8`). No production or test bytes were modified by this review; only the existing review records were appended/updated.

All 14 mapped checks and four additional checks are GREEN at this exact source. These include the real browser journey/artifacts, native Excel inputs, main navigation, runtime boundary, and `make architecture-check` with HTTP qualification. Every prior Gate 3 approval and final-review correction remains satisfied, including workbook issue deduplication/mode banners/typed dates, payment export eligibility, canonical 67/67/66 weights, and current-read payment race protection.

The external #257 producer and live enforcement #107 remain honestly `UNKNOWN`; missing evidence stays fail-closed and this approval does not claim those external integrations complete. Exact-source CI remains a separate mandatory publication gate. This final review authorizes neither merge nor deployment by itself.

---

## Complete CI-failure correction final review — 2026-09-27

- Verdict: `APPROVED`
- Exact source: `c5d69ad1b3a54a55276e558a1ce7a1fc679c99b6a83b6f59b662e669da786838`
- Snapshot: base `719c00cca1b3505ef60d8253c71776febbebadcc`, patch SHA-256 `761bd3bf10e89b3f3d102977778749ac07324182463ec65157bca2b6127adcb1`
- Reviewer: `/root/acceptance_review`
- Gate: `final`

### Findings

No blocking findings remain.

The complete three-failure CI correction is valid:

- Production restores only the pre-existing `data-recovery-decision` and `data-recovery-deduction` hooks. The unchanged real browser recovery test now reaches the form and independently proves the 422 error, preserved amount/reason/document, identical operation/revision tokens, reopened editor, and no financial facts.
- The cutover contract changes only the literal SHA-256 values for the reviewed `pilot.css` and `otiz.js` bytes. MIME types, cache policy, security expectations, routes, and every other pinned asset remain unchanged.
- Historical snapshot compatibility now requires exactly the agreed two visible OTIZ sections and independently follows the legacy `/pilot/otiz/history` route to its 303 unified-history destination. Historical content/XLSX, guest authorization, retired legacy-writer rejection, and no-write guarantees remain intact.

All 17 mapped checks plus four additional checks are GREEN at this exact source (`21/21`). This includes the three formerly failing consumers, both browser journeys/artifacts, native inputs, main navigation, runtime boundary, and `make architecture-check` with HTTP qualification. The prior failed CI run remains preserved as diagnosis; this review does not reinterpret it as flaky or skip any known failure.

All earlier final-review and Gate 3 dispositions remain satisfied. The external #257 producer and live enforcement #107 remain explicitly `UNKNOWN`, with missing evidence fail-closed. A new exact-source CI run is still required; this approval alone does not authorize merge, deployment, backfill, or working-data mutation.

No production or test bytes were altered by this review. Only the existing final-review Markdown and JSON records were updated.
