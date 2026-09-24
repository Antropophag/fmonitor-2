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

## Rereview 4 — consistent coexistence phase

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Reviewed source: commit `91a88aad89630621cf701e045df345ee2353d4d5`, candidate source `7b7c657ecf362b3829c8c543fbbc2bc91ffac80034a5b5d6a897d650ab2d1f07`, executable source `d26ffd48f42509d206dacd33d95e629849494faa6fbe8f05a5c11496723b4e49`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T190556Z-7d6477e15f/package.json`; plan SHA-256 `7d92323daf6f81e73357dae761426add0c817629336464cb2463007db17d7c93`
- Bound stage-one test SHA-256: `9f928f9d961f0d4da2eea4690d8d19eea03009db5845a0c9e583106c2e702dd4`; unchanged surfaces/browser tests retain their prior bound hashes
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; no mapped tests are missing
- Verdict: `APPROVED`

### Prior finding disposition

The remaining contradiction is resolved. While lift 4999 is current and lift 4512 is confirmed but unopened, the card, picker and directory consistently require current count one, effective upcoming registration `EFFECTIVE-4512`, and the conjunctive `current=present&upcoming=present` result. The duplicate concurrent card reads observe that same phase. Only afterward does the fixture add PTO for 4999; lift 4512 is then factually reopened before the active-history phase. No later assertion incorrectly treats 4999 as current or requires the coexistence phase to have zero current work.

The dedicated surfaces test independently proves the required PTO sequence 3→2→1→0 without declaration/payment/certificate prerequisites, so another immediate 4999-only zero assertion is not required for acceptance sensitivity.

### Complete assessment

The reviewed suite now covers the normative stage-one matrix through public HTTP/browser seams:

- applied composition plus factual opening and same-lift PTO release, including same-address independent lifts and 3→2→1→0;
- confirmed-original upcoming behavior versus selection/template negatives, elapsed planned date, explicit unknown date, and simultaneous current plus separate upcoming work;
- effective requisites overriding stale base values;
- server-side present/absent conjunctive filters, full-set later-page matching, count, stable history ordering and pagination;
- shared compact current/upcoming expectations across directory, card and picker;
- replacement history boundaries, adjacent retained-assignment coalescing, mixed native/legacy pagination and fail-closed malformed/current-source behavior;
- guest, denied and limited object-scoped directory/card/picker outcomes with forbidden-literal absence;
- escaping, mandatory-source unavailable-not-zero, replay/read-only facts, overlapping duplicate GET determinism and bounded query count;
- desktop/mobile directory-to-card return state and working assignment-order picker context.

Expected values are fixed from the specification or explicit fixtures rather than copied from production output. The tests remain read-only except for isolated fixture setup and deliberate source-fact transitions, and compare business facts around read phases. Root authorship, separate executor authorization, independent review, exact candidate binding and fresh intended-RED evidence are preserved.

### Required changes

None. Gate 4 implementation may proceed against exact candidate source `7b7c657ecf362b3829c8c543fbbc2bc91ffac80034a5b5d6a897d650ab2d1f07` without changing the approved expectations.

## Rereview 5 — effective-requisites oracle correction with paused executor WIP

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Gate boundary: test/spec review only; the dirty executor production WIP is source-bound for evidence but is neither reviewed nor approved here
- Root-authored test commit: `a626bd1ccb90dd8e8d1e6c7e342e41757d78ea8d`
- Reviewed candidate source: `e4003ecd2516a695d511d85ebcced29f10384d137837cefc342eff26685e9c4f`; executable source `4357cefe6ac24aba14cba58392394fdb85707931e07e6693851f93194328d1f1`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T194525Z-8d441ddc91/package.json`; plan SHA-256 `f1c3d2ab111d33fcc81ae6be9efb5c2dbada524f3c99f6b70aef28b4c0bc82b5`
- Corrected stage-one test SHA-256: `d39e7584bc4446c0fd9d500ac147961a23d2c0ea00fd36816f9dc4587ebe29c4`
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; no mapped tests are missing
- Verdict: `CHANGES_REQUESTED`

### Correction assessment

The two positive oracle changes are correct. Upcoming and active-history card assertions now require `EFFECTIVE-4512` and explicitly reject `STALE-4512`, matching contract clause 4 and the existing fixture where effective object-detail edits override stale base requisites. They agree with the already-approved directory and picker expectations and do not weaken the coexistence/history matrix.

### Finding

**HIGH — two no-leak assertions still use the superseded `TEST-4512` literal.** After this correction, the normative observable registration number is `EFFECTIVE-4512`, while `STALE-4512` is the deliberately forbidden base value. However:

- the denied card/picker assertion checks only that neither body contains `TEST-4512`;
- the malformed-current fail-closed assertion checks only `TEST-4512` and the table prefix.

Neither assertion would fail if a denied or sanitized `503` response leaked the now-canonical `EFFECTIVE-4512`; the malformed response could also leak `STALE-4512` without detection. This leaves contract clause 11's no-leak outcome and the fail-closed body oracle insensitive precisely where the positive oracle was corrected.

Update both negative assertions to reject all fixture-sensitive object literals that could reveal data: at minimum `EFFECTIVE-4512`, `STALE-4512`, `CURRENT-4999`, `Эффективный адрес`, and `Устаревший адрес`, while retaining the internal table-prefix rejection for the infrastructure failure. A small shared forbidden-literals helper would reduce future oracle drift.

### Required changes

- Correct the denied and malformed/fail-closed negative oracles to reject effective, stale and other sensitive fixture literals rather than only `TEST-4512`.
- Capture fresh exact-source intended-RED evidence and regenerate the reviewer package.
- Return only the test correction for Gate 3 rereview; production WIP remains subject to later independent Gate 5 review.

## Rereview 6 — hardened no-leak oracle follow-up

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Gate boundary: tests only; paused dirty executor WIP remains outside Gate 3 and is not production approval
- Root-authored test commit: `7ea3636590992a94fdcd7648f84d7d992428be08`
- Reviewed candidate source: `6ef498dd39a1cf415caea620e199c8fc6657b303cdb837eefc3e6c4428da65a4`; executable source `04076da876a4ea6aa00dbb09ac6f71964c6410283e449062556dd1ea6ad1ea03`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T194840Z-f32fc04aef/package.json`; plan SHA-256 `cd59d73a20deaaf55699a9d7b3e3081d8721dfe1bc79573e634ade9b5dadb864`
- Corrected stage-one test SHA-256: `0a0ce451891ef53ed13cee247ad4553089d4937d3febc11f90445f1e681f51ea`
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; no mapped tests are missing
- Verdict: `CHANGES_REQUESTED`

### Prior finding disposition

**Partially resolved.** The denied card/picker loop now rejects `EFFECTIVE-4512`, `STALE-4512`, `CURRENT-4999`, `Эффективный адрес`, and `Устаревший адрес`. The malformed-current `503` loop now rejects the three registration identifiers and internal table prefix.

### Remaining finding

**MEDIUM — malformed-response sanitization still omits both address literals.** The exact malformed-response loop is:

```php
['EFFECTIVE-4512', 'STALE-4512', 'CURRENT-4999', $f->p]
```

It does not include `Эффективный адрес` or `Устаревший адрес`, despite both being sensitive fixture data already included in the denied-response oracle and explicitly requested in the previous finding. A fail-closed response leaking either address would pass. Add both address literals to the malformed-response forbidden set; then the effective/stale/current object data and internal prefix are consistently protected at both negative seams.

### Required changes

- Add `Эффективный адрес` and `Устаревший адрес` to the malformed-current `503` forbidden-literal loop.
- Refresh the exact-source intended-RED record and reviewer package for the corrected stage-one test.
- Keep production WIP paused for separate Gate 5 review.

## Rereview 7 — complete effective/stale no-leak oracle

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Gate boundary: tests only; paused dirty executor production WIP is explicitly not reviewed or approved
- Root-authored test commit: `0e621a7b017d48708a7ea00c1d63dd9a11eba2e5`
- Reviewed candidate source: `6185aa61a260783dd5622688bbec4c3c0a507288a756c6f4d1dc950e44f8af38`; executable source `47103bbc496386d78dea4ad199ab26ca923394710c00e3999d8acf0942c81ad8`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T195132Z-43b1c76252/package.json`; plan SHA-256 `fff859a81dfe4210619b08ef9924d08b57a70523991deeff771f79d90c68ea89`
- Corrected stage-one test SHA-256: `161ce3270e915d61ec765c663d184502d9646e9506667bc991ffbdf4fb6471f1`
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records with exit `255`; no mapped tests are missing
- Verdict: `APPROVED`

### Finding disposition

Resolved. The malformed-current `503` oracle now rejects `EFFECTIVE-4512`, `STALE-4512`, `CURRENT-4999`, `Эффективный адрес`, `Устаревший адрес`, and the fixture table prefix. Together with the denied card/picker loop, both negative seams now fail on leakage of effective values, stale base values, the separate current lift, or internal storage identity.

The positive effective-requisites assertions remain unchanged and require `EFFECTIVE-4512` while rejecting `STALE-4512` in upcoming and active-history output. Thus the corrected suite consistently distinguishes canonical display, forbidden stale fallback, scoped/denied disclosure, and sanitized infrastructure failure without weakening the previously approved acceptance matrix.

### Required changes

None. Gate 3 is approved for exact candidate source `6185aa61a260783dd5622688bbec4c3c0a507288a756c6f4d1dc950e44f8af38`. This approval covers the test oracle only; executor production WIP still requires independent Gate 5 review and exact-source verification.

## Rereview 8 — guest request reachability correction

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Gate boundary: tests only; paused dirty executor production WIP remains outside this verdict
- Root-authored test commit: `8618917484c3a0b41852430b94db81e451f489fb`
- Reviewed candidate source: `26d834e6bc5ff51d9d350150ad5fe888f96f7775d270da934f357db56a2b3897`; executable source `52108a587b364b506696d3da46587c68c0d066323683bdb78251a91e1506151e`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T195919Z-33bcef31bf/package.json`; plan SHA-256 `cf4ab59e3e56906961cdd149627fb58559f63b4827730d0cc5bc0e475600c033`
- Corrected stage-one test SHA-256: `6bf6d42b4dde63b1e579e412983ec940c79d1f80ff83b6edc8d7aacbea4c940a`
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records; the stage-one command exits `255` at `active history EFFECTIVE-4512`, after the guest assertion is reached successfully
- Verdict: `APPROVED`

### Assessment

The correction only introduces an empty `$guestCookies` variable and passes it to the existing request helper's by-reference cookie parameter. It does not alter the route, method, expected `303` guest outcome, denied actor expectations, fixture data, or any utilization oracle.

The refreshed stage-one evidence is behaviorally relevant: execution passes setup, selection/template/original classification, effective requisites, scoped directory/card/picker, coexistence filters, concurrent reads, guest and denied request construction, and then fails at the still-unimplemented active-history effective registration assertion. The failure is not a PHP argument/reference error or fixture-reachability failure. The surfaces and browser commands retain fresh candidate-bound intended RED records, and the package reports no missing mapped tests.

### Required changes

None. Tests-only Gate 3 is approved for exact candidate source `26d834e6bc5ff51d9d350150ad5fe888f96f7775d270da934f357db56a2b3897`. This is not a Gate 5 review or approval of the paused executor WIP.

## Rereview 9 — scoped count oracle correction

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Review source: clean review-only worktree `/tmp/fm258-gate3-count`; verdict recorded in the primary delivery worktree
- Root-authored test commit: `8eb9afb3cee41e0a80bc305dd088b93cb899e961`
- Reviewed candidate source: `e0f2d3bf3aa7024cbd24ae859791b37a1d56a42bccb0fc45fb947c79bb910765`; executable source `df8108bd3fe3467ea4fb3c4fbbd9e916669bc43dfda00b6d92eecbabe7573684`
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260924T201150Z-c4b1cdb48e/package.json`; plan SHA-256 `40235dd2ee2a3d5b73fa0ec38c17ac0de040d61d937170f7c880716c415fcbbe`
- Corrected surfaces test SHA-256: `0f43f99bc949032354c883afb426aa0330b7a275ab68b537bdb2b02376a9e30d`
- RED evidence: all three mapped commands have fresh candidate-bound `INTENDED_RED` records; surfaces exits `255` after reporting `INTENDED_RED current-load filter absent` and receiving `400` instead of the required `200`
- Verdict: `APPROVED`

### Assessment

The fixture contains multiple installers who may satisfy `current=present&upcoming=absent`, so the prior unsearched assertion `data-filtered-count="1"` incorrectly narrowed the meaning of the current-present filter. The correction adds the explicit search `q=Монтажник 001`; one result is now independently implied by the fixture and the expected filtered count is coherent without redefining utilization semantics.

The correction does not weaken server-side behavior coverage. The same request still combines search, current/upcoming filters and page before asserting the full searched/filtered count. The stage-one test separately adds sixty preceding workforce rows and requires installer 7001 to be returned with filtered count one for a conjunctive utilization query, preserving the full-set-before-pagination and later-page sensitivity.

Fresh evidence is bound to the exact corrected hash and fails at the absent utilization filter admission/behavior seam, not because the fixture count is contradictory. The stage-one and browser commands retain fresh mapped intended RED evidence, and the planner reports no missing mapped tests.

### Required changes

None. Tests-only Gate 3 is approved for exact candidate source `e0f2d3bf3aa7024cbd24ae859791b37a1d56a42bccb0fc45fb947c79bb910765`.

## Rereview 10 — canonical planned-start fixture mutation

- Reviewer: independent `gpt-5.6-sol/low` agent `/root/issue258_gate3`
- Gate boundary: narrow root-authored test delta only; production is not reviewed or approved
- Review source: clean review-only worktree `/tmp/fm258-gate3-count`; verdict recorded in the primary delivery worktree
- Root-authored test commit: `8b381cfc7c08be48fb976ce1ec100396ba2b7b7a`
- Corrected stage-one test SHA-256: `9f7da5f8b4c34cf9ddc29428ae9da6b27d33692d7c840c2c987ce39626561e4f`
- Historical RED: record `1790283810934397000-f7d95e746785458aa9a9d70a14f7af83`, previous test blob `6bf6d42b4dde63b1e579e412983ec940c79d1f80ff83b6edc8d7aacbea4c940a`, exit `255` at the absent installer-card seam
- Corrected GREEN: record `1790283846611463000-ff6abb29b0074743a6a02c01a1bb5a1b`, corrected test blob `9f7da5f8b4c34cf9ddc29428ae9da6b27d33692d7c840c2c987ce39626561e4f`, exit `0` with `PASS: INSTALLER-UTILIZATION-STAGE-ONE-001 native participation history`
- Verdict: `APPROVED`

### Delta assessment

The two old fixture updates targeted `fm2_assignment_orders.id=81`, but that row does not exist at those execution points, so the statements changed no source fact and could not drive either the elapsed-plan or unknown-plan oracle. The replacement updates object 4512's canonical legacy planned-start field, `fm_maintable.workdatestart`, first to `2020-01-01` and then to `NULL`.

This matches the unchanged public read contract and existing implementation boundary: `MariaDbYiiObjectQueue::plannedStartDateExpression('l.workdatestart')` validates and returns the first ten characters of the canonical field, and the fixture itself originally seeds object 4512 through that field. No production constant, output, or implementation-derived value is copied into the expectation.

Behavioral expectations are unchanged and remain independently meaningful:

- an elapsed canonical planned date does not add a second current work because factual opening still owns current classification;
- a missing canonical planned date renders the explicit unknown upcoming date;
- subsequent cross-surface, concurrency, authorization, history and no-leak assertions are unchanged.

### Evidence lineage assessment

The historical record proves the approved pre-implementation test reached a behaviorally relevant missing-card RED rather than setup failure. The corrected record proves the same full stage-one command now traverses the formerly ineffective date mutations and completes GREEN against the already-present implementation. The differing command blobs are exactly explained by the two-line fixture correction. Because the harness cannot classify an unchanged mapped test becoming GREEN during reviewer composition, this independent comparison records the lineage explicitly rather than treating GREEN alone as Gate 3 evidence.

### Required changes

None. The narrow canonical planned-start fixture correction at commit `8b381cfc7c08be48fb976ce1ec100396ba2b7b7a` is approved for Gate 3. This verdict does not constitute production or Gate 5 approval.
