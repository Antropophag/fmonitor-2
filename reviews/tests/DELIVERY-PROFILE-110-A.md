# Test review: DELIVERY-PROFILE-110-A

- Current Gate 3 verdict: `APPROVED` (final bounded rereview below)

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

## Rereview — 2026-09-13

- Reviewed correction source: commit `1ea9dbce45006ce716f4124b3c2dd36763d91223`; candidate source `905456241aa5a3291e09adbb350867d3e511d36347c435bc511025ef8067476b`; executable source `86ff9d87d293b73aa63a1f401e4a2127f58d1021e6353b9e3a70d856311d292a`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T140333Z-563ddc54de/snapshot` (base commit `1ea9dbce45006ce716f4124b3c2dd36763d91223`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T140333Z-563ddc54de/package.json`
- Fresh RED: `php tests/Verification/quality_graph_ci_setup_001_test.php`; harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789308191869502000-5f4259879bd04a02be7f627006ee6745.json`; exit `255`, intended first failure remains the missing launcher
- Final verdict: `CHANGES_REQUESTED`

### Findings disposition

1. **Partially resolved; still blocking.** The correction now proves container execution (`/.dockerenv`), checks that the reported image exists, and compares PHP, Python and Node runtime versions with `tools/delivery/dependencies.env`. It does not check the canonical `COMPOSER_VERSION`, `UV_VERSION`, or `NPM_VERSION` pins, and the browser assertion checks only that Playwright is importable rather than that its installed version agrees with the applicable npm lockfile. Consequently a profile with drifting tool/package versions still passes DP110A-03. Add direct bounded version comparisons for Composer, uv and npm, plus a Playwright installed-versus-lockfile version comparison. This is a small extension of the existing probe, not a new manifest or framework.
2. **Resolved.** The isolated marker proves an unknown profile is rejected before its command executes.
3. **Resolved.** Exact argv, numeric non-negative duration, and failed-command argv/exit evidence are now asserted.

The fresh RED remains valid and isolated. No new scope or implementation issue was reviewed. After the remaining DP110A-03 assertions and fresh RED are captured, submit the corrected exact source for another bounded rereview.

## Final bounded rereview — 2026-09-13

- Reviewed correction source: commit `e1f4eff37c179690139764b4845b52ffb8e56757`; candidate source `a9f8be5f47374ac77e44f526dd0b5cef3c73d89a9dae2bc5df3084103bb320ed`; executable source `1b628dfafa057139835ee8f35aba0b4c954ce4a02595a4a990d21d970dfc7c46`; retained snapshot `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T140611Z-91aac4c031/snapshot` (base commit `e1f4eff37c179690139764b4845b52ffb8e56757`, empty patch SHA-256 `e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855`)
- Correction package: `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/packages/20260913T140611Z-91aac4c031/package.json`
- Fresh RED: `php tests/Verification/quality_graph_ci_setup_001_test.php`; harness record `/Users/antropophag/.local/share/fmonitor-2/delivery-harness/records/1789308359284296000-538a36f2bc6a4299a9dc1ffd2fab8b11.json`; exit `255`, intended first failure `INTENDED_RED DELIVERY-PROFILE-110-A launcher is missing`
- Verdict: `APPROVED`

### Findings disposition

1. **Resolved.** The public-seam probe now checks container execution, actual existence of the reported image, exact PHP/Python/Node pins, exact Composer/uv/npm pins, Composer platform requirements, and browser Playwright version against the lockfile from the pinned `shlz-ui` dependency workspace.
2. **Resolved.** The isolated marker proves an unknown profile is rejected before its command executes.
3. **Resolved.** Successful and failed evidence checks cover exact argv, numeric non-negative duration and exit code propagation.

The corrected test is traceable to DP110A-01 through DP110A-05, exercises the declared launcher seam, is sensitive to host execution and version drift, derives expected versions from canonical pins/lockfiles rather than duplicating them, and remains deterministic and isolated from production systems. The fresh RED fails for the absent launcher before Docker or dependency setup. No blocking findings remain; Gate 4 implementation may proceed against this approved test source without changing its expectations.
