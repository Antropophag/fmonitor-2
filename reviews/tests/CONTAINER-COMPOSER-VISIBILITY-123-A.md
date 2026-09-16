# Test review: CONTAINER-COMPOSER-VISIBILITY-123-A

- Reviewer: independent `gpt-5.6-sol / low` Gate 3 agent (`/root/issue123_gate3`); authored neither specification nor tests.
- Test author: primary/root Codex session, as recorded in `docs/operations/issue-123-slice-a-delivery.md`.
- Reviewed source: commits `65c53a18` + `1a8d7c32` (`HEAD` `1a8d7c32cf332bb808dc7534164d33734f23e53d`); exact candidate source `c4acf14743d0b1fbcf45990a99650e0322078f1ebac25d9d678c27866c59c8f4`.
- Prepared reviewer package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260916T022859Z-fe96dc3f80/package.json`; package plan SHA-256 `4239958a0643c2ec110b0ea3f32362a16662ad52f0b273ebcaadda2c70e29522`; context manifest SHA-256 `d9e477d3f5b619614288c3d3c6731d04335a4e5952b3a0649d284975ecb62a69`.
- Reconstructible source: package snapshot base `1a8d7c32cf332bb808dc7534164d33734f23e53d`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`.
- Agreed review scope: initial Gate 3 review of the complete slice A candidate; normative specification, public seam, A–J traceability, test sensitivity/independence/determinism/isolation, and captured RED validity. No prior findings.
- Specification: `specs/CONTAINER-COMPOSER-VISIBILITY-123-A.md` (package-bound SHA-256 `7e3e942516631e4bbd076af7839daed669577ee27770ac804e0ce53883f32daa`).
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`.
- Planner: `CRITICAL`; required reviews `gate3`, `final`; required categories `governance`, `integration`.
- Verdict: `CHANGES_REQUESTED`.

## Evidence reviewed

- `python3 tests/Verification/container_composer_visibility_123_a_test.py` — exit `1`, recorded `INTENDED_RED` for exact candidate source in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525655563874000-192e74c947514cbfac8cb31a0ba0fa3f.json`. A/C/G-H fail through the public seam for the expected pre-implementation visibility/host-masking gap, but F fails in fixture setup before invoking the seam.
- `php tests/Verification/quality_graph_ci_setup_001_test.php` — exit `0`, `QUALITY-GRAPH-CI-SETUP-001 PASSED`, recorded for the same exact candidate source in `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789525675970521000-96b5cecc4a114db59093a3ac28a13563.json`.
- No full local `make test` or `make verify` was run. The reviewer performed source/evidence inspection only.

## Acceptance and quality assessment

- A, B, D, I, J: mapped by `test_a_b_d_i_j_two_clean_worktrees_candidate_origin_and_warm_repeat`; two disposable detached worktrees, candidate marker origin, dependency/autoloader origin, read-only dependency view, tracked digest, absence of host `vendor/`, warm repeat and compact timing evidence are asserted.
- C: mapped by `test_c_stale_host_vendor_is_masked`; the hostile host fixture makes accidental host use observable.
- E: mapped by `test_e_changed_lock_never_silently_reuses_old_dependency_identity`; a disposable changed lock must not reach Yii behavior using the old identity. Either a matching rebuild or a pre-behavior failure remains permitted by the specification.
- F: intended mapping exists, with hostile host fallback material, but is not executable as submitted; see blocking finding F1.
- G and H: mapped across the existing governance, integration and browser profiles through the same public Yii bootstrap seam.
- Expected values are independently derived from the normative paths/origins, marker values, exit behavior and compact evidence contract rather than planned implementation internals. Tests use disposable worktrees and Docker images, check Docker availability explicitly, avoid production systems, and preserve cleanup/isolation. The only determinism/setup defect found is F1.
- The normative specification is observable at the confirmed public seam and adequately states actor, preconditions, successful outcome, prohibited host state, lock identity rejection, fail-closed behavior, profile compatibility and tracked-input invariants for this tooling-only slice.

## Findings

### F1 — Blocking — acceptance F is a setup failure, not a public-seam RED

- Location: `tests/Verification/container_composer_visibility_123_a_test.py:183`; actual recipe at `tools/delivery/Dockerfile.focused-checks:44`.
- The fixture searches for `RUN composer install --working-dir=/workspace --no-interaction --no-scripts`, while the bound Dockerfile installs with `--working-dir=/opt/fmonitor`. Therefore `re.sub` makes no change and line 189 raises `SETUP_FAILURE: dependency install seam not found` before `bootstrap(root)` is called.
- The captured full RED confirms this exact failure. Consequently acceptance F (missing/corrupt container dependency must fail closed through canonical `run-in-profile`, without host fallback) has no valid executable RED, and the aggregate `INTENDED_RED` outcome cannot advance Gate 2/3 for the complete A–J matrix.
- Correction: make the corrupt-layer fixture target the actual canonical `/opt/fmonitor` dependency-install seam (or another deterministic, implementation-independent fixture that produces a missing/corrupt container dependency), assert that the mutation occurred, then invoke the public launcher and prove nonzero pre-behavior failure with no host fallback. Capture a fresh exact-source RED record and resubmit the corrected delta for independent Gate 3 rereview.

## Required changes

1. Correct F1 and retain a fresh complete RED record demonstrating that every test reaches its intended assertion path; the suite may remain RED for the missing production behavior, but must contain no setup failure.
2. Refresh the prepared reviewer package/plan if the test/source binding changes, and request independent rereview of the correction.

Gate 3 does not advance to implementation on this source.
