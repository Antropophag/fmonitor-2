```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/bootstrap_contract","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b","baseCommit":"b5fca7d4df56404a4ac4eba802d497066a42b6c8","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"архивное повторное использование: php tests/Verification/quality_graph_history_envelope_001_test.php на source до исправления, зафиксированное RED v3 commit 83ff618","observedFailure":"архивный checker source с SHA-256 5a48cf5dc21431b2635f26378c62610c6b3ab6c5a779a0bea05c5710b79a399c принял post-review edit/revert и вывел DELIVERY_EVIDENCE_OK вместо обязательного commit_mismatch; исходный test SHA-256 c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c","recordedAt":"2026-09-08T04:24:24+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 — binding refresh RED v4

Этот append-only record обновляет только canonical binding после согласованного
administrative format change commit `4558778`: H1 перемещён ниже уже существующего
первого `delivery-metadata` block. Содержание v0.6 не изменено; новый canonical
spec SHA-256 —
`5722160a2b7feffb82dc331c8ae80f0769844cffe1ba27e78b32480bb6ab0c5b`.
Предыдущий spec blob `189111...` и все связанные records сохраняются как история.

Нового RED-прогона на уже исправленном current code этот record не заявляет.
Он повторно связывает неизменный набор восьми tests с genuine historical RED v3,
committed как `83ff618`. Исходные identities этого доказательства:

- test `quality_graph_history_envelope_001_test.php` SHA-256
  `c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c`;
- checker `tools/delivery/check-evidence.php` SHA-256
  `5a48cf5dc21431b2635f26378c62610c6b3ab6c5a779a0bea05c5710b79a399c`;
- raw log `history-envelope-red-v3.raw.log` SHA-256
  `e11245b5cb883c62c7e5fd9f45ce3b6f4a48020de494a44f522acc4ad7ab0408`;
- original observed result: checker вывел success после governed source edit и
  exact-byte restore; test потребовал `commit_mismatch` и завершился exit255.

Test bytes, base `b5fca7d4df56404a4ac4eba802d497066a42b6c8`, behavior и
ожидаемые категории не менялись. Новый Gate 3 должен независимо подтвердить
format-only transform, unchanged complete test set и допустимость reuse этого
historical RED. Production code, tests и remote state этим record не меняются.
