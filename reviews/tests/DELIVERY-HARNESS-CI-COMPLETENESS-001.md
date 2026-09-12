# Test review: DELIVERY-HARNESS-CI-COMPLETENESS-001

- Reviewer: Codex agent `/root/issue99_gate3` (independent Gate 3 reviewer)
- Test author: root Codex session (owner-authorized autonomous spec/test author)
- Reviewed source: base `a8ba73a926d1031c8b039555d0b6c3f142abd991` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T132610Z-9d23e8f34d/snapshot/source.patch`, SHA-256 `ba72fad05d8d22eaea3f3e06672ad99f13b28f654ce9287cafbb03dfc809f6e8`
- Agreed review scope / prior findings disposition (for rereview): initial Gate 3 review of issue #99, its OpenSpec change, normative specification and complete RED candidate; no prior findings
- Specification: `specs/DELIVERY-HARNESS-CI-COMPLETENESS-001.md`
- Public seam: generated verification plan, role package, runner evidence and pre-publication admission exposed by `tools/delivery/harness.py` and `tools/delivery/change-verification.py`
- Red command and intended failure: `python3 tests/Verification/delivery_harness_ci_completeness_001_test.py`; external record `1789219561042429000-516fdd2ab4a04d98bf04a35a292eefbc`, source `171ab56f09d31d7e455382dd9d8e6a962ea6608189bb531c4efd0b84faea1786`, `INTENDED_RED`, exit 1. All five tests failed on absent typed plan/policy relations, source split, CLI contracts and preflight rather than setup failure.
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — most of R1–R7 has no executable acceptance coverage.** `tests/Verification/delivery_harness_ci_completeness_001_test.py:20-86` checks one Dockerfile relation, one MariaDB declaration, command field presence, digest field lengths, CLI option names and one current-repository GREEN preflight. It does not exercise the required E2E-inventory consumer or unknown-consumer rejection (R1); undeclared import and DB-in-unit rejection with exact diagnostics or category-equivalent isolation (R2); acceptance/boundary/category package acceptance and unrelated-record rejection (R3); historical RED/current GREEN/test-delta lineage and mismatch rejection (R4); lifecycle-only stability, executable invalidation and unknown-path fail-closed classification (R5); dependency manifest identity, allowed realpath, missing/mutable identity, symlink escape or consumer restrictions (R6); nor the PR #98/#100 bad fixtures, complete failure inventory, missing/UNKNOWN evidence rejection and publication readiness semantics (R7). The verification input consequently overstates `filesystem_effects`, `idempotence`, `failure_semantics`, `retained_evidence`, `worktree_concurrency_isolation` and registry synchronization as covered.

2. **Blocking — tests bypass material parts of the declared public seam.** `test_plan_commands_are_typed_and_environment_bound` imports and calls `build()` directly and `test_source_identity_separates_executable_and_lifecycle_metadata` imports and calls `source_details()` directly. They do not observe serialized plan/package/record output through the stated CLI seam and cannot detect wiring, serialization, admission or exit-status regressions. The only subprocess behavior test invokes `preflight`; no test prepares a reviewer/publication package through `harness.py`, although package acceptance and publication blocking are central requirements.

3. **Blocking — the preflight assertion is insensitive to the fail-closed contract.** `test_preflight_is_fail_closed_and_records_environment` only expects GREEN for the repository's own plan and equality of two reported digest strings. It neither creates a deterministic constrained CI profile nor injects drift, stale inventory, undeclared dependencies, missing evidence or `UNKNOWN`. An implementation that always reports GREEN and copies the same digest into both keys would satisfy this test while violating R1, R2 and R7. It also does not verify no candidate/dependency/GitHub/domain side effects, retained rejection evidence, repeat idempotence or isolation between worktrees.

4. **Blocking — source identity assertions do not validate identity semantics.** Checking that two strings have length 64 and that `lifecycle_paths` exists permits both digests to be arbitrary or identical across every mutation. The test must independently mutate lifecycle-only bytes and executable bytes and verify candidate/executable digest behavior, evidence reuse/invalidation and unknown-path fail-closed classification required by R5.

5. **Blocking — expected values and fixtures do not substantiate issue #99's historical oracle.** The spec names PR #98 runs `34606125088`/`34609680703` and PR #100 run `34686467885`, but the test contains no independently defined synthetic failure inventories for those incidents. Therefore it cannot prove that all known primary obligations are discovered in one bounded pass, nor that a corrected fixture becomes publication-ready without using forbidden full local suites or richer product services.

RED determinism and setup isolation are adequate for the five currently asserted missing API/schema elements: the retained output names each absent contract and contains no environmental exception. That evidence does not compensate for the missing acceptance matrix above.

## Required changes

1. Replace the aggregate `R1-R7` coverage claim with explicit acceptance mappings and add deterministic tests for every positive and fail-closed scenario listed in findings 1 and 5, including exact primary-obligation/diagnostic assertions for PR #98/#100 fixtures.
2. Exercise plan generation, evidence recording, reviewer-package preparation and publication admission through the documented CLI/public serialized seam; keep direct pure-function tests only as supplementary checks.
3. Add mutation fixtures for lifecycle/executable/unknown paths and isolated dependency workspaces, plus two-worktree and repeated-run assertions for source effects, idempotence and state isolation.
4. Capture a new exact-source intended RED after correcting the tests and regenerate the Gate 3 package/verification plan before rereview.
