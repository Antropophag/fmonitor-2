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

## Rereview 1 — corrected exact-source cycle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Reviewed source: commit `63f993827322b7784a108230a07e8f139d4babb5`, candidate source `c0ca4de772a9823e299a63e7ca56661f0b882d4fe77d390b81231f8080a1a074`, executable source `20dddc90ed12a0095cfc9b22603a867676008c9d7d3329924e29f08b5d2f69c0`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T185413Z-6d9d19f44d/package.json`; plan SHA-256 `144905f31133229c942b1973ea4c6423135703978f58785de456e2e55dce1dbf`
- Bound test hashes: stage-one `40a8f99faf0de0deec8a3437f2feb5243bf76871353067c0f5b3e6b8ba8beb9b`; surfaces `4daf1279f84796ec31a7083919a66bc796fa170ee2f91bb63bfb7433f95eb41a`; browser PHP `f4baa53ba24760e2cfd1f570b0ad3655ab691a633d821873f376fc74994d28ab`; browser script `0e87cef005c0cbfe746424b8f0161ceb93d5e3f1498d5ae1817385fbd40e01c4`
- RED evidence: all three selected PHP commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; command blobs match the reviewed PHP tests and the candidate source binds the changed browser script
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Server filters/count/pagination — RESOLVED.** Sixty preceding directory rows make installer 7001 a later-page match; `current=absent&upcoming=present` must yield it with filtered count one. The surfaces test independently exercises `current=present&upcoming=absent`, filtered count, stable three-lift projection, individual current filters, and closed query shapes.
2. **Upcoming negative oracle — RESOLVED.** Selection-only and generated-template states are observed before upload and must not be upcoming; the uploaded confirmed original becomes upcoming before opening/application.
3. **Cross-surface equality — RESOLVED for the exercised upcoming state.** Independent current-count and registration-number literals are asserted across directory, card and picker.
4. **Authorization — PARTIALLY RESOLVED.** Guest card and wholly denied card/picker cases were added, with no-leak assertions. The required limited/object-scoped case remains absent.
5. **Unknown/unavailable/identity — RESOLVED.** Three different lifts share one address and remain distinct; a null planned date renders explicit unknown; removal of the mandatory PTO source produces sanitized `503`/`Retry-After`, not zero.
6. **Elapsed planned date and PTO independence — RESOLVED.** An elapsed 2020 planned date remains non-current without factual opening, and the final PTO reaches zero after all non-PTO completion facts are deleted.
7. **Browser route — RESOLVED.** Both viewport passes preserve the exact allowlisted directory state, and the browser opens the assignment-order picker and observes utilization context.

### Remaining findings

1. **BLOCKER — limited/object-scoped authorization remains untested.** Contract clause 11 and acceptance L explicitly distinguish a denied actor from a limited actor who may see only utilization within an allowed object scope. The correction tests an unauthenticated request and actor 95 receiving blanket `403`, but never creates an actor authorized for one object and unauthorized for another while the same installer has facts in both. Consequently a card aggregation or picker enrichment that reads all objects once the actor has the general permission can pass and leak out-of-scope registration numbers, counts, or history. Add a two-object fixture with partial scope, then require directory/card/picker projections and counts to contain only the allowed object's facts and to omit the forbidden literals.

2. **HIGH — simultaneous independent current and upcoming contexts are still absent.** Contract clause 3 and acceptance F require one installer to retain a current lift while a *different* confirmed-original lift is upcoming, including conjunctive filtering and the shared compact projection. The corrected test transitions the same lift from upcoming to current; it never constructs both sets simultaneously. An implementation using a mutually exclusive person-level status, dropping upcoming whenever current count is nonzero, or evaluating `current=present&upcoming=present` incorrectly can pass. Add distinct current and future lifts for one installer and assert both contexts across directory, card and picker plus the both-present filter/count.

3. **HIGH — effective requisites are not exercised.** Clause 4 requires joins and presentation to use canonical effective object/lift requisites. The same-address probe correctly rejects address grouping, but all fixture identities and displayed registration numbers still come directly from base `fm_maintable`/order snapshots; no effective-detail override conflicts with a stale base value. An implementation that keys correctly by object ID yet ignores the canonical effective-requisites seam can pass. Seed a base/effective disagreement and assert that all three surfaces join the same lift and display the effective registration/address while the stale base literals remain absent.

4. **MEDIUM — concurrent GET determinism is only claimed, not tested.** Clause 12 and acceptance M require concurrent as well as replayed GETs to be deterministic and create no business facts. The suite takes sequential snapshots/repeats and bounds query counts, but launches no overlapping directory/card/picker reads. Add a deterministic concurrent read probe against the same fixture, require identical semantic results/statuses, and compare complete business facts before/after.

### Required changes for rereview 2

- Add partial object-scope coverage across directory, card and picker with explicit forbidden-literal absence.
- Add one installer with a current lift and a separate confirmed-original future lift, including `current=present&upcoming=present` full-set filtering and cross-surface equality.
- Add an effective-requisites disagreement fixture and independently expected effective display/join values.
- Exercise overlapping GETs and prove identical output semantics and no new business facts.
- Refresh exact-source RED evidence and the reviewer package, then request another Gate 3 cycle.

## Rereview 2 — full-matrix rebuild exact-source cycle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Reviewed source: commit `a65c1a188ffb57191ab40ec885b4faba42c01c7f`, candidate source `335a8a02f2bb9bc8758a99b9d5e50f25a435931a98dec2803a2f1e73a6a4bdca`, executable source `9715cad0563d4ac718ff37526545d6c7cc18e07d71f0749fa0b5ecfdaea10872`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T185932Z-54cb11b223/package.json`; plan SHA-256 `1ff441499a222298d9be75805c9d0839e5d93a75876cffa313de999c0ddacc0b`
- Bound test hashes: stage-one `b34e4323be2e65401c1ccf86fded2b48a4b23fc47538fd55b8c71702e7bfe775`; surfaces `4daf1279f84796ec31a7083919a66bc796fa170ee2f91bb63bfb7433f95eb41a`; browser PHP `f4baa53ba24760e2cfd1f570b0ad3655ab691a633d821873f376fc74994d28ab`; browser script `0e87cef005c0cbfe746424b8f0161ceb93d5e3f1498d5ae1817385fbd40e01c4`
- RED evidence: all three selected PHP commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; no missing mapped tests are reported
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Limited/object scope — PARTIALLY RESOLVED.** Actor 73 receives installer/picker permissions, the card/picker are exercised for object 4512, and the forbidden `CURRENT-4999` literal must be absent. The directory projection/count for that scoped actor is still not exercised.
2. **Simultaneous current and upcoming — TEST STATE ADDED, but the complete test is internally inconsistent.** Current lift 4999 and confirmed-original upcoming lift 4512 coexist and the both-present filter is asserted. Later assertions incorrectly assume current lift 4999 disappeared.
3. **Effective requisites — RESOLVED.** Object 4512 has conflicting stale base and effective edited values; the card must show the effective registration/address and omit both stale literals.
4. **Concurrent GET — PARTIALLY RESOLVED.** Three public GET seams overlap and business facts are compared before/after. Deterministic equality of repeated concurrent requests is not yet observed.

### Findings

1. **BLOCKER — the rebuilt fixture contradicts its later expected current count and filters.** Lift 4999 is created as factually started and assigns installer 7001, with no PTO or replacement ending that participation. After applying lift 4512 and clearing only case 6101's opening, lift 4999 necessarily remains current. Nevertheless lines 18–20 require the card not to contain `Текущая работа`, require picker `currentWorkCount === 0`, query `current=absent&upcoming=present`, and define `expectedCompact.currentWorkCount` as zero. The concurrent request later correctly queries `current=present&upcoming=present` and expects `CURRENT-4999`, confirming the same state. A conforming implementation cannot satisfy both groups. Preserve current count one and use the both-present filter/projection throughout, or explicitly add a PTO/replacement boundary for 4999 before testing the absent-current state.

2. **HIGH — scoped authorization still omits the directory seam and its aggregates.** The scoped actor test calls only `/pilot/installers/7001` and the object-4512 picker. Clause 11 applies to all public seams, and the primary leak risk includes directory rows, filtered counts, and compact totals computed before rendering the card. Add a scoped directory request in the two-object fixture and require count/current/upcoming data to reflect only allowed object 4512 while omitting `CURRENT-4999`. Also make the scope basis explicit in fixture data so it is independent of incidental role/global-read behavior.

3. **MEDIUM — the concurrency probe proves overlap/no-write but not deterministic replay.** It launches one directory, one card and one picker request, then checks one distinguishing literal from each. Because the endpoints and expected representations differ, there is no pair of identical concurrent requests whose semantic outputs are compared. Duplicate each seam concurrently (or at minimum the aggregation-heavy directory/card seam), normalize only intentionally rotating values, and require pairwise equal status/body or complete parsed projections in addition to the no-facts assertion.

### Required changes for rereview 3

- Reconcile every post-4999 expectation with the persistent current lift, or explicitly end that lift before the absent-current subcase.
- Exercise scoped directory rows/counts/compact totals and bind the allowed/forbidden object scope explicitly.
- Compare duplicate overlapping GET results for deterministic equality while retaining the before/after business-fact check.
- Capture fresh exact-source intended RED evidence and regenerate the prepared reviewer package.

## Rereview 3 — contradiction/scope/concurrency correction cycle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Reviewed source: commit `ac6213ae9c4e786626b929e50ee4a3273fd09750`, candidate source `6b0bbb0d1f16f9dd9849250b9a3cdb91bde19e3d9a03b784c044d264c749b63d`, executable source `23b5cd2923168e79174645f52b8dba2a734cf7afb4ffad9a0548ca21c2faab95`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T190242Z-ba7c7bb048/package.json`; plan SHA-256 `13e2696c4a8ef05b9686ad75273e6b952fbf7f27de7ffe98e3936b6e62d11598`
- Bound stage-one test SHA-256: `d304635efc7348a23689ba95b6252515b476474cd0fd9998fcf173bfb33448f7`; the unchanged surfaces/browser tests retain their prior bound hashes
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; no missing mapped tests are reported
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Persistent-current contradiction — OPEN.** A PTO for lift 4999 was added, but it is inserted after every zero/absent-current assertion, so it cannot affect them.
2. **Scoped directory — RESOLVED at the observable seam.** The scoped actor now exercises directory, card and picker; the directory asserts filtered count one, effective object 4512, and absence of forbidden lift 4999.
3. **Concurrent deterministic replay — RESOLVED for the card seam.** Two overlapping identical card GETs must be byte-identical, the directory and picker overlap them, and complete business facts are unchanged.

### Blocking finding

**BLOCKER — PTO closure still occurs after the contradictory assertions.** The exact execution order remains:

1. lift 4999 is created as factually started/current for installer 7001;
2. lift 4512 is restored to pre-opening/upcoming;
3. the test requires the card to omit `Текущая работа`, picker current count `0`, directory `current=absent&upcoming=present`, and compact current count `0`;
4. concurrent requests then still require `current=present&upcoming=present` and `CURRENT-4999`;
5. only after all of those observations does the test insert the PTO for case 6199.

The PTO therefore closes 4999 only for later history/replacement assertions, not for the zero-current assertions it was intended to repair. A correct projection must report current count one in steps 2–4 and cannot pass the reviewed test. Move the PTO insertion to immediately after the simultaneous/concurrency coverage and before the first zero/absent-current observation; alternatively retain current count one and both-present filters until the existing insertion point. Then independently assert the 1→0 transition caused by that PTO so the state boundary is explicit.

### Required changes for rereview 4

- Reorder the PTO before all zero/absent-current expectations, or change those expectations to the still-current state.
- Add a direct before/after assertion that the 4999 PTO changes current count from one to zero while 4512 remains upcoming.
- Refresh exact-source RED evidence and the reviewer package.
