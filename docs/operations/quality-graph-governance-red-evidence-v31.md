```delivery-metadata
{"schemaVersion":1,"kind":"red","sliceId":"QUALITY-GRAPH-GOVERNANCE-001","author":"agent:/root","specPath":"specs/QUALITY-GRAPH-GOVERNANCE-001.md","specSha256":"189111265896cf1f83531c6786cb2e13e3f7e00f64d54c423807ab683fe8f859","baseCommit":"9c87164393b2048428fc0987c357e65e0e9fc146","tests":[{"path":"tests/Verification/quality_graph_governance_001_test.php","status":"A","sha256":"5b1ca1d8f6c95e3483894f32d9b917314052e89b465e374c786ce7d2bc05420b"},{"path":"tests/Verification/quality_graph_publisher_001_test.php","status":"A","sha256":"391fe3e2aa3d162b978b21643c43eb92cb701319f1d69cca803d6f1ca6271600"},{"path":"tests/Verification/quality_graph_toolchain_001_test.php","status":"A","sha256":"ede3aae46a8860a15369c70a802e5204827d5ca7495f5aec3e7c321424e58863"}],"command":"php tests/Verification/quality_graph_governance_001_test.php","observedFailure":"unapproved docs/operations path after reviewed implementation is accepted by the directory-wide evidence-envelope exception","recordedAt":"2026-09-04T00:55:56+03:00"}
```

# QUALITY-GRAPH-GOVERNANCE-001 Gate 2 RED v31

Изолированный Git fixture сначала доказывает GREEN для полной lineage и её
immutable supersession. Затем он добавляет после reviewed implementation
посторонний `docs/operations/unrelated-note.md`, не входящий в разрешённые
spec-ом parity/final-verification evidence paths, и вызывает тот же публичный
checker seam.

Команды:

```text
php -l tests/Verification/quality_graph_governance_001_test.php
php tests/Verification/quality_graph_governance_001_test.php
```

Наблюдаемый результат:

```text
No syntax errors detected in tests/Verification/quality_graph_governance_001_test.php
PHP Fatal error: Uncaught TestFailure: Unallowlisted post-review operations evidence must fail;
evidence={"status":0,"stdout":"DELIVERY_EVIDENCE_OK receipts=1 head=46c9d2945819a7fadcf175ffd2a36bf15ac44503\n","stderr":""}
Expected: true
Actual: false
exit=255
```

Это intended RED: fixture, valid lineage и supersession проходят; checker
ошибочно принимает ровно тот directory-wide обход, который указан blocking
finding Gate 5 v2. Production implementation не менялась.
