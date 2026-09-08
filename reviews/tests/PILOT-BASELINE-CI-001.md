# Test review: PILOT-BASELINE-CI-001

- Reviewer: `agent:/root/linux_test_review`
- Test author: `agent:/root`
- Reviewed commit: `7d0b61c2e08dfac5c037e7cdf463aefd4dd5ce77`
- Transfer source: `9f530017ab769de4e6e1647cadb990281e34c0e9`
- Base commit: `12c96f674b0960617727dae72993615b61674929`
- Specification: `specs/PILOT-BASELINE-CI-001.md`
- Public seams: `make ci-setup`, `make test-tools`, `make fresh-test-verify`
- RED command: `php tests/Verification/quality_graph_ci_setup_001_test.php`
- Intended RED: absent `rg` must exit 1 with exact `SETUP_FAILURE: required command unavailable: rg` before list output or classification
- Verdict: `APPROVED`

## Reviewed test set

| Status | Path | SHA-256 |
|---|---|---|
| M | `tests/InstallationProcess/pilot_object_list_001_test.php` | `752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e` |
| M | `tests/InstallationProcess/pilot_shlz_assets_001_test.php` | `571f958174a970007884ddec35098502a8455f52e3eb17d7ac268242df120f6b` |
| A | `tests/Support/PilotSafeAuthorizationLog.php` | `e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943` |
| M | `tests/Support/SelectedOriginalFixture.php` | `920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7` |
| A | `tests/Support/ShlzManifestCaptureProbe.php` | `abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501` |
| M | `tests/Support/construction_control_completed_filter_browser.cjs` | `bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61` |
| A | `tests/Support/shlz_manifest_capture_witness.php` | `a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357` |
| A | `tests/Verification/quality_graph_ci_setup_001_test.php` | `3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab` |

## Findings

The complete bytewise-sorted `git diff --no-renames --name-status 12c96f674b0960617727dae72993615b61674929..7d0b61c2e08dfac5c037e7cdf463aefd4dd5ce77 -- tests/` result contains exactly the eight entries above. Each current blob is byte-identical to the same path at source commit `9f530017ab769de4e6e1647cadb990281e34c0e9`, and every SHA-256 agrees with the transfer RED record.

The transferred tests preserve their previously independently reviewed expectations. Portable temporary paths and Playwright module discovery remove developer-machine assumptions. The authorization-log parser accepts only the exact safe event and constrained PHP loopback transport grammar. The CSS fixture uses the owned PHP test image, witnesses descriptor capture, preserves exact GET/HEAD and fail-closed HTTP responses, and restores owned fixture UID and modes before surfacing partial-success Docker diagnostics. These tests remain deterministic and isolated from owner data and production systems.

The historically named `quality_graph_ci_setup_001_test.php` has no Quality Graph module dependency. It requires only `tests/bootstrap.php`, copies and invokes `tools/verification/run.sh`, calls the public `make test-tools` target, and validates the resulting local image through Docker and Git. It does not load or inspect graph declarations, delivery receipts, checker code, runtime actions, publisher code, provenance results or approval behavior. Retaining its filename therefore does not import the Quality Graph slice into PR41.

The expected missing-`rg` result is independently derived from the baseline setup contract: classification cannot be trustworthy when its required scanner is absent, so setup must fail before emitting any list. The isolated fixture supplies the other required commands and contains one ordinary and one database-tagged sample. Retained log `~/.local/state/fmonitor2/pr41-baseline-ci-20260908/setup-red.log`, SHA-256 `308d6c70d78416a80f2affc66541c477f578e6b7128276f631e7f8f4c1b62f11`, records exit 255 because current `run.sh` instead exits zero, emits both samples as unit tests, and prints two shell `rg: command not found` diagnostics. This is a genuine RED for the missing baseline behavior rather than broken fixture setup.

The eight tests are compatible with the narrow PR41 contract: one read-only Ubuntu baseline job, the existing nine-stage harness, owned test tooling, strict setup failure, and no Quality Graph runner or publisher. No implementation was reviewed or authored here.

## Required changes

None.
