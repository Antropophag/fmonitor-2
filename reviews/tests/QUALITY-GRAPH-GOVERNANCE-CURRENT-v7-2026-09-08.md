```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/linux_test_review","verdict":"CHANGES_REQUESTED","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"8c206d41f24e7c1d8eda907efbc2aaee08808811a7097fa19f9bcb3c631df8f7"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"a18d7981d6bdb6b99249ed42153c5e04c0df4330","recordedAt":"2026-09-08T10:49:03+03:00"}
```

# Independent Gate 3 review v7 — CSS ownership cleanup correction

- Reviewer: `agent:/root/linux_test_review`
- Test author: `agent:/root`
- Reviewed commit: `a18d7981d6bdb6b99249ed42153c5e04c0df4330`
- Specification: `QUALITY-GRAPH-GOVERNANCE-001` v0.6, SHA-256 `5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`
- Public seam: inherited `PILOT-SHLZ-ASSETS-001` HTTP GET/HEAD contract within the repository `db-test` and `make fresh-test-verify` seams
- RED evidence: GitHub baseline `34199261119` at `eacc4d4`; private exact-old-helper Linux reproduction
- Verdict: `CHANGES_REQUESTED`

## Findings

The canonical metadata is exact. The bytewise-sorted base-to-RED Git diff contains the fourteen paths above; every status and SHA-256 matches commit `a18d7981d6bdb6b99249ed42153c5e04c0df4330`. Only `tests/InstallationProcess/pilot_shlz_assets_001_test.php` changed from the v6 set. The other thirteen reviewed bytes and the HTTP response expectations remain unchanged.

The captured RED is genuine and sensitive. Baseline `34199261119` reached the inherited CSS child, which exited zero but left root-owned `0555`/`0444` fixture paths after the old helper interpreted successful Docker pull diagnostics as failure and returned before restoration. Strict bootstrap stderr checking then exposed the resulting `unlink` and `rmdir` permission errors. The retained baseline and private RED hashes match the v7 record. The private non-root probe uses real root-to-65534 ownership changes and reproduces exit 255 on the exact old helper bytes.

The correction improves isolation by replacing the undeclared `mariadb:10.11` image with the already-owned PHP test image and `--pull=never`. Its `try/finally` begins before provisioning, and the supplied noisy-provisioning probe proves that a partial-success setup diagnostic remains visible while ownership and modes are restored before outer cleanup. The quiet probe also preserves exact HTTP GET/HEAD behavior.

One blocking symmetric case remains at lines 80–86. Restoration still calls the boolean `psaDockerOwner()`, whose value is false for any stderr even when `chown` succeeds. `assertSameValue()` at line 81 then throws immediately. The subsequent ownership check and both mode-restoration calls do not execute. A successful-but-noisy restoration therefore leaves the directory runner-owned but mode `0555`; the runner still cannot unlink its child, so outer cleanup can reproduce the same permission failure. The private noisy probe makes provisioning noisy and restoration quiet, so it does not cover this branch.

Gate 3 requires deterministic setup isolation and cleanup under the rejected partial-success case that caused the real RED. The current test does not yet guarantee cleanup for the same outcome during restoration.

## Required changes

1. Separate ownership mutation result (`exit`, stdout and stderr) from observed ownership. During restoration, inspect actual UID after the command before surfacing its diagnostic result, and restore file/directory modes whenever ownership returned to the runner. Preserve the primary and restoration diagnostics after cleanup rather than asserting before cleanup completes.
2. Add a focused private or executable control in which the restoration chown succeeds and emits stderr. It must prove that the diagnostic remains visible and the runner can completely remove the fixture afterward.

No production change is requested.
