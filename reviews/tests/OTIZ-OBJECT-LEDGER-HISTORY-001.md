# Test review: OTIZ-OBJECT-LEDGER-HISTORY-001

- Reviewer: independent Gate 3 reviewer `/root/gate3_review` (gpt-5.6-sol/low); did not author the specification or tests
- Test author: root Codex session, owner-authorized scope/spec/test authorship
- Reviewed source: targeted scope-corrected candidate `3875e2e70402fedfa24579e2281350f7abd6db87a24aea678339356665cde0c3`, executable source `82c37a62180c20be55e3339022f52760b00116917c3e175c62d5fda87cdeb8e5`, base `bbf9489db16cba388f4430321a865459902e17ba`
- Reviewed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022727Z-5d49532d75/package.json`
- Retained snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022727Z-5d49532d75/snapshot`, patch SHA-256 `83a25baab52a005f0c486f9c2336c89332979b7b8e39fcefa5542eccf2c25adf`
- Agreed review scope / prior findings disposition: targeted Gate 3 scope-correction rereview of the root-owned specification/test delta only: GET-only route boundary, corrected retained-current-snapshot A09 oracle, Yii bootstrap and precise foreign-reversal assertion; production implementation was not reviewed at this gate
- Specification: `specs/OTIZ-OBJECT-LEDGER-HISTORY-001.md`; OpenSpec `object-ledger-history`
- Public seam: `GET /pilot/otiz/snapshots/{snapshotId}?object={objectId}&ledgerPage={page}` plus browser transition from the existing object drawer; the existing route remains GET-only and HEAD is explicitly out of scope
- RED evidence: `php tests/Yii2/yii2_otiz_object_ledger_history_001_test.php` reached the browser flow and failed at the intended missing server-rendered drawer transition (`expected 1`, `actual 0`) in fresh retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1790216834410954000-5614aaa233f34d079168854267ea24ca.json`; source and end source both match the scope-corrected candidate.
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

## Review summary

The targeted scope correction removes both executor-reported contradictions without weakening the accepted A01-A10 behavior. The GET-only public seam matches the owner boundary; A09 now protects all existing current-snapshot rows while the new view remains object-filtered and cross-snapshot; bootstrap and foreign-reversal checks are deterministic and precise. All earlier Gate 3 findings remain fixed, no regression or new risk was found in the reviewed spec/test delta, and the exact-source RED fails for the intended missing drawer transition.

## Required changes

None. Gate 3 is approved for the reviewed source. Any later test/spec change requires plan recomputation and independent review as selected by the refreshed plan.
