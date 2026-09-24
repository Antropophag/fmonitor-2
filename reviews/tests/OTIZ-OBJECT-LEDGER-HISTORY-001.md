# Test review: OTIZ-OBJECT-LEDGER-HISTORY-001

- Reviewer: independent Gate 3 reviewer `/root/gate3_review` (gpt-5.6-sol/low); did not author the specification or tests
- Test author: root Codex session, owner-authorized scope/spec/test authorship
- Reviewed source: Gate 5 blocking-correction candidate `aa8f2e7dea974552853aa86743bcdc097b5e868e869aab49e222583a4d0a2813`, executable source `18ae1824b9d096799759832b0b19b80b496dcf9f13d0b6e33eae4e89a1e2a284`, base `fac7ac8afa9a8eb2de0b4184cc22e457332bbac3`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T024124Z-07d70c3d37/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T024124Z-07d70c3d37/snapshot`, patch SHA-256 `5ce006ee38c19fa46cb8528811d21dbfe6ed40ef641a1910c5ad3b84ae8f14a9`
- Agreed review scope / prior findings disposition: Gate 3 review of the root-owned blocking-correction specification/test delta from Gate 5, limited to cross-page same-object reversal links and a syntactically valid `PHP_INT_MAX` out-of-range page; production implementation was not reviewed at this gate
- Specification: `specs/OTIZ-OBJECT-LEDGER-HISTORY-001.md`; OpenSpec `object-ledger-history`
- Public seam: `GET /pilot/otiz/snapshots/{snapshotId}?object={objectId}&ledgerPage={page}` plus browser transition from the existing object drawer; the existing route remains GET-only and HEAD is explicitly out of scope
- RED evidence: `php tests/Yii2/yii2_otiz_object_ledger_history_001_test.php` failed at the intended extreme-page behavior (`ledgerPage=PHP_INT_MAX`: expected `200`, actual `503`) in exact retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790217672486144000-cd312639786444b3b1279e89c2f0e34f.json`; source and end source both match the blocking-correction candidate.
- Verdict: `APPROVED`

## Findings

No current findings.

### Prior finding dispositions

- Prior finding 1 (pagination/order/consistent read): **fixed**. Page size is now normative at 10; unique closure IDs assert exact disjoint ordered pages and the controlled post-aggregate commit proves one repeatable-read response snapshot.
- Prior finding 2 (financial projection/cross-object reversal): **fixed**. Totals and ordinary row semantics remain covered; both mixed and malformed foreign-reversal rows are now located by closure ID and assert their exact type and all three signed components while the foreign target remains hidden.
- Prior finding 3 (guest/context rejection): **fixed**. Exact safe return, unknown/invalid snapshot/object/page forms, 403 and representative leakage markers are covered in HTTP, with safe return also exercised in Chromium.
- Prior finding 4 (empty/retained behavior): **fixed**. Existing sums, current-snapshot ledger, drawer actions/forms, lazy exclusion, empty count and all four zero totals are asserted.
- Prior finding 5 (browser/side effects): **fixed**. Chromium synchronizes an authenticated permission change, observes 403 with no ledger leakage, restores access, and at 390px verifies bounding boxes for type, date, all three amount cells, basis, source cell/link and reversal link after contained scrolling. Escaping, empty state, outbound trap and full no-write inventory remain covered.

### Scope-correction dispositions

- GET-only route: **approved**. The normative specification, delta requirement and HTTP test consistently remove HEAD. A08 continues to prove authorized GET, pagination and source transitions are read-only, with no state or external writes. This removes an unauthorized route expansion without narrowing the product's requested read capability.
- Retained A09 oracle: **approved**. The ordinary snapshot now positively asserts its existing same-snapshot ledger rows for the selected, neighbor and paged fixtures (`B deductions`, `NEIGHBOR SECRET`, `Paged 12`) plus existing sums/actions/forms. It excludes only cross-snapshot A/C rows. The lazy object history independently excludes the neighbor object while including A/B/C across snapshots, so retained behavior and new filtering cannot mask each other.
- Test setup: **approved**. Composer autoload and `vendor/yiisoft/yii2/Yii.php` are loaded before the Yii command subclass, removing the setup contradiction while the fresh run reaches the intended product RED.
- Foreign reversal oracle: **approved**. The assertion now rejects the exact foreign closure anchor and exact source-label disclosure, alongside neighbor basis/identity, without falsely rejecting an unrelated occurrence of the numeric identifier.

### Gate 5 blocking-correction dispositions

- Same-object reversal split across pages: **approved**. The isolated object `7901` has exactly 11 ordered rows: a September reversal, nine August fillers, and its July original. With normative page size 10 this independently and deterministically places the reversal on page 1 and original on page 2. The test proves the membership split, requires the exact current-context URL `?object=7901&amp;ledgerPage=2#closure-{originalId}`, and proves that anchor exists on page 2. Existing hostile foreign-object reversal assertions remain in force, so the correction cannot authorize a cross-object target.
- Extreme syntactically valid page: **approved**. `PHP_INT_MAX` is submitted as a positive decimal integer, distinct from invalid-parameter rejection. The test requires HTTP `200`, zero page rows, and the same full-set `12` count and `12,00 ₽` total as pages 1–3. It therefore detects arithmetic overflow, accidental 503/error handling, clamping to the last page, or leakage/substitution while preserving the specified empty out-of-range behavior.
- Fixture and source-link sensitivity: **approved**. Snapshot objects `7901` exist in A/B/C, the original closure belongs to source A and the reversal to source C, while the reversal link deliberately stays in the requested B object-history context so it can resolve the original's actual paginated position. Source-snapshot links remain a separate established projection. No permissions, writers, formulas or foreign-object visibility are broadened.

## Review summary

The Gate 5 blocking-correction delta adds two narrow, independently determined expectations without weakening prior A01-A10 coverage. Cross-page reversal navigation is tested at the actual page and anchor while foreign targets remain hidden; the maximum valid integer page is distinguished from malformed input and must return an empty, internally consistent `200` response. Fixtures, context links and sensitive object isolation are coherent. All earlier Gate 3 findings remain fixed, no new risk was found in the reviewed spec/test delta, and the exact-source RED fails for the intended 503 overflow/error behavior.

## Required changes

None. Gate 3 is approved for the reviewed source. Any later test/spec change requires plan recomputation and independent review as selected by the refreshed plan.
