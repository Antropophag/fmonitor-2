```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root/bootstrap_contract","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"b5fca7d4df56404a4ac4eba802d497066a42b6c8","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_history_envelope_001_test.php","observedFailure":"checker принимает историю с post-review изменением governed source и последующим восстановлением exact reviewed bytes, потому что проверяет только net GREEN..HEAD tree diff","recordedAt":"2026-09-08T03:43:04+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 — Gate 2 RED v3

Новый bounded verifier использует только публичный `check-evidence.php --repo`
над синтетическими Git repositories. Семь ранее approved test files не изменены;
добавлен только `quality_graph_history_envelope_001_test.php`.

Каждая fixture с нуля создаёт valid full lineage: base, spec/test/RED, независимый
test review, GREEN с implementation, независимый code review и receipt. Positive
baseline сначала обязан пройти.

Первая negative fixture после review изменяет governed implementation source
отдельным commit, затем следующим commit восстанавливает exact reviewed bytes.
История всё равно содержит неразрешённое post-review изменение и обязана дать
ровно один `commit_mismatch` без `DELIVERY_EVIDENCE_OK`. Текущий checker сравнивает
только итоговый tree diff и ошибочно возвращает success.

Вторая fixture через `git commit-tree` создаёт настоящий merge с двумя точными
родителями `[pre-GREEN base-main ancestor, approved feature HEAD]` и деревом,
byte-identical approved feature HEAD. Parent list и tree identity отдельно
проверяются до вызова checker. Такая GitHub-like synthetic merge topology обязана
оставаться допустимой: один success, без failure. Fixture расположена после первого
RED и в фактическом прогоне ещё не исполнилась; GREEN обязан доказать оба случая,
чтобы исправление не стало blanket запретом merge commits.

Фактический RED:

```text
DELIVERY_EVIDENCE_OK receipts=1 head=<synthetic-head>
TestFailure: mutate then restore history exit
Expected: 1
Actual: 0
exit=255
```

Private raw log:
`~/.local/state/fmonitor2/quality-graph-current-20260908/history-envelope-red-v3.raw.log`,
mode0600, SHA-256
`e11245b5cb883c62c7e5fd9f45ce3b6f4a48020de494a44f522acc4ad7ab0408`.

PHP lint и `git diff --check` проходят. DB, Docker, browser, network, live stand,
remote actions и publication не использовались. Это append-only Gate 2 evidence;
implementation и новый Gate 3 pending.
