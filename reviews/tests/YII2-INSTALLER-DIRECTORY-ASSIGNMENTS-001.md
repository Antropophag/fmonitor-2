# Test review: YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001

- Reviewer: independently tasked agent `/root/issue38_gate3`; not an author of the specification, OpenSpec artifacts, fixture, test, evidence, or production code.
- Test author: root agent, as recorded by the current goal and delivery record.
- Reviewed source: base `cf0299d8ea65332b654662c696966b5bb953a1d6` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T202702Z-1334501a32/snapshot/source.patch`, SHA-256 `acc5631afd334db31b3e426435b3adc62c677f76f8157407e4fa93706063b450`; candidate source `a554824160ebd148321fd812f74d9dd99a50ff78c3bfee0f5aac97ab0ef780b2`.
- Agreed review scope / prior findings disposition: first complete Gate 3 review of A1-A5, the root-authored RED test, its deterministic fixture boundary, and the sole package-referenced RED record. No prior findings.
- Specification: `specs/YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001.md`, SHA-256 `55dedf4700f8c9fe221197596842026b5f4478c24b5eb56c5c1efd1e4b5165c4`.
- Public seam: native selection, original upload and application followed by `GET|HEAD /pilot/installers`.
- Red command and intended failure: `php tests/Yii2/yii2_installer_directory_native_assignments_001_test.php`; package record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/evidence/issue-38-red-20260914.json` labels it `INTENDED_RED` at the candidate source, but does not retain exit status or failure output.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **HIGH — A1's document-basis expectation is derived from the forbidden legacy projection and is not sensitive to the specified native payload behavior.** The fixture writes `fm_maintable.regnumber='TEST-4512'`, and the test merely searches for `TEST-4512`. The contract instead requires the basis from the application payload and, when no separate native registration number exists, a stable order designation with its document date without inventing a legacy registration fact. An implementation that continues rendering the old `fm_maintable.regnumber` would pass this assertion. The test also does not identify the basis within installer 7001's assignment row, so unrelated page text can satisfy it. Use independently chosen, deliberately conflicting object label, legacy registration number, native order identity, and document date; assert the exact native-derived label in the selected installer's row and absence of the legacy canary.

2. **HIGH — the mandatory A2 history/replacement matrix is absent.** The test creates one application only. It does not establish that maximum `application_sequence` replaces the prior crew, that an installer removed from the latest immutable snapshot becomes free, that previous application rows and snapshot bytes remain unchanged, that multiple current cases appear once each in stable `tabId`/date/object order, or that repeated reads preserve history. The existing before/after comparison only proves one read leaves the sole application table unchanged. Add at least two native applications with conflicting crews and retained byte-level history, plus the multi-case/order/repeat witnesses required by A2.

3. **HIGH — A3/A4 branch and failure coverage is incomplete.** The free-state phrase is asserted anywhere in the page rather than within installer 7002's row; filters check only names and do not verify the unique-installer summary or the distinct empty-workforce-catalog state. There is no no-application registered-order fallback case, no conflicting legacy row proving that any application suppresses fallback/mixing, and no malformed/missing current application schema/snapshot case requiring sanitized `503` with `Retry-After: 60`, no internal-data disclosure, and no stale composition. `HEAD` is also part of the declared public seam but is not exercised. These omissions permit split semantics between rows, summaries, filters, methods, and failure paths.

4. **HIGH — A4/A5 read-only and boundedness claims are materially overstated.** The test snapshots only `fm2_assignment_order_applications`; a directory read that writes another domain table would pass. It supplies no SQL-count/bounded-query witness and no runtime-DDL witness. `noLegacy()` is a useful runtime-closure check for `rapid-pilot` and `app/PilotHttp`, but it cannot establish no legacy data use, no DML/DDL, or no per-row queries. Snapshot all relevant facts (using the fixture's existing authentication-attempt exclusion where appropriate), observe query count or an equivalent bounded statement budget across a larger catalog/case set, and retain the runtime closure assertion.

5. **MEDIUM — exact-capability authorization/rejection is not covered.** The positive actor receives `installers.read` in addition to role 1's existing unrelated permissions, but there is no public-seam request without that exact capability and no assertion of the inherited closed response. A permission regression could expose the directory while every submitted assertion passes. Add a denied actor with otherwise plausible access and assert the specified sanitized denial before the authorized matrix.

6. **HIGH — the retained RED record is insufficient evidence of intended failure.** `issue-38-red-20260914.json` contains only argv, the asserted outcome label, source, and environment digest. It has no exit code, assertion message, or bounded stdout/stderr excerpt. The delivery prose says the run reached successful selection/upload/application and failed only because the directory omitted `TEST-4512`, but its named `issue-38-red-20260914.txt` is neither referenced by the package nor present with the record. Consequently Gate 3 cannot verify that all setup/public actions passed and that the first failure was the missing behavior rather than setup or a different assertion. Capture fresh exact-source RED after completing the matrix, retaining exit status and relevant failure output with no source drift.

The test does cite the stable specification and its main positive path uses real native HTTP actions followed by the Yii directory HTTP seam. `PreopeningFixture` creates an isolated random database, private artifact directory, loopback server, fixed domain dates/identities, and owned cleanup; those are sound deterministic foundations. Expected literal installer/object values are fixture-owned, but finding 1 prevents expected-value independence for the central document-basis behavior. The package plan correctly selects `CRITICAL` with Gate 3 and final review. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval.

## Required changes

Return to Gate 2 and correct all six findings as one complete matrix without changing the command owner or expanding adjacent issue scope. Capture a new reconstructible exact-source package and complete intended-RED evidence, then obtain fresh independent Gate 3 review before Gate 4 implementation.

---

## Gate 3 correction rereview — 2026-09-14

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203150Z-ae146f1f67/package.json`.
- Corrected exact source: base `cf0299d8ea65332b654662c696966b5bb953a1d6` plus reconstructible snapshot, candidate source `9c7cd3bb86faee2b0b50984671d3242656a1844d9cedf9a8f79560e6d25f1b06`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203150Z-ae146f1f67/snapshot/source.patch`, SHA-256 `0606de3b8cbe1dfaf9c342652011b415dcc1b3007a257d9e13b8d39d7888edb4`.
- Reviewed correction delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203150Z-ae146f1f67/delta.patch`, SHA-256 `ac6d4ea2c44257e28d24c6da759c3a92c1c30e4e4e027bd30f948534e92d2148`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203150Z-ae146f1f67/verification-plan.json`, SHA-256 `592c4d93ecf3e4be1bbb05ef58921956dfa7feb8aa3a3ead02e56c504259b706`.
- Independence remains unchanged: this reviewer authored none of the corrected specification, OpenSpec, fixture, tests, production code, or evidence.

### Prior-finding disposition

- Prior finding 1 is resolved by an explicit normative correction. A1 now defines the existing `fm_maintable` registration number and address as object description only, while application snapshots exclusively own assignment presence/composition. The row-scoped object label/link assertions are sensitive to that clarified boundary, and the design is coherent with it.
- Prior finding 2 is partially resolved. The correction creates a second append-only application with a different installer, proves maximum-sequence replacement, absence of stale legacy mixing, and byte-equivalent preservation of the first row.
- Prior finding 3 is partially resolved. Empty-state text is row-scoped; GET/HEAD success, registered-order fallback, stale-mix rejection, malformed-current-snapshot `503`/`Retry-After`, and basic redaction are now exercised.
- Prior finding 4 is partially resolved. Whole-fixture fact snapshots now cover successful, filtered, replacement, HEAD, and failing reads, so DML/runtime-DDL changes are observable. Runtime closure remains asserted.
- Prior finding 5 is resolved by an authenticated plausible user without `installers.read` receiving `403` before the authorized flow.
- Prior finding 6 is resolved. The fresh source-bound record includes exit `255`, the exact first failing assertion and expected/actual values, and confirms the preceding login, selection, upload, application, application-row, and directory-200 assertions passed.

### Complete correction findings

1. **HIGH — A2's multiple-case and repeat/order behavior remains untested.** The corrected test still has only one current native-application case. The second application replaces composition on that same case; the added case is legacy fallback, not another current application. Therefore an implementation that drops one of two current native cases, duplicates an assignment, or orders multiple native assignments incorrectly can pass. The test also performs only one read of the replacement state, so A2's explicit repeated-read behavior is not independently exercised beyond the general fact snapshot. Add a second current native-application case for the same installer with deliberately reverse insertion/object ordering, assert both assignments appear exactly once in specified tabId/object-id order, repeat the read, and retain exact facts.

2. **HIGH — required A3 and A4 observable branches remain missing.** The test never asserts summary `assigned` from authoritative applications and never constructs the distinct empty-workforce-catalog state, both explicitly required by A3. Its filters cover the first composition but not the replacement state, so summary/filter/row split semantics after maximum-sequence replacement remain possible. A4 names missing application schema as well as invalid current snapshot, but only `{}` snapshot validation is covered; the `HEAD` failure path is also absent despite `GET|HEAD` being the declared seam. Add authoritative summary assertions before and after replacement, replacement-state assigned/free checks, an isolated empty-catalog request, and deterministic missing-schema plus malformed-current-snapshot GET/HEAD fail-closed witnesses with sanitized output.

3. **HIGH — the bounded-query correction is not connected to the submitted acceptance test.** The delta adds `fm2_assignment_order_applications` counting to `InstallerDirectoryFixture::start()`/`routeNoLegacy()`, but `yii2_installer_directory_native_assignments_001_test.php` requires and instantiates only `PreopeningFixture`, whose server router has no query counter, and calls `PreopeningFixture::noLegacy()`, not `InstallerDirectoryFixture::routeNoLegacy()`. No package-referenced test executes the changed counter against application data. Thus per-installer/per-case application queries can regress without failing this Gate 2 artifact. Put the counter on the actual native-assignment HTTP fixture or add a mapped bounded control that seeds multiple applications and demonstrably executes the application-query budget.

The corrected fixtures remain isolated and deterministic, expected object/installer values follow the clarified normative contract, and the intended RED still fails at the first missing production projection after healthy public setup. These strengths do not fill the three observable gaps above. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval.

### Correction verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked. Return to Gate 2 for the three bounded corrections above, retain all resolved witnesses and fresh exact-source RED evidence, then submit one complete package for independent rereview.

---

## Gate 3 third review — rebuilt complete matrix — 2026-09-14

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203540Z-67c53f221d/package.json`.
- Exact candidate source: `cddf55d4008e179e73ca9db24b58b2d349a3f92ef5f00acd0115381fb73a3488` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203540Z-67c53f221d/snapshot/source.patch`, SHA-256 `9e64dcb5cdfd4f010b7756c35f9e0e48b7a01eef1e19de9760f21f315745cf3f`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203540Z-67c53f221d/delta.patch`, SHA-256 `fe91a0cf9d99e201b882d802a5d6733b8cc6956a9294791921c184c4c4b9c73e`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203540Z-67c53f221d/verification-plan.json`, SHA-256 `b823198f6c1377e2c7e55bfb713e3b6cd9b7d2e30f6678fbb100b8ae9eaa9880`.
- Independence remains unchanged; this reviewer authored no reviewed contract, fixture, test, implementation, or evidence.

### Disposition of the preceding three findings

- The prior multiple-case/order/repeat finding is resolved. The rebuilt test adds a second current native case for installer 7002 after the higher object id, requires exactly the two expected links in object-id order, repeats the GET byte-for-byte, and preserves the complete fact snapshot.
- The prior A3/A4 branch finding is substantially resolved. The test now covers initial authoritative summary, replacement assigned/free filters, the distinct empty-workforce state, malformed-snapshot and missing-schema GET/HEAD failures, whole-fact preservation, and fallback/stale-mix behavior.
- The prior disconnected-counter finding is resolved as an execution-path issue. `PreopeningFixture` now installs the counting Yii command when this bound test sets `countDirectoryQueries=true`, and `noLegacy()` requires an observed installer-directory GET with counted queries before passing.

### Complete third-review findings

1. **HIGH — unique-installer summary sensitivity is still missing at the only state that can distinguish it.** A3 requires summary `assigned` to count unique installers. The sole summary assertion occurs when there is one installer on one case, where counting assignments/cases and counting unique installers both yield `1`. After the test gives installer 7002 two current native cases, it checks the two links but never asserts that summary remains `1`. An implementation that reports `2` assigned would pass every assertion. Parse the multi-case response and require the assigned summary to remain exactly one unique installer; retain the exact-two-link assertion.

2. **HIGH — the executed `<=6` query counter is not sensitive to the forbidden per-row SQL strategy.** Execution wiring is now correct, but the largest current-application set contains only two cases. A plausible implementation with one application/object query per current case plus a small fixed baseline can remain at or below six and pass. The counter therefore proves only a small absolute bound for this fixture, not A4's explicit “без per-row SQL” requirement. Seed more current application cases than the allowed budget (or compare two fixture sizes and require constant query count) while preserving the same `<=6` ceiling, so a per-case/per-installer query implementation necessarily fails.

3. **MEDIUM — HEAD failure assertions omit the required `Retry-After: 60`.** The contract declares `GET|HEAD` as the public seam and A4 defines malformed/missing schema failure as sanitized `503` with `Retry-After: 60`. Both new HEAD checks assert only status `503` and empty body; an implementation that omits the retry header on HEAD passes. Assert the header for both malformed-current-snapshot and missing-schema HEAD responses, alongside the empty-body and fact-preservation checks.

All other prior findings remain resolved. The new RED record is bound to exact source `cddf55d4008e179e73ca9db24b58b2d349a3f92ef5f00acd0115381fb73a3488`, retains exit `255` and the intended first projection failure after successful denied-auth, authorized native workflow, application persistence, and directory `200`. Setup remains isolated and deterministic; expected values follow the corrected contract; full-fact snapshots and runtime closure are sensitive. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval.

### Third-review verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on the three narrow sensitivity corrections above. Preserve the rebuilt matrix, capture fresh exact-source RED, and submit the next complete package for independent Gate 3 rereview.

---

## Gate 3 fourth correction review — 2026-09-14

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203807Z-cfeadaceba/package.json`.
- Exact candidate source: `5c6c427f28940f903136bfd038524b68d0ec2938f9e0d9a3cafb13efd8066895` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203807Z-cfeadaceba/snapshot/source.patch`, SHA-256 `db022daf454baa71d17264b176aeee969ba8480069563a86e6e29a629409fbcd`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203807Z-cfeadaceba/delta.patch`, SHA-256 `01b35838590a5d7fa07a6d70eb8d827567bf89063da99293941d9ba4b85c2b32`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T203807Z-cfeadaceba/verification-plan.json`, SHA-256 `9407f0fbca990bfe6d8f5c1f0f5a89f90646e5b94a766973d82d81173c2c8d12`.
- Reviewer independence is unchanged; no reviewed specification, fixture, test, production code, or evidence was authored by this reviewer.

### Prior-finding disposition

- The unique-summary finding is resolved. The response containing two current native cases for installer 7002 must expose assigned summary `1`, while the row must contain exactly the two ordered object links.
- The HEAD retry finding is resolved. Both malformed-current-snapshot and missing-application-schema HEAD requests now require status `503`, `Retry-After: 60`, and an empty response body.
- The query-invariance finding is only partially resolved. The native fixture now records response status and requires equal counts across at least three successful unfiltered GETs, but the selected observations include a semantically different empty-workforce branch.

### Complete fourth-review findings

1. **HIGH — the query-invariance oracle overconstrains the valid empty-workforce branch and can reject a correct implementation.** `PreopeningFixture::noLegacy()` collects every successful unfiltered `GET /pilot/installers`, including the request after all workforce rows are changed to `missing_from_delivery`. The production read shape legitimately omits its assignment-detail query when the delivered installer ID list is empty; a bounded implementation can therefore use one fewer statement for this explicit A3 empty state than for the populated one-, replacement-, fallback-, and two-native-case states. `count(array_unique($counts))===1` would fail that correct optimization even though query count is independent of application/case row cardinality within comparable populated states. Record/check named query-count checkpoints before the empty-catalog mutation, or otherwise exclude/segregate the intentional empty branch, and require invariance across the populated one-case and multi-case responses only. Retain a separate absolute ceiling for every observed request.

No other findings. The complete current matrix now covers the clarified assignment owner, exact capability, native public workflow, latest-sequence replacement, immutable history, stale-legacy exclusion, registered-order fallback only without applications, multi-case order/dedup/repeat, unique summary, initial and replacement filters, scoped free state, distinct empty catalog, read-only facts, runtime closure, and malformed/missing GET+HEAD fail-closed behavior. Expected values are fixture-owned and independent of the planned implementation. The fresh intended RED is bound to source `5c6c427f28940f903136bfd038524b68d0ec2938f9e0d9a3cafb13efd8066895`, exits `255`, and fails first at the missing native projection after healthy authorization and native setup. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval.

### Fourth-review verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked only on the bounded query-oracle correction above. Preserve every other resolved witness, capture fresh exact-source RED, and submit the corrected package for independent Gate 3 rereview.

---

## Gate 3 fifth and final bounded rereview — 2026-09-14

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T204001Z-afd31db348/package.json`.
- Exact candidate source: `cea8e7b01e9d66466fd417e4e6df84bd1d9e2ed349eb4b12a928be5d1e0cc392` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T204001Z-afd31db348/snapshot/source.patch`, SHA-256 `d42c8d9e6da11c26cd563249ba5503965f8a6022f1ce6f0bc3f8edc2245da6c7`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T204001Z-afd31db348/delta.patch`, SHA-256 `d0af6dbe6d8f304fbae0b9dbc35d339c80562fc0ad6736449bbdca8121a1e0ac`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T204001Z-afd31db348/verification-plan.json`, SHA-256 `0c8bd038dbfe9d84df17abc370577dc70aea5f97015ac0e037505ea3ffc042a9`.
- Independence remains unchanged; this reviewer authored none of the reviewed artifacts or evidence.

### Final correction disposition

The sole fourth-review finding is resolved. The intentional empty-workforce request now uses `/pilot/installers?q=empty-catalog`, so it remains behaviorally verified but is excluded from the exact-URI populated invariance cohort. `PreopeningFixture::noLegacy()` compares at least three successful unfiltered `GET /pilot/installers` observations spanning the initial native case, replacement, added no-application fallback case, two-current-native-case state, and repeat. It requires one constant count and a ceiling of six for every cohort member. This is sensitive to per-case/per-installer SQL growth while allowing a correct empty-catalog path to skip assignment-detail work.

### Complete findings

None.

The complete A1-A5 test matrix is traceable to the corrected normative contract, uses the native HTTP writer and Yii directory public seam, and has independent expected values. It covers exact authorization, authoritative latest application composition, object description, replacement and immutable history, stale legacy exclusion, bounded no-application fallback, multiple-case order/dedup/repeat, unique summary, initial and replacement filters, scoped free and empty-catalog states, whole-fact read-only behavior, comparable-cardinality query invariance, runtime closure, and sanitized malformed/missing-schema GET+HEAD failures including `Retry-After: 60`.

The fixture is isolated and deterministic. Retained intended RED is bound to exact source `cea8e7b01e9d66466fd417e4e6df84bd1d9e2ed349eb4b12a928be5d1e0cc392`, exits `255`, and fails first at `INTENDED_RED native application object in selected installer row` after denied authorization, authorized login, selection, upload, application, application persistence, and directory HTTP `200` pass. CI, deployment, and enforcement remain `UNKNOWN`/not configured; this review does not treat them as GREEN or authorize publication/deployment.

### Final Gate 3 verdict

`APPROVED`

Gate 3 passes for exact source `cea8e7b01e9d66466fd417e4e6df84bd1d9e2ed349eb4b12a928be5d1e0cc392`. Gate 4 may proceed against this reviewed package without changing approved expectations. Any later test/spec change requires the planner-selected review cycle again.

---

## Renewed Gate 3 review after Gate 5 findings — 2026-09-15

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210128Z-e364900be7/package.json`.
- Exact candidate source: `0dd691c670adda39877523b107772dafd40d935c74c0228c1aa7f2dd5a177168` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210128Z-e364900be7/snapshot/source.patch`, SHA-256 `1a25619705198620819249faf579d1aea20a16c764c47eb952de385d7a3a3755`.
- Reviewed delta from the Gate 5 snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210128Z-e364900be7/delta.patch`, SHA-256 `208ab146784d50377faaeaf259a497b1d0e51ee6d8e3d02fb6ed1a8e1cc9b6dc`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210128Z-e364900be7/verification-plan.json`, SHA-256 `d8681c40e8b04ba41edc1c6eacd7409affd4100d4d65d95db8907810a68f1a45`.
- Gate 5 source finding reviewed: `reviews/code/YII2-INSTALLER-DIRECTORY-ASSIGNMENTS-001.md`, candidate `642c46e6a701c96830f7ada0d38825be1e2f23332a84fe9b036ee800fda8665f`, verdict `CHANGES_REQUESTED`.
- Independence remains unchanged; this reviewer authored none of the specification, corrected tests, fixture, implementation, Gate 5 findings, or evidence.

### Gate 5 finding disposition

- The mixed native/fallback projection finding is fully covered. After native application replacement selects installer 7002 and a no-application registered fallback selects installer 7001, the corrected test requires the fallback link, unique assigned summary `2`, both installers in `assigned`, and neither in `free`. These expectations follow directly from the union of the two independently constructed authoritative branches and will reject the observed `$nativeIds`-only classification defect.
- The malformed-snapshot finding is partially covered. The table drives both GET and HEAD through top-level absence, junk-suffixed tab ID, empty installer name, empty installer position, and a null engineer container; every case requires sanitized `503`/`Retry-After: 60`, empty HEAD body, no stale/internal output, and unchanged facts.

### Complete renewed findings

1. **HIGH — the strict mandatory nested/identity integrity boundary is still under-specified by the RED matrix.** The Gate 5 finding explicitly identifies the established `YiiObjectCardApplicationIntegrity::validateComposition()` boundary, which requires not merely a non-null `selectedEngineer` container but positive/matching `selectedEngineer.userId`, nonempty engineer `fullName` and `position`, ordered/unique installer IDs, and row-bound `composition_identity`/`composition_sha256`. The new `engineer => null` case can be satisfied by checking only `is_array($selectedEngineer)`; none of the tests fail an implementation that ignores every mandatory engineer child or trusts a snapshot whose persisted composition identity/hash does not match it. Likewise the single `"7002junk"` case establishes rejection of junk coercion, while the existing installer field cases appropriately cover its mandatory strings. Add independently constructed GET+HEAD cases for at least mismatched/non-positive engineer user ID, empty engineer name/position, and mismatched composition identity/hash (plus duplicate/unsorted installer IDs if the directory adopts the canonical integrity contract). Each must retain the same sanitized failure, stale-fallback exclusion, and fact-preservation witnesses.

No other findings. Mixed summary/filter expectations are independent of production structure, and the intended RED record is correctly bound to source `0dd691c670adda39877523b107772dafd40d935c74c0228c1aa7f2dd5a177168`: exit `255`, first failure `native plus fallback unique assigned summary`, with earlier native workflow, replacement, filters, history, fallback link, ordering, and read-only checks reported passed. The existing complete matrix, deterministic isolation, bounded query oracle, runtime closure, and prior findings remain intact. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as approval or GREEN.

### Renewed Gate 3 verdict

`CHANGES_REQUESTED`

Gate 4 correction work remains blocked until the strict nested/identity cases above are added, captured as fresh exact-source intended RED, and independently rereviewed as one complete Gate 5 correction matrix.

---

## Renewed strict-integrity correction rereview — 2026-09-15

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210439Z-78620000b9/package.json`.
- Exact candidate source: `4e7b95a6ef202864ba7f7059b2f25589f84ff3e4178bf8f29a6eaa94fd32e8e8` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210439Z-78620000b9/snapshot/source.patch`, SHA-256 `2fed77876a428a4559cd9668541c3afc3d97fec91bad1e8fcc5ef04e5c7989b2`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210439Z-78620000b9/delta.patch`, SHA-256 `1e246862bda1985566e90442b18d05d0262e410a54b207ec20624eafd3981037`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210439Z-78620000b9/verification-plan.json`, SHA-256 `d54e7f033955a1042cd7a303c45ad8db6e4b6f886e61178c88466d40312994fe`.
- Independence remains unchanged; this reviewer authored none of the corrected tests, fixtures, specification, production code, Gate 5 findings, or evidence.

### Prior-blocker disposition

The correction adds the requested engineer container, zero/mismatched ID, empty engineer name/position, duplicate installer, unsorted installer, persisted composition identity, and persisted hash cases. Every case exercises both GET and HEAD, requires sanitized `503`/`Retry-After: 60`, excludes stale/internal output, and snapshots all facts. The name/position cases and direct persisted identity/hash mutations are independently sensitive. The sole prior blocker is therefore narrowed, but not fully resolved for semantic fields that participate in the hash.

### Complete findings

1. **HIGH — ID/order malformed cases are not independent from the stale composition hash.** `engineer-id-zero`, `engineer-id-mismatch`, `duplicate-installers`, and `unsorted-installers` mutate `selected_snapshot_json` while leaving the original row's `composition_sha256` unchanged. All four therefore fail the canonical hash comparison even if production never validates positive/matching engineer ID, duplicate IDs, or sorted installer order. An implementation that checks only hash consistency plus the already tested container/string fields would pass the entire new matrix while still accepting those semantic defects whenever a snapshot arrives with a correspondingly recomputed hash. For each of these semantic cases, compute and persist the hash that correctly corresponds to the mutated composition (using the independently specified canonical payload), then require GET+HEAD to fail for the semantic rule itself. Restore both snapshot and hash after every case. Keep the separate wrong-hash case to prove hash integrity independently.

No other findings. Empty engineer/installer name and position do not participate in the canonical composition hash and therefore already isolate their required validation. The direct `composition_identity` and `composition_sha256` mutations independently cover persisted identity integrity. Mixed native/fallback summary and filters remain complete and independent. The source-bound intended RED remains valid: at exact source `4e7b95a6ef202864ba7f7059b2f25589f84ff3e4178bf8f29a6eaa94fd32e8e8`, it exits `255` at the mixed native/fallback summary assertion after the earlier native, replacement, filter, history, ordering, fallback-link, and read-only witnesses pass. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or approval.

### Rereview verdict

`CHANGES_REQUESTED`

Gate 4 remains blocked on the one expected-value-independence correction above. Capture fresh exact-source RED after making the semantic malformed cases hash-consistent, then obtain renewed independent Gate 3 approval before implementation.

---

## Final Gate 5-correction Gate 3 rereview — 2026-09-15

- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210721Z-3823d6b430/package.json`.
- Exact candidate source: `b49c150c1f93f659fb104283c47a3215e00d170f77ac40a2fadb3d47ce54f30c` over base `cf0299d8ea65332b654662c696966b5bb953a1d6`.
- Snapshot: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210721Z-3823d6b430/snapshot/source.patch`, SHA-256 `de60ed78653df522484f8b5539aefa96ddb178f0d667dc05b194d8934ca330d6`.
- Reviewed delta: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210721Z-3823d6b430/delta.patch`, SHA-256 `b6e6f64ccc56e5effe4b04dc2d5352784a5b327622ada5e18930fe6fa9ac2fa9`.
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210721Z-3823d6b430/verification-plan.json`, SHA-256 `c6fa55fdad37ab7b04126c08cd0d8f8d9216e70e31989578c88466d40312994fe`.
- Reviewer independence is unchanged; no reviewed contract, fixture, test, implementation, Gate 5 finding, or evidence was authored by this reviewer.

### Sole-blocker resolution

Resolved. For every malformed case whose engineer and installer containers can form a composition payload, the test now derives and persists a SHA-256 from the independently specified canonical fields: case ID, persisted composition identity, mutated engineer ID, mutated installer ID sequence, and order ID. Thus junk-type coercion, zero/mismatched engineer ID, duplicate installers, and unsorted installers each carry a hash consistent with the mutated semantics and cannot fail merely on a stale-hash mismatch. Empty engineer/installer names and positions remain independently observable because those descriptive fields deliberately do not participate in the canonical hash. Null/top-level shape cases cannot form a canonical payload and correctly retain the original hash without weakening their structural failure witness.

The separate persisted `composition_identity` and `composition_sha256` mismatch cases remain intact after the valid snapshot/hash restoration, so identity integrity is tested independently from semantic validation. Every malformed case still exercises GET and HEAD with sanitized `503`, `Retry-After: 60`, empty HEAD body, stale/internal-data exclusion, and whole-fact preservation.

### Complete findings

None.

The mixed native/fallback summary and assigned/free filter RED remains independent and complete. Retained intended RED is bound to exact source `b49c150c1f93f659fb104283c47a3215e00d170f77ac40a2fadb3d47ce54f30c`, exits `255`, and fails first at `native plus fallback unique assigned summary` after the earlier native workflow, replacement, filters, history, ordering, fallback-link, and read-only witnesses pass. The full previously approved A1-A5 matrix, deterministic fixture isolation, comparable-cardinality query invariant, runtime closure, and corrected strict integrity matrix remain coherent. CI, deployment, and enforcement remain `UNKNOWN`/not configured and are not treated as GREEN or authorization.

### Final renewed Gate 3 verdict

`APPROVED`

The complete Gate 5 correction test matrix is approved for exact source `b49c150c1f93f659fb104283c47a3215e00d170f77ac40a2fadb3d47ce54f30c`. Minimal correction implementation may proceed without changing these expectations; any later test/spec change requires the planner-selected review cycle again.

---

## Post-approval test-delta audit — valid synthetic application integrity — 2026-09-15

- Audit scope: only root-authored changes to `tests/Yii2/yii2_installer_directory_native_assignments_001_test.php` after the approved snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260914T210721Z-3823d6b430/snapshot`.
- Approved snapshot patch SHA-256: `de60ed78653df522484f8b5539aefa96ddb178f0d667dc05b194d8934ca330d6`.
- Current source supplied for audit: `0fa2e7d1d1354481c0f6b443cc7978f8806f6d6a4dfe4dfd241d0c49a2b7e501`.
- Current test SHA-256: `d60657dbd3ea35f829a3dc85e721a5577157195087cab0fceb116b9fd2e686e0`.
- Independence: this reviewer authored none of the audited test changes, specification, fixture, or production implementation. Production is explicitly outside this disposition.

### Exact delta disposition

The isolated post-approval test delta contains only three related corrections:

1. The synthetic second application changes the selected installer from 7001 to 7002, so it now recomputes `composition_sha256` from its unchanged canonical identity and the resulting case/engineer/installer/order payload. This corrects an internally invalid fixture row; it does not change the expected latest-sequence replacement behavior.
2. The second synthetic native case changes case ID, order ID/version, and object. It now sets canonical `composition-82-v2` and recomputes the corresponding hash before insert. This corrects fixture integrity while preserving the already approved two-link order/dedup/repeat expectation.
3. At the multi-case point, installer 7001 still owns the valid no-application fallback and installer 7002 owns both native cases. The assigned summary must therefore be `2` unique installers, not `1`. Updating that literal restores consistency with A3/A4 and the already approved mixed-union assertions; it neither counts assignments instead of installers nor weakens uniqueness sensitivity.

### Findings

None.

These are fixture/oracle corrections required by the canonical integrity and mixed native/fallback contract. They do not remove a branch, relax a fail-closed assertion, broaden accepted malformed data, change the public seam, or couple expected values to production structure. All previously approved authorization, replacement/history, filter, ordering/dedup/repeat, strict malformed GET+HEAD, read-only, query-bound, and runtime-closure expectations remain unchanged.

### Test-delta verdict

`APPROVED`

The scoped post-approval test delta is approved at current source `0fa2e7d1d1354481c0f6b443cc7978f8806f6d6a4dfe4dfd241d0c49a2b7e501`. This disposition approves only the stated test-fixture/oracle corrections and makes no production-code, Gate 5, CI, publication, or deployment claim.
