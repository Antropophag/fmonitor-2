# Test review: CHANGE-VERIFICATION-PLACEMENT-181

- Reviewer: independent Gate 3 agent `/root/issue181_gate3`
- Test author: root
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T195435Z-eaaeee9ede/snapshot`; manifest SHA-256 `d049fc44d96864d6c760b60da46217bf7fefd272a744ba9e6f736f54914a9b49`; patch SHA-256 `a159200646eb30dff694b7d34a91b34c1e4cdbacc000b0e06bed7429d1692ead`; candidate source `dc07cf116dee6b28e132d942ab14aeb89d053c9ff2822cf844feec8abf2d735b`
- Agreed review scope / prior findings disposition (for rereview): initial independent Gate 3 review for issue #181; no prior findings
- Specification: `specs/CHANGE-VERIFICATION-PLACEMENT-181.md`
- Public seam: existing `change-verification.py plan/check/run`, `harness.py prepare`, reviewer package, and existing CI selection/aggregate
- Red command and intended failure: `python3 tests/Verification/change_verification_placement_181_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789674848176866000-0b7e2edb2a58457da6cdfdc1bf1eb0ee`; exit 1 with four failures, one of which is not an intended missing-behavior failure as described below
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **BLOCKING — the executable matrix does not cover most placement reasons required by the contract.** The test exercises a direct acceptance command inherited from the fixture and one synthetic `changed boundary obligation`, but it does not independently exercise a regression mapping, a changed registered test, or a known transitive consumer verifier (`tests/Verification/change_verification_placement_181_test.py:17-45`). Requirement 2 assigns all of those reasons to local execution, and requirement 4 makes precedence across multiple reasons central behavior. An implementation could place those untested classes in CI while satisfying the current assertions. Add separate cases for every local reason, plus mixed semantic-closure cases proving each stronger reason promotes the command to local exactly once and preserves all reasons.

2. **BLOCKING — the focused-run RED fails because the fixture is broken, not because placement behavior is absent.** The method invokes the whole focused plan and expects exit 0 (`tests/Verification/change_verification_placement_181_test.py:47-66`), but the plan also contains `make governance` and the disposable repository has no matching Makefile target. The retained run therefore exits 2 on `No rule to make target 'governance'` after all integration commands have run. This is an unrelated setup failure and does not establish the required RED. The same method only proves that CI-only commands should not create marker files after GREEN; it does not inject a failing local direct/consumer verifier and prove the bounded run returns non-success as requirement 7 demands. Make every selected local fixture command executable, assert the exact executed command set/count, then add a controlled local verifier failure with a diagnostic attributable to that verifier.

3. **BLOCKING — the actual reviewer-package seam is not tested.** `test_plan_exposes_local_and_ci_obligations_for_package_consumers` inspects planner JSON only (`tests/Verification/change_verification_placement_181_test.py:68-75`). It never invokes `harness.py prepare --role reviewer`, supplies valid local evidence, or inspects the emitted package. Consequently it does not prove requirement 5: completed local obligations must be distinguishable from pending CI obligations, CI-only obligations must require no fabricated local evidence, missing local evidence must still fail closed, and package preparation must succeed with the honest split. Add a package-route fixture that runs the local obligations through the harness, prepares the exact reviewer package, and asserts the complete local-evidence and CI-pending inventories and their absence/presence rules.

4. **BLOCKING — unchanged full-CI selection and fail-closed CI admission are untested.** The only CI assertion is the literal `make test` item in planner output (`tests/Verification/change_verification_placement_181_test.py:23-33`). No test invokes the existing CI selection or aggregate public seam. Missing, unexpected skip, failure, and cancellation are not covered at all, nor is the required case where local GREEN must not mask a CI-only consumer failure. This leaves requirements 6 and 7 insensitive and directly misses the requested admission matrix. Exercise the existing CI consumer with independently declared expected category/check inventory and assert non-success for each mandatory CI state: missing, skipped, failed, and cancelled; include local-GREEN/CI-failure and successful exact-source controls.

5. **BLOCKING — the promised shipped-#187 before/after oracle is absent.** The normative example requires the delivered #187 input to demonstrate reduced local commands with unchanged CI obligations (`specs/CHANGE-VERIFICATION-PLACEMENT-181.md:22-26`), and the design names the current harness/CI seams plus the #187 comparison fixture. The executable test imports only a synthetic semantic-closure fixture and never reads `tests/fixtures/delivery/issue-49-delivery.json` or another pinned #187 delivery input. Its expected integration set is generated from the same synthetic inventory it feeds the planner (`tests/Verification/change_verification_semantic_closure_153_test.py:38-44`), so it cannot detect omission or alteration of the repository's real required CI composition. Add a pinned independently expected before/after inventory for the supplied delivered input, proving exact local command/count change and byte-for-byte-equivalent CI obligations without executing the full suite.

6. **MAJOR — multi-reason execution behavior is only asserted at plan shape, and determinism is incomplete.** The test verifies one planner item and an exact two-reason list (`tests/Verification/change_verification_placement_181_test.py:35-45`), but it never runs that promoted command to prove “executes once per level,” never proves the ordering is deterministic under reversed discovery/order, and never checks that a CI-only command is not also duplicated in a second CI obligation representation. Add invocation-count markers for a promoted command and deterministic replay/order cases covering both plan commands and CI obligations.

7. **MAJOR — explicit fail-closed prepare compatibility is only inherited indirectly.** The acceptance example requires missing verifier, conflicting ownership, and invalid policy to retain fail-closed prepare behavior (`specs/CHANGE-VERIFICATION-PLACEMENT-181.md:26`). The inherited suite covers a missing integration inventory entry and malformed semantic-surface metadata at planner level, but the #181 test never invokes prepare and does not cover conflicting ownership. Add focused compatibility cases at the public prepare seam for all three specified rejection reasons, with stable diagnostics and no package publication.

The specification and OpenSpec artifacts otherwise keep the slice appropriately limited to delivery tooling, retain the full CI obligation, avoid FAST/product/admission expansion, and correctly select Gate 3 for sensitive verification policy. The retained RED contains three useful missing-schema/placement failures, but the unrelated focused-run setup failure and the missing public-seam coverage prevent approval.

## Required changes

1. Cover every normative local-selection reason and mixed-reason precedence with independently expected commands, reasons, deduplication, and execution counts.
2. Repair the disposable focused-run fixture and add a controlled local-verifier failure assertion.
3. Exercise the real reviewer `prepare` route with complete local evidence and explicit pending-CI obligations, including missing-local-evidence rejection and no fabricated CI evidence.
4. Exercise existing CI selection/aggregate behavior for success plus missing, skipped, failed, and cancelled mandatory CI, including local GREEN with CI-only failure.
5. Add the pinned shipped-#187 before/after comparison required by the contract while avoiding a local full-suite run.
6. Add deterministic replay/order and prepare fail-closed coverage for missing verifier, conflicting ownership, and invalid policy.
7. Retain a refreshed exact-source RED in which every failure is attributable to missing #181 behavior, then request independent Gate 3 rereview before implementation.

---

## Gate 3 rereview v2 — corrected RED source

- Reviewer: independent Gate 3 agent `/root/issue181_gate3`
- Test correction author: root
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T200016Z-478f6ecd52/snapshot`; manifest SHA-256 `0748ce45fccda18e954631fe8984b572d94d1eead49e475268461bbd66e0c223`; patch SHA-256 `2cb1243cb39c4ccae3852619dc2714e64d1dca389a4659e82ad19209d3acaf29`; candidate source `6187a29f02a7c752ac9af3c890fbab01d4c5de5ddc3597eda0eab2e57b24d5d7`
- Refreshed RED: `python3 tests/Verification/change_verification_placement_181_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789675182479426000-87a422204ace464da34358aefeb5a716.json`; exit 1 with six intended missing-behavior failures
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Unresolved (BLOCKING).** The corrected test still has no independently exercised regression-mapping, changed-registered-test, or known-transitive-consumer reason. The direct acceptance and `changed boundary obligation` cases remain the only concrete local-reason classes (`tests/Verification/change_verification_placement_181_test.py:18-46`). The #187 count test does not identify the four local commands or their rationales and therefore does not close this gap.

2. **Resolved.** The focused fixture now supplies a valid `governance` target, commits the executable fixture state, and reaches the placement assertion (`tests/Verification/change_verification_placement_181_test.py:48-71`). The refreshed RED shows it fails because CI-only integration markers were executed, not because setup failed. A separate controlled exit-23 direct verifier proves a local failure makes focused execution non-success and names the failed command (`:73-86`).

3. **Partially resolved; remains BLOCKING.** The test now invokes the real harness reviewer-prepare route, supplies a real local record, requires local and CI inventories, rejects missing local evidence, and guards against an extra fabricated evidence record (`:97-126`). It only asserts that both inventories are non-empty and that total evidence length is one. It never independently declares or compares the exact expected local/CI obligation entries, binds the retained evidence argv/source to the local inventory, or proves every CI-only entry lacks local evidence. Swapped, omitted, or fabricated obligation membership can pass. Assert exact independently expected obligation inventories and the evidence-to-local mapping, including explicit absence of evidence for every pending-CI obligation.

4. **Resolved.** The existing full aggregate receives one valid full result and rejects the integration obligation when missing, skipped, failed, or cancelled (`:128-143`). The successful local work is irrelevant to those mandatory CI failures, so it cannot mask them; the two required integration shard conclusions remain asserted.

5. **Partially resolved; remains BLOCKING.** The correction clones the shipped tree at the pinned commit, uses `tests/fixtures/delivery/issue-49-delivery.json`, and avoids executing the old suite (`:145-167`). But the expected old result is not reconstructed independently: the test trusts the corrected planner's new `comparison.before_local_count` field for `279`, and only checks that `ci_obligations` is truthy. It neither runs/captures the old planner output nor asserts an independently pinned exact pre-change CI/category/command inventory against the post-change inventory. A planner that reports `279` and emits one arbitrary CI obligation passes. Reconstruct the pre-change plan with the pinned old planner (or check in its exact independently reviewed inventory), then compare the exact unchanged CI obligations and assert the exact four post-change local argv/reasons.

6. **Unresolved (MAJOR).** The corrected source still checks multi-reason deduplication only in plan shape (`:36-46`). It does not execute that promoted command with a counter, reverse discovery order, replay the plan, or compare deterministic obligation ordering. The ordinary focused marker test uses semantic-only commands rather than the promoted multi-reason command.

7. **Unresolved (MAJOR).** No new prepare-level cases cover missing verifier, conflicting ownership, and invalid policy. The inherited planner-level missing-inventory and malformed-surface cases remain, but conflicting ownership and no-package-publication behavior at `harness.py prepare` are still absent.

### New findings

None beyond the incompletely resolved findings above. The refreshed RED is now valid: its six failures are attributable to absent #181 execution metadata, focused filtering, reviewer-package inventories, and #187 comparison behavior. The CI-state and controlled-local-failure cases pass, establishing usable setup. Gate 3 nevertheless cannot advance while requirements 2, 4, 5 and the normative #187 comparison remain insensitive in the ways listed above.

### Required changes for v3

1. Add independent cases for regression mapping, changed registered tests, and known transitive consumers, including semantic-closure overlap and exact preserved rationales.
2. Assert exact reviewer-package local and pending-CI inventories and map each retained evidence record only to its local obligation.
3. Reconstruct or pin the old #187 plan independently and compare exact CI obligations; assert the exact four new local argv/reason entries rather than planner-authored summary/count fields alone.
4. Execute the promoted multi-reason command exactly once and add deterministic replay/reordered-discovery coverage.
5. Add public-prepare rejection cases for missing verifier, conflicting ownership, and invalid policy, asserting that no package is published.
6. Retain another exact-source RED and request independent rereview before executor dispatch.

---

## Gate 3 rereview v3 — rebuilt acceptance matrix

- Reviewer: independent Gate 3 agent `/root/issue181_gate3`
- Rebuild author: root
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T200336Z-0ba9862d0d/snapshot`; manifest SHA-256 `ad82ba4a60ff660d18a639828ebad1e590dde4e2f9d49a09ed04daec23aca832`; patch SHA-256 `2c84f6a581a93cba70518e07e6e5009688a6903ab731c709a679034528271062`; candidate source `cd103559c6cbea8e9ece73e9ee594476ab9426c4936cd1c4c5be3109f60086e9`
- Rebuilt RED: `python3 tests/Verification/change_verification_placement_181_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789675381619334000-cda01f9530f04e05a28fcd1f2bfcc591.json`; exit 1 with eight failures, including two setup failures described below
- Verdict: `CHANGES_REQUESTED`

### Prior findings disposition

1. **Substantially resolved.** A changed registered integration test now has an exact independently expected local placement and two preserved reasons; the imported consumer-frontier fixture checks direct and transitive known-consumer commands for local placement and the ownership rationale (`tests/Verification/change_verification_placement_181_test.py:48-70`). The existing direct acceptance command covers mapped acceptance/regression execution at the planner seam. No additional blocking reason-class gap remains.

2. **Remains resolved.** The repaired focused fixture and controlled local-failure case remain intact (`:72-110`).

3. **Partially resolved; remains BLOCKING.** Package local and CI inventories are now compared exactly to the bound plan, and every CI obligation is checked absent from retained evidence (`:137-173`). However, the test still does not assert that the retained evidence argv belongs to the exact local inventory, or that every locally required obligation has matching current-source evidence. `len(evidence) == 1` can pass with an unrelated one-record package while the local inventory contains a different command or multiple uncovered commands. Compare normalized evidence argv/source against the exact evidence-required subset of `local_obligations`; retain the missing-local-evidence rejection.

4. **Remains resolved.** The full aggregate success and missing/skipped/failure/cancelled matrix is unchanged and valid (`:175-190`).

5. **Coverage design resolved, but current RED setup is invalid.** The rebuilt test now runs the pinned old planner before copying the corrected planner and compares exact old semantic/integration argv with new CI obligations (`:192-222`), removing the circular planner-authored count/oracle. But `before.json` is written inside the cloned worktree and is neither ignored nor removed before the corrected planner runs. The corrected planner therefore fails with `SETUP_FAILURE: unknown or ambiguous boundary: before.json` at `:214`, before any after-plan assertion. Write both plan outputs outside the checkout, add them to `.git/info/exclude`, or remove `before.json` before the corrected plan. Then retain RED at the intended placement/count comparison.

6. **Coverage added, but current RED setup is invalid and deterministic replay remains absent.** The promoted command has a counter and an exact once assertion (`:112-126`), but the method creates an uncommitted `Makefile`; `build_semantic()` rejects it as `SETUP_FAILURE: unknown or ambiguous boundary: Makefile` before producing a plan. Commit the fixture state/reset `base`, as the working focused fixture does. The rebuild still has no repeat/reordered-discovery assertion for stable command/rationale/CI-obligation ordering; add a deterministic second plan (and, where possible, reversed declaration/input order) comparison.

7. **Unresolved (MAJOR).** The rebuild adds no public-prepare rejection matrix for missing verifier, conflicting ownership and invalid policy/no package publication. Imported planner tests prove some underlying rejection behavior but do not prove the normative prepare seam remains fail closed.

### New findings

1. **BLOCKING — the retained RED is again contaminated by fixture setup failures.** `test_promoted_multi_reason_command_executes_once` fails on the unclassified `Makefile`, and `test_shipped_187_before_after_contract_is_pinned_without_running_it` fails on the generated `before.json`. Neither reaches the assertion for missing #181 behavior. Gate 2 requires every submitted RED failure to be attributable to the missing behavior rather than broken setup; both must be corrected and the exact-source RED refreshed.

The newly added changed-test and consumer-frontier failure is an intended missing-placement failure, and the independent old-planner reconstruction is the right oracle once its output is kept outside planner input discovery. No implementation should begin from the current contaminated RED.

### Required changes for v4

1. Commit/classify the promoted-execution fixture's `Makefile` before planning, then demonstrate the promoted command executes exactly once.
2. Keep `before.json`/`plan.json` outside the #187 checkout or explicitly exclude/remove them so the corrected planner reaches the intended before/after comparison.
3. Bind package evidence argv and exact source to the exact evidence-required local obligation set, not merely count one record and exclude CI argv.
4. Add deterministic repeat/reordered-discovery comparison for commands, rationales and CI obligations.
5. Add prepare-level missing-verifier, conflicting-ownership and invalid-policy rejection/no-publication cases.
6. Retain a clean exact-source RED containing only intended missing-behavior failures and request rereview.

---

## Gate 3 rereview v4 — final test approval

- Reviewer: independent Gate 3 agent `/root/issue181_gate3`
- Correction author: root
- Reviewed source: base `e245ba1c173cc09f9183380228a7532c8dc942d2` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260917T200553Z-e65a590a57/snapshot`; manifest SHA-256 `ca9a4fa8d7c628c719b1ace289d2355fa93c244ac8b733a6a8daa1cb1f6428a7`; patch SHA-256 `943d041472cf71a6b10af1cc9a05acbfb82135fbb5f69cb20e8631ea66817de8`; candidate source `ed992b2273744e8d1175735cf275d11ece256782021790a6a20d4be53d4bba87`
- Refreshed RED: `python3 tests/Verification/change_verification_placement_181_test.py`; retained record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789675519761060000-39334d512b364a3b94ec2c24ddc0be53.json`; exit 1 with eight intended missing-behavior failures and no setup failures
- Verdict: `APPROVED`

### Remaining findings disposition

1. **Resolved — clean promoted-execution RED.** The fixture commits its Makefile and executable baseline before changing the registered integration test (`tests/Verification/change_verification_placement_181_test.py:118-137`). Planning succeeds, reaches the absent execution-placement assertion, and the post-implementation expectation will prove the promoted command runs exactly once.

2. **Resolved — clean independent #187 reconstruction.** The old planner output is read and removed before the corrected planner is copied and invoked (`:232-263`). The refreshed RED reaches the intended `4 != 0` local-placement assertion rather than an unknown-boundary failure. The test independently fixes the old focused count at 279 and compares the exact ordered old semantic/integration argv inventory with the corrected plan's CI obligations.

3. **Resolved — positive evidence/local binding.** The reviewer-package case compares exact package inventories to its bound plan, binds retained evidence to the exact expected argv and candidate source, proves that argv belongs to the local inventory, and proves no CI-pending argv has fabricated local evidence (`:148-186`). The no-evidence reviewer preparation remains rejected.

4. **Resolved — deterministic replay/order.** Reversing semantic-surface discovery order and rebuilding must reproduce the byte-equivalent canonical command structure (`:36-52`). Combined with exact ordered rationales, single-command deduplication, the execution counter, and exact ordered #187 CI argv comparison, this is sensitive to unstable placement/order behavior.

5. **Resolved — prepare remains fail closed.** The real public `prepare` route rejects ambiguous capability ownership, invalid semantic category policy, and an unavailable integration verifier with their established diagnostics (`:187-211`). These checks complement the inherited planner fail-closed matrix and demonstrate that invalid inputs do not yield an admitted package.

6. **Resolved — RED integrity.** The retained exact-source run has 21 tests: 13 pass and eight fail only at absent #181 execution metadata/filtering/package/comparison expectations. The earlier `Makefile` and `before.json` setup failures are gone. Passing controls cover controlled local failure, existing CI aggregate success and mandatory missing/skipped/failed/cancelled rejection, inherited semantic closure, FAST preservation, and policy validation.

### Final assessment

The specification and rebuilt executable matrix now cover the complete bounded slice at the declared public seams: semantic-only CI placement; local precedence for acceptance/mapped regression, changed registered tests, direct boundary obligations and known transitive consumers; deterministic multi-reason deduplication and once-only focused execution; honest reviewer-package local evidence versus CI-pending obligations; exact shipped-#187 before/after composition; and unchanged fail-closed full-CI admission for missing, skipped, failed and cancelled mandatory work.

Expected values are independently anchored in synthetic inventories, pinned shipped history, exact argv/reason lists and existing public consumer behavior rather than planned implementation internals. Fixtures are disposable and deterministic, do not contact production systems, and do not execute the prohibited local full suite.

Gate 3 is approved for separate executor implementation. This approval covers the specification, tests and retained RED only; it is not Gate 5 approval of implementation or CI evidence.

### Required changes

None.
