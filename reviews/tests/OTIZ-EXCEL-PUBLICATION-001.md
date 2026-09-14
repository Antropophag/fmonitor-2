# Test review: OTIZ-EXCEL-PUBLICATION-001

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Test author: root agent
- Reviewed source: base `79b3a4cecae558d74d781f353e967f03c036b921` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T194815Z-973ab35822/snapshot`, patch SHA-256 `8fb3fed6f448632ef3a173ca31a6474d21e4d71651ade3417d62b342793ff099`; restored and checked at `/private/tmp/fmonitor-issue66-publication-g3.zgRuJz/checkout`
- Agreed review scope / prior findings disposition (for rereview): first Gate 3 review of the owner-bounded existing publication/acceptance/settlement lifecycle wired to V2, including current Yii/browser/XLSX behavior. Discarded entitlement supersession, cross-period policy, new reversal policy and legacy rapid-pilot compatibility are explicitly excluded and were not treated as requirements. Numeric v25 migration adjacency belongs to the separately reviewed certificate slice.
- Specification: `specs/OTIZ-EXCEL-PUBLICATION-001.md`, SHA-256 `f2c5c429dd7bc0110390ea7d0bf9883fbfe75b160cd0166e2fb3576846fdf546`
- Public seam: existing `SnapshotPublication::buildAndPublish/accept/read/history`, `OtizSettlement::completeSnapshotPayments`, and current Yii certificate/publication/payment/export routes
- Red commands and intended failures: `php tests/Otiz/excel_publication_001_test.php` exits 255 at `RED_ASSERTION publication uses Excel v2` because the current snapshot still stores `premium-calculation-v1`; `php tests/Yii2/excel_publication_browser_001_test.php` exits 255 because the certificate form still exposes `sourceLabel/sourceLocator`. Both were verified in retained exact-source records `1789415268159998000-476b1cf0cd914fecb5f0c31a917ba916` and `1789415268159993000-f8a6658df8b24102b87479068612a7b0` at candidate source `1432d9f57be36827103630e5568f7af4fe697a942637347696d716413200409b` / executable source `3d1404042d7646d6b0c0ecc817d644dd1d8c69cfa0cb976e80f51b01c4c19907`.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — the persisted V2 calculation evidence contract is only partially asserted** (`specs/OTIZ-EXCEL-PUBLICATION-001.md:15-16`, `tests/Otiz/excel_publication_001_test.php:8,15`). The test proves `sourceEvidence`, selected amount fields and reconstructed closure evidence, but does not assert the retained `operandEvidence`, exact ordered `formulaTrace`, `allocationVersion`, or exclusion/blocker list in `inputs_json`. A builder could store the right totals and UI labels while omitting or rewriting these required audit facts. Add exact independently stated assertions over the complete V2 calculation payload for a representative ready object and a blocked object. The existing `OTIZ-SNAPSHOT-PUBLICATION-001` blocker regression proves atomic refusal/history behavior, but it does not prove the new V2 evidence payload.
2. **HIGH — signed confirmed payment recurrence is not exercised through publication** (`specs/OTIZ-EXCEL-PUBLICATION-001.md:14,18`, `tests/Otiz/excel_publication_001_test.php:9-16`). The new test creates only positive paid closures. Existing settlement tests establish that reversals append negative closure rows, and pure calculator tests establish signed arithmetic, but no test sends a positive and linked negative `paid_cents` history through the native publication reader into a new V2 snapshot. An integration that filters negative rows or sums absolute values would pass. Add a bounded reversal/current-ledger scenario and assert the next snapshot's `paidBefore`, pool/target, and full ordered signed closure evidence.
3. **HIGH — Yii-derived certificate provenance is not verified after removing the technical fields** (`specs/OTIZ-EXCEL-PUBLICATION-001.md:26`, `tests/Yii2/excel_publication_browser.mjs:9-13`, `tests/Yii2/excel_publication_browser_001_test.php:13-14`). The browser proves only that `sourceLabel/sourceLocator` inputs disappeared and that a generic certificate label becomes visible. It never checks the accepted command or persisted certificate for exact label `Справка о переносе срока`, locator `yii-upload/<requestId>`, and the uploaded PDF content hash. An adapter could drop, invent, or mis-bind provenance while the journey passes. Query the accepted certificate/current revision after upload and compare all three values to independently known request/PDF evidence.
4. **MEDIUM — the newly specified V2 UI/export and stale-version outcomes are under-observed** (`specs/OTIZ-EXCEL-PUBLICATION-001.md:22,28`, `tests/Yii2/excel_publication_browser.mjs:11-13`, `tests/Yii2/excel_publication_browser_001_test.php:14`, `tests/Support/HistoricalCalculationFixture.php:12-13`). The browser checks three trace labels and the XLSX for version, those labels and one deadline. It does not discriminate the displayed original deadline, chosen certificate/PDF revision, PTO absence/date, exact paid/remaining/penalty values, or corresponding XLSX source/hash values. The historical fixture proves direct-seam `STALE_CALCULATION`, but no test proves the required current Yii HTTP 409 and useful “новый расчёт” return message for accept/payment. Add focused HTTP/browser assertions for these observable fields and stale mapping. Existing retained authorization, CSRF, method, replay, atomicity, consistent-read and historical export regressions may continue to own those unchanged behaviors.

The independent recurrence amounts, cumulative targets, draft non-payment, report-date cutoff, no-change duplicate prevention, allocation pennies, discipline separation, content-hash source sensitivity, atomic object/receipt failures, historical immutability and v1 direct-seam stale denials are otherwise traceable. The existing publication suite supplies the inherited authorization/date/UUID, blockers, transaction, consistent-read, manifest and concurrency matrix without reviving excluded legacy application scope. Both captured RED failures are deterministic missing-behavior failures after valid database/browser setup.

## Required changes

1. Assert the complete stored V2 calculation evidence for ready and blocked objects.
2. Exercise a signed reversal through new publication and verify recurrence plus ordered closure evidence.
3. Verify exact Yii-derived certificate provenance against the uploaded PDF and request identity.
4. Add discriminating V2 UI/XLSX evidence/value assertions and HTTP 409 stale-calculation return behavior.

## Correction rereview — 2026-09-14

- Reviewed source: base `79b3a4cecae558d74d781f353e967f03c036b921` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T195315Z-854b811480/snapshot`, patch SHA-256 `fca19328b89be6d3c59765f5c4645bddc3141e79d569473e946dc59aa35295cf`; restored and checked at `/private/tmp/fmonitor-issue66-publication-g3-r2.G6oHww/checkout`
- Corrected specification SHA-256: `c385e5e990f2317e5ddc3f96e6fbbdf2e2a95f737f438bf2b36f45cdda44eca3`
- Corrected core test SHA-256: `cd020fced5e8147eb00e561ab20260ca72564cb2fbea94bb13b207aa0cf8ecf1`
- Corrected browser wrapper/script SHA-256: `5ba463af8a4747cd1521b889acdccb0eebc46b76f8390db5106eb849dbeaf251` / `8ee7517e1e038017eba78cc609fb93b2d65781b03856f5632c1b6abb7a84335a`
- Red evidence: retained exact-source records `1789415542122977000-cc4076d105824d388723a2feb2f23f66` and `1789415542118815000-005051e33a2d4fa09ec8ea676b306440` are `INTENDED_RED` at candidate source `913ba88363e3fbc2025324898b11aeac6488933376cddfb1fd483d85f01a982e` / executable source `dec5bea5981be3e0e0ff2cf49bca3ac1b56caa98f646464f6f0b110311fabf58`. The reviewer reproduced the core missing-V2 RED and, after attaching the omitted local Playwright dependency to the disposable restore, reproduced the browser RED at the removed technical provenance fields.
- Prior findings disposition: findings 1–3 resolved. Finding 4 is resolved for the current V2 trace/date/money/certificate UI, XLSX values/source locator, and stale-payment response, but retains the acceptance-route gap below.
- Verdict: `CHANGES_REQUESTED`

### Complete rereview findings

1. **MEDIUM — stale acceptance has no HTTP 409 assertion** (`specs/OTIZ-EXCEL-PUBLICATION-001.md:20,28`, `tests/Support/HistoricalCalculationFixture.php:12`, `tests/Otiz/excel_publication_001_test.php:21`). The contract prohibits both accepting and paying an unpaid old-formula snapshot and requires the current Yii user response to be HTTP 409 with a useful “новый расчёт” instruction. The correction proves direct-seam stale denial for acceptance and payment, but exercises HTTP mapping only through `/payments/complete`. Because acceptance is a distinct controller action and user transition, an implementation could retain a redirect or generic error for stale acceptance while these tests pass. POST the historical draft to the current acceptance route and independently assert 409, the new-calculation message, and unchanged facts.

The corrected tests otherwise prove the exact six operands, allocation version, exclusions, ordered seven-step trace, blocked null calculation/source/blockers/zero distribution, signed positive-plus-negative publication recurrence and evidence, preserved original closure, exact Yii-derived certificate label/locator/PDF hash, current UI dates/PTO absence/money/certificate link, XLSX version/values/source locator, and stale-payment HTTP behavior. These additions remain within the owner-bounded existing lifecycle and introduce no entitlement, cross-period, reversal-policy or rapid compatibility scope.

### Required changes for approval

1. Add the stale-draft acceptance HTTP 409/message/no-facts case described above.

## Final bounded correction rereview — 2026-09-14

- Reviewed source: base `79b3a4cecae558d74d781f353e967f03c036b921` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T195935Z-662990d5e8/snapshot`, patch SHA-256 `d9d7a4717bf9c78d6bc37d00102905fd2822b838fe080d4407ef77dc54232535`; restored and checked at `/private/tmp/fmonitor-issue66-publication-g3-r3.km52eQ/checkout`
- Corrected historical fixture SHA-256: `29116ffdb994a2866a3e0e358e8a74c0b6c714a1b5626c206896342d5beeecca`
- Unchanged bounded inputs: specification `c385e5e990f2317e5ddc3f96e6fbbdf2e2a95f737f438bf2b36f45cdda44eca3`; core test `cd020fced5e8147eb00e561ab20260ca72564cb2fbea94bb13b207aa0cf8ecf1`; browser wrapper/script `5ba463af8a4747cd1521b889acdccb0eebc46b76f8390db5106eb849dbeaf251` / `8ee7517e1e038017eba78cc609fb93b2d65781b03856f5632c1b6abb7a84335a`
- Red evidence: retained exact-source records `1789415957417739000-13436b17d34b4a21a01732a8f7ec6803` and `1789415958554757000-a82d8ddb6f1b4468a59bbc87dd1e0df2` are `INTENDED_RED` at candidate source `7e338430014c173b1757e373a67af2147a7aaac3658aebbafd2d67a093a35678` / executable source `d53ea6b5c5ad9ae97121747f4ca176336fdd0006d738301eb7b0b14aea630b2d`. They fail respectively because publication remains V1 and the current certificate form still exposes technical provenance fields.
- Prior findings disposition: the sole remaining correction finding is resolved. Before changing the historical fixture to accepted status, the test now posts the stale draft to the current acceptance route and independently asserts HTTP 409, the “новый расчёт” instruction, and byte-for-byte unchanged persisted facts. All earlier corrected scenarios remain present.
- Verdict: `APPROVED`

### Complete findings

None for the owner-bounded publication scope. The complete matrix now sensitively covers the existing publication/acceptance/settlement lifecycle wired to V2, signed confirmed-payment recurrence, exact persisted evidence and trace, source-bound content identity, blockers and atomicity, historical V1 reads/replay/stale guards, current Yii-derived provenance, mobile return flow, and XLSX values/sources. The review does not require or approve the discarded entitlement, cross-period, new reversal-policy or legacy rapid-pilot compatibility work.

### Required changes

None.

## CI fixture correction review — 2026-09-14

- Reviewer: Codex independent reviewer `/root/review_calc` (gpt-5.6-sol / low)
- Test author: root agent
- Reviewed source: base `b1d6b5178126090e6e2ee3d6eb090100547d3c47` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T204829Z-31a636cb46/snapshot`, restored outside the working checkout at `/private/tmp/fmonitor-issue66-ci-review.sPkmNU/checkout`
- Candidate source: `bb4306f2748aac3f8c5fe88898a5a07713717503bea26174d158abf9576cee35`; executable source: `514b31eccae83b2157b16059e5538c7b19b0a6afcc3df07a57f96dc8ef3e1446`
- Evidence reviewed: the complete 18-test first-CI failure inventory and retained full failing segments/logs under `/Users/antropophag/.local/share/fmonitor-2/issue66-ci-*`; exact current native Yii browser GREEN records `1789418762067842000-1c52d70579b54137b709536db845ff33` and `1789418763208114000-ea2030db9afa46229cf023a088172a0d`.
- Excluded ownership: the prefix-25 certificate schema witness is reviewed with the certificate slice.
- Gate 3 verdict for the bounded test corrections: `APPROVED`

### Findings

None.

The publication browser now uses the public native `InspectionFixture` opening and item-28 completion for object 4512, retains public Yii calculate/accept/settle/reverse/XLSX behavior, and replaces the obsolete synthetic 4520 eligibility oracle with the independently stated `65000000 × 2% = 1300000` pool. Existing receipt, event, replay, XLSX signature/content and three-closure assertions remain. The production runtime browser likewise consumes the native 4512 journey and keeps its restart, private-original and OTIZ export assertions; it does not introduce rapid/demo compatibility.

Settlement fixtures preserve their original money oracle. Snapshot 302 is current V2 and remains payable for 150000; snapshot 301 remains historical V1 for read/replay/stale behavior. The browser fixture obtains its full trace from the already approved pure V2 calculator using independently literal operands and source evidence, while retaining the 100000 settlement and export assertions.

The removed duplicate harness start in the core publication test fixes the observed bind conflict without removing a behavior assertion. `PreopeningFixture::start()` now keeps the first explicitly captured server alive for the established two-server concurrency test; replacement is opt-in and used by the certificate fault-injection test. The latter restores the ordinary server explicitly. The recovery fixture changes only traversal of the extracted historical public-code directory to 0755; private controls remain 0700. The frozen role-catalog hash update is consistent with the retained old catalog plus the five specified certificate permissions and keeps exact catalog sensitivity. The CI composition golden adds the newly registered browser test and retains exact list equality.

### Required changes

None.
