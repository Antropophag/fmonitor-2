```delivery-metadata
{"schemaVersion":1,"kind":"code-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/linux_code_review","verdict":"APPROVED","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"8aa2ba051e7bea2b594b999992af1980a8d45c8afeed2b009a248d1aa2ee644e"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"implementationCommit":"3e1bfbb039b27fb99ffbc815f03417d8f40a6f1f","implementationFiles":[{"path":"Makefile","status":"M","sha256":"d1c8a64bbe83ce512a503f7eaeb36eec58139f2c12e818e2b474adb587532646"},{"path":"docs/operations/quality-graph-check-only-publisher-owner-proposal-2026-09-08.md","status":"A","sha256":"6aa7687c9e342ab0f215bc6f0912394f23d5fcce4af1d54391f15faf2ad21380"},{"path":"tools/delivery/ci-setup.sh","status":"M","sha256":"b37f7ce00617d55e318c8f53232ffe8002aaf79742de4bb14b30e8e12dfcc4ed"},{"path":"tools/verification/Dockerfile.test","status":"A","sha256":"62d3e360873e22a188337a11d304b0e537c1ee13eb248f3371b42be79b453ea5"},{"path":"tools/verification/run.sh","status":"M","sha256":"0fdf6bd19ac94d9f1a50f98019631bdec621e9d9b805ea2eb05f4b32b78b7f5f"}],"recordedAt":"2026-09-08T07:22:44Z"}
```

# Gate 5 code review v4: Linux Quality Graph verification repair

- Reviewer: `agent:/root/linux_code_review`
- Implementation author: `agent:/root`
- Reviewed implementation commit: `3e1bfbb039b27fb99ffbc815f03417d8f40a6f1f`
- Approved test review: `535800a` / `agent:/root/linux_test_review`
- Specification: `QUALITY-GRAPH-GOVERNANCE-001` v0.6, SHA-256 `5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`
- Verdict: **APPROVED**

## Scope and findings

No blocking finding remains. The exact implementation set derived from approved
Gate 3 commit `535800a` through GREEN commit `3e1bfbb` contains the five paths in
the metadata above after excluding only the GREEN record itself. The complete
fourteen-path approved test set is unchanged between RED `8519c55`, Gate 3 and
GREEN, and every reviewed file hash matches GREEN v4.

The Linux repair is minimal and fail-closed. `tools/verification/run.sh` verifies
that `rg` exists before directory discovery or test classification, so a missing
classifier cannot silently place a database test in the unit suite. The dedicated
CI setup installs and verifies ripgrep, then builds the repository-owned test-tools
image. `make test-tools` binds that image to the exact checkout revision. Its PHP
8.5 image provides `mysqli`, `pcntl` and `util-linux`; the approved public-seam test
proved root startup and a working unprivileged `setpriv` transition. Application
runtime and the pilot Dockerfile are unchanged.

The cumulative governance boundaries remain intact. Quality Graph declaration,
runner and publisher workflows, permissions, package/action pins, application
entry points and the historical receipt were not modified by this implementation.
Receipt `qg-current-20260908-v3.json` remains byte-identical to its introducing
commit (`76461350bc382cc518975fc5dfca375e37c6210127a46e4aff6b409884b3bdf5`).
The added check-only publisher document is explicitly `PROPOSED / НЕ РАЗРЕШЕНО /
НЕ РЕАЛИЗОВАНО`; it neither authorizes nor proves Phase B, and no publisher change
is approved by this review.

## Verification evidence

- GREEN v4 records exit 0 for the Linux CI setup test, `make test-tools`, all six
  governance suites, architecture validation, both relevant installation probes,
  the original production-boundary test and `git diff --check`.
- During independent review, source hashes, strict gate ancestry, the exact five
  implementation paths, the exact fourteen test paths, unchanged post-Gate-3 test
  bytes, and immutable receipt bytes were checked directly. The reviewed Quality
  Graph CI setup, governance, history-envelope, publisher, runner-security,
  toolchain and provenance tests all passed in the reviewer's partial local run.
- The authoritative serialized exact-SHA full run at
  `3e1bfbb039b27fb99ffbc815f03417d8f40a6f1f` passed all nine stages and emitted
  literal `VERIFY_OK`, exit 0. It ran from `2026-09-08T07:00:16.627458Z` to
  `2026-09-08T07:21:18.026584Z` (1261.37 seconds), with unchanged HEAD. Private
  `verify-3e1bfbb-002.log` SHA-256:
  `21a9c5e5c5d9e887b192d09c3d3d77864af86a9f7c94863a1d9c99baa398ba9f`;
  the matching private JSON execution receipt records the same result.

An earlier root run and the reviewer's overlapping run were stopped after their
shared test-database use was detected. The reviewer's partial log is retained as
`code-review-make-verify-v4.log`, SHA-256
`bc14109c5419efe59df19ad55eb957ad878ca3b9a34789408851cd04a98551d2`;
its observed failures were solely the absent TCPDF dependency in that clean
worktree, and it produced no full-verification verdict. Neither discarded run is
used as approval evidence; the serialized `002` run above is authoritative.

Actual trusted-publisher Phase B and any owner decision on the check-only proposal
remain pending. This approval covers the exact Linux verification repair and the
unchanged cumulative local governance boundaries only.

Blocking changes: None.
