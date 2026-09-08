```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","baseCommit":"b5fca7d4df56404a4ac4eba802d497066a42b6c8","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"571f958174a970007884ddec35098502a8455f52e3eb17d7ac268242df120f6b"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"Private Linux nonroot real-chown probe, old a18d798 helper, NOISY=2","observedFailure":"Restoration chown succeeds but emits stderr: old a18 helper throws before restoring mode0555; outer cleanup raises unlink Permission denied, exit255. Correction checks resulting owner/modes before surfacing restoration diagnostics.","recordedAt":"2026-09-08T07:51:50+00:00"}
```

# CSS cleanup correction RED v8

Gate3v7 CHANGES_REQUESTED выявил тот же partial-success случай при restoration.
Root воспроизвёл его на exact a18d798 helper: реальный Linux chown вернул владельца,
но transport stderr привёл к assertion до chmod; cleanup exit255/Permission denied.
В test-only correction сначала проверяются actual owners и восстанавливаются
modes, затем сообщается diagnostic failure. HTTP assertions/strict stderr
контракт и14-path set сохранены. Новой production implementation нет.

Один private Linux FIFO-transport probe выполняет реальные chown и непривилегированный
PHP/HTTP: quiet mode0 PASS, noisy provisioning mode1 PASS с ожидаемым setup failure,
noisy restoration mode2 PASS с ожидаемым restoration failure после cleanup.
Все три проверяют полное удаление fixture и отсутствие cleanup warnings.
Historical REDv6/v7, CRreviewv7 и receiptsv3/v4 не изменяются. Новый Gate3 ожидается.

Private `css-cleanup-v8-old-2.log` SHA-256 `5dba735071c88b9edc9a486753c1602b6e25266a9f9ed189ded5160e2801f389`.

Private `css-cleanup-v8-fixed-0.log` SHA-256 `8f770d2c2185725941d4ba6b7366242cf82751bd7e46b2b48e18d068523273c3`.

Private `css-cleanup-v8-fixed-1.log` SHA-256 `5d1f64fb3ad3e24fa32c4aebcfc02bd864e0e468ffcad63410af3b6a1e98b950`.

Private `css-cleanup-v8-fixed-2.log` SHA-256 `bd062cd03da19c55bc83de71e843d54c98b996a530f4ba1924d7593a7de423cd`.
