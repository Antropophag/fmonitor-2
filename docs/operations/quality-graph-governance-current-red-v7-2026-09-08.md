```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","baseCommit":"b5fca7d4df56404a4ac4eba802d497066a42b6c8","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"8c206d41f24e7c1d8eda907efbc2aaee08808811a7097fa19f9bcb3c631df8f7"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"Linux unprivileged private reproduction of eacc4d4 CSS ownership helper; real GitHub baseline34199261119 make fresh-test-verify","observedFailure":"Inherited CSS test exits0 but emits unlink/rmdir Permission denied because a partial-success Docker chown returns false on stderr and bypasses fixture restoration. Actual baseline fails db-test; independent Linux probe exits255 before repair.","recordedAt":"2026-09-08T07:47:43+00:00"}
```

# CSS cleanup correction RED v7

Реальный baseline34199261119/head eacc4d4 завершился FAIL только db-test.
Bootstrap требовал exit0/пустой stderr дочернего CSS test и получил exit0 с
Permission denied при cleanup root-owner-allowed. Остальные8stages PASS.

Старый test helper использовал undeclared mariadb:10.11 и считал любой stderr
неуспехом. После потенциально успешного chown ранний return обходил restoration.
Private Linux probe выполняет реальные chown root→65534 в одноразовом контейнере;
Docker CLI transport заменён локальным FIFO responder с контролируемым stderr.
На exact старых helper bytes eacc4d4 он воспроизвёл exit255/Permission denied.
Это конкретное regression evidence для test-only maintenance, не новый domain RED.

Текущие14test hashes включают исправленную только ownership fixture в CSS test:
owned PHPtest image с --pull=never, try/finally до первой mutation, проверка
фактических uid и возврат owner/modes даже после partial-success diagnostics.
HTTP ожидания/strict stderr bootstrap не изменены. Quiet HTTP GET/HEAD PASS и
noisy partial-success cleanup PASS в том же Linux probe. Нового production кода
нет; прежние RED/GREEN/reviews/receipt-v3/v4 сохраняются неизменно.
Нужен свежий независимый Gate3; старое approval не переносится на новые bytes.

Private `css-cleanup-red-real-chown.log` SHA-256 `7e45ee0433b0796e6001e5d1b42a0467899ec121f528dfa3319cc52a3c610e6a`.

Private `css-cleanup-linux-1-002.log` SHA-256 `5d1f64fb3ad3e24fa32c4aebcfc02bd864e0e468ffcad63410af3b6a1e98b950`.

Private `css-cleanup-linux-0-002.log` SHA-256 `8f770d2c2185725941d4ba6b7366242cf82751bd7e46b2b48e18d068523273c3`.

Private `github-pr37/positive-eacc4d4/baseline-failed.log` SHA-256 `2c1d92380e88317594af4ed8c208c6e9664e94e8c23a323d51740dac58ec93fb`.
