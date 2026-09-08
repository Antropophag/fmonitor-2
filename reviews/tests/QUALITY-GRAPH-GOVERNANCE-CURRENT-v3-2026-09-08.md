```delivery-metadata
{"schemaVersion":1,"kind":"test-review","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","reviewer":"agent:/root/bootstrap_review","verdict":"APPROVED","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","tests":[{"path":"tests/Support/SelectedOriginalFixture.php","status":"M","sha256":"920f6a3ae580e52159cec5fad11eb90b7453d543a115695563cb7493044ffee7"},{"path":"tests/Support/construction_control_completed_filter_browser.cjs","status":"M","sha256":"bc1d5313cf710eab977b6b3ea507adcfd08fe5572edb4140262b96104f4a1f61"},{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"e5ed277d91e835ed6ad397937bf2f50d5e9d5bde0f37027fbc1c52b45a9e3695"},{"path":"tests/Verification/quality_graph_history_envelope_001_test.php","status":"A","sha256":"c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_publisher_provenance_001_test.py","status":"A","sha256":"5bab0b410345b2c3dfb3236301c7232fec0903f0dd5cff093e3f7fb4f2048d1d"},{"path":"tests/Verification/quality_graph_runner_security_001_test.php","status":"A","sha256":"a99ca7f53c811bb9be8a5abf761805569eec150d3a96e3f376f2c5f5b2261af9"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"redCommit":"83ff618956d13cbffceddd62feb332a5bf22b9e6","recordedAt":"2026-09-08T03:45:36+03:00"}
```

# Независимый Gate 3 review v3 — history envelope

Вердикт: **APPROVED** для исправления post-review history bypass по immutable
`QUALITY-GRAPH-GOVERNANCE-001` v0.6. Reviewer
`agent:/root/bootstrap_review` независим от test author и не выполняет Gate 5
code review этого slice.

## Проверенная версия

RED v3 commit: `83ff618956d13cbffceddd62feb332a5bf22b9e6`.
Исполнимая спецификация не изменена; SHA-256
`189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859`.
Append-only RED v3 evidence SHA-256:
`1ba7a97c8bcf3743893326f47b5cf8cc74bcad852fdbf3a7b5d7ee115c6c7f48`.
Новый verifier SHA-256:
`c042942e66238224496bae95263ddf3375e6f28bfffeae556fd828fef70a578c`.

Полный Git-derived diff `base..RED -- tests/` содержит ровно восемь paths из
canonical metadata: семь ранее approved файлов побайтно неизменны, добавлен только
`quality_graph_history_envelope_001_test.php`. Статусы, порядок и hashes совпадают
с commit.

## Качество сценариев

Обе fixture с нуля создают корректную полную lineage: base, spec/test/RED,
независимый test review, GREEN/implementation, независимый code review и receipt.
Перед мутацией baseline обязан дать один terminal success без failure.

Negative scenario после code review изменяет governed implementation path отдельным
commit, затем отдельным commit восстанавливает exact reviewed bytes. Итоговый tree
совпадает с approved tree, но история содержит запрещённое post-review изменение.
Verifier требует exit 1, ровно один `commit_mismatch` для current receipt и ни
одного `DELIVERY_EVIDENCE_OK`. Сценарий проверяет именно history envelope и не может
быть удовлетворён обычным net tree diff.

Positive scenario создаёт настоящий two-parent commit через `git commit-tree`.
Тест до checker отдельно доказывает точный parent list и равенство merge tree
approved feature tree. Такой tree-identical merge обязан сохранить один success и
не дать failure. Это не позволяет исправить negative blanket-запретом merge
commits и сохраняет допустимую GitHub-like evidence topology.

## RED evidence

Независимый запуск verifier подтвердил intended RED: baseline прошёл, после
edit→restore текущий checker вернул exit 0 и
`DELIVERY_EVIDENCE_OK`, из-за чего test завершился exit 255 вместо ожидаемого
`commit_mismatch`. Это соответствует committed evidence и Gate 5 finding. Merge
positive расположен после первого RED и честно ещё не заявлен выполненным; GREEN
обязан доказать оба сценария.

Тест использует только публичный `check-evidence.php --repo` и изолированные Git
repositories. DB, Docker, browser, network, remote actions, production данные и
owner stand не используются. PHP lint и `git diff --check` прошли.

Блокирующих замечаний нет.
