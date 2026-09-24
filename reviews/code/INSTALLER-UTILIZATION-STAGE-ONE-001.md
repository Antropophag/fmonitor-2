# Final code review: INSTALLER-UTILIZATION-STAGE-ONE-001

- Gate: 5 — independent final implementation review
- Reviewer: independent agent `/root/issue258_final`; authored neither specification/tests nor production implementation
- Reviewed commit: `b47e53bee769945143d6a9ef1873c4260aaf8b91`
- Candidate source: `1ced177a92344bd8eeaebb8203b4e195ea1b90f41769ddfbf75b73dcdab3dcb5`
- Base: `d9dddb31f9c6e07092bcf6d4c04df761a1a13ccd`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T205629Z-be97f1d92a/package.json`
- Normative contract: `specs/INSTALLER-UTILIZATION-STAGE-ONE-001.md`
- Gate 3 history: `reviews/tests/INSTALLER-UTILIZATION-STAGE-ONE-001.md`, final tests-only verdict APPROVED
- Bound GREEN evidence: the three mapped installer-utilization PHP commands are GREEN on the exact candidate source and their command blobs match the reviewed tests
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKER — the shared upcoming projection does not prove that the current selection's exact composition has a confirmed original.** `MariaDbInstallerUtilization::projection()` builds `upcomingRows` by joining `fm2_assignment_order_original_roots` only on `installation_case_id` and `assignment_order_id` (`app/Workforce/MariaDbInstallerUtilization.php:83`). It does not join `r.composition_identity=s.composition_identity` and `r.composition_sha256=s.composition_sha256`, although `directoryPage()` correctly applies both predicates at line 56. After a selection is revised under the same order, an original root for the earlier composition therefore makes the new, unconfirmed selection appear upcoming on the card and picker. The directory filter/count can simultaneously say that no upcoming assignment exists, breaking clauses 2 and 10 and acceptance E/J. Join the root on the complete immutable composition identity/hash, as the directory query already does, and add a regression in which an accepted original is followed by a different unconfirmed selection for the same order.

2. **HIGH — all upcoming start dates are discarded, so a known date is always reported as unknown.** The upcoming SQL selects `NULL planned_start` unconditionally (`app/Workforce/MariaDbInstallerUtilization.php:83`), even though the selected assignment order carries `planned_start_date_snapshot`. Consequently the card renders `Дата начала неизвестна` and the picker emits `plannedStartDate: null` for every confirmed original. Contract clauses 2, 7 and 8 require the known effective date when available and reserve the unknown marker for genuinely missing dates; acceptance D/K explicitly distinguishes those states. Join the selected order (or the canonical effective planning source) and project its validated planned start without synthesizing one. The GREEN test changes the order date to 2020 but only asserts that it does not create current work; it never asserts that the known date reaches the UI, so this defect passes.

3. **HIGH — PTO removes native work from current state but does not close its historical period at the available PTO boundary.** `historyPage()` computes a native period's `end` solely from the first later application that excludes the installer (`app/Workforce/MariaDbInstallerUtilization.php:125-127`). It never consults the lift's `pto_act` fact. If PTO occurs without a later composition replacement, the card correctly drops the lift from current work but still labels the history period `продолжается`. This contradicts clauses 5, 8, 9 and the stage goal: PTO ends current participation for that lift, and its known `fact_date` is the available completion boundary. The surfaces test only checks that history remains present after PTO, not that the period is closed, so exact-source GREEN does not cover the failure. End the period at the earliest applicable boundary (PTO or replacement) and add the direct 1→0 history-boundary assertion.

4. **MEDIUM — the installer card contains an unintended visible quote before its return-link label.** `app/YiiRuntime/Views/installer-card.php:1` emits `>">К списку монтажников`, producing a stray `"` in the rendered UI. Correct the literal and retain the existing escaped allowlisted URL handling.

## Other review observations

- Directory utilization filters, total count, stable ordering and pagination are applied in SQL before the page ID query. The current/upcoming filters are conjunctive.
- Current classification requires an applied composition, factual opening and absence of the lift's PTO fact. Multiple cases are counted independently rather than grouped by address.
- Effective registration/address expressions are used by current, upcoming and history projections; user/external strings are escaped in the reviewed HTML views and encoded by the JSON response.
- General card access checks `installers.read`; construction-control engineers are restricted to their latest assigned objects in directory/card/history projection. The scoped picker path additionally denies access unless its object-specific check succeeds. No write command is introduced by the new GET seams, and the read model performs no inserts or updates.
- The package's bound GREEN evidence is internally exact-source consistent, but the three findings above expose acceptance-insensitive paths. The package also lists three additional local obligations without bound records; this review does not upgrade those absent records to GREEN.

## Required changes

- Bind upcoming rows to the exact selected composition's confirmed original.
- Preserve and display/serialize a known upcoming start date, with null only for a genuinely unknown source value.
- Close native participation history at PTO `fact_date` when that precedes any replacement boundary.
- Remove the stray quote in the card return link.
- Add focused regression assertions for each semantic correction, refresh exact-source evidence, and obtain a new independent Gate 5 review.

## Rereview 1 — corrected implementation, incomplete regression sensitivity

- Reviewed commit: `26c8914c1fb48378e849a2622ac4c3362c9b8aa8`
- Candidate source: `c2708c6e0eac83a320be116574960ee86dfd7d7102bf611ee6fdc3fd08dca5cb`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T211030Z-14027e992d/package.json`
- Bound evidence: all three mapped installer-utilization PHP commands are GREEN on the exact corrected source; no mapped test is reported missing
- Verdict: `CHANGES_REQUESTED`

### Prior implementation findings disposition

1. **Exact confirmed composition binding — implementation RESOLVED.** The upcoming projection now joins the root on `installation_case_id`, `assignment_order_id`, `composition_identity`, and `composition_sha256`, matching the directory predicate. A root for an older composition can no longer confirm the latest changed selection.
2. **Known upcoming start — implementation RESOLVED.** The projection now reuses `MariaDbYiiObjectQueue::plannedStartDateExpression('m.workdatestart')`, the existing canonical validated planning expression, and forwards the resulting nullable date to both card and picker DTOs. The corrected fixture now mutates the actual canonical source rather than a not-yet-existing order row.
3. **PTO history boundary — implementation RESOLVED.** Native history now chooses the earliest non-null boundary between the first excluding replacement and the first qualifying PTO `fact_date`, so a PTO-only completion no longer remains labelled as continuing.
4. **Return-link quote — RESOLVED.** The unintended visible quote was removed without changing escaped return-URL handling.

The correction is narrowly scoped, preserves read-only behavior and actor/object predicates, and introduces no new writer or schema surface. No additional production defect was found in the corrected diff or the full candidate review.

### Remaining findings

1. **HIGH — exact-composition confirmation remains unprotected by an executable regression.** The stage-one test proves selection-only/template-only are not upcoming before any original exists, then proves a matching accepted original is upcoming. It never creates the distinguishing state from the Gate 5 blocker: accept an original, revise the selection under the same order to a different composition, and observe that the old root does not confirm the new selection. The former two-key join would still pass every mapped test. The first review explicitly required this regression alongside the production fix; exact-source GREEN therefore does not close test sensitivity for contract clause 2 and directory/card/picker equality.

2. **HIGH — PTO history closure remains unprotected by an executable regression.** The surfaces test still asserts only that the card has no current work and that history remains present after PTO. It does not assert that the completed period ends on the PTO `fact_date` or that `продолжается` is absent for that lift. The former implementation, which dropped current work but left native history open, would still pass every mapped test. The first review explicitly required a direct history-boundary assertion; exact-source GREEN therefore does not close clauses 5, 8 and 9.

The known/unknown canonical planned-start behavior is now exercised by the corrected fixture and exact-source GREEN. The quote correction is trivial rendered markup and does not warrant a separate blocking test.

### Required changes

- Add a focused stale-root/new-composition case and require no upcoming assignment consistently in directory, card and picker until that exact composition has its own accepted original.
- Add a focused PTO-only native period assertion requiring its end to equal the PTO fact date and rejecting the continuing marker for that period.
- Refresh exact-source evidence and return for Gate 5 rereview. No further production change is requested by this cycle.

## Rereview 2 — complete semantic regressions and legacy PTO history

- Reviewed commit: `f86800b1b153284db33cbc6f13803f82dec5a470`
- Candidate source: `58d392b44f81d9d9f18a7fc57da9e678144065ab2c4fa65ea109f51a020b24e8`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T212153Z-f119471144/package.json`
- Gate 3: APPROVED at rereview 12 after preserving fixture-reachability isolation
- Bound evidence: all three mapped installer-utilization PHP commands are GREEN on the exact candidate source; command blobs match the final tests and no mapped test is missing
- Verdict: `APPROVED`

### Prior findings disposition

1. **Stale accepted root versus revised composition — RESOLVED.** The isolated fixture accepts an original for installer 7001's composition, revises the same order to an unconfirmed composition containing installer 7002, and requires both the card and picker to expose no upcoming assignment for 7002. This directly fails the former case/order-only root join and protects the exact identity/hash correction. The semantic scenario executes only after the fixture-reachability early exit and has bounded cleanup.
2. **PTO history boundary — RESOLVED.** The three-lift legacy fixture now requires the completed card to show the exact PTO date `21.09.2026` and to contain no continuing marker. This fails the former history implementation while retaining the independent 3→2→1→0 current-count assertions.

### Legacy history correction assessment

The final production delta correctly extends the history rule to legacy assignments. It no longer removes a PTO-completed legacy membership from history. Instead, its period end is the earliest non-null boundary of `valid_to` and the first PTO `fact_date` on or after `valid_from`, matching the native earliest-PTO/replacement rule and the contract's available-boundary requirement. Current-work queries remain unchanged and still exclude any lift with an active PTO fact.

The change remains a read-only projection: no schema, command, mutation, hidden score, eligibility rule or assignment writer was introduced. Existing actor/object scope predicates remain on both native and legacy history branches. Effective requisites, escaping, unavailable-source behavior, server-side filters/count/order/pagination, cross-surface compact equality, known/unknown planning dates, deterministic concurrent reads, replacement boundaries and mixed history pagination remain covered by the complete exact-source GREEN matrix.

### Final verdict

No blocking, high, medium or low finding remains. Gate 5 is approved for exact candidate source `58d392b44f81d9d9f18a7fc57da9e678144065ab2c4fa65ea109f51a020b24e8`.

This verdict is limited to the reviewed source and bound local evidence. Publication, exact-source GitHub CI, merge and deployment remain outside this review and must retain their own recorded states.

## Rereview 3 — post-rebase exact-source confirmation

- Rebased base: `b81b08d91ae5639f08413628b411587ae28176df` (`origin/main`, including merged #249/#250)
- Reviewed HEAD: `d2b0c8a2784b0d636592f8ad35f54284d4bf554c`
- Candidate source: `3820ab692241b787e5cd84f7bfd5a7f6506e4df1010c706dd89c3de1929a8d8c`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T212740Z-79542a9394/package.json`
- Exact-source mapped evidence: all three installer-utilization PHP commands GREEN; command blobs retain the approved final test hashes and no mapped test is missing
- Additional reported post-rebase checks: Quality Graph `18/18` GREEN and architecture `59/59` GREEN
- Verdict: `APPROVED`

### Rebase assessment

`git range-diff` maps every one of the 23 implementation/specification/test/review-history commits before the final review record to its rebased counterpart with `=`. The rebase therefore preserves the previously approved semantic patches without edit. The only additional commit in the rebased range records the final Gate 5 review itself.

The full candidate diff against `b81b08d9` retains the same bounded stage-one surfaces and the previously approved corrections: exact confirmed-composition binding, canonical known/unknown planning dates, native and legacy PTO history boundaries, server-side directory filters/count/pagination, scoped card/picker projections, escaping/fail-closed behavior, and read-only GET semantics. The merged #249/#250 base changes produced no textual conflict and no observed behavioral regression in the refreshed exact-source checks.

### Final verdict

No finding was introduced by the rebase. Gate 5 remains approved for exact rebased candidate source `3820ab692241b787e5cd84f7bfd5a7f6506e4df1010c706dd89c3de1929a8d8c` at HEAD `d2b0c8a2784b0d636592f8ad35f54284d4bf554c`.

Exact-source GitHub CI, PR publication, merge and deployment remain separate states and are not implied by this local final-review verdict.
