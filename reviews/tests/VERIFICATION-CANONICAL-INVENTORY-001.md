# Test review: VERIFICATION-CANONICAL-INVENTORY-001

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the specification or tests.
- Test author: root agent.
- Reviewed source: base `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T113903Z-f66ec7964f/snapshot`; patch SHA-256 `ffd46c0bd22193287bddb60a969feda14de967845333c666be42adb3997d681e`.
- Candidate/executable source: `a803c3e9de16c6350da792f86652642a6e2481dee6da1e288c701edb5fbd3ec9` / `ba5b45c9de8c263111f11b447d825ff4e52c02cc7cf9bb0f13bcb2696e09607b`.
- Agreed review scope: normative spec and complete root-authored Gate 2 candidate for issue #135; traceability, public seams, sensitivity, independent expected values, rejected cases, determinism, isolation and intended RED.
- Specification: `specs/VERIFICATION-CANONICAL-INVENTORY-001.md` SHA-256 `e5baa4363d293168efdf3d1025b2a8ceb3b48c6f0f492ae2c94f3d99838c8601`.
- Public seams: canonical inventory CLI, Make registration, change-verification build/check, CI composition and Quality Graph execution.
- RED evidence: exact-source harness records `1789472227870965000-9a73705191ac475b95a4c69e26600413`, `1789472278229782000-13bde798cca145e3b9f028e5e94cc5ea` and `1789472227867421000-332b3d4149534616916e7c45d29c39d1`; all exit 1 and are recorded as `INTENDED_RED` for the candidate/executable source above.
- Verdict: `CHANGES_REQUESTED`.

## Findings

1. **HIGH — migration preservation has no independent executable oracle** (`specs/VERIFICATION-CANONICAL-INVENTORY-001.md:26,65`; `tests/Verification/verification_inventory_001_test.py:98-120`). R1 requires exact preservation of all 427 base mappings across path, legacy suite, runtime and CI category. The repository-baseline test reads the post-migration `suites.tsv` itself to construct `expected`, then compares runner output and a summary count to that same candidate file. An implementation may change a category, runtime or suite, or remove a row consistently, and this test will accept the changed candidate. Freeze an independently reconstructed base roster/digest (or compare mechanically to `main@25aee552`'s two manifests) and assert the complete resulting four-field mapping and exact count.

2. **HIGH — concurrent stale-writer rejection is untested although the plan marks it covered** (`specs/VERIFICATION-CANONICAL-INVENTORY-001.md:16,47`; `openspec/changes/canonical-verification-inventory/verification-input.json:54`; `tests/Verification/verification_inventory_001_test.py:122-149`). The registration test performs one successful write followed only by sequential invalid requests. It never overlaps two valid registrations or proves that a stale writer fails without losing the winning row and without changing bytes after rejection. Add a deterministic synchronization-controlled two-writer test through the public registration seam and verify one serialized success, explicit stale rejection, preservation of the winner, and a valid final manifest. Correct the verification-plan coverage claim until that exists.

3. **HIGH — the repository-owned Make registration seam and its required-argument contract are not exercised** (`specs/VERIFICATION-CANONICAL-INVENTORY-001.md:12,45-47`; `tests/Verification/verification_inventory_001_test.py:122-149`). The only registration test invokes `inventory.py register` directly. It cannot catch a missing or incorrectly quoted `register-test` target, inferred/defaulted values, Make-variable leakage, or modifications outside `suites.tsv`. Add tests invoking `make register-test` for success, each omitted required variable, hostile but valid argv content/path handling as applicable, and rejected requests; compare a complete repository-tree snapshot so success changes only the manifest and every failure preserves its bytes.

4. **HIGH — fast validation is checked by source substrings, not observable behavior** (`specs/VERIFICATION-CANONICAL-INVENTORY-001.md:57-61,74-76`; `tests/Verification/verification_ci_001_test.py:342-352`). `test_fast_validates_inventory_without_running_governance_contract` passes if the function merely contains the text `tools/verification/inventory.py`; a comment, dead branch or ignored child exit is sufficient. It does not invoke `run-fast-node`, corrupt the manifest, prove nonzero exit before category execution, or trace that the governance contract was not executed. Exercise the public fast seam with valid and each material invalid inventory/discovery condition, trace interpreter calls, and assert fail-closed ordering plus absence of direct governance-test execution.

5. **MEDIUM — removal of the second registry and all active references is not regression-sensitive** (`specs/VERIFICATION-CANONICAL-INVENTORY-001.md:26,66`; all three submitted tests). No assertion requires `tools/verification/categories.json` to be absent or searches the bounded active consumers for references. An implementation can retain the JSON registry (and even keep an active consumer reading it) while satisfying the submitted tests. Add a bounded repository assertion for deletion and zero references in active policy, Make, workflow, runner, CI and planner sources, while explicitly excluding historical review/archive records as the contract requires.

The test fixtures are isolated in temporary repositories and require no network, Docker or live database. Ordering checks for canonicalization and integration sharding are deterministic. Invalid enums, duplicate/missing/path failures and early unregistered-test diagnostics receive useful coverage. These strengths do not close the missing normative seams above.

The three RED records are source-bound and reproducible evidence of a missing implementation, but they are broad: the current three-field parser/JSON consumers reject many converted fixtures before several individual assertions can reach their intended behavior. This is acceptable only as preliminary RED evidence; the corrected candidate must retain at least one causally specific failure for each newly added behavioral axis, especially concurrency, Make routing, exact base mapping and fast fail-closed execution.

## Required changes

1. Add an independent exact oracle for all 427 migrated four-field mappings.
2. Add deterministic competing-writer/stale-write coverage and correct the plan's concurrency claim.
3. Exercise `make register-test`, all four required arguments, quoting, and complete filesystem effects.
4. Replace the fast source-text check with behavioral public-seam tests for valid and corrupt inventories and execution ordering.
5. Assert deletion of `categories.json` and absence of references from every bounded active consumer.
6. Capture new exact-source intended-RED evidence and submit the complete corrected spec/test delta for independent rereview before Gate 4.

## Correction rereview — 2026-09-15

- Reviewed source: base `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T114540Z-dd15aca231/snapshot`; patch SHA-256 `fc5f0bae52c09401567afcf01581fff7c5ee455782b3006aba1fa40598009b0e`.
- Candidate/executable source: `ee3f2b50cc53ea76cf1941d4127f9a41275e5abeb93c9d75349642614896b4c0` / `3756f535bc5c6f6a7c71a26d0e8837fca774d037b85eb3556fdda3cdc3f5dfde`.
- Corrected specification SHA-256: `ead2e1597d8f65ee95ecba5ac46b2d6bb2fac6322e4fabfd717b5b4676b5ce91`.
- Corrected test SHA-256 values: `9522df1fe572666975a4ca062b2bb4702e009c1d34ca23dceafad1d55c35a2ca` (`verification_ci_001_test.py`) and `97fd50790ed7595737aecdaa4ad3a3244af8922ab169272fcea317830a45ac2f` (`verification_inventory_001_test.py`). The other two submitted tests are unchanged from the first review.
- RED evidence: exact-source records `1789472681408348000-2a279e4baffe4c649002d2eb41de10dd`, `1789472681408346000-8382b6c839c54d31883df64b48e2ed94` and `1789472681413556000-3312df4868314ee78353118c4b20f075` all exit 1 and bind to the corrected candidate/executable source above.
- Prior findings disposition: findings 1, 3, 4 and 5 resolved; finding 2 remains blocking because the revised normative artifacts contradict one another.
- Verdict: `CHANGES_REQUESTED`.

### Complete correction findings

1. **HIGH — stale-writer behavior remains normative and untested despite being declared out of scope elsewhere** (`specs/VERIFICATION-CANONICAL-INVENTORY-001.md:16,47`; `openspec/changes/canonical-verification-inventory/design.md:26`; `openspec/changes/canonical-verification-inventory/verification-input.json`, `worktree_concurrency_isolation`; `tests/Verification/verification_inventory_001_test.py:150-207`). The revised actor/seam section and design now say inter-process coordination and a stale-writer protocol are not introduced, and the verification input marks that dimension not applicable. However, R3 still explicitly includes `competing stale write` among failures that MUST preserve the original manifest bytes. That acceptance remains unobservable: the corrected tests contain no competing writers or stale-write request. Resolve the contract in one direction before Gate 4: either remove the stale-writer requirement from R3 and its related acceptance language consistently, or restore the behavior to scope and add deterministic public-seam coverage. A plan `not_applicable` declaration cannot waive a still-normative MUST.

The exact 427-row migration check now reconstructs all four fields independently from both manifests at fixed base `25aee552` and compares the complete candidate mapping. The Make test exercises successful registration, all omitted inputs, shell metacharacter handling, and whole-tree effects. The fast-node test calls the orchestration function, proves validation is first, propagates failure before later checks, and excludes direct governance execution. The deletion/reference test covers the bounded active consumers while leaving historical records alone. These resolve prior findings 1, 3, 4 and 5.

The corrected RED records are exact-source and include causally specific failures for the new migration, duplicate-registry, Make and fast-order assertions. Fixture isolation and deterministic expected values remain adequate. No additional findings were identified in the corrected scope.

### Required correction

1. Reconcile the stale-writer normative contradiction. If it remains a MUST, add deterministic competing-writer RED coverage; if it is intentionally excluded from issue #135, remove the remaining MUST and align the stable spec, delta/design and verification plan.
2. Capture exact-source RED for the resulting final test/spec candidate and return the corrected delta for independent rereview before Gate 4.

## Final correction rereview — 2026-09-15

- Reviewed source: base `25aee5524f790292d350175ba278bc47e282ed4c` plus retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T114805Z-9fcaba3b28/snapshot`; patch SHA-256 `f87cd6c10b1f69cd1d3736b9f7e611b7c9b38c1a4a16f5fdeece3bb50bcfd566`.
- Candidate/executable source: `9eea87b00d60db8ad5a3f7b68f0d7a0f4f2bbd8416d431675684a336cd89c8ca` / `65060f1dab6a551db9bca64f63ac68b7fc2d09052d614a5b5163fff31517f5e7`.
- Final specification SHA-256: `e87770d45b17bf8fca22c476bda4dcd309a3cf79c7fd259fa80d3afbacd3fe15`.
- Test SHA-256 values: `45a2fd71385b1c9a2cc7f3dcf10752e01bffe83b57d4dde23f8340190e8d3384`, `9522df1fe572666975a4ca062b2bb4702e009c1d34ca23dceafad1d55c35a2ca`, `97fd50790ed7595737aecdaa4ad3a3244af8922ab169272fcea317830a45ac2f`, and `2cdd59caf7e16cc4af4b015c2ce0190b35fb55d4a84443168e88d6f1ff0559f5` for the four package-listed test files respectively.
- RED evidence: exact-source records `1789472832480156000-56bd0b180033408b9a238356cc41c32c`, `1789472832471453000-42d416ea0085481b91f0e3096fcf843e` and `1789472832479454000-027626ffe180458b9e2d4402b8c62936`; each exits 1 with candidate and executable digests unchanged at completion.
- Prior findings disposition: all resolved.
- Verdict: `APPROVED`.

### Complete final findings

None.

The stable specification now limits atomic registration to validation before one atomic replace and byte preservation for failures before that replace. It explicitly excludes inter-process writer coordination. The OpenSpec design makes the same decision, the delta specification contains no stale-writer requirement, and the verification plan records concurrency isolation as not applicable with the matching bounded-scope reason. The final correction therefore resolves the only remaining contradiction without weakening any other R1–R5 acceptance.

The complete test candidate remains traceable and sensitive: it independently reconstructs the exact 427-row base mapping; exercises validator, CLI and Make registration seams; checks unchanged bytes and whole-tree effects on rejection/success; checks canonical ordering, discovery, category partitioning and planner rejection; observes fast validation ordering and fail-closed propagation; excludes direct governance execution from fast; and enforces removal of the duplicate registry and active references. Expected values come from the fixed base manifests or explicit contract examples, and temporary repository fixtures keep execution deterministic and isolated from production services.

The three focused records remain intended RED for the missing implementation rather than environment setup: current consumers still require the old three-field/JSON inventories, the canonical CLI and Make target do not yet exist, the duplicate registry remains, and fast runs its old command first. Gate 4 may begin against this reviewed test/spec source without weakening expectations. Final exact-source focused GREEN, planner-selected full CI, and independent Gate 5 review remain required.

### Required changes

None.

## Final main-integration roster test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the tests or implementation.
- Main synchronization: `origin/main@7c31ffb89d62d00fd88b4d4c8e454b9a7d4cdb9f` adds `tests/Yii2/yii2_main_navigation_001_test.php`; current branch registers it as `db/php/integration`.
- Delta: `tests/Verification/verification_ci_001_test.py` SHA-256 `535c83f4d083bcc0f66d5030ea2f71868b28eb3347b17b81e295c641993d92f5`; the hard-coded complete-roster SHA assertion was replaced by equality between `ci.py` output and a projection of the current candidate manifest plus a unique-path assertion.
- Verdict: `CHANGES_REQUESTED`.

### Complete delta findings

1. **HIGH — the replacement removes the independent oracle for post-base main additions** (`tests/Verification/verification_ci_001_test.py:423-451`; `tests/Verification/verification_inventory_001_test.py:128-142`; `tools/verification/suites.tsv:211`). Both sides of the new equality are derived from the same candidate `suites.tsv`: `ci.py list` reads it, and the test's `manifest` list reads it directly. If an implementation or later reconciliation deletes the synchronized `yii2_main_navigation_001_test.php` row (and its test file), both projections shrink together, uniqueness remains true, the explicit required list does not mention this path, and the historical-427 subset test remains GREEN because this row was added after `25aee552`. Thus the delta checks consumer self-consistency but no longer proves preservation of the exact new `main@7c31ffb8` registration. This repeats the oracle weakness found in the initial Gate 3 review, now specifically for post-base additions.

The manifest/projection equality and unique-path invariant are useful R2/R5 assertions and should remain. They are not substitutes for an independently derived preservation expectation. Preserve legitimate future additions without a perpetually stale whole-roster literal by reconstructing the required synchronized-main tuples from fixed `main@7c31ffb8`'s `suites.tsv` plus `categories.json` and asserting that set is a subset of the current four-field manifest, analogous to the approved historical-427 test. A narrower explicit assertion that `yii2_main_navigation_001_test.php` occurs exactly once as `db/php/integration` would close this immediate delta, but a fixed-main subset oracle better covers all parallel additions incorporated by this synchronization.

The public-route registration itself is correctly shaped and canonically placed. The retired-path bans and existing required semantic assertions are unchanged. No production-code finding is made in this test-only review.

### Required changes

1. Retain the new complete projection equality and uniqueness checks, but add an independent executable oracle that preserves every registration incorporated from `main@7c31ffb8`, including exact `db/php/integration` membership for `tests/Yii2/yii2_main_navigation_001_test.php`.
2. Capture focused evidence for the corrected test delta and return it for independent rereview before relying on this post-sync candidate.

## Final main-integration roster correction rereview — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the test or implementation.
- Corrected test: `tests/Verification/verification_inventory_001_test.py` SHA-256 `e5152aff0f991af8187cc241a5497e12f4610ae56c75e7852355a31a01c63535`. The projection test remains `535c83f4d083bcc0f66d5030ea2f71868b28eb3347b17b81e295c641993d92f5`; stable specification remains `e87770d45b17bf8fca22c476bda4dcd309a3cf79c7fd259fa80d3afbacd3fe15`.
- Focused verification independently run by this reviewer: `python3 tests/Verification/verification_inventory_001_test.py` — 22/22 GREEN in 17.208 seconds.
- Prior finding disposition: resolved.
- Verdict: `APPROVED`.

### Complete correction findings

None.

The new test independently enumerates all five registrations accumulated from the post-`25aee552` main synchronizations and requires each exact tuple once: `db/php/<path>/integration`, including `tests/Yii2/yii2_main_navigation_001_test.php`. Removing a row, duplicating it, or changing its suite, runtime or category now fails independently of `ci.py` and its projection of the current manifest.

The previously added complete manifest-versus-category projection equality and global unique-path invariant remain unchanged, so consumer completeness and single-category composition stay covered. Together with the historical 427-row subset oracle, the tests now protect the full synchronized provenance without using a whole-roster hash that becomes stale on every legitimate later addition.

The correction is bounded to preservation assertions for already integrated main registrations. It changes no parser behavior, product test semantics, retired/required lists or issue scope. No weakening or additional finding was identified.

### Required changes

None.

## Post-main-sync historical-roster test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the test or implementation.
- Main synchronization: `origin/main` advanced to `2ec819e83b1fd769fb5ec9d916ffa239da83b56b`, adding four canonical inventory entries beyond the contract's historical base `25aee5524f790292d350175ba278bc47e282ed4c`.
- Delta: one assertion in `tests/Verification/verification_inventory_001_test.py`; current SHA-256 `f79936e65957d6e6f11147279ecc7b15ad212a54e1f4c11ebd158d43f669acf2`. Stable specification SHA-256 remains `e87770d45b17bf8fca22c476bda4dcd309a3cf79c7fd259fa80d3afbacd3fe15`.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The normative migration oracle explicitly protects the 427 mappings from `main@25aee552`. The corrected assertion still reconstructs every historical `(legacy suite, runtime, path, CI category)` tuple independently from that commit's two manifests, still requires exactly 427 historical entries, and now requires that complete set to be a subset of the synchronized canonical manifest. Any removal or change of suite, runtime, path or category for an original row remains RED.

Changing equality to subset membership is necessary for authorized parallel additions from newer `main`; equality would incorrectly turn every later registered test into a regression. It does not make the four new entries unchecked: the public validator and repository-baseline tests still require all current rows to have valid enums, existing/discovered paths, unique membership and exact canonical ordering, and category/suite projections must cover the entire current manifest. At review time the synchronized manifest contains 431 rows, 431 unique paths, and is in normative tuple order.

No production behavior, parser rule, historical expected tuple or specification changed. The delta neither permits mutation of the protected 427 mappings nor expands issue #135; it only distinguishes immutable historical migration evidence from legitimate append-only main evolution.

### Required changes

None.

## CI pycache filesystem-effect test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the tests or implementation.
- Delta: one filesystem-effect assertion in `tests/Verification/change_verification_001_test.py` and one in `tests/Verification/verification_ci_001_test.py`.
- Current test SHA-256 values: `66c42a1d7427e17c86ae452c522bfb891af060e0a5d0ff85fa4aa4fb1dfed5e3` and `900197b568998a2e38f2a3d48966ff3150c93c618284df843a84912c94c22d14` respectively. The stable specification and inventory/native tests remain unchanged at the hashes recorded in the preceding reviews.
- RED evidence: exact GitHub CI run `34974282041` reported plan, fast, E2E and both integration shards GREEN; unit and governance failed, with downstream verify blocked. The shared complete failure inventory identifies `tools/verification/__pycache__/inventory*.pyc` appearing between planner snapshots: `fast_lane_118` fails in unit and `change_verification_001_test.py` fails in governance from nondeterministic source reconstruction/stale-plan detection. Local focused executions remain GREEN because that environment does not reproduce bytecode emission.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The new assertions exercise observable filesystem effects at public consumer seams. The planner test invokes planning twice, already requires byte-identical plans, then requires that importing the shared inventory parser created no `tools/verification/__pycache__`. The CI composition test lists all four categories through the public CLI, already checks exact partitioning and absence of runtime/DB execution, then applies the same no-cache assertion. Neither assertion mocks the parser or depends on a private function.

This directly strengthens the accepted idempotence and filesystem-effect contract. A generated `.pyc` under a tracked source boundary changes candidate/source snapshots and can make the same logical input reconstruct differently; therefore its absence is required for deterministic planning and read-only listing even though local Python settings may suppress its creation. The environment-specific CI failure is valid RED characterization of that observable behavior, not an excuse to weaken or skip the check.

No previous expected value, command, fixture, category member or failure condition changed. No production/specification scope was added: the assertions constrain side effects of the already required shared parser at the already reviewed planner and CI seams. Exact-source GREEN after correction and final independent Gate 5 review remain required.

### Required changes

None.

## Post-CI direct-consumer test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the tests or implementation.
- Reviewed delta: `tests/Verification/delivery_harness_001_test.py`, `delivery_harness_hardening_001_test.py`, `delivery_harness_ci_completeness_001_test.py`, `fast_lane_118_classification_test.py`, and `quality_graph_ci_setup_001_test.php`; no `fast_lane_118_admission_test.py` byte change is present.
- Current SHA-256 values: `96878c17f5fc5e926edc93b527d9e7555d574b3b3f190da20c8c8a8fa01ef08c`, `2838af779d45c007b0092ccbdbdc099ff63f536d1588514c9c1cfad92e75f46a`, `99e89fab65ec99f7bf827b4441a4858e6d456e633f361fe8aef1e219cdd999e3`, `6fc67d402923279efb63010c07667b48aa187c9b124dbe84103c232915e6492b`, and `d818dc8c1e0ca275bcf92049f403484938a36134a70d7c238cdf1b765e1ec0f2` in the file order above.
- Focused evidence reported with the review request: `delivery_harness_001_test.py` 25/25 GREEN; `delivery_harness_hardening_001_test.py` 9/9 GREEN; `fast_lane_118_classification_test.py` 7/7 GREEN; `delivery_harness_ci_completeness_001_test.py` 16/17 in the aggregate run followed by the remaining lineage case GREEN with its planner-derived typed environment; lint GREEN. No exact record identifiers were supplied for these runs, so this review does not claim independent record/source binding for them. The earlier complete CI failure inventory remains the reason for this bounded compatibility delta.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The five changed direct-consumer tests are migrated mechanically from deleted `categories.json` plus three-column rows to sorted four-column `suites.tsv`. Isolated repositories now receive `inventory.py`, materialize every registered path, and register synthetic canonical tests before planner/package operations. These are required runtime and discovery preconditions for the already approved one-parser contract; they do not bypass inventory validation.

The PR98 stale scenario is stronger and correctly sequenced. Its new canonical test first fails planning with exact `UNREGISTERED_TEST`, then is explicitly registered. The later preflight still rejects the candidate, preserves `publication_ready == false`, retains evidence, and requires the two remaining independent failures `GENERATED_SOURCE_DRIFT` and `UNDECLARED_TEST_DEPENDENCY`. Removing `STALE_VERIFICATION_INVENTORY` from that later set does not weaken admission because stale dual-registry state no longer exists and early canonical validation has already tested the replacement failure.

Other semantic protections remain intact: missing catalog members fail rather than becoming `missing_tests`; Gate 3 evidence completeness, mixed RED/GREEN mapping, planner bindings, undeclared PHP/Python/Node dependencies, local-source precedence, source drift, and CI lineage remain asserted. The lineage case now passes the exact planner-selected command environment into both historical RED and current GREEN records, strengthening source/environment identity rather than altering the expected decision.

The hardening test continues to prove an unregistered discovered E2E test fails and that explicit registration restores one exact E2E member. Its fixture changes from Python to Node only to use an actually discovered canonical naming/runtime pair. Adding `delivery_execution_107_i1_test.py` to the bounded verify-list expectation reflects the entry already present on current `main`; it changes no #107 product behavior or admission rule.

The PHP Quality Graph setup test merely copies the shared parser required by `run.sh` and supplies canonical sorted rows with unchanged unit/db intent. Fast-lane fixtures likewise use the canonical registry and materialize its paths; lane classification and real-oracle expectations are unchanged.

No assertions were deleted to obtain GREEN, no allow-failure path was added, and no production/specification behavior or issue scope changed. These corrections preserve the original consumer semantics under the new canonical inventory boundary.

### Required changes

None.

## Planner and CI canonical-fixture test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the tests or implementation.
- Delta test SHA-256 values: `d84d50855438976cb5f86a4311b3b2bd7cc6f44b8b5099ed6ba168367d941c76` (`change_verification_001_test.py`) and `09a65415ce1155805f96eae3d141147855f299ebeea2c919bd41c3dca87e2cf9` (`verification_ci_001_test.py`). The approved inventory/native test and stable specification hashes remain `1509b3ca053f41bf739197e6a098cc45dac760b57586b064fa1cf70f3fe5d684`, `443c64188d9c36055031cc27676f4fde4b401dcbb1d04b03bde63cf56d632ac0`, and `e87770d45b17bf8fca22c476bda4dcd309a3cf79c7fd259fa80d3afbacd3fe15` respectively.
- Exact CI GREEN evidence: record `1789475550453659000-8c9681fbe6864e768f91771982bbb2df`, candidate/executable source `0a944e808be703353e2a24e8f75c968df24634f215b7cfcdb349a3baa5e6ff8d` / `dfeb07b8982953693387232a9a64517fc2d108538cf9ff4ca1125756b98eee84`; command blob equals the current CI-test SHA-256 and all 18 tests pass. Root also reports the direct change-verification file 18/18 GREEN; no separate record identifier was supplied for that result. The immediately preceding exact inventory record remains 21/21 GREEN.
- Verdict: `APPROVED`.

### Complete delta findings

None.

Every changed fixture writer now uses the normative `(legacy suite, path, runtime, CI category)` key rather than raw TSV lexical order: both change-verification inventory helpers, the CI fixture's initial rows, added integration rows, and dynamically appended Python row. This aligns valid test setup with R1 and removes no validation assertion.

The integration-shard test remains at least as sensitive as before. It independently constructs the canonical expected path order for unsharded output, verifies each alternating shard exactly, checks their union and disjointness, and proves listing invokes no runtime or database probe. After deliberately reversing the manifest, both shard requests must now fail with the canonical-order diagnostic. Replacing the former “reversed input produces the same shard output” expectation is required by the newly reviewed fail-closed order contract; accepting reversed persisted bytes would contradict R1/R2.

The change-verification fixture adjustments only keep synthetic registrations canonical. Dynamic tests are still explicitly registered, planner selection/category assertions remain unchanged, and child failure propagation is still exercised. No production or specification bytes changed, and no test, shard member, failure condition or public seam was removed.

The exact CI record confirms all 18 cases GREEN at the current source, while the prior inventory RED/GREEN evidence continues to establish sensitivity for strict ordering. No weakening or scope expansion was found.

### Required changes

None.

## Canonical fixture-order test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the tests or implementation.
- Prior deterministic-order RED boundary: `tests/Verification/verification_inventory_001_test.py` SHA-256 `30f24af937e4b75f7f697383586e7353a7f0c252163ab701e1d80df9910968fc`, record `1789475026433788000-70370ab80e414dddb1dc3dae2fc64ddc`.
- Current test SHA-256 values: `1509b3ca053f41bf739197e6a098cc45dac760b57586b064fa1cf70f3fe5d684` (`verification_inventory_001_test.py`) and `443c64188d9c36055031cc27676f4fde4b401dcbb1d04b03bde63cf56d632ac0` (`verification_native_suites_001_test.py`). Stable specification and the other two reviewed tests are unchanged.
- Exact GREEN evidence: record `1789475258870812000-f7d1c3bb1945405b9c54bbd4a5bfa77a`, candidate/executable source `3385df47c114093988148dfa7aa31c41ba520f383efc70ea2863c2b55a289888` / `e8d4f1c488caff3204a7b45ad7f2b0e573f805cf5c4f0c01e7def10b415a3561`; command blob equals the current inventory-test SHA-256 and source digests are unchanged at completion.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The shared isolated catalog builder and the characterization/E2E extension now sort rows by the exact normative tuple `(legacy suite, path, runtime, CI category)`. This supplies valid canonical fixtures after strict order validation became executable; it changes no production behavior or acceptance expectation.

The characterization order update follows directly from that tuple: `example_test.php` sorts before `example_test.py` by path, so the PHP predecessor must execute before the failing Python member. The corrected assertion still requires the Python invocation, a nonzero suite result, and the complete canonical predecessor trace. It therefore preserves failure sensitivity and accurately replaces the former one-member fail-fast expectation, which depended on a noncanonical fixture order now forbidden by R1.

The prior exact RED remains the missing-order-enforcement witness: only `validate` and `list --category unit` accepted reversed rows. The current exact-source run reports all 21 tests GREEN, including those strict-order assertions and all failure/continuation cases. No test was removed, skipped or weakened, and no scope beyond deterministic inventory ordering was introduced.

### Required changes

None.

## Deterministic-order sensitivity test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the test or implementation.
- Prior approved test boundary: `tests/Verification/verification_inventory_001_test.py` SHA-256 `04f204b47b2a91a7de33a2ad5e2584702d054c05acbe0ca55ca6297b44529594` from the narrow registration-discovery approval above.
- Current test SHA-256: `30f24af937e4b75f7f697383586e7353a7f0c252163ab701e1d80df9910968fc`. The stable specification remains unchanged at SHA-256 `e87770d45b17bf8fca22c476bda4dcd309a3cf79c7fd259fa80d3afbacd3fe15`; all other reviewed test hashes are unchanged.
- Exact RED evidence: record `1789475026433788000-70370ab80e414dddb1dc3dae2fc64ddc`, candidate/executable source `3957c9c76edbae5347fd2c71407d59ceea8eb2226b8e5992f1187e201cec0349` / `b157e88701f01cf96852c60085b23b7918f6abc00e1b46edabdbc322e766f386`; command blob equals the current test SHA-256 and source digests are unchanged at completion.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The delta adds two assertions after reversing the valid logical inventory: public `validate` and `list --category unit` must reject the persisted noncanonical order, while public `canonicalize` must still emit the same normalized representation as it did before reversal. This makes the R1 canonical-order MUST and R2 validator ownership regression-sensitive at both the explicit validation seam and a consumer projection seam. It preserves the distinct normalization behavior of `canonicalize`; it does not require input discovery order itself to be stable or change the canonical tuple definition.

The exact RED is causally precise: 20 tests pass, and the sole test failure contains exactly two subtest failures because the current implementation returns zero for `validate` and `list --category unit` on the reversed manifest. The initial and final `canonicalize` equality assertion passes, demonstrating valid setup and preserved normalization independently of the missing fail-closed enforcement.

No production file, specification, fixture roster, enum, or existing expected result changes in this delta. It closes the Gate 5 sensitivity gap without weakening prior coverage or expanding issue #135 beyond canonical inventory validation and projection. Implementation may be corrected against this reviewed expectation; exact-source GREEN and final independent rereview remain required.

### Required changes

None.

## Narrow registration-discovery test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the test or implementation.
- Prior approved boundary: current snapshot from package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T120153Z-c6480ba415/package.json`, candidate/executable source `44e1c42e8cfe8cefc79f80451ccc35e45a93d3ee179c34ca611c119e841f9ca2` / `ec7a6151f15a3e83f2b22e31ca9fa6aff78e3581fd2b096c406b6ed864756889`.
- Delta scope: only `test_public_registration_is_atomic_and_canonical` in `tests/Verification/verification_inventory_001_test.py`; current SHA-256 `04f204b47b2a91a7de33a2ad5e2584702d054c05acbe0ca55ca6297b44529594` (prior `ba0aad2efc7447147fcf0da3d3d58013c8a5c8a6510bb38a2cc0274259fa124c`). All other reviewed test and stable specification hashes remain identical to the preceding approval.
- Exact RED evidence: record `1789474440035667000-f33d9f8d5cd74f648c73fecdfd9bd533`, candidate/executable source `a65b8ba345cce6f9e734e7c64de3a3c4d60be76958448c79c9bf3b2e1bb4d965` / `51b03d708a2ba64173043d87a7cad66bd5811a0604719216cba2995204f0a2c2`, command blob equal to the current test SHA-256 above.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The fixture moved from `tests/Verification/registered_test.py`, which is outside the repository's canonical discovery set, to `tests/InstallationProcess/registered_test.php`, a real canonical discovered path. The corresponding explicit registration values changed coherently from `characterization/python3/governance` to valid `unit/php/unit`; duplicate, missing-file, invalid-category, invalid-runtime and invalid-suite cases continue to exercise the same rejection and unchanged-bytes assertions.

This is a sensitivity improvement, not a changed acceptance: the successful public registration must now make a path that validation initially discovers as unregistered become valid through the registered candidate. The exact RED run executes all 21 inventory tests, with 20 GREEN and one failure only at that success assertion: `SETUP_FAILURE: UNREGISTERED_TEST: tests/InstallationProcess/registered_test.php`. That proves the test reaches the intended R3/R4 boundary and fails because registration validates discovery before incorporating the candidate, not because of fixture setup or an unrelated regression.

The delta changes no stable specification, production source, Make seam, inventory roster expectation, or adjacent test. It neither weakens the previously approved RED/GREEN evidence nor expands issue #135 scope. Executor correction may resume; the corrected implementation and resulting exact-source GREEN remain subject to final review.

### Required changes

None.

## Post-Gate-4 test-delta review — 2026-09-15

- Reviewer: Codex independent reviewer `/root/gate3_review`; not author of the tests or implementation.
- Lineage: prior APPROVED RED package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T114805Z-9fcaba3b28/package.json` compared byte-for-byte with current root package `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260915T120153Z-c6480ba415/package.json`. The typed `--test-delta` route is not applicable to issue #135, so the two base-plus-binary-patch snapshots are the reconstructible review boundary.
- Prior/current snapshot patch SHA-256: `f87cd6c10b1f69cd1d3736b9f7e611b7c9b38c1a4a16f5fdeece3bb50bcfd566` / `d6ad15cd2e3f1c995ea4cec7c50b4ad35774fd8904a1dacd670104bb4285b57f`.
- Prior APPROVED candidate/executable source: `9eea87b00d60db8ad5a3f7b68f0d7a0f4f2bbd8416d431675684a336cd89c8ca` / `65060f1dab6a551db9bca64f63ac68b7fc2d09052d614a5b5163fff31517f5e7`.
- Current candidate/executable source: `44e1c42e8cfe8cefc79f80451ccc35e45a93d3ee179c34ca611c119e841f9ca2` / `ec7a6151f15a3e83f2b22e31ca9fa6aff78e3581fd2b096c406b6ed864756889`.
- Current test SHA-256 values: `a7a57310f3354d23b909f1914d0bffa687146792ecb47afa4767f1dbb6e5b639` (`change_verification_001_test.py`), `120ee68bf88faf393c4291efa83757773eb128e0ad40332c293d38def021fcee` (`verification_ci_001_test.py`), `ba0aad2efc7447147fcf0da3d3d58013c8a5c8a6510bb38a2cc0274259fa124c` (`verification_inventory_001_test.py`), and unchanged `2cdd59caf7e16cc4af4b015c2ce0190b35fb55d4a84443168e88d6f1ff0559f5` (`verification_native_suites_001_test.py`).
- Current focused GREEN evidence: records `1789473720914799000-0b1d6e894e8f432ab0e342973541e572` (18 tests), `1789473749302217000-ae56d08957af49449766c907e7a35d81` (18 tests), and `1789473767502131000-5b8f3ffdaa2846738a156a97bd78a890` (21 tests). Each exits 0 and is bound at completion to the current candidate/executable source.
- Prior RED sensitivity: retained by the three exact-source `INTENDED_RED` records named in the final correction rereview above; the old approved snapshot, not the current implementation, remains the missing-behavior witness.
- Verdict: `APPROVED`.

### Complete delta findings

None.

The delta does not weaken the approved contract:

- `verification_inventory_001_test.py` replaces reuse of a DB fixture as a second inventory member with a unique E2E fixture. This removes an invalid duplicate-path setup while preserving exact E2E list and execution assertions.
- `verification_ci_001_test.py` replaces the manually duplicated complete E2E literal with a manifest-derived expected category projection. This assertion now directly proves that the public category list reproduces the canonical manifest. Complete membership cannot drift silently because the adjacent independently frozen full-roster SHA-256 assertion still covers every category/runtime/path tuple, while uniqueness and the explicit critical-member assertions remain intact.
- `change_verification_001_test.py` materializes and commits every path from the real canonical inventory before planner checks, registers dynamically created executable fixtures, and uses a non-test application deletion for Git-state coverage. These changes establish valid discovery/baseline preconditions; they do not relax planner commands, category mapping, unregistered-test rejection, source-drift checks, or child-RED propagation.
- The added fixture directories and `app/Otiz/Delete.php` baseline are deterministic disposable-repository setup. They neither touch production state nor substitute implementation behavior.

The comparison found no specification or expectation changes in this post-approval test delta. The old package demonstrates that the approved tests fail before implementation, and the current exact-source records demonstrate that the corrected fixtures pass with the implementation. Executor work may resume. Any later test-byte change requires another independent delta review.

### Required changes

None.
