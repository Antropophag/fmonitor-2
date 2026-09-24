# Test review: INSTALLER-UTILIZATION-STAGE-ONE-001

- Gate: 3 — independent review of the root-authored specification and executable tests
- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`; authored neither the specification/tests nor production implementation
- Test/spec author: root delivery agent; Git candidate commit authored by Timofey Grishin
- Reviewed source: commit `b7f2176bcea050e99c9b90a9719d1b0a834d3cf1`, candidate source `27814b297109fb2cf4eb68ea9788bc4e02129b548ff6eb564d25959d3941b1da`, executable source `44c15afdf6e7e0e2865542ddbccf847c3902c122fc85b4f85719b2e2000556f8`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T184915Z-f26fefb68d/package.json`; plan SHA-256 `41733985fe8b5bce05315743a9e4ed99b306a1c4fca74aa8979ae9fdb16a07ee`
- Specification: `specs/INSTALLER-UTILIZATION-STAGE-ONE-001.md`, SHA-256 `5ae77894f4997f3c879c3e3ef3dfd817a8e1053a4fdd558988f9fa78613e1e58`
- Public seams: GET installer directory, GET installer card, and the existing assignment-order installer picker
- RED evidence: all three mapped PHP commands have exact candidate-bound `INTENDED_RED` records with exit `255`; command blobs equal the reviewed test hashes
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKER — server-side filters, count and pagination are not acceptance-sensitive.** Contract clauses 6 and acceptance I require current/upcoming present/absent filters, conjunctive combinations, total count, stable ordering, and a match located beyond the first page to be computed over the full dataset before pagination. `yii2_installer_utilization_surfaces_001_test.php` exercises only `load=working` and `load=idle` around one installer and checks neither a total/page boundary nor any upcoming filter or conjunctive combination. Existing directory tests cover the older employment/availability filters, not these utilization predicates. An implementation that filters only the rendered page, returns a stale count, omits upcoming filters, or combines filters with OR can pass.

2. **BLOCKER — upcoming classification lacks the required negative oracle.** Acceptance D/E and clause 2 distinguish a confirmed original from a draft, generated template, and unapplied selection. `yii2_installer_utilization_stage_one_001_test.php` proves the positive uploaded-original case, but never constructs and observes draft-only, template-only, or selection-only installers before confirmation. The later inserted `status='prepared'` order is added after factual opening and no assertion attributes an upcoming result to it. A reader that treats any prepared order/selection as upcoming can pass.

3. **HIGH — cross-surface projection equality is not proved.** Acceptance J and clause 10 require the same person's current count and upcoming context in directory, card, and picker. The native test compares card and picker only before opening; the PTO test compares directory and card only and never invokes the picker. Assertions are also reduced to selected literals rather than independently expected complete compact DTO fields. Separate, inconsistent implementations for the three surfaces can satisfy the suite.

4. **HIGH — authorization coverage stops at the pre-existing directory route.** Clause 11 and acceptance L require guest, denied and limited/scoped actors not to learn out-of-scope utilization. The new tests grant `installers.read` to a broad fixture actor and do not exercise guest/denied requests to the new card, picker utilization payload, or two objects split across actor scope. Existing directory authorization does not prove the new detail route and picker enrichment enforce the same boundary. Add indistinguishable denials and no-leak/safe-body expectations for every new seam.

5. **HIGH — unknown/unavailable and identity cases are overclaimed.** Acceptance C/K requires same-address lifts to remain distinct by effective identity, missing dates to remain explicitly unknown, and mandatory-source failure to render unavailable rather than a false zero. The three-lift fixture asserts distinct registration numbers but does not give distinct lifts the same address. `load=unavailable` is used only to verify escaping of a workforce source string; it does not assert an unavailable marker or prevent zero utilization. No upcoming/history fixture removes a date and asserts an explicit unknown value. A join grouped by address, a synthesized date, or exception-to-zero fallback can pass.

6. **MEDIUM — two explicit classification boundaries have no direct test.** Acceptance G requires a passed planned start without factual opening not to become current, and clauses 1/5 require PTO release without declaration, payment, or OTIZ certificate. The current negative merely has no factual start; it does not make the planned date elapsed. The PTO assertion checks that the word `Декларация` is absent from HTML, but fixtures do not add or withhold the other completion facts and compare outcomes, so an implementation may still depend on payment/certificate facts accidentally supplied by the shared fixture. Construct the distinguishing states and assert the exact counts/history.

7. **MEDIUM — browser coverage does not exercise the enriched picker or return-state contract.** The browser script validates directory-to-card navigation, history text, and horizontal overflow at two widths. It never opens the existing assignment-order picker, verifies utilization context there, follows an object link, or starts from non-default allowlisted search/filter/page state and proves that the card return link preserves exactly that state. The required working browser route is therefore only partially protected.

## Evidence and authorization assessment

The package, source hashes, commit and three RED records are internally consistent. The RED failures are at absent utilization behavior rather than reported GREEN, and no production implementation is part of this candidate. Root ownership of specification/tests and separate independent review are recorded consistently with the owner-authorized workflow. The test files are registered in the verification inventory, and the planner reports no missing mapped tests; mapping completeness does not cure the behavioral gaps above.

The 3→2→1→0 PTO sequence, replacement history boundaries, adjacent same-installer coalescing, long-history pagination/query bound, malformed-current fail-closed outcome, read-only fact snapshots, hostile-string escaping, and a basic responsive card route are materially sensitive and may be retained.

## Required changes

- Add full-dataset utilization filter/count/order/pagination cases, including upcoming filters and conjunctive combinations with a later-page match.
- Add draft-only, template-only and unapplied-selection negative upcoming cases, plus elapsed planned-date/no-opening.
- Assert one independently defined compact projection across directory, card and picker for the same fixture.
- Add guest, denied and object-scoped no-leak cases for card and picker enrichment.
- Add same-address/different-effective-lift, explicit unknown-date, and mandatory-source-unavailable-not-zero cases.
- Complete browser coverage for picker context and exact allowlisted return state.
- Capture fresh exact-source intended RED evidence and regenerate the prepared reviewer package before Gate 3 rereview.
