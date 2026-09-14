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
