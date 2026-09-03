```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"16fc1c6d652561543247c990cc244c2d05fe2a84a0b2e15ba2a2f9a0077b4dde"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"approved parity/final-verification paths pass, then an unrelated docs/operations path is accepted by the directory-wide exception","recordedAt":"2026-09-04T00:58:37+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v32

Исправленный тест сначала добавляет два разрешённых spec-ом post-review класса:
фактический phase-A parity record и именованный final-verification record. Оба
обязаны сохранить GREEN. После этого отдельный commit добавляет посторонний
operations record, который обязан дать один `commit_mismatch`.

Фактические команды:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Результат: lint exit `0`; valid lineage, supersession и разрешённый evidence
envelope проходят; затем checker возвращает exit `0` и
`DELIVERY_EVIDENCE_OK` для постороннего `docs/operations/unrelated-note.md`, а
тест завершается exit `255` на intended assertion. Production code не менялся.
