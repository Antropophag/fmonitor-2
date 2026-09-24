# CALENDAR-EFFECTIVE-OBJECT-DETAILS-001 — Gate 3 test review

- Reviewer: `/root/gate3_review` (`gpt-5.6-sol`, low; independent and authored none of the reviewed specification, lifecycle artifacts, tests, or production code).
- Review date: 2026-09-24.
- Audit base / HEAD: `b1542f92009b8dc4216a36962ff38a51e0b6c388`.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T014854Z-167867937c/package.json`.
- Exact prepared candidate source: `926ddc2957507848244b583dbea6393d2173d06713884b71546553bd4dc6f732`; executable source: `1e99fe2ff88265ecb054f4fa35a888f22bed60f2361efabb1d0ad25336768b73`.
- Verification-plan SHA-256: `c7969cd978791519f548870855348d0ac02afa254e4f64b280762ae99f7ec050`; planner decision: `CRITICAL`, required reviews `gate3` and `final`.
- Reviewed bindings: specification SHA-256 `c69227901fa02e3c02d49187c8f22b5d2dd1e3f3ee58b0b24a97f9700fae8f58`; PHP test SHA-256 `4220b71eb8bce7d2b0e0a9ebd859342fc4770d51110f746579d8b8144a511ac8`; browser test SHA-256 `313473fd412639ae980032de5fafb66e5382864645fffa1e87e34b150a95b9b9`.
- Verdict: **CHANGES_REQUESTED**.

## Findings

1. **BLOCKING — the effective-value field matrix is incomplete, so an incorrect resolver use can pass.** `tests/Yii2/yii2_calendar_effective_object_details_001_test.php` never exercises the A2 absent-key fallback. Its first revision covers a non-empty address, an empty entrance, and a null registration number; its second revision checks only the address and registration number. It never asserts that entrance becomes `7`. An implementation that resolves address and registration correctly but leaves entrance empty after revision 2 would pass both the PHP and browser checks. Likewise, an implementation that treats an absent key as empty instead of falling back to legacy is not observable. Add independently distinguishable fixtures/assertions that prove, for address, entrance, and registration number across `inspection`, `planned_start`, and `planned_end`, the four normative states: absent key -> legacy, explicit `null` -> empty, explicit empty string -> empty, and non-empty correction -> corrected value. At minimum the revised test must assert entrance `7` after revision 2 and add an absent-key phase with unique legacy sentinels.

2. **BLOCKING — A3/A4 claim identity, dates, count, and order are unchanged across replacement, but the test does not compare the before/after event sequence.** The test checks three nodes before the edit replacement and checks that nodes of the three types exist afterward, while only the database schedule row is compared. It does not capture and compare the rendered event identities/dates/order before and after revision 2. A query change that reorders events or changes a planned event date/identity while retaining one node per type and the expected text could pass. Capture a normalized ordered sequence containing event type, schedule/object identity, and date before replacement and assert it is identical after replacement. Existing `yii2_calendar_003_test.php` adequately covers authorization, validation, 5000-row source/combined overflow behavior, cache, safe schema failure, and baseline ordering, but it does not replace the required same-fixture before/after invariant for this correction.

## RED evidence assessment

The retained focused RED is credible for the original discrepancy: the authenticated request returned HTTP 200 and failed at `INTENDED_RED effective address in inspection` with expected `true`, actual `false`, on the exact base plus root-authored specification/test artifacts. This is an intended behavior failure rather than setup failure, and the real Yii HTTP route is the correct public seam. The test also structurally reaches all three event types once the first assertion is corrected and includes a real browser reload plus card/registry regressions.

However, that early RED cannot approve the uncovered A2 and A3/A4 cases above. After correcting the matrix, regenerate the source-bound verification plan/package and retain a fresh focused RED showing the corrected test still fails for the missing calendar behavior before Gate 4 implementation.

## Assessment

The normative specification and OpenSpec artifacts are coherent and properly bounded to issue #241. They explicitly require reuse of `MariaDbEffectiveObjectDetails::sqlValue()`, preserve the two bounded queries and existing event behavior, and exclude writers, schema, import, dates, history, OTIZ, general UI, deployment, #233 and #45. The proposed test uses isolated MariaDB state, the authenticated HTTP seam, deterministic sentinels, and no production systems. The two coverage gaps are nevertheless material because the owner's central requirement is that all three fields use the existing effective values in all three event types while event sequence remains unchanged.

Gate 3 does not advance until both findings are corrected and independently rereviewed against a refreshed exact source.

---

## Gate 3 correction rereview — 2026-09-24

- Reviewer independence: unchanged; `/root/gate3_review` (`gpt-5.6-sol`, low) authored none of the specification, lifecycle artifacts, tests, or production code.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T015205Z-98683cf286/package.json`.
- Exact corrected candidate source: `e169e0bde10bbbd12729157edeb1f36a29aad05db0d2bf496b9f0c19dae3b0a2`; executable source: `f0189986549b48f43c160f2f6381edc82c6541a59eb354aab9445ab0685d40b1`.
- Refreshed verification-plan SHA-256: `872a38853784df2d0c05a999572e6fe480c94d40ad0d9e3fda85323bab6aafec`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected PHP test SHA-256: `e8f106c0f5535d6c037404472e6fd394ccbb9d6ea7877fc2659a0e557cc6ab87`.
- Verdict: **APPROVED**.

### Prior findings disposition

1. **Resolved.** The corrected test now performs four distinct revisions of the existing edit row. An empty JSON object proves absent-key fallback to unique legacy address, entrance, and registration sentinels for each of `inspection`, `planned_start`, and `planned_end`. Subsequent revisions prove explicit JSON `null`, explicit empty strings, and non-empty corrections independently. The final assertions specifically require address `Next address`, entrance `7`, and registration number `CUR-4512` in every event type, so partial field resolution cannot pass.

2. **Resolved.** Before changing the edit revision, the test captures the ordered table sequence as event type, object id, schedule id, and date. It compares that complete normalized sequence after all correction states. Together with the three-event count, unchanged legacy row and inspection schedule, this detects additions, removals, reordering, identity changes, and date movement caused by the correction. Existing `yii2_calendar_003_test.php` continues to own the broader authorization, validation, cache, bounded-source/combined-overflow, incompatible-schema and baseline-ordering regressions.

### Fresh RED assessment

The refreshed focused run used the isolated Compose project and real authenticated Yii HTTP route documented in `docs/operations/issue-241-calendar-effective-details-delivery.md`. The absent-key phase passed, then the request returned HTTP 200 and the test failed at `INTENDED_RED explicit null does not fall back in inspection` with expected `false`, actual `true`. That is the earliest missing production behavior in the corrected matrix, not a fixture or dependency failure. The remaining assertions are deterministic and become reachable after the same effective-values defect is corrected.

No blocking Gate 3 findings remain. The corrected specification/test candidate is approved for Gate 4 implementation. Production implementation, GREEN evidence, final review, exact-source CI and publication remain outside this verdict.

---

## Gate 3 CI test-harness delta rereview — 2026-09-24

- Reviewer independence: unchanged; `/root/gate3_review` (`gpt-5.6-sol`, low) did not author the browser correction.
- Refreshed package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T022702Z-cd04d49939/package.json`.
- Exact reviewed candidate source: `935d8afebec418154b91880b7eb099ca23f19dddb71586adee1a3a1f1455015e`; executable source: `60c0354bbfec7cc8b79f2a3bcb2efac75cd665a210eb1f7d1059b2916d90dcc3`.
- Verification-plan SHA-256: `3d2fde312c71fa7d16db7d42cedb145bb021e1d2a019fc602cc04946a923c332`; planner decision remains `CRITICAL`, required reviews `gate3` and `final`.
- Corrected browser test SHA-256: `46484f86c5a8bcdc488b28a27cb552b20f97a38a95e615068abc56a0919f1b5e`.
- Verdict: **APPROVED**.

### Delta assessment

The only test semantic delta replaces dynamic ESM directory import with the established repository pattern `createRequire(import.meta.url); require(input.playwright)`. Node's ESM loader rejects a Playwright package directory even when that directory is a valid CommonJS package root; `createRequire` uses the package's normal CommonJS entry resolution. The passed path remains fixture-controlled, and this change neither weakens nor bypasses browser startup.

All browser acceptance assertions are byte-for-byte unchanged: authenticated calendar navigation, one located copy of each event type, effective address and registration content, reload, and the six grid/agenda copies. The normative specification, PHP acceptance matrix, production implementation and prior intended RED are unchanged. `node --check` and `git diff --check` pass, and the recorded default-path focused run is GREEN. This is therefore a test-harness compatibility correction, not an expectation change.

No Gate 3 finding remains for this delta. This approval does not approve the current production source or substitute for refreshed independent Gate 5 and exact-source CI on the complete candidate.
