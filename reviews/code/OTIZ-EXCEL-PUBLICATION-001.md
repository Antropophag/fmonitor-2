# Code review: OTIZ-EXCEL-PUBLICATION-001

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Implementation authorship: separate executor for production code; root for the approved specification, primary tests, and bounded adjacent fixture/verification-registration updates
- Reviewed source: base `d6daceea01cd5e4a6dd20b56921f767224cd1fb5` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T201635Z-dc7a235762/snapshot`, patch SHA-256 `0b5302c3f7ea61bfc3ac1121a8bbed832a778187dac1284c6dda2d7884c952f1`; restored and checked outside the working checkout at `/private/tmp/fmonitor-issue66-publication-g5.szAAwI/checkout`
- Candidate source: `76539fec0ff2f9314a33e437cc5827abcefaa8fc65ea331665524daad8e24ecd`; executable source: `011b284d4a8eb03ee4a5ada7663c104869c848fd5ac4d11e043c024a83f9a541`
- Contract: `specs/OTIZ-EXCEL-PUBLICATION-001.md`, SHA-256 `c385e5e990f2317e5ddc3f96e6fbbdf2e2a95f737f438bf2b36f45cdda44eca3`
- Approved Gate 3 inputs: core test SHA-256 `cd020fced5e8147eb00e561ab20260ca72564cb2fbea94bb13b207aa0cf8ecf1`; browser wrapper/script SHA-256 `5ba463af8a4747cd1521b889acdccb0eebc46b76f8390db5106eb849dbeaf251` / `8ee7517e1e038017eba78cc609fb93b2d65781b03856f5632c1b6abb7a84335a`
- Scope: the existing publication, acceptance, settlement, Yii and XLSX lifecycle wired to the approved V2 calculation. Entitlements, draft supersession, cross-period policy, new reversal policy, rapid-pilot compatibility, the certificate implementation already reviewed separately, and the separately reviewed v26 schema frontier are outside this verdict.
- Verdict: `APPROVED`

## Findings

None.

## Moscow-midnight browser fixture Gate 5 — 2026-09-15

- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T213231Z-5cb47b19b0/package.json`, prepared against base `408bada41f2d25049e946f0801cd5da7f5c5a60f`
- Candidate source: `65f70187104700c090f90a8f9e87dc4f46277e393bd1bc84dbebe80a86e49d2c`; executable source: `f606bc3c0a07919d66aae560aa0220357cc2f980a798ae3783b2f2656163c7f7`
- Scope: root-authored changes only to `tests/Yii2/excel_publication_browser_001_test.php` and `tests/Yii2/excel_publication_browser.mjs`; no production change
- Verdict: `APPROVED`

### Findings

None.

The test now derives a Moscow-relative report cutoff that includes the real payment date and remains valid if execution crosses midnight. It preserves the exact two-day Kss 9800 calculation, 1274000 first pool, confirmed-payment recurrence to 25480, certificate source/PDF assertions, trace values, PTO absence, XLSX evidence and public browser lifecycle. It removes only the artificial checklist server-time rewrite and adds a direct audit that the payment date is within the cutoff.

`php tests/Yii2/excel_publication_browser_001_test.php` is exact-source `GREEN` in record `1789421431877653000-eaee1fb80eac420ca37e3a7597df923e`. The second CI inventory reports both integration shards, fast/unit and governance green; its e2e failure is this pre-correction fixed-date fixture, with verify consequentially failing and Quality Graph log retrieval returning API 404. Those CI observations establish the correction's trigger but are not relabeled as final-source GREEN.

### Required changes

None.

## CI fixture correction final-review supplement — 2026-09-14

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Reviewed source: base `b1d6b5178126090e6e2ee3d6eb090100547d3c47` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T204829Z-31a636cb46/snapshot`, restored at `/private/tmp/fmonitor-issue66-ci-review.sPkmNU/checkout`
- Candidate source: `bb4306f2748aac3f8c5fe88898a5a07713717503bea26174d158abf9576cee35`; executable source: `514b31eccae83b2157b16059e5538c7b19b0a6afcc3df07a57f96dc8ef3e1446`
- Source findings: none.
- Final Gate 5 status: `PENDING_EVIDENCE`

The bounded corrections faithfully repair setup drift exposed by the complete first-CI failure inventory. They do not change production code, relax product outcomes, erase historical V1 coverage, or add rapid/demo compatibility. The current native Yii publication and settlement browser corrections have exact GREEN records `1789418762067842000-1c52d70579b54137b709536db845ff33` and `1789418763208114000-ea2030db9afa46229cf023a088172a0d`. Final approval is reserved until the requested runtime-focused records arrive for this frozen source lineage.

### Required changes

None at source review. Supply the pending bounded runtime evidence for final disposition.

## CI fixture correction final evidence disposition — 2026-09-14

- Final candidate source: `da76d7ab12d50961828b1766d1ad27d892b59a64abb4c6226de655b1acc330ec`; executable source: `c2722b8f263ea92689370f6432408980f2f1ba1ddc53cc5e31f1f152d9941b4c`.
- Prior `PENDING_EVIDENCE` disposition: resolved by fresh exact-source bounded records after the production FK-name correction.
- Final Gate 5 verdict for the bounded publication/fixture correction scope: `APPROVED`.

### Findings

None.

Exact-source GREEN evidence:

- Production native browser: `1789419051785523000-8b377658a87d42c2ae349c15ce5ee992`.
- Historical certificate recovery: `1789419052946746000-0eb8b5af72a542338de1d5e8a08b8196`.
- Runtime settlement compatibility: `1789419054129608000-07aae90269734864af3e8f3b196ceabf`.
- Publication core: `1789419066465774000-725f4ec192674ca89fe3a24a4de023b3`.
- Certificate HTTP fixture replacement: `1789419066470843000-b36fd3c2c41d49c893ca1c3953aafee5`.
- Full prefix-25 workforce fixture: `1789419066480706000-28c8d3529bf94386aec44a7e5cc2913b`.
- Architecture check: `1789419066480727000-a008e99083644249befa070f70cb35dd`.

All records report exit 0 and `GREEN` with matching candidate and executable digests. This approval covers only the reviewed publication and CI-fixture corrections. The separately owned added prefix-25 schema-test expectation remains outside this review, as do the three known legacy CI failures and overall merge admission.

### Required changes

None.

The implementation preserves the existing transaction and replay seams while changing newly built snapshots to `premium-calculation-v2-excel`. Publication reads signed paid closures through the report date, retains their ordered source envelope in the content identity, keeps discipline/deadline closure amounts outside `paidBefore`, and persists the complete calculator operands, seven-step trace, exclusions, allocation version and deterministic allocations. Blocked inputs remain nonpayable and snapshots with blockers remain unacceptably draft. The acceptance and payment seams reject V1 snapshots with `STALE_CALCULATION`; operation receipts are checked before settlement work, so an already recorded operation still replays unchanged.

The Yii integration derives certificate provenance from the accepted request and uploaded bytes, maps stale acceptance and payment to HTTP 409 with the new-calculation instruction, renders the V2 trace and certificate evidence with escaped values, links the selected PDF revision, and exports the corresponding money, deadline, PTO and provenance rows. The native browser journey verifies the mobile return flow and persisted label, locator and PDF hash. The inherited OTIZ layout remains outside this bounded behavioral change.

The root-authored adjacent test changes are appropriate migration maintenance. Synthetic publication inputs now carry explicit source evidence; the changed empty-publication golden is independently tied to the literal V2 header/date/empty identity; current settlement and Yii command fixtures use V2 while historical snapshot 305 remains V1; and the eight new tests are registered without removing prior inventory or widening the policy boundary beyond the certificate module. These changes do not weaken the retained owner, concurrency, replay, historical-read or export assertions.

## Verification evidence

- `php tests/Otiz/excel_publication_001_test.php`: exact-source `GREEN`, record `1789416959028583000-dfa30c9b80ab46009317e75a6a83a4c0`.
- `php tests/Yii2/excel_publication_browser_001_test.php`: exact-source `GREEN`, record `1789416960237496000-293234e6b1044a4cb8b92d8ed8eedaf9`.
- `make architecture-check`: exact-source `GREEN`, record `1789416985558315000-f5c5e2648e364e408b8816151743d829`.
- The verification owner reported the final eight bounded checks and the five retained adjacent regression checks green at the same candidate/executable source. The repository-mandated local full suite was not run.

## Required changes

None.
