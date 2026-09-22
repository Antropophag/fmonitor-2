# Gate 3 test review: YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001

- Reviewer: independent Gate 3 agent `/root/gate3_review`; authored none of the reviewed specification, OpenSpec artifacts, test, or production code.
- Review date: 2026-09-22.
- Specification: `specs/YII2-CONSTRUCTION-CONTROL-SERVER-FILTERING-001.md`.
- OpenSpec change: `openspec/changes/fix-construction-control-server-filtering/`.
- Reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T233739Z-856ab2ddc5/package.json`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260921T233739Z-856ab2ddc5/verification-plan.json`, SHA-256 `06526064fdb96e7e21a29771f887df8d390c5d5f7cf0caf99cc916fdde6c955b`.
- Package candidate source: `c127b15d509f7443d34b69edfdb3ad48fd5dc95ccab8d992100822259e45f7d4`; executable source: `f0abb8a91af4183d04da76e43773e7ba0ba6aadc467be61fb4459717810e2788`; base `dd1cd5a2aafa5a68a8872d21c95d20a4002e90c9`.
- Reviewed test SHA-256: `99a64e1ad30dc03e054bfae878af75564e17fdde446872e2e5a029f773648eae`.
- Planner decision: `CRITICAL`; required reviews: `gate3`, `final`.
- Reported RED: isolated integration profile exited `255`; the intended assertion at test line 21 expected one row and observed 50.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **BLOCKING — the prepared package does not bind the submitted test or its RED evidence.** The package reports `tests/Yii2/yii2_construction_control_server_filtering_001_test.php` in `missing_tests`, its plan bindings do not contain the test hash, and package `evidence` is empty. The supplied exit `255` excerpt is credible intended RED for the headline defect, because setup reached the real Yii HTTP seam and the existing implementation returned the unfiltered 50-row page. It is not retained, source-bound evidence for the reviewed test bytes, and candidate source differs from executable source. Regenerate the plan/package after the complete corrected test exists, then retain a no-drift integration-profile RED record whose failure inventory identifies the intended missing behavior.

2. **BLOCKING — ownership and authorization semantics are almost entirely untested.** The test sends only `ownership=all` (lines 19, 25, 28, 30). It never proves the default/explicit `mine` predicate, current native self-assignment, rejection of historical-order or legacy-author fallback, exclusion of a foreign assignment, conjunctive mine+query behavior, or that `all` removes only ownership without widening authorization. An implementation that treats `mine` and `all` identically, uses a legacy owner, or leaks inaccessible rows can pass. Add isolated own-current, own-historical-only, foreign-current, and inaccessible fixtures; exercise default mine, explicit mine/all, and combined filters through authorized GET/HEAD, with the inherited exact-permission denial control mapped or directly asserted.

3. **BLOCKING — the query and completion boundary matrix is incomplete.** The only positive search is an exact registration number and the only negative is an absent ASCII token. There is no address search, trimming, case-insensitive match, literal `%`, `_`, and escape handling, corrected effective-details participation after #226, non-scalar values for every filter, or the `>160` Unicode-character rejection. Completion checks cover a fully completed neighbor but not the normative PTO-only exclusion. Add independent fixture values and expected results for address/regnumber, case/trim/literal wildcard and escape characters, the 160/161 Unicode boundary, effective details, PTO-only, malformed `ownership`/`completed`/`page`/`query`, and verify each rejected read is 404 with unchanged facts.

4. **BLOCKING — COUNT/rows/page coherence, stable pagination, and URL behavior can regress while this test stays GREEN.** The test checks only filtered page 1. It does not establish a multi-page filtered set, stable order, matching total/pages/rows from one predicate, filter-preserving pagination links, controlled out-of-range behavior without page substitution, refresh identity, or that changing/clearing controls omits an old `page=2`. The empty case checks zero rows and text but not the required visible empty state or pages=1. Add a deterministic multi-page filtered fixture and HTTP assertions for pages 1/2, ordering, totals, links and out-of-range input, plus an actual browser/DOM test for control change, clear, page reset and refresh. The planned `tests/Support/construction_control_server_filtering_browser.cjs` does not exist in the reviewed source.

5. **BLOCKING — preservation assertions are lexical and do not cover the observable UI/process contract.** Lines 35–38 merely search JavaScript source for two forbidden strings and three retained function names. Renaming the old DOM filtering, leaving another total rewrite, or retaining dead functions passes; conversely a harmless refactor can fail. No test observes shipment indicator, checklist/photo links, appearance, IndexedDB/local-operation preservation, sync, or prefetch. Add a browser-level witness that server-returned rows and total are not rewritten after initialization/filter changes and retain mapped executable controls for shipment/navigation/offline sync/prefetch. Keep source checks only as supplemental ownership guards.

6. **HIGH — GET-only coverage does not prove the declared public seam and complete read-only envelope.** The contract declares GET/HEAD, but the test exercises GET only. `$http->facts()` is a useful before/after durable-fact check, yet it does not demonstrate HEAD parity or absence of local operations/history/assignment changes if those are outside that inventory. Add HEAD cases for representative valid and invalid filters and explicitly cover or map the existing inventories for assignments, history, and local operations. This can be combined with findings 2–3 rather than creating a separate broad test.

## Assessment

The specification, OpenSpec delta, and verification plan agree on the public HTTP seam and the central server-before-pagination behavior. The fixture is isolated, uses deterministic IDs and values, and the line-21 expected value is independently derived from the single unique tail fixture rather than planned implementation details. The reported 50-versus-1 failure is therefore sensitive to the original defect, not a setup failure.

That valid headline RED does not cover the complete normative slice. Most sensitive semantics—native ownership, authorization preservation, literal query boundaries, canonical PTO-only completion, page coherence, HEAD behavior, and browser preservation—could be wrong while the current test passes. Gate 4 must not proceed on this artifact.

## Required correction

Correct the complete matrix in one Gate 2 revision, add the missing browser support where observable browser behavior is normative, regenerate the verification plan/package so all test bytes are source-bound, capture fresh exact-source intended RED evidence, and request independent Gate 3 rereview. Existing CI and deployment remain `UNKNOWN` and are not approval or GREEN.

## Verdict

`CHANGES_REQUESTED`
