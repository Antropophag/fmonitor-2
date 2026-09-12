# Independent Gate 3 test review — YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001

- Reviewer: separately tasked agent `issue39_gate3`; authored none of the reviewed specification, OpenSpec, verification input, plan, or test.
- Review date: 2026-09-12.
- Prepared package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160129Z-48423ecd2b/package.json`.
- Exact reviewed source: reconstructible dirty snapshot over base `6e4bf6bb3a6e923999fc06216af9a6e2abe697bb`, source digest `7d9a18c34bf2fa03afa1729bd91b246b70ca51d7469c4be5d6e162dcfda9b66c`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160129Z-48423ecd2b/snapshot/source.patch`, SHA-256 `8da29ddd41dfadce8ff3c4ae61da3d9c90e1d8af8b6a114d3ca1dff688f14c5c` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160129Z-48423ecd2b/verification-plan.json`, SHA-256 `2ff6b393c50b69ac0290fbd3c3622798df22800cd5b75bf365a2060a1b8f24f9`.
- Normative contract: `specs/YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001.md`; lifecycle change: `openspec/changes/exclude-documentary-closure-from-construction-control/`.

## Findings

1. **HIGH — the test is insensitive to the normative filtered-pagination boundary.** Locations: `specs/YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001.md:28-30`; OpenSpec scenario `Дело с актом ПТО не отображается`; `tests/Yii2/yii2_construction_control_active_queue_001_test.php:14-44`. The contract explicitly requires `total`, `pages`, `LIMIT/OFFSET`, and page-boundary validation to use the same post-PTO predicate, with the independent 51-eligible-case example producing pages of 50 and 1 and no excluded case creating an empty tail page. The test creates only four cases and requests only the default page. An implementation that filters the selected rows after applying the old count/offset, or updates SELECT but not COUNT, will satisfy every current assertion while violating the principal pagination requirement. Correction: add an isolated 51-eligible-case plus excluded-PTO fixture, exercise the real HTTP page boundaries (including rejection of the now-nonexistent tail), and assert exact identities/counts for both pages.

2. **HIGH — the verification plan does not retain the nearest inherited Yii2 queue controls promised unchanged by the contract.** Locations: normative points 3 and 5; `openspec/changes/exclude-documentary-closure-from-construction-control/verification-input.json`; prepared `verification-plan.json`. The plan includes the new test, schema, several photo characterizations, runtime storage, and governance checks, but omits `tests/Yii2/yii2_inspection_journey_001_test.php` and `tests/Yii2/yii2_inspection_boundaries_001_test.php`, which own the migrated queue controls, ordering/projection behavior, and safe boundary regressions inherited from YII2-INSPECTION-JOURNEY-001 A8/A9. Consequently the package claims preservation of latest engineer, mine/all/search/show-completed, sorting, links, Yii shell, escaping, empty return, and safe-error behavior without mapping or retaining their direct executable controls. Correction: map and plan the applicable existing Yii2 inspection queue/boundary tests (and any exact auth control relied upon), then regenerate the source-bound package.

3. **MEDIUM — repeated-read determinism and the full declared method/auth envelope are not exercised by the new public-seam test.** Locations: normative points 4-5 and OpenSpec scenario `Повторное чтение детерминировано`; `tests/Yii2/yii2_construction_control_active_queue_001_test.php:22-44`. The test compares database inventories around one authorized GET, then changes facts before the next authorized GET. It never repeats an identical GET without a fact change and compares composition/total/page boundaries, never sends HEAD, and does not assert the inherited guest redirect. The denied-user 403 case is useful but cannot establish the broader declared envelope. Correction: add two unchanged authorized reads with exact composition/count equality and cover HEAD/guest directly or explicitly bind retained tests that do so.

4. **MEDIUM — “without PTO regardless of checklist activity” has no sensitive pair.** Locations: normative point 1; `tests/Yii2/yii2_construction_control_active_queue_001_test.php:14-30`; `tests/Yii2/InspectionFixture.php:58-67`. The sole included working case has no constructed checklist-activity contrast. A regression that accidentally couples PTO exclusion to completion/activity ordering can pass. Correction: include eligible cases with and without checklist activity and assert both remain, while PTO-only and PTO-plus-declaration cases remain absent in their defined sort positions.

## Traceability and evidence assessment

The normative spec and OpenSpec agree on the public Yii2 GET seam, the first accepted `pto_act` fact as the exclusion boundary, read-only history, RBAC, and excluded adjacent scope. The test cites the stable specification identifier, uses the real Yii2 HTTP seam, creates isolated fixtures, derives literal expected identities from the contract, and snapshots all fixture tables through `PreopeningFixture::facts()`. Its basic PTO-only sensitivity and denied-user no-write assertion are sound.

The package-retained record `1789228878175247000-702378692ef2467289828e14df20654d` is source-bound with no drift and fails on the intended PTO-only visibility assertion, but declares fixture identity `UNKNOWN`; it is therefore not independently treated as sufficient evidence. A fresh reviewer run through the delivery harness supplied explicit fixture identity `isolated-inspection-fixture`: record `1789228964505813000-829ce329247242da97a2c678642c6b63`, source-bound to the same digest, fails with expected `false`, actual `true` at `INTENDED_RED PTO-only documentary case is absent`. This is valid intended RED for the behavior it reaches, but does not cure the coverage and plan gaps above.

`openspec validate exclude-documentary-closure-from-construction-control --strict` is GREEN, `git diff --check` is clean, and package/snapshot/plan hashes match. Harness reports CI and deployment `UNKNOWN`; neither is treated as GREEN or approval.

## Verdict

`CHANGES_REQUESTED`

Gate 4 is blocked. Return to Gate 2, add the bounded pagination/activity/repeat envelope and retained Yii2 queue controls, capture fresh intended RED at the regenerated exact source, and resubmit the complete package for independent review.

---

## Gate 3 correction rereview — 2026-09-12

- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160611Z-3bc65a9d4b/package.json`.
- Corrected exact source: reconstructible dirty snapshot over base `6e4bf6bb3a6e923999fc06216af9a6e2abe697bb`, source digest `ccf9fa9bcf990809d64848b7f208c1d9bf9dc48fbf4b1731b57ad288ee00d69b`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160611Z-3bc65a9d4b/snapshot/source.patch`, SHA-256 `50e2f40784e8462d6f275596f5f65b8ae2f31a95d5df6ae6eb35b3509e34f0c8` (matches `snapshot/manifest.json`).
- Corrected verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160611Z-3bc65a9d4b/verification-plan.json`, SHA-256 `d0dfe8a46bac2eecbfbaae5bd4213cdfcc63e4bdceb496fe7ee415b75d8a93da`.
- Independence remains unchanged: this reviewer authored none of the corrected contract, OpenSpec, verification input, test, or evidence.

### Resolution of previous findings

1. The filtered-pagination finding is resolved. The corrected test constructs exactly 51 eligible working cases plus separate PTO-only, PTO-plus-declaration, and non-working exclusions; it exercises pages 1 and 2 through real Yii2 HTTP, requires 51 combined rows, exact one-row tail, filtered totals on both pages, and after the tail case receives `pto_act` requires 50 rows, `total=50`, and rejection of the stale page 2. This is sensitive to filtering after LIMIT/OFFSET and to COUNT/SELECT predicate drift.
2. The verification-plan finding is resolved. `verification-input.json` and the generated plan now retain both `tests/Yii2/yii2_inspection_journey_001_test.php` and `tests/Yii2/yii2_inspection_boundaries_001_test.php`, alongside the new acceptance test and the applicable schema/governance/runtime controls.
3. The repeat/method/auth finding is resolved. The test compares byte-identical repeated authorized GET responses without intervening fact changes, checks complete fixture-table inventories, exercises authorized HEAD with an empty body, guest redirect to `/pilot/login`, exact-permission 403, and no facts on all reads/rejections.
4. The activity-sensitivity finding is resolved. A real checklist operation is accepted before the queue fixture is observed; the test independently requires both the active case with checklist activity and the active case without activity to remain, while PTO-only and PTO-plus-declaration cases are absent. The exact tail identity also retains sensitivity to the inherited activity ordering.

### Evidence assessment

No new findings. The corrected normative examples, OpenSpec scenario, verification input, test, and plan are coherent at the reviewed source. Expected identities, page sizes, total, transition, authorization outcomes, and preserved inventories are fixture-derived or contract-literal rather than copied from the planned SQL implementation. The real HTTP seam and `noLegacy()` load-boundary witness keep the test on the intended Yii2 public adapter.

Retained record `1789229148551420000-c2ebd807f91d4486ab676b1830f40d94` is `INTENDED_RED`, bound at start and end to corrected source `ccf9fa9bcf990809d64848b7f208c1d9bf9dc48fbf4b1731b57ad288ee00d69b`, with `source_drift=false` and a concrete fixture digest. It reaches the corrected pagination fixture and fails specifically because PTO-only object 4516 remains in the response: expected `false`, actual `true`. This is the intended missing production predicate, not setup failure.

`openspec validate exclude-documentary-closure-from-construction-control --strict` is GREEN and `git diff --check` is clean. Harness reports CI and deployment `UNKNOWN`; neither is treated as GREEN, approval, or deployment authorization.

### Correction verdict

`APPROVED`

Gate 3 passes for exact source `ccf9fa9bcf990809d64848b7f208c1d9bf9dc48fbf4b1731b57ad288ee00d69b`. Gate 4 may proceed against this reviewed package. Any later change to the approved specification or test expectations requires a new Gate 2/3 review.

---

## Gate 3 post-implementation test-delta review — repeat response — 2026-09-12

- Current exact-source package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T161223Z-589df541df/package.json`.
- Current source digest: `562e2924620ec0401558bda3aa12b22adc1fa2d327ebd15cb00f8f78a4f0416e`, over base `6e4bf6bb3a6e923999fc06216af9a6e2abe697bb`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T161223Z-589df541df/snapshot/source.patch`, SHA-256 `2bb98a9ac95c39d752b7002615eeacb089c23c3d9e34802cfdedc5b97be67ac5` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T161223Z-589df541df/verification-plan.json`, SHA-256 `7fe0a5b40d1c976593568b5c500dd0e185c8ecd882d80e27d6a61741b80bc158`.
- Previously approved Gate 3 package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T160611Z-3bc65a9d4b/package.json`.
- Review scope is only the repeat-GET assertion delta in `tests/Yii2/yii2_construction_control_active_queue_001_test.php`; this reviewer authored neither that delta nor the implementation.

### Assessment

No findings. The byte-level snapshot comparison shows that the approved repeat assertion changed from full HTML equality to three observable, contract-owned comparisons: identical ordered `data-object-id` values, the same literal `51 объектов` total, and the same link to page 2. This is sensitive to a repeated read changing queue membership/order, filtered count, or page boundary, while correctly ignoring the rotating Yii CSRF token that is not a queue determinism requirement. The complete fixture inventory comparison immediately following the assertions still proves that neither repeated GET writes process, checklist, completion, assignment, auth, or schema facts.

The production expectation and PTO filter assertions are unchanged. Historical record `1789229148551420000-c2ebd807f91d4486ab676b1830f40d94` remains valid intended RED: it is bound without drift to the previously approved source `ccf9fa9bcf990809d64848b7f208c1d9bf9dc48fbf4b1731b57ad288ee00d69b`, uses a concrete fixture digest, and fails earlier at the unchanged assertion that PTO-only object 4516 must be absent. The repeat assertion is not reached in that RED, so removing unstable full-HTML equality neither manufactures nor weakens the missing-behavior evidence.

Current record `1789229545082786000-010ba51f281c4caa8d3a56930c0c035d` is GREEN, bound at start and end to current source `562e2924620ec0401558bda3aa12b22adc1fa2d327ebd15cb00f8f78a4f0416e`, with `source_drift=false` and the same concrete fixture digest. It completes all PTO filtering, 50/1 pagination, activity/no-activity, transition/tail rejection, repeat semantic projection, HEAD, guest/403, history, and Yii-only load assertions.

Harness issue #99 currently cannot construct the standard current-source Gate 3 reviewer package for an already-GREEN test delta. Accordingly, the supplied package is role `root`, declares `approval=NOT_REVIEWED`, and has an empty embedded evidence list. Those fields remain UNKNOWN/not approval; this narrow verdict is instead based explicitly on the reconstructible exact-source snapshot, byte-level comparison to the prior approved package, the separately retained source-bound historical RED, and the supplied source-bound current GREEN record. CI and deployment remain `UNKNOWN` and are not treated as GREEN or authorization.

### Delta verdict

`APPROVED`

The narrow repeat-GET test correction is approved for exact source `562e2924620ec0401558bda3aa12b22adc1fa2d327ebd15cb00f8f78a4f0416e`. This verdict does not approve the production implementation (Gate 5), CI, or deployment, and does not broaden beyond the reviewed assertion delta.

---

## Gate 3 post-CI browser-oracle delta review — 2026-09-12

- Review scope: only the post-CI expectation delta in `tests/Yii2/inspection_browser.mjs` and its acceptance mapping in `openspec/changes/exclude-documentary-closure-from-construction-control/verification-input.json`; this reviewer authored neither.
- Root package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T172943Z-dfe871c4ee/package.json`.
- Exact reviewed source: committed base/head `b1d35f6d0bc6fae5fabf6b6fed505818c6a46454` plus reconstructible snapshot, candidate source `2d362e127a276241bfb09621a545f140889a6072001920a013ad5680ceec2258`, executable source `51f29917d20a143bc1806dbbd9eab190a4aedb055a754c773830f29f63f9b0ef`.
- Snapshot patch: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T172943Z-dfe871c4ee/snapshot/source.patch`, SHA-256 `6ef5e5e187f31bb57cd23e8066a52b87ba4ec1753b3b411c3559d6b6e26f1959` (matches `snapshot/manifest.json`).
- Verification plan: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T172943Z-dfe871c4ee/verification-plan.json`, SHA-256 `4d0bff52f3f45fec240eb05d05b90404d35f72d034744a8bbece366ad288dfad`.
- Triggering CI: GitHub Actions run `34706862367`, exact commit `b1d35f6d0bc6fae5fabf6b6fed505818c6a46454`, overall `failure`.

### Findings

No findings in the reviewed browser-oracle delta.

### Assessment

The previous browser expectation contradicted the already approved normative rule by requiring documentary case 4514 to remain server-rendered and allowing the completed toggle to reveal it. The corrected assertions derive directly from `YII2-CONSTRUCTION-CONTROL-ACTIVE-QUEUE-001`: exact initial server rows are `[4513,4512]`, 4514 is absent, mine/all still expose the active rows appropriate to those controls, the completed toggle cannot resurrect 4514, searching for excluded `QUEUE-4514` produces the empty state, and searching for active `QUEUE-4513` finds exactly one row. This is sensitive both to server-side leakage of documentary closeout and to accidental removal of the retained ownership/search controls. The production filter, normative specification and previously approved HTTP acceptance expectations are unchanged.

The verification input now maps `active-queue-browser-controls` to the real browser wrapper `tests/Yii2/yii2_inspection_browser_001_test.php`, declares expected GREEN, and retains the HTTP/history acceptance separately. The wrapper executes the changed `.mjs` against the real Yii2 browser fixture, so the mapping reaches the changed oracle rather than a lexical substitute.

All 15 generated focused commands are retained GREEN at candidate source `2d362e127a276241bfb09621a545f140889a6072001920a013ad5680ceec2258` and executable source `51f29917d20a143bc1806dbbd9eab190a4aedb055a754c773830f29f63f9b0ef`, each with `source_drift=false`. Records span IDs `1789234190867090000-94e00b66b4a44fcc8288cdaca2cb4eec` through `1789234405584218000-7d6dc23382b340a8acddb507732e34fd`. In particular, browser record `1789234198906063000-ac2406bb354a4dddb717c36105e90811` exits zero and reports `PASS: YII2-INSPECTION-JOURNEY-001 browser`; the active-queue HTTP, inspection journey/boundaries, schema, photo consumers, e2e composition, runtime, architecture, inventory and governance focused controls are also GREEN.

The triggering CI inventory must be reported precisely. Run `34706862367` did contain the relevant e2e failure `tests/Yii2/yii2_inspection_browser_001_test.php` at `only-working queue sorted no activity first`, which this delta corrects. It also contained two distinct unrelated failures: governance `tests/Verification/delivery_harness_ci_completeness_001_test.py` (`evidence source does not match current source`) and integration shard 1 `tests/InstallationProcess/assignment_order_original_database_setup_001_test.php` (lock-wait witness false). Therefore that CI run remains RED and is not treated as exact-source qualification, even though those two failures are outside this browser-oracle delta review.

Legacy verification plan v1 requires intended RED and cannot construct the standard reviewer package for this already-GREEN post-implementation correction. Consequently the supplied package is role `root`, has `approval=NOT_REVIEWED`, and embeds no evidence. These facts remain UNKNOWN/not approval. This independent narrow verdict instead relies on the reconstructible exact-source snapshot, the exact two-file diff, the triggering CI failure, and the separately retained source-bound GREEN records. A fresh exact-source full CI is still required after all CI findings are resolved.

### Delta verdict

`APPROVED`

The browser-oracle test delta is approved for candidate source `2d362e127a276241bfb09621a545f140889a6072001920a013ad5680ceec2258`. This does not approve or green CI run `34706862367`, the two unrelated CI failures, merge, deployment, or any production change beyond the previously reviewed implementation.
