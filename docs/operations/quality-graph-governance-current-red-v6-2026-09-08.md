```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","baseCommit":"b5fca7d4df56404a4ac4eba802d497066a42b6c8","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"8aa2ba051e7bea2b594b999992af1980a8d45c8afeed2b009a248d1aa2ee644e"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_ci_setup_001_test.php","observedFailure":"Missing rg in an otherwise valid isolated classification fixture returns exit0 and lists the DB sample as unit; expected explicit SETUP_FAILURE before any classification. Test exits255.","recordedAt":"2026-09-08T06:24:17.533161+00:00"}
```

# Linux CI RED v6 — ограниченный runner

Новый RED повторён до изменения CI tooling: exit255, отсутствующий rg всё ещё
даёт exit0 и неверно включает DB fixture в unit list. Downstream image build
этим RED не достигнут. Tests set остаётся 14 путей; изменён только qcsRun.

Прежний qcsRun воспроизведён отдельным локальным probe: запись 1MiB в stderr
блокирует последовательное чтение stdout (внешний deadline3s). Новый runner
читает оба pipe через nonblocking stream_select и имеет deadline900s; private
probe подтвердил точные stdout, stderr length, exit7 и принудительный deadline0.2s.
Это исправление тестовой инфраструктуры, не production-поведения.

RED v5 и прежние reviews/receipt-v3 неизменны. Автор текущего runner и RED:
agent:/root; coauthor прежнего test set agent:/root/bootstrap_contract.
Новый независимый Gate3 пока не выполнен. GREEN/новой implementation нет.

Private `ci-setup-red-v6-local.log` SHA-256 `eacc6956c59e4c4108a562a948beb3cf1e566e04b0b2c2ca5a6c3df5d0ca7cd3`.
