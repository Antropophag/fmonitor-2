```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/linux_test_review","verdict":"APPROVED","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"571f958174a970007884ddec35098502a8455f52e3eb17d7ac268242df120f6b"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"6b30c1b401c3e5da1f8c0a2ce8c8b34bb2f55568","recordedAt":"2026-09-08T10:54:00+03:00"}
```

# Independent Gate 3 review v8 — CSS restoration diagnostics

- Reviewer: `agent:/root/linux_test_review`
- Test author: `agent:/root`
- Reviewed commit: `6b30c1b401c3e5da1f8c0a2ce8c8b34bb2f55568`
- Specification: `QUALITY-GRAPH-GOVERNANCE-001` v0.6, SHA-256 `5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`
- Public seam: inherited `PILOT-SHLZ-ASSETS-001` HTTP GET/HEAD contract within repository `db-test` and `make fresh-test-verify`
- RED command: private Linux non-root real-chown probe using exact `a18d798` helper with noisy restoration
- Verdict: `APPROVED`

## Findings

The canonical metadata is complete and exact. The bytewise-sorted `git diff --no-renames --name-status b5fca7d4df56404a4ac4eba802d497066a42b6c8..6b30c1b401c3e5da1f8c0a2ce8c8b34bb2f55568 -- tests/` result contains exactly the fourteen paths above. Every status and SHA-256 matches the reviewed commit. Only the CSS test hash changed from v7; all other test bytes and their previous independent expectations remain unchanged.

The v8 RED independently isolates the blocking case recorded by the immutable v7 `CHANGES_REQUESTED` review. On the exact `a18d798` helper, a real Linux restoration chown returned ownership to UID 65534 but controlled stderr made the boolean helper report failure. The old assertion then ran before mode restoration, leaving the directory at `0555`; outer cleanup raised `unlink` permission denied and exited 255. Retained `css-cleanup-v8-old-2.log` has the recorded SHA-256 `5dba735071c88b9edc9a486753c1602b6e25266a9f9ed189ded5160e2801f389`.

The corrected fixture stores the restoration result instead of asserting immediately. It then checks the actual directory and file owners, restores file mode `0600` and directory mode `0700`, and only afterward reports any non-quiet restoration result. This preserves diagnostic sensitivity while ensuring the owned tree is removable after a partial-success ownership command. No HTTP or production expectation is weakened.

The private FIFO transport controls are sufficient and deterministic for this test-only correction. They execute real root-to-65534 ownership changes and the HTTP GET/HEAD oracle as a non-root process. Quiet mode 0 passes; noisy provisioning mode 1 raises the expected setup diagnostic after full cleanup; noisy restoration mode 2 raises the expected restoration diagnostic after full cleanup. All three assert that the fixture tree is absent and emit no cleanup warnings. Their hashes match the v8 record: `8f770d2c2185725941d4ba6b7366242cf82751bd7e46b2b48e18d068523273c3`, `5d1f64fb3ad3e24fa32c4aebcfc02bd864e0e468ffcad63410af3b6a1e98b950`, and `bd062cd03da19c55bc83de71e843d54c98b996a530f4ba1924d7593a7de423cd`.

The correction uses the repository-owned PHP test image with `--pull=never`, keeps the production source unchanged, preserves strict stderr behavior, and closes the exact setup-isolation defect without expanding the domain test contract. Traceability, sensitivity, expected-value independence, rejected partial-success cases, determinism, cleanup isolation and captured RED are sufficient for Gate 3. Reviewer identity is independent from the test author.

## Required changes

None.
