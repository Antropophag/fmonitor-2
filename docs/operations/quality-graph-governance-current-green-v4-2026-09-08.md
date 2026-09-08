```delivery-metadata
{"schemaVersion":1,"kind":"green","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","tests":[{"path":"tests/InstallationProcess/pilot_object_list_001_test.php","status":"M","sha256":"752aa4765f35ea2a39b60afa779fa8a72b37898ce0380e10ca9a9d8c33bf055e"},{"path":"tests/InstallationProcess/pilot_shlz_assets_001_test.php","status":"M","sha256":"8aa2ba051e7bea2b594b999992af1980a8d45c8afeed2b009a248d1aa2ee644e"},{"path":"tests/Support/PilotSafeAuthorizationLog.php","status":"A","sha256":"e6fcbed10b086064228a151fa9ff70b0fecc744bd71f770b43653073f3d90943"},{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/ShlzManifestCaptureProbe.php","status":"A","sha256":"abe0ad400bc535a7632277800aa88c65cf3ceda458d8836aa892e2b637f8c501"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Support/shlz_manifest_capture_witness.php","status":"A","sha256":"a87af3e38204c0f38dc1ed2f6ad2842c2a59456c62def8bd4ce7bc1abb09c357"},{"path":"tests/Verification/quality_graph_ci_setup_001_test.php","status":"A","sha256":"3f61386a0fe582f142d3ff6e366f1081a4d339d2eaae559bad31cc90f8e3b4ab"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"testReviewRecordPath":"reviews/tests/QUALITY-GRAPH-GOVERNANCE-CURRENT-v6-2026-09-08.md","implementationFiles":[{"path":"Makefile","status":"M","sha256":"d1c8a64bbe83ce512a503f7eaeb36eec58139f2c12e818e2b474adb587532646"},{"path":"docs/operations/quality-graph-check-only-publisher-owner-proposal-2026-09-08.md","status":"A","sha256":"6aa7687c9e342ab0f215bc6f0912394f23d5fcce4af1d54391f15faf2ad21380"},{"path":"tools/delivery/ci-setup.sh","status":"M","sha256":"b37f7ce00617d55e318c8f53232ffe8002aaf79742de4bb14b30e8e12dfcc4ed"},{"path":"tools/verification/Dockerfile.test","status":"A","sha256":"62d3e360873e22a188337a11d304b0e537c1ee13eb248f3371b42be79b453ea5"},{"path":"tools/verification/run.sh","status":"M","sha256":"0fdf6bd19ac94d9f1a50f98019631bdec621e9d9b805ea2eb05f4b32b78b7f5f"}],"commands":["php tests/Verification/quality_graph_ci_setup_001_test.php","make test-tools","make governance-test","make architecture-check","FMONITOR_TEST_DB_ADMIN_PASSWORD=<test-local> php tests/InstallationProcess/pilot_object_list_001_test.php","FMONITOR_TEST_DB_ADMIN_PASSWORD=<test-local> php tests/InstallationProcess/pilot_shlz_assets_001_test.php","php tests/InstallationProcess/assignment_order_original_production_boundary_001_test.php","git diff --check"],"recordedAt":"2026-09-08T06:52:24+00:00"}
```

# Linux CI focused GREEN v4

Approved RED v6 / Gate3 v6 сохранены. Минимальная implementation добавляет
repository-owned test image PHP8.5 с mysqli/pcntl/util-linux, make test-tools,
установку ripgrep в CI и fail-closed precondition перед классификацией тестов.
Runtime/application и Dockerfile пилота не изменены.

Pending publisher proposal включён в exact tree исключительно как предложение
для решения владельца; publisher permissions/pins/workflows не менялись.

Все перечисленные focused команды завершились exit0. Первый ручной CSS запуск
без test DB password завершился setup Access denied; повтор с штатным test-local
окружением PASS. Ошибка первого запуска не выдана за domain RED и лог сохранён.
Полный exact-SHA verify и новый независимый Gate5 ещё ожидаются.

- PASS exit0; ci-setup-green-v4-local.log sha256=566fb63dbd0c3a689b76d85d78dd325a0e0b24e3892b3e04c01ad26a567336b8
- PASS exit0; test-tools-default-v4.log sha256=03727f44993b8576ffd2fcf27866c9c5682373c01494c1f9c8ab617c7f688f58
- PASS exit0; governance-green-v4.log sha256=66bf3801363462cc092951de2bd829516cd675e91e25f74a513bb4cfaa6e9598
- PASS exit0; architecture-green-v4.log sha256=3198284fd53557d0aa7cfd807439e9027c6bf874d1639a76a06386c7bcb1889b
- PASS exit0; object-list-green-v4.log sha256=9a3dc4ef7c6e83fae4798df27ea02a832a8021dbd02510e1bde6ee4e7ee27427
- PASS exit0; css-green-v4-002.log sha256=2dbc3d62695979e6241eea666c586cfbcf5c6df1dff2277c981e47b9608acdf7
- PASS exit0; original-boundary-green-v4.log sha256=b6312a43cab01a0ecd3aa3db979fbd3e0d011a3535fd66ff2993d865aeb0f55c
- PASS exit0; git diff --check
