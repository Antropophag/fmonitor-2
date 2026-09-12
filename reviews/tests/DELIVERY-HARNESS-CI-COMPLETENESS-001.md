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

## Rereview 2026-09-12 — corrected Gate 3 candidate

- Reviewed source: base `63135104cbeb2f1554f905287343b3fa881e79a7` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133112Z-9937a40279/snapshot/source.patch`, SHA-256 `c3daabfb85ca7edec061d46a38e69f8e0e677d0773a092ecf5d2266bfbdb5fa3`
- Candidate source: `bc953d6cc24774282d621b49730593e09e50c41b5eb1822bf33c5dcc75b4985d`
- Evidence: external record `1789219837346283000-c044dff1e37746b09e099b036b716bf5`, `INTENDED_RED`, exit 1
- Prior findings disposition: findings 2, 4 and 5 are materially addressed by new CLI fixtures, source mutations and synthetic PR #98/#100 inventories. Findings 1 and 3 are only partially addressed; the corrections add important fail-closed paths but omit required R4 and isolation/idempotence behavior, and part of the captured RED is caused by fixture/setup defects rather than missing behavior.
- Verdict: `CHANGES_REQUESTED`

### Rereview findings

1. **Blocking — the captured RED includes unrelated setup/regression failures.** Both `test_pr98_inventory_is_complete_before_publication` and `test_pr100_db_in_unit_is_rejected_in_unit_environment` stop before their new acceptance assertions because `plan()` writes `--output` beneath the external evidence directory and the current planner reports `SETUP_FAILURE: unsafe repository path`. The specification does not require arbitrary planner output paths outside the checkout, so this is not demonstrated as the intended missing behavior. Separately, `test_reviewer_package_accepts_plan_owned_category_evidence_only` runs the copied existing `change_verification_001_test.py`, which fails because `fixture_repo()` copied an incomplete repository and omitted an owner file required by `test_harness_refresh_and_confirmed_consumers`. This is a fixture regression, not the intended plan-owned-category-evidence RED. Gate 2 requires deterministic failure for missing behavior rather than broken setup.

2. **Blocking — R4 remains untested.** The corrected file checks that help mentions `--historical-red` and `--test-delta`, but never prepares a package containing current GREEN, an exact base/current test delta and a linked historical `INTENDED_RED`; it also never tests rejection of a mismatched acceptance, missing historical RED or changed mapping. Option-name presence cannot catch a non-functional or fail-open test-delta implementation.

3. **Blocking — declared idempotence and worktree concurrency isolation remain untested.** `test_source_identity_mutations_and_repeat_preflight_are_observable` does not run preflight at all despite its name, and no test repeats an identical source/environment admission or creates two worktrees/evidence pointers. Thus the verification input's `idempotence` and `worktree_concurrency_isolation` dimensions remain unsupported. The corrected tests also do not verify candidate/dependency bytes are unchanged by rejection.

4. **Blocking — R6 negative matrix is incomplete.** The dependency-workspace test covers one declared manifest and a symlinked manifest path, but does not assert fail-closed behavior for missing/mutable identity, root realpath escape, missing workspace, changed lock/source digest, or a command/consumer outside the manifest. An implementation validating only `Path.is_symlink()` could pass while violating the manifest identity and consumer contract.

5. **Blocking — R7 has no corrected/publication-ready fixture.** The bad PR #98/#100 candidates are now modeled, but the normative requirement also says the corrected fixture becomes publication-ready without local full suites or product DB/PDF/runtime/E2E services. No test corrects the fixture, reruns bounded admission on one executable digest, verifies `publication_ready`, and preserves actual PR/CI as `UNKNOWN`.

### Required changes after rereview

1. Repair the disposable repository fixture/output placement so every intended RED reaches the new acceptance seam; copy all owners needed by any existing command selected in the fixture, or use a narrower deterministic command that is independently GREEN before testing package evidence behavior.
2. Add a full public R4 lineage test with the valid current-GREEN + historical-RED + exact-delta case and the specified mismatch/missing rejection cases.
3. Add repeated same-source admission and two-worktree external-state isolation tests, including no source/dependency mutation on rejection.
4. Complete the dependency workspace negative matrix and add a corrected PR fixture that becomes publication-ready while PR/CI remain `UNKNOWN`.
5. Capture a clean intended RED for the corrected exact source and regenerate the reviewer package before the next rereview.

## Rereview 2026-09-12 — third Gate 3 candidate

- Reviewed source: base `5cda4eb9a25e84c1d8474ceab29e3e4a2f8002e0` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T133459Z-1f9cbf1669/snapshot/source.patch`, SHA-256 `21bd20d3c4cb5714de76fa9db359daaf5b6d0e34482163b6a08e8ae6a315f978`
- Candidate source: `2a2c2dbdedb0a82f697a5abcfed5f550019b78f393a7740164a9fc309ba0dfce`
- Evidence: external record `1789220078475949000-40aefd4122074ff2a6885bc1317ca02c`, `INTENDED_RED`, exit 1; 12 tests report `FAIL`, zero `ERROR`
- Prior findings disposition: all blocking findings from the initial review and first rereview are resolved. Plans are written to a repository-local path; acceptance/category commands are self-contained; PR #98/#100 failures reach their intended assertions; the corrected PR #98 fixture verifies repeatable GREEN, unchanged candidate bytes and PR/CI `UNKNOWN`; R4 exercises valid current-GREEN/historical-RED/test-delta lineage and rejects unrelated RED; R5 mutates lifecycle, executable and unknown paths; R6 covers accepted manifest plus symlink, missing identity/root, unauthorized consumer and mutable workspace rejection; separate worktrees prove binding/plan isolation.
- Verdict: `APPROVED`

### Findings

None. The corrected tests are traceable to R1–R7, use the public serialized CLI/package seams for the material integration behavior, derive concrete failure inventories from issue #99 rather than current implementation, and are deterministic and isolated in disposable repositories and external evidence homes. The retained RED fails for absent planned behavior: typed policy/plan schema, preflight, typed evidence, source split, dependency workspace validation, test-delta lineage and worktree-scoped evidence.

### Required changes

None. Gate 4 may proceed against exact candidate source `2a2c2dbdedb0a82f697a5abcfed5f550019b78f393a7740164a9fc309ba0dfce`; any test/spec change requires a new Gate 3 review.

## Rereview 2026-09-12 — post-Gate-5 sensitivity delta

- Reviewed source: base `13ed2015801c45aff3ab391425899b5b4e2884a9` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T140900Z-a212e904d2/snapshot/source.patch`, SHA-256 `c15899403fd0a72a774745e1e5318791227015ebf89c41bc27efc7583adbcae3`
- Candidate source: `9bfeb6412c7fcb9bc1a4d5bc3684f35cf8516e10193cc7f38c8502cf9658714d`
- Executable source: `cebbb62eaced98220f9ca7db798394b92b563eea261b06e3e94530e25d5940bf`
- Evidence: external record `1789222112921275000-aa8d6681748745219d0b65c7aad79496`, `INTENDED_RED`, exit 1; eight existing tests GREEN and four corrected sensitivity tests FAIL, zero ERROR
- Review input: Gate 5 findings in `reviews/code/DELIVERY-HARNESS-CI-COMPLETENESS-001.md`
- Acceptance continuity: normative `DELIVERY-HARNESS-CI-COMPLETENESS-001` R1–R7 is unchanged; the delta strengthens tests for behavior already required by R1–R4, R6 and R7.
- Verdict: `APPROVED`

### Findings

None. The delta independently detects every Gate 5 gap at the public seam:

- the exact repository plan must retain the generated-source verification consumer after deduplication;
- a GREEN publication decision must contain plan-owned evidence for every command id, with typed purpose and observed services/dependencies;
- retained records, not package inference, must own matching command id, purpose and environment;
- dependency manifests must bind an allowed realpath, verifiable lock digest and an authorized plan consumer, rejecting missing/wrong/mutable/outside inputs;
- Gate 3 lineage must expose distinct exact base/current blobs, a delta digest and the unchanged acceptance id, and reject a historical RED from another acceptance.

The retained result is a valid intended RED: the original eight tests remain GREEN, the four affected tests fail at the newly asserted missing contracts, and there are no setup errors or unrelated regression failures. Fixtures remain deterministic, disposable and isolated from production systems.

### Required changes

None. Gate 4 correction may proceed against exact executable source `cebbb62eaced98220f9ca7db798394b92b563eea261b06e3e94530e25d5940bf`. Any further spec or test change requires another independent Gate 3 review.

## Rereview 2026-09-12 — observed environment sensitivity delta

- Reviewed source: base `4cdc276e2c36d3d78d677843e986062f42e3bf74` + retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260912T144304Z-d19ac42ff6/snapshot/source.patch`, SHA-256 `da99e780938d653180525fa3722161c7ae54fb5aed139c25f3f873512e43024e`
- Candidate source: `74da9b27dcf3f1b7deef3833cb7e373bc39a08386d42ba7178dbf80b16bcbee1`
- Executable source: `d69fb45e3fe9beddf58bb1ca7edfe50a92a68bbfad330e36093f7271f89b856d`
- Evidence: external record `1789224149932063000-14c981e0f41a44f2936e426a1e6c6b61`, typed acceptance `INTENDED_RED`, exit 1; 12 existing tests GREEN, one focused test FAIL, zero ERROR
- Review input: remaining Gate 5 environment-observation finding in `reviews/code/DELIVERY-HARNESS-CI-COMPLETENESS-001.md`
- Acceptance continuity: normative R2/R7 is unchanged; this delta makes its actual category-environment observation requirement sensitive.
- Verdict: `APPROVED`

### Findings

None. The new test uses the public plan/preflight seam in a disposable repository. Its integration-profile command independently declares a Python dependency and requires MariaDB; deterministic failing probes and an absent module must block publication with exactly `SERVICE_UNAVAILABLE` and `DEPENDENCY_UNAVAILABLE`, must omit MariaDB from observed availability, must mark the profile incompatible and must identify the observation method as a probe. The positive half then supplies the module and a successful MariaDB probe and requires GREEN evidence containing both observed facts. This distinguishes actual observation from copying the target profile and catches the precise Gate 5 defect without relying on production services.

The retained result is an intended RED rather than setup failure: all 12 prior tests remain GREEN, the new test reaches its first admission assertion, and current implementation incorrectly returns zero exactly as the Gate 5 finding predicts.

### Required changes

None. Gate 4 correction may proceed against exact executable source `d69fb45e3fe9beddf58bb1ca7edfe50a92a68bbfad330e36093f7271f89b856d`. Any further specification or test change requires another independent Gate 3 review.
