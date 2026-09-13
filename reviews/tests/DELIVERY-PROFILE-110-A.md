# Test review: DELIVERY-PROFILE-110-A

- Reviewer: Codex independent Gate 3 reviewer (`/root/gate3_profile_a`)
- Test author: root Codex
- Reviewed source: commit `0947256ed45eafb372562c0c31c5bfc38b6debb6`; candidate source `f9cced03730012025b052e18960cccbb1e226a0d84a26d2aae7e67143f656b6c`; executable source `f6b5aeb2f909e92c3d039772dfd7c5c4426b6fd08635bca69bd32ca96b6345a7`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T140001Z-18e36c687f/snapshot` (base commit `0947256ed45eafb372562c0c31c5bfc38b6debb6`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Agreed review scope / prior findings disposition (for rereview): initial review of the complete PR A specification, OpenSpec artifacts, Gate 2 test and intended RED; no prior findings
- Specification: `specs/DELIVERY-PROFILE-110-A.md`; OpenSpec change `openspec/changes/pinned-focused-check-profiles/`
- Public seam: `tools/delivery/run-in-profile <profile> <command> [args...]`
- Red command and intended failure: `php tests/Verification/quality_graph_ci_setup_001_test.php`; harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789307992029591000-29f35e9803614ec0a9b1168225bf6af6.json`; exit `255`, intended first failure `INTENDED_RED DELIVERY-PROFILE-110-A launcher is missing`
- Verdict: `CHANGES_REQUESTED`

## Findings

1. **Blocking — DP110A-03 is not sensitively tested.** `tests/Verification/quality_graph_ci_setup_001_test.php` accepts any launcher that prints a syntactically valid `sha256:` value. It neither proves that the command ran in the reported container image nor compares runtime/package versions with the canonical pins and lockfiles. A host-side launcher that fabricates a digest would pass the new assertions, so the test cannot establish the slice's central local/CI pinned-environment behavior. Add bounded public-seam probes that fail when execution occurs on the host or when applicable PHP/Composer, Python/uv, Node/npm and browser dependencies diverge from canonical sources. Validate that the reported digest identifies the image actually used. Running the same deterministic contract test locally and in CI may establish parity without changing Quality Graph files.
2. **Blocking — rejected-profile safety is not tested.** The unknown-profile assertion checks only a nonzero exit. The supplied command can run and still produce that exit, so the requirement that rejection happen *without launching the command* is not protected. Use an isolated temporary marker (or equivalent observable side effect) and assert that it remains absent.
3. **Blocking — DP110A-04 evidence values are materially unchecked.** The test verifies field names and selected successful values, but never checks that `argv` equals the exact submitted argument vector, that duration is a non-negative numeric value, or that a failing child records exit code `23`. A launcher can emit incomplete/fabricated run evidence while passing. Assert the exact argv and basic duration contract for a successful run, and inspect the failure run's evidence for exit `23`.

The intended RED itself is valid and isolated: the captured first failure is the missing public launcher, not broken Docker/test setup. The test is deterministic apart from its intentional local Docker dependency, uses the declared public seam, and does not require production systems. OpenSpec strict validation passed during review. No implementation or Quality Graph changes were reviewed or authorized.

## Required changes

- Strengthen the existing focused test to cover the three blocking sensitivity gaps above, without adding a framework or expanding PR A scope.
- Capture a fresh intended RED for the corrected test and submit the corrected exact source for independent Gate 3 rereview before implementation.
